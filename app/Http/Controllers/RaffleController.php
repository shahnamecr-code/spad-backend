<?php

namespace App\Http\Controllers;

use App\Models\PropertyRaffle;
use App\Models\RaffleGateway;
use App\Models\RaffleOrder;
use App\Models\RaffleSetting;
use App\Models\RaffleTicket;
use App\Models\WalletSetting;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class RaffleController extends Controller
{
    public function index()
    {
        $items = PropertyRaffle::query()
            ->whereIn('status', ['published', 'completed'])
            ->with(['advertisement.city', 'advertisement.region', 'advertisement.files'])
            ->orderByRaw("CASE
                WHEN status = 'published' AND start_at <= ? AND end_at >= ? THEN 0
                WHEN status = 'published' AND start_at > ? THEN 1
                WHEN status = 'published' THEN 2
                WHEN status = 'completed' THEN 3
                ELSE 4 END", [now(), now(), now()])
            ->orderByRaw("CASE WHEN status = 'completed' THEN end_at END DESC")
            ->orderBy('start_at')
            ->get()
            ->map(function ($raffle) {
                return $this->summary($raffle);
            });

        return response()->json(['data' => $items]);
    }

    public function active()
    {
        $raffle = PropertyRaffle::query()
            ->where('status', 'published')
            ->where('end_at', '>=', now())
            ->orderByRaw('CASE WHEN start_at <= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('start_at')
            ->first();

        if (!$raffle) {
            $raffle = PropertyRaffle::query()
                ->whereIn('status', ['published', 'completed'])
                ->latest('end_at')
                ->first();
        }

        if (!$raffle) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->payload($raffle, true)]);
    }

    public function show($slug)
    {
        $raffle = PropertyRaffle::query()
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'completed'])
            ->firstOrFail();

        return response()->json(['data' => $this->payload($raffle, true)]);
    }

    public function history()
    {
        $items = PropertyRaffle::query()
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'published')->where('end_at', '<', now());
                    });
            })
            ->latest('end_at')
            ->limit(12)
            ->get()
            ->map(function ($raffle) {
                return $this->summary($raffle);
            });

        return response()->json(['data' => $items]);
    }

    public function myTickets(Request $request)
    {
        $tickets = RaffleTicket::query()
            ->where('user_id', $request->user()->id)
            ->with(['user', 'raffle.advertisement.city', 'raffle.advertisement.region', 'order', 'raffle.ticketDesign'])
            ->latest('purchased_at')
            ->paginate(20);

        $tickets->getCollection()->transform(function ($ticket) {
            $raffle = $ticket->raffle;
            $status = $ticket->status;
            if ($status === 'active' && $raffle && in_array($raffle->computedStatus(), ['ended'])) {
                $status = 'waiting_draw';
            }

            $statusText = [
                'active' => 'فعال',
                'waiting_draw' => 'در انتظار قرعه‌کشی',
                'winner' => 'برنده شده',
                'not_winner' => 'برنده نشده',
                'cancelled' => 'لغو شده',
            ][$status] ?? $status;

            $property = $raffle && $raffle->advertisement ? userShowCategorySingleLayout($raffle->advertisement) : null;

            return [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'sequence_no' => $ticket->sequence_no,
                'status' => $status,
                'status_text' => $statusText,
                'purchased_at' => optional($ticket->purchased_at)->toIso8601String(),
                'registrant_name' => optional($ticket->user)->full_name,
                'raffle' => [
                    'id' => optional($raffle)->id,
                    'slug' => optional($raffle)->slug,
                    'title' => optional($raffle)->title,
                    'draw_at' => optional(optional($raffle)->draw_at)->toIso8601String(),
                    'end_at' => optional(optional($raffle)->end_at)->toIso8601String(),
                    'ticket_price' => optional($raffle)->ticket_price,
                    'winner_announcement' => optional($raffle)->winner_announcement,
                    'ticket_design' => optional(optional($raffle)->ticketDesign)->image,
                ],
                'property' => $property ? [
                    'id' => $property['id'],
                    'title' => $property['title'],
                    'gallery' => $property['gallery'],
                    'location' => $property['location'],
                ] : null,
                'order' => [
                    'tracking_code' => optional($ticket->order)->tracking_code,
                    'reference_id' => optional($ticket->order)->reference_id,
                    'amount' => optional($ticket->order)->amount,
                    'paid_at' => optional(optional($ticket->order)->paid_at)->toIso8601String(),
                    'payment_method' => $ticket->order
                        ? (($ticket->order->meta['payment_method'] ?? null) ?: ($ticket->order->gateway_id ? 'gateway' : 'wallet'))
                        : null,
                ],
            ];
        });

        return $tickets;
    }

    public function gateways()
    {
        return response()->json([
            'data' => RaffleGateway::query()
                ->where('enabled', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get(['id', 'driver', 'title', 'is_default'])
        ]);
    }

    public function purchase(Request $request, PropertyRaffle $raffle)
    {
        $data = $request->validate([
            'ticket_count' => ['required', 'integer', 'min:1', 'max:20'],
            'gateway_id' => ['required', 'exists:raffle_gateways,id'],
            'accept_terms' => ['accepted'],
        ]);

        $gateway = RaffleGateway::query()->where('id', $data['gateway_id'])->where('enabled', true)->firstOrFail();
        $user = $request->user();
        $reservationMinutes = max(5, (int) RaffleSetting::valueOf('reservation_minutes', 15));

        $order = DB::transaction(function () use ($raffle, $data, $gateway, $user, $reservationMinutes) {
            $locked = PropertyRaffle::query()->whereKey($raffle->id)->lockForUpdate()->firstOrFail();

            if ($locked->computedStatus() !== 'active') {
                abort(422, 'ثبت‌نام این قرعه‌کشی در حال حاضر فعال نیست.');
            }

            $alreadyOwned = RaffleTicket::query()
                ->where('raffle_id', $locked->id)
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            $pendingByUser = RaffleOrder::query()
                ->where('raffle_id', $locked->id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->sum('ticket_count');

            if (($alreadyOwned + $pendingByUser + $data['ticket_count']) > $locked->max_tickets_per_user) {
                abort(422, 'تعداد بلیط انتخابی بیشتر از سقف مجاز برای هر کاربر است.');
            }

            if ($locked->remainingCapacity(true) < $data['ticket_count']) {
                abort(422, 'ظرفیت باقی‌مانده برای این تعداد بلیط کافی نیست.');
            }

            $amount = ((int) $locked->ticket_price) * ((int) $data['ticket_count']);
            $config = $gateway->secureConfig();
            $currency = isset($config['currency']) ? strtoupper($config['currency']) : 'T';
            $gatewayAmount = $currency === 'R' ? $amount * 10 : $amount;

            return RaffleOrder::create([
                'user_id' => $user->id,
                'raffle_id' => $locked->id,
                'gateway_id' => $gateway->id,
                'ticket_count' => $data['ticket_count'],
                'amount' => $amount,
                'gateway_amount' => $gatewayAmount,
                'gateway_currency' => $currency,
                'status' => 'pending',
                'tracking_code' => 'SPAD-' . strtoupper(Str::random(6)) . '-' . now()->format('ymdHis'),
                'callback_token' => Str::random(64),
                'expires_at' => now()->addMinutes($reservationMinutes),
            ]);
        });

        try {
            $invoice = (new Invoice)->amount($order->gateway_amount)
                ->detail('raffle_order', $order->tracking_code)
                ->detail('tickets', $order->ticket_count);

            $callbackUrl = url('/api/raffle-payments/callback/' . $order->id . '/' . $order->callback_token);
            $payment = Payment::via($gateway->driver)
                ->config($gateway->secureConfig())
                ->callbackUrl($callbackUrl)
                ->purchase($invoice, function ($driver, $transactionId) use ($order) {
                    $order->transaction_id = (string) $transactionId;
                    $order->save();
                })
                ->pay();

            return response()->json([
                'status' => true,
                'order_id' => $order->id,
                'tracking_code' => $order->tracking_code,
                'payment' => $payment->jsonSerialize(),
            ]);
        } catch (\Throwable $e) {
            $order->update([
                'status' => 'failed',
                'failure_message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'امکان اتصال به درگاه پرداخت وجود ندارد. لطفاً درگاه دیگری را انتخاب کنید.',
            ], 422);
        }
    }

    public function purchaseWithWallet(Request $request, PropertyRaffle $raffle, WalletService $wallets)
    {
        $data = $request->validate([
            'ticket_count' => ['required', 'integer', 'min:1', 'max:20'],
            'accept_terms' => ['accepted'],
        ]);

        if (WalletSetting::valueOf('wallet_enabled', '1') !== '1') {
            return response()->json([
                'status' => false,
                'message' => 'پرداخت از کیف پول در حال حاضر غیرفعال است.',
            ], 422);
        }

        $user = $request->user();

        try {
            $result = DB::transaction(function () use ($raffle, $data, $user, $wallets) {
                // Lock the raffle first so capacity cannot be sold twice by concurrent requests.
                $locked = PropertyRaffle::query()->whereKey($raffle->id)->lockForUpdate()->firstOrFail();

                if ($locked->computedStatus() !== 'active') {
                    throw new \RuntimeException('ثبت‌نام این قرعه‌کشی در حال حاضر فعال نیست.');
                }

                $alreadyOwned = RaffleTicket::query()
                    ->where('raffle_id', $locked->id)
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['cancelled'])
                    ->count();

                $pendingByUser = RaffleOrder::query()
                    ->where('raffle_id', $locked->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->sum('ticket_count');

                if (($alreadyOwned + $pendingByUser + $data['ticket_count']) > $locked->max_tickets_per_user) {
                    throw new \RuntimeException('تعداد بلیط انتخابی بیشتر از سقف مجاز برای هر کاربر است.');
                }

                if ($locked->remainingCapacity(true) < $data['ticket_count']) {
                    throw new \RuntimeException('ظرفیت باقی‌مانده برای این تعداد بلیط کافی نیست.');
                }

                $amount = ((int) $locked->ticket_price) * ((int) $data['ticket_count']);

                // gateway_id is nullable in the original raffle schema. For wallet orders it stays null.
                $order = RaffleOrder::create([
                    'user_id' => $user->id,
                    'raffle_id' => $locked->id,
                    'gateway_id' => null,
                    'ticket_count' => $data['ticket_count'],
                    'amount' => $amount,
                    'gateway_amount' => 0,
                    'gateway_currency' => 'WALLET',
                    'status' => 'pending',
                    'tracking_code' => 'SPAD-W-' . strtoupper(Str::random(6)) . '-' . now()->format('ymdHis'),
                    'callback_token' => Str::random(64),
                    'expires_at' => null,
                    'meta' => [
                        'payment_method' => 'wallet',
                    ],
                ]);

                // WalletService uses SELECT ... FOR UPDATE and a unique reference,
                // so the debit is atomic and cannot be applied twice for the same order.
                $walletTransaction = $wallets->debit($user, $amount, [
                    'type' => 'purchase',
                    'reference' => 'raffle-order-wallet:' . $order->id,
                    'source_type' => RaffleOrder::class,
                    'source_id' => $order->id,
                    'title' => 'خرید بلیط قرعه‌کشی ملک',
                    'description' => 'پرداخت ' . $data['ticket_count'] . ' بلیط برای «' . $locked->title . '» از کیف پول SPAD',
                    'meta' => [
                        'raffle_id' => $locked->id,
                        'raffle_slug' => $locked->slug,
                        'ticket_count' => (int) $data['ticket_count'],
                        'order_tracking_code' => $order->tracking_code,
                    ],
                ]);

                $lastSequence = (int) RaffleTicket::query()
                    ->where('raffle_id', $locked->id)
                    ->lockForUpdate()
                    ->max('sequence_no');

                $tickets = [];
                for ($i = 1; $i <= (int) $data['ticket_count']; $i++) {
                    $sequence = $lastSequence + $i;
                    $ticket = RaffleTicket::create([
                        'raffle_id' => $locked->id,
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'sequence_no' => $sequence,
                        'code' => 'SPAD-R' . $locked->id . '-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                        'status' => 'active',
                        'purchased_at' => now(),
                    ]);

                    $tickets[] = [
                        'id' => $ticket->id,
                        'code' => $ticket->code,
                        'sequence_no' => $ticket->sequence_no,
                        'status' => $ticket->status,
                    ];
                }

                $orderMeta = $order->meta ?: [];
                $orderMeta['wallet_transaction_id'] = $walletTransaction->id;
                $orderMeta['wallet_reference'] = $walletTransaction->reference;

                $order->status = 'paid';
                $order->paid_at = now();
                $order->transaction_id = 'wallet:' . $walletTransaction->id;
                $order->reference_id = 'WALLET-' . $walletTransaction->id;
                $order->failure_message = null;
                $order->meta = $orderMeta;
                $order->save();

                return [
                    'order' => $order,
                    'wallet_transaction' => $walletTransaction,
                    'tickets' => $tickets,
                ];
            });

            return response()->json([
                'status' => true,
                'payment_method' => 'wallet',
                'order_id' => $result['order']->id,
                'tracking_code' => $result['order']->tracking_code,
                'reference_id' => $result['order']->reference_id,
                'amount' => (int) $result['order']->amount,
                'wallet_balance' => (int) $result['wallet_transaction']->balance_after,
                'tickets' => $result['tickets'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function orderStatus(Request $request, RaffleOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('tickets');

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
            'tracking_code' => $order->tracking_code,
            'reference_id' => $order->reference_id,
            'amount' => $order->amount,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
            'payment_method' => (($order->meta['payment_method'] ?? null) ?: ($order->gateway_id ? 'gateway' : 'wallet')),
            'tickets' => $order->tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'code' => $ticket->code,
                    'sequence_no' => $ticket->sequence_no,
                    'status' => $ticket->status,
                ];
            }),
        ]);
    }

    protected function payload(PropertyRaffle $raffle, $withProperty = false)
    {
        $raffle->load(['advertisement', 'ticketDesign', 'winnerTicket.user']);
        $sold = $raffle->soldTicketsCount();
        $remaining = $raffle->remainingCapacity(true);
        $property = null;
        if ($withProperty && $raffle->advertisement) {
            $property = userShowCategorySingleLayout($raffle->advertisement);
        }

        return array_merge($this->summary($raffle), [
            'subtitle' => $raffle->subtitle,
            'description' => $raffle->description,
            'terms' => $raffle->terms,
            'sold_tickets' => $sold,
            'reserved_tickets' => $raffle->reservedTicketsCount(),
            'remaining_capacity' => $remaining,
            'progress_percent' => $raffle->capacity > 0 ? min(100, round(($sold / $raffle->capacity) * 100, 1)) : 0,
            'max_tickets_per_user' => $raffle->max_tickets_per_user,
            'hero_image' => $raffle->hero_image,
            'panorama_image' => $raffle->panorama_image,
            'virtual_tour_url' => $raffle->virtual_tour_url,
            'live' => [
                'enabled' => $raffle->live_enabled,
                'url' => $raffle->live_url,
                'start_at' => optional($raffle->live_start_at)->toIso8601String(),
            ],
            'highlights' => $raffle->highlights ?: [],
            'ticket_design' => $raffle->ticketDesign ? [
                'id' => $raffle->ticketDesign->id,
                'title' => $raffle->ticketDesign->title,
                'image' => $raffle->ticketDesign->image,
            ] : null,
            'winner' => $raffle->winnerTicket ? [
                'ticket_code' => $raffle->winnerTicket->code,
                'sequence_no' => $raffle->winnerTicket->sequence_no,
                'name' => optional($raffle->winnerTicket->user)->full_name,
                'announcement' => $raffle->winner_announcement,
            ] : null,
            'property' => $property,
            'settings' => [
                'page_title' => RaffleSetting::valueOf('page_title', 'قرعه‌کشی ویژه ملک'),
                'page_intro' => RaffleSetting::valueOf('page_intro', 'فرصتی متفاوت برای ورود به قرعه‌کشی یک ملک ویژه.'),
                'support_phone' => RaffleSetting::valueOf('support_phone', ''),
                'payment_notice' => RaffleSetting::valueOf('payment_notice', 'پس از پرداخت موفق، بلیط و کد شرکت در قرعه‌کشی در پنل کاربری شما ثبت می‌شود.'),
            ],
        ]);
    }

    protected function summary(PropertyRaffle $raffle)
    {
        $raffle->loadMissing(['advertisement.city', 'advertisement.region', 'advertisement.files']);
        $propertyTitle = $raffle->title;
        $image = $raffle->hero_image;
        $property = null;
        if ($raffle->advertisement) {
            $property = userShowCategorySingleLayout($raffle->advertisement);
            $propertyTitle = $property['title'] ?: $propertyTitle;
            if (!$image) {
                $image = isset($property['gallery'][0]) ? $property['gallery'][0] : null;
            }
        }

        $sold = $raffle->soldTicketsCount();
        $reserved = $raffle->reservedTicketsCount();
        $remaining = max(0, (int) $raffle->capacity - $sold - $reserved);

        return [
            'id' => $raffle->id,
            'slug' => $raffle->slug,
            'title' => $propertyTitle,
            'subtitle' => $raffle->subtitle,
            'status' => $raffle->computedStatus(),
            'database_status' => $raffle->status,
            'start_at' => optional($raffle->start_at)->toIso8601String(),
            'end_at' => optional($raffle->end_at)->toIso8601String(),
            'draw_at' => optional($raffle->draw_at)->toIso8601String(),
            'drawn_at' => optional($raffle->drawn_at)->toIso8601String(),
            'capacity' => $raffle->capacity,
            'ticket_price' => $raffle->ticket_price,
            'sold_tickets' => $sold,
            'reserved_tickets' => $reserved,
            'remaining_capacity' => $remaining,
            'progress_percent' => $raffle->capacity > 0 ? min(100, round(($sold / $raffle->capacity) * 100, 1)) : 0,
            'property_location' => isset($property) ? ($property['location'] ?? null) : null,
            'property_category' => isset($property) ? ($property['category'] ?? null) : null,
            'image' => $image,
        ];
    }
}
