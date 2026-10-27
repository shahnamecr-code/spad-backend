<?php

namespace App\Http\Controllers;

use App\Models\InquiryRequest;
use App\Services\InquiryExecutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;

class InquiryPaymentController extends Controller
{
    protected $executor;

    public function __construct(InquiryExecutor $executor)
    {
        $this->executor = $executor;
    }

    public function callback(Request $request, InquiryRequest $inquiry, $token)
    {
        $frontend = rtrim(env('FRONTEND_URL', env('APP_FRONTEND_URL', 'https://spad.estate')), '/');
        $resultUrl = $frontend . '/inquiries/payment-result?reference=' . urlencode($inquiry->reference);

        if (!$inquiry->callback_token || !hash_equals((string) $inquiry->callback_token, (string) $token)) {
            return redirect()->away($resultUrl . '&status=failed&reason=invalid');
        }

        if ($inquiry->payment_status === 'paid') {
            return redirect()->away($resultUrl . '&status=success');
        }

        if (!$inquiry->gateway || !$inquiry->transaction_id) {
            $inquiry->update(['payment_status' => 'failed', 'failure_message' => 'اطلاعات درگاه ناقص است.']);
            return redirect()->away($resultUrl . '&status=failed');
        }

        try {
            $receipt = Payment::via($inquiry->gateway->driver)
                ->config($inquiry->gateway->secureConfig())
                ->amount($inquiry->gateway_amount)
                ->transactionId($inquiry->transaction_id)
                ->verify();

            DB::transaction(function () use ($inquiry, $receipt) {
                $locked = InquiryRequest::query()->whereKey($inquiry->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status === 'paid') return;
                $locked->payment_status = 'paid';
                $locked->paid_at = now();
                $locked->gateway_reference_id = method_exists($receipt, 'getReferenceId') ? (string) $receipt->getReferenceId() : null;
                $locked->failure_message = null;
                $locked->save();
                $this->executor->log($locked, 'paid_gateway', 'پرداخت آنلاین استعلام با موفقیت تأیید شد.');
            });

            $this->executor->process($inquiry->fresh());
            return redirect()->away($resultUrl . '&status=success');
        } catch (InvalidPaymentException $e) {
            $inquiry->update(['payment_status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($resultUrl . '&status=failed');
        } catch (\Throwable $e) {
            $inquiry->update(['payment_status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($resultUrl . '&status=failed');
        }
    }
}
