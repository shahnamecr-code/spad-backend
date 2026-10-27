<?php

namespace App\Http\Controllers;

use App\Models\WalletTopup;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;

class WalletPaymentController extends Controller
{
    protected $wallets;

    public function __construct(WalletService $wallets)
    {
        $this->wallets = $wallets;
    }

    public function callback(Request $request, WalletTopup $topup, $token)
    {
        $frontend = rtrim(env('FRONTEND_URL', env('APP_FRONTEND_URL', 'https://spad.estate')), '/');

        if (!hash_equals((string) $topup->callback_token, (string) $token)) {
            return redirect()->away($frontend . '/wallet/payment-result?status=failed&reason=invalid');
        }

        if ($topup->status === 'paid') {
            return redirect()->away($frontend . '/wallet/payment-result?status=success&topup=' . $topup->id);
        }

        if (!$topup->gateway || !$topup->transaction_id) {
            $topup->update(['status' => 'failed', 'failure_message' => 'اطلاعات درگاه ناقص است.']);
            return redirect()->away($frontend . '/wallet/payment-result?status=failed&topup=' . $topup->id);
        }

        try {
            $receipt = Payment::via($topup->gateway->driver)
                ->config($topup->gateway->secureConfig())
                ->amount($topup->gateway_amount)
                ->transactionId($topup->transaction_id)
                ->verify();

            DB::transaction(function () use ($topup, $receipt) {
                $locked = WalletTopup::query()->whereKey($topup->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') return;

                $transaction = $this->wallets->credit($locked->user, $locked->amount, [
                    'type' => 'topup',
                    'reference' => 'wallet-topup:' . $locked->id,
                    'source_type' => WalletTopup::class,
                    'source_id' => $locked->id,
                    'title' => 'شارژ کیف پول',
                    'description' => 'شارژ آنلاین کیف پول از طریق ' . optional($locked->gateway)->title,
                    'meta' => ['tracking_code' => $locked->tracking_code],
                    'allow_inactive' => true,
                ]);

                $locked->status = 'paid';
                $locked->paid_at = now();
                $locked->reference_id = method_exists($receipt, 'getReferenceId') ? (string) $receipt->getReferenceId() : null;
                $locked->failure_message = null;
                $locked->meta = array_merge((array) $locked->meta, ['wallet_transaction_id' => $transaction->id]);
                $locked->save();
            });

            return redirect()->away($frontend . '/wallet/payment-result?status=success&topup=' . $topup->id);
        } catch (InvalidPaymentException $e) {
            $topup->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($frontend . '/wallet/payment-result?status=failed&topup=' . $topup->id);
        } catch (\Throwable $e) {
            $topup->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($frontend . '/wallet/payment-result?status=failed&topup=' . $topup->id);
        }
    }
}
