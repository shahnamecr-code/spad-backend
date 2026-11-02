<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\AdvertisementTool;
use App\Models\PropertyDepositOrder;
use App\Models\WalletGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;

class PropertyDepositController extends Controller
{
    private function activeTool(Advertisement $advertisement): AdvertisementTool
    {
        abort_unless($advertisement->status === Advertisement::APPROVED
            && !$advertisement->is_investor_exclusive, 404);
        $tool = $advertisement->toolConfig()->first();
        abort_unless($tool && $tool->deposit_enabled && (int) $tool->deposit_amount >= 1000, 404);
        return $tool;
    }

    public function show(Advertisement $advertisement)
    {
        $tool = $this->activeTool($advertisement);
        return response()->json([
            'data' => [
                'advertisement_id' => $advertisement->id,
                'amount' => (int) $tool->deposit_amount,
                'currency' => 'تومان',
                'gateways' => WalletGateway::query()->where('enabled', true)
                    ->orderByDesc('is_default')->orderBy('id')
                    ->get(['id', 'title', 'is_default']),
                'notice' => 'پرداخت بیعانه به‌تنهایی به معنی انعقاد قرارداد یا انتقال مالکیت نیست؛ شرایط معامله و استرداد وجه باید به‌صورت مکتوب توافق شود.',
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function pay(Request $request, Advertisement $advertisement)
    {
        $validated = $request->validate([
            'gateway_id' => ['required', 'integer', 'exists:wallet_gateways,id'],
            'accept_terms' => ['required', 'accepted'],
        ]);
        $gateway = WalletGateway::query()->where('enabled', true)
            ->findOrFail($validated['gateway_id']);

        // Lock the tool row so the amount can only be snapshotted from the server.
        $order = DB::transaction(function () use ($advertisement, $gateway, $request) {
            $tool = $advertisement->toolConfig()->lockForUpdate()->first();
            abort_unless($advertisement->status === Advertisement::APPROVED
                && !$advertisement->is_investor_exclusive && $tool
                && $tool->deposit_enabled && (int) $tool->deposit_amount >= 1000, 422);

            $userId = (int) $request->user()->id;
            $pending = PropertyDepositOrder::query()->where('user_id', $userId)
                ->where('advertisement_id', $advertisement->id)
                ->where('status', 'pending')->where('expires_at', '>', now())
                ->first();
            if ($pending) {
                abort(409, 'یک پرداخت در حال انجام است. نتیجه آن را بررسی کنید یا پس از اتمام مهلت دوباره تلاش کنید.');
            }

            $config = $gateway->secureConfig();
            $currency = strtoupper((string) ($config['currency'] ?? 'T'));
            abort_unless(in_array($currency, ['T', 'R'], true), 422, 'واحد مبلغ درگاه معتبر نیست.');
            $amount = (int) $tool->deposit_amount;

            return PropertyDepositOrder::create([
                'advertisement_id' => $advertisement->id,
                'user_id' => $userId,
                'gateway_id' => $gateway->id,
                'amount' => $amount,
                'gateway_amount' => $currency === 'R' ? $amount * 10 : $amount,
                'gateway_currency' => $currency,
                'status' => 'pending',
                'tracking_code' => 'SPAD-D-' . strtoupper(Str::random(12)),
                'callback_token' => Str::random(64),
                'expires_at' => now()->addMinutes(30),
            ]);
        });

        try {
            $invoice = (new Invoice)->amount($order->gateway_amount)
                ->detail('property_deposit', $order->tracking_code)
                ->detail('advertisement_id', $advertisement->id);
            $callback = url('/api/property-deposits/callback/' . $order->id . '/' . $order->callback_token);
            $payment = Payment::via($gateway->driver)->config($gateway->secureConfig())
                ->callbackUrl($callback)
                ->purchase($invoice, function ($driver, $transactionId) use ($order) {
                    $order->update(['transaction_id' => (string) $transactionId]);
                })->pay();

            return response()->json([
                'status' => true,
                'order_id' => $order->id,
                'tracking_code' => $order->tracking_code,
                'payment' => $payment->jsonSerialize(),
            ]);
        } catch (\Throwable $e) {
            $order->update(['status' => 'failed', 'failure_message' => 'payment_initialization_failed']);
            return response()->json(['status' => false,
                'message' => 'اتصال به درگاه پرداخت برقرار نشد. لطفاً دوباره تلاش کنید.'], 422);
        }
    }

    public function callback(Request $request, PropertyDepositOrder $order, string $token)
    {
        $frontend = rtrim(env('FRONTEND_URL', env('APP_FRONTEND_URL', 'https://spad.estate')), '/');
        $result = $frontend . '/property/deposit-result?order=' . $order->id;
        if (!hash_equals((string) $order->callback_token, $token)) {
            return redirect()->away($frontend . '/property/deposit-result?status=invalid');
        }
        if ($order->status === 'paid') {
            return redirect()->away($result);
        }
        if (!$order->transaction_id || !$order->gateway) {
            $order->update(['status' => 'failed', 'failure_message' => 'missing_gateway_transaction']);
            return redirect()->away($result);
        }

        try {
            $receipt = Payment::via($order->gateway->driver)
                ->config($order->gateway->secureConfig())
                ->amount($order->gateway_amount)->transactionId($order->transaction_id)->verify();

            DB::transaction(function () use ($order, $receipt) {
                $locked = PropertyDepositOrder::query()->whereKey($order->id)
                    ->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') return;
                $locked->status = 'paid';
                $locked->paid_at = now();
                $locked->reference_id = method_exists($receipt, 'getReferenceId')
                    ? (string) $receipt->getReferenceId() : null;
                $locked->failure_message = null;
                $locked->save();
            });
        } catch (InvalidPaymentException $e) {
            // A gateway rejection is not evidence of success.
            $order->refresh();
            if ($order->status !== 'paid') $order->update(['status' => 'failed', 'failure_message' => 'gateway_rejected']);
        } catch (\Throwable $e) {
            // Transport errors are unknown/pending: permit a later verification retry.
            $order->refresh();
            if ($order->status !== 'paid') $order->update(['failure_message' => 'verification_pending']);
        }
        return redirect()->away($result);
    }

    public function status(Request $request, PropertyDepositOrder $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
        return response()->json(['data' => [
            'id' => $order->id,
            'advertisement_id' => $order->advertisement_id,
            'amount' => (int) $order->amount,
            'status' => $order->status,
            'tracking_code' => $order->tracking_code,
            'reference_id' => $order->reference_id,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
        ]])->header('Cache-Control', 'private, no-store');
    }
}
