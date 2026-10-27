<?php

namespace App\Http\Controllers;

use App\Models\PropertyRaffle;
use App\Models\RaffleOrder;
use App\Models\RaffleTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;

class RafflePaymentController extends Controller
{
    public function callback(Request $request, RaffleOrder $order, $token)
    {
        $frontend = rtrim(config('raffle.frontend_url', 'https://spad.estate'), '/');

        if (!hash_equals((string) $order->callback_token, (string) $token)) {
            return redirect()->away($frontend . '/raffle/payment-result?status=failed&reason=invalid');
        }

        if ($order->status === 'paid') {
            return redirect()->away($frontend . '/raffle/payment-result?status=success&order=' . $order->id);
        }

        if (!$order->gateway || !$order->transaction_id) {
            $order->update(['status' => 'failed', 'failure_message' => 'اطلاعات درگاه ناقص است.']);
            return redirect()->away($frontend . '/raffle/payment-result?status=failed&order=' . $order->id);
        }

        try {
            $receipt = Payment::via($order->gateway->driver)
                ->config($order->gateway->secureConfig())
                ->amount($order->gateway_amount)
                ->transactionId($order->transaction_id)
                ->verify();

            DB::transaction(function () use ($order, $receipt) {
                $lockedOrder = RaffleOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status === 'paid') return;

                $raffle = PropertyRaffle::query()->whereKey($lockedOrder->raffle_id)->lockForUpdate()->firstOrFail();

                // The seats were reserved at checkout. We still guard against damaged/manual data.
                if ($raffle->soldTicketsCount() + $lockedOrder->ticket_count > $raffle->capacity) {
                    throw new \RuntimeException('ظرفیت قرعه‌کشی تکمیل شده است. پرداخت نیاز به بررسی اپراتور دارد.');
                }

                $lastSequence = (int) RaffleTicket::query()
                    ->where('raffle_id', $raffle->id)
                    ->lockForUpdate()
                    ->max('sequence_no');

                for ($i = 1; $i <= $lockedOrder->ticket_count; $i++) {
                    $sequence = $lastSequence + $i;
                    RaffleTicket::create([
                        'raffle_id' => $raffle->id,
                        'order_id' => $lockedOrder->id,
                        'user_id' => $lockedOrder->user_id,
                        'sequence_no' => $sequence,
                        'code' => 'SPAD-R' . $raffle->id . '-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                        'status' => 'active',
                        'purchased_at' => now(),
                    ]);
                }

                $lockedOrder->status = 'paid';
                $lockedOrder->paid_at = now();
                $lockedOrder->reference_id = method_exists($receipt, 'getReferenceId') ? (string) $receipt->getReferenceId() : null;
                $lockedOrder->failure_message = null;
                $lockedOrder->save();
            });

            return redirect()->away($frontend . '/raffle/payment-result?status=success&order=' . $order->id);
        } catch (InvalidPaymentException $e) {
            $order->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($frontend . '/raffle/payment-result?status=failed&order=' . $order->id);
        } catch (\Throwable $e) {
            $order->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            return redirect()->away($frontend . '/raffle/payment-result?status=failed&order=' . $order->id);
        }
    }
}
