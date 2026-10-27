<?php

namespace App\Http\Controllers;

use App\Models\WalletGateway;
use App\Models\WalletSetting;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class WalletController extends Controller
{
    protected $wallets;

    public function __construct(WalletService $wallets)
    {
        $this->wallets = $wallets;
    }

    public function summary(Request $request)
    {
        $wallet = $this->wallets->walletFor($request->user());

        $totals = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('status', 'completed')
            ->selectRaw("SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) as credits")
            ->selectRaw("SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END) as debits")
            ->first();

        return response()->json([
            'data' => [
                'id' => $wallet->id,
                'balance' => (int) $wallet->balance,
                'status' => $wallet->status,
                'total_credits' => (int) ($totals->credits ?? 0),
                'total_debits' => (int) ($totals->debits ?? 0),
                'gateways' => WalletGateway::query()
                    ->where('enabled', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->get(['id', 'driver', 'title', 'is_default']),
                'settings' => [
                    'wallet_enabled' => WalletSetting::valueOf('wallet_enabled', '1') === '1',
                    'topup_enabled' => WalletSetting::valueOf('topup_enabled', '1') === '1',
                    'min_topup' => (int) WalletSetting::valueOf('min_topup', 100000),
                    'max_topup' => (int) WalletSetting::valueOf('max_topup', 100000000),
                    'wallet_title' => WalletSetting::valueOf('wallet_title', 'کیف پول SPAD'),
                    'wallet_intro' => WalletSetting::valueOf('wallet_intro', ''),
                ],
            ],
        ]);
    }

    public function transactions(Request $request)
    {
        $wallet = $this->wallets->walletFor($request->user());
        $query = WalletTransaction::query()->where('wallet_id', $wallet->id);

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        $items = $query->latest()->paginate(min(50, max(5, (int) $request->get('per_page', 15))));
        $items->getCollection()->transform(function ($item) {
            return $this->transactionPayload($item);
        });

        return $items;
    }

    public function topUp(Request $request)
    {
        if (WalletSetting::valueOf('wallet_enabled', '1') !== '1' || WalletSetting::valueOf('topup_enabled', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'شارژ کیف پول در حال حاضر غیرفعال است.'], 422);
        }

        $min = (int) WalletSetting::valueOf('min_topup', 100000);
        $max = (int) WalletSetting::valueOf('max_topup', 100000000);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:' . $min, 'max:' . $max],
            'gateway_id' => ['required', 'exists:wallet_gateways,id'],
        ]);

        $gateway = WalletGateway::query()->whereKey($data['gateway_id'])->where('enabled', true)->firstOrFail();
        $wallet = $this->wallets->walletFor($request->user());
        if ($wallet->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'کیف پول شما غیرفعال است.'], 422);
        }

        $config = $gateway->secureConfig();
        $currency = strtoupper(isset($config['currency']) ? $config['currency'] : 'T');
        $amount = (int) $data['amount'];
        $gatewayAmount = $currency === 'R' ? $amount * 10 : $amount;

        $topup = WalletTopup::create([
            'user_id' => $request->user()->id,
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'amount' => $amount,
            'gateway_amount' => $gatewayAmount,
            'gateway_currency' => $currency,
            'status' => 'pending',
            'tracking_code' => 'WAL-' . strtoupper(Str::random(8)) . '-' . now()->format('ymdHis'),
            'callback_token' => Str::random(64),
            'expires_at' => now()->addMinutes(30),
        ]);

        try {
            $invoice = (new Invoice)->amount($topup->gateway_amount)
                ->detail('wallet_topup', $topup->tracking_code)
                ->detail('user_id', $request->user()->id);

            $callbackUrl = url('/api/wallet-payments/callback/' . $topup->id . '/' . $topup->callback_token);
            $payment = Payment::via($gateway->driver)
                ->config($gateway->secureConfig())
                ->callbackUrl($callbackUrl)
                ->purchase($invoice, function ($driver, $transactionId) use ($topup) {
                    $topup->transaction_id = (string) $transactionId;
                    $topup->save();
                })
                ->pay();

            return response()->json([
                'status' => true,
                'topup_id' => $topup->id,
                'tracking_code' => $topup->tracking_code,
                'payment' => $payment->jsonSerialize(),
            ]);
        } catch (\Throwable $e) {
            $topup->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'اتصال به درگاه پرداخت انجام نشد. لطفاً دوباره تلاش کنید.',
            ], 422);
        }
    }

    public function topUpStatus(Request $request, WalletTopup $topup)
    {
        abort_unless((int) $topup->user_id === (int) $request->user()->id, 404);
        return response()->json([
            'data' => [
                'id' => $topup->id,
                'amount' => (int) $topup->amount,
                'status' => $topup->status,
                'tracking_code' => $topup->tracking_code,
                'reference_id' => $topup->reference_id,
                'paid_at' => optional($topup->paid_at)->toIso8601String(),
                'failure_message' => $topup->failure_message,
            ],
        ]);
    }

    protected function transactionPayload(WalletTransaction $item)
    {
        $titles = [
            'topup' => 'شارژ کیف پول',
            'admin_credit' => 'افزایش موجودی توسط مدیریت',
            'admin_debit' => 'کاهش موجودی توسط مدیریت',
            'purchase' => 'پرداخت از کیف پول',
            'refund' => 'بازگشت وجه',
        ];

        return [
            'id' => $item->id,
            'direction' => $item->direction,
            'type' => $item->type,
            'type_text' => $titles[$item->type] ?? ($item->title ?: 'تراکنش کیف پول'),
            'amount' => (int) $item->amount,
            'balance_before' => (int) $item->balance_before,
            'balance_after' => (int) $item->balance_after,
            'status' => $item->status,
            'reference' => $item->reference,
            'title' => $item->title,
            'description' => $item->description,
            'created_at' => optional($item->created_at)->toIso8601String(),
        ];
    }
}
