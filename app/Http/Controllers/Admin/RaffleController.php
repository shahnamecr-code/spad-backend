<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\PropertyRaffle;
use App\Models\RaffleGateway;
use App\Models\RaffleOrder;
use App\Models\RaffleSetting;
use App\Models\RaffleTicket;
use App\Models\RaffleTicketDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RaffleController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyRaffle::query()->with(['advertisement.city', 'advertisement.region', 'winnerTicket.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $items = $query->latest()->paginate(20);
        $items->getCollection()->transform(function ($raffle) {
            return $this->adminSummary($raffle);
        });
        return $items;
    }

    public function show(PropertyRaffle $raffle)
    {
        $raffle->load(['advertisement', 'ticketDesign', 'winnerTicket.user', 'drawOperator']);
        return response()->json([
            'data' => array_merge($raffle->toArray(), [
                'computed_status' => $raffle->computedStatus(),
                'sold_tickets' => $raffle->soldTicketsCount(),
                'remaining_capacity' => $raffle->remainingCapacity(true),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRaffle($request);
        $raffle = PropertyRaffle::create($data);
        return response()->json(['status' => true, 'message' => 'قرعه‌کشی با موفقیت ایجاد شد.', 'data' => $raffle]);
    }

    public function update(Request $request, PropertyRaffle $raffle)
    {
        $data = $this->validateRaffle($request, $raffle);
        $raffle->update($data);
        return response()->json(['status' => true, 'message' => 'اطلاعات قرعه‌کشی به‌روزرسانی شد.', 'data' => $raffle->fresh()]);
    }

    public function delete(PropertyRaffle $raffle)
    {
        if ($raffle->tickets()->exists() || $raffle->orders()->where('status', 'paid')->exists()) {
            return response()->json(['status' => false, 'message' => 'قرعه‌کشی دارای بلیط فروخته‌شده است و قابل حذف نیست.'], 422);
        }
        $raffle->delete();
        return response()->json(['status' => true, 'message' => 'قرعه‌کشی حذف شد.']);
    }

    public function advertisements(Request $request)
    {
        $query = Advertisement::query()->where('status', Advertisement::APPROVED)->with(['city', 'region'])->latest();
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($builder) use ($q) {
                if (is_numeric($q)) $builder->orWhere('id', $q);
                $builder->orWhereHas('properties', function ($p) use ($q) {
                    $p->where('property_slug', 'title')->where('string_value', 'like', "%{$q}%");
                });
            });
        }

        $items = $query->limit(80)->get()->map(function ($ad) {
            $property = userShowCategorySingleLayout($ad);
            return [
                'id' => $ad->id,
                'title' => $property['title'],
                'image' => isset($property['gallery'][0]) ? $property['gallery'][0] : null,
                'location' => $property['location'],
                'category' => $property['category'],
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:40960'],
            'type' => ['required', Rule::in(['hero', 'panorama', 'ticket', 'live'])],
        ]);

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, $allowed)) {
            return response()->json(['status' => false, 'message' => 'فرمت فایل باید JPG، PNG یا WEBP باشد.'], 422);
        }

        $path = $request->file('file')->store('raffles/' . $data['type'], 'public');
        return response()->json([
            'status' => true,
            'path' => $path,
            'url' => url(Storage::url($path)),
        ]);
    }

    public function tickets(Request $request, PropertyRaffle $raffle)
    {
        $query = RaffleTicket::query()
            ->where('raffle_id', $raffle->id)
            ->with(['user', 'order']);

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($builder) use ($q) {
                $builder->where('code', 'like', "%{$q}%")
                    ->orWhere('sequence_no', $q)
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('mobile', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $items = $query->orderBy('sequence_no')->paginate(40);
        $items->getCollection()->transform(function ($ticket) {
            return [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'sequence_no' => $ticket->sequence_no,
                'status' => $ticket->status,
                'purchased_at' => optional($ticket->purchased_at)->toDateTimeString(),
                'user' => [
                    'id' => optional($ticket->user)->id,
                    'name' => optional($ticket->user)->full_name,
                    'mobile' => optional($ticket->user)->mobile,
                ],
                'order' => [
                    'id' => optional($ticket->order)->id,
                    'tracking_code' => optional($ticket->order)->tracking_code,
                    'reference_id' => optional($ticket->order)->reference_id,
                    'amount' => optional($ticket->order)->amount,
                ],
            ];
        });

        return $items;
    }

    public function setWinner(Request $request, PropertyRaffle $raffle, RaffleTicket $ticket)
    {
        abort_unless($ticket->raffle_id === $raffle->id, 404);
        $data = $request->validate([
            'announcement' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($raffle, $ticket, $data) {
            $locked = PropertyRaffle::query()->whereKey($raffle->id)->lockForUpdate()->firstOrFail();

            RaffleTicket::query()->where('raffle_id', $locked->id)
                ->where('id', '<>', $ticket->id)
                ->whereIn('status', ['active', 'winner'])
                ->update(['status' => 'not_winner']);

            $ticket->update(['status' => 'winner']);
            $locked->update([
                'winner_ticket_id' => $ticket->id,
                'draw_operator_id' => optional(auth('admin')->user())->id,
                'winner_announcement' => $data['announcement'] ?? null,
                'drawn_at' => now(),
                'status' => 'completed',
            ]);
        });

        return response()->json(['status' => true, 'message' => 'برنده با موفقیت ثبت شد. نتیجه در پنل کاربران قابل مشاهده است.']);
    }

    public function resetWinner(PropertyRaffle $raffle)
    {
        DB::transaction(function () use ($raffle) {
            RaffleTicket::query()->where('raffle_id', $raffle->id)->whereIn('status', ['winner', 'not_winner'])->update(['status' => 'active']);
            $raffle->update([
                'winner_ticket_id' => null,
                'draw_operator_id' => null,
                'winner_announcement' => null,
                'drawn_at' => null,
                'status' => 'published',
            ]);
        });
        return response()->json(['status' => true, 'message' => 'نتیجه قرعه‌کشی بازنشانی شد.']);
    }

    public function orders(Request $request, PropertyRaffle $raffle)
    {
        $query = RaffleOrder::query()->where('raffle_id', $raffle->id)->with(['user', 'gateway']);
        if ($request->filled('status')) $query->where('status', $request->status);
        return $query->latest()->paginate(40);
    }

    public function getSettings()
    {
        $settings = RaffleSetting::query()->pluck('value', 'key');
        return response()->json([
            'settings' => $settings,
            'gateways' => RaffleGateway::query()->orderByDesc('is_default')->orderBy('id')->get()->map(function ($gateway) {
                return [
                    'id' => $gateway->id,
                    'driver' => $gateway->driver,
                    'title' => $gateway->title,
                    'enabled' => $gateway->enabled,
                    'is_default' => $gateway->is_default,
                    'config' => $gateway->maskedConfig(),
                    'has_config' => (bool) $gateway->config_encrypted,
                ];
            }),
            'ticket_designs' => RaffleTicketDesign::query()->latest()->get(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'page_title' => ['nullable', 'string', 'max:200'],
            'page_intro' => ['nullable', 'string', 'max:1000'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'payment_notice' => ['nullable', 'string', 'max:1500'],
            'reservation_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);
        foreach ($data as $key => $value) RaffleSetting::putValue($key, $value);
        return response()->json(['status' => true, 'message' => 'تنظیمات قرعه‌کشی ذخیره شد.']);
    }

    public function saveGateway(Request $request, RaffleGateway $gateway = null)
    {
        $data = $request->validate([
            'driver' => ['required', Rule::in(['local', 'zarinpal', 'idpay', 'zibal', 'payir'])],
            'title' => ['required', 'string', 'max:100'],
            'enabled' => ['boolean'],
            'is_default' => ['boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $gateway = $gateway ?: new RaffleGateway();
        $gateway->driver = $data['driver'];
        $gateway->title = $data['title'];
        $gateway->enabled = (bool) ($data['enabled'] ?? false);
        $gateway->is_default = (bool) ($data['is_default'] ?? false);

        $oldConfig = $gateway->exists ? $gateway->secureConfig() : [];
        $newConfig = isset($data['config']) ? array_filter($data['config'], function ($value) {
            return $value !== null && $value !== '' && strpos((string) $value, '••') === false;
        }) : [];
        $config = array_merge($oldConfig, $newConfig);
        if (!isset($config['currency'])) $config['currency'] = 'T';
        $gateway->setSecureConfig($config);
        $gateway->save();

        if ($gateway->is_default) {
            RaffleGateway::query()->where('id', '<>', $gateway->id)->update(['is_default' => false]);
        }

        return response()->json(['status' => true, 'message' => 'درگاه پرداخت ذخیره شد.', 'id' => $gateway->id]);
    }

    public function deleteGateway(RaffleGateway $gateway)
    {
        if ($gateway->orders()->exists()) {
            $gateway->update(['enabled' => false]);
            return response()->json(['status' => true, 'message' => 'درگاه به علت داشتن تراکنش قبلی غیرفعال شد و حذف نشد.']);
        }
        $gateway->delete();
        return response()->json(['status' => true, 'message' => 'درگاه حذف شد.']);
    }

    public function saveTicketDesign(Request $request, RaffleTicketDesign $design = null)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'image' => ['required', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
        $design = $design ?: new RaffleTicketDesign();
        $design->fill($data)->save();
        if ($design->is_active) RaffleTicketDesign::query()->where('id', '<>', $design->id)->update(['is_active' => false]);
        return response()->json(['status' => true, 'message' => 'طرح بلیط ذخیره شد.', 'data' => $design]);
    }

    public function deleteTicketDesign(RaffleTicketDesign $design)
    {
        PropertyRaffle::query()->where('ticket_design_id', $design->id)->update(['ticket_design_id' => null]);
        $design->delete();
        return response()->json(['status' => true, 'message' => 'طرح بلیط حذف شد.']);
    }

    protected function validateRaffle(Request $request, PropertyRaffle $raffle = null)
    {
        $id = $raffle ? $raffle->id : null;
        $data = $request->validate([
            'advertisement_id' => ['required', 'exists:advertisements,id'],
            'slug' => ['required', 'alpha_dash', 'max:190', Rule::unique('property_raffles', 'slug')->ignore($id)],
            'title' => ['required', 'string', 'max:250'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'draw_at' => ['nullable', 'date', 'after_or_equal:end_at'],
            'capacity' => ['required', 'integer', 'min:1'],
            'ticket_price' => ['required', 'integer', 'min:0'],
            'max_tickets_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(['draft', 'published', 'completed', 'cancelled'])],
            'hero_image' => ['nullable', 'string', 'max:1000'],
            'panorama_image' => ['nullable', 'string', 'max:1000'],
            'virtual_tour_url' => ['nullable', 'string', 'max:1500'],
            'live_enabled' => ['boolean'],
            'live_url' => ['nullable', 'string', 'max:1500'],
            'live_start_at' => ['nullable', 'date'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['nullable', 'string', 'max:250'],
            'ticket_design_id' => ['nullable', 'exists:raffle_ticket_designs,id'],
            'winner_announcement' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($raffle && $raffle->tickets()->count() > 0 && (int) $data['capacity'] < $raffle->soldTicketsCount()) {
            abort(422, 'ظرفیت نمی‌تواند کمتر از تعداد بلیط‌های فروخته‌شده باشد.');
        }

        $data['live_enabled'] = (bool) ($data['live_enabled'] ?? false);
        $data['highlights'] = array_values(array_filter($data['highlights'] ?? []));
        return $data;
    }

    protected function adminSummary(PropertyRaffle $raffle)
    {
        $property = $raffle->advertisement ? userShowCategorySingleLayout($raffle->advertisement) : null;
        return [
            'id' => $raffle->id,
            'slug' => $raffle->slug,
            'title' => $raffle->title,
            'property_title' => $property ? $property['title'] : '',
            'image' => $raffle->hero_image ?: ($property && isset($property['gallery'][0]) ? $property['gallery'][0] : null),
            'start_at' => optional($raffle->start_at)->toDateTimeString(),
            'end_at' => optional($raffle->end_at)->toDateTimeString(),
            'draw_at' => optional($raffle->draw_at)->toDateTimeString(),
            'status' => $raffle->status,
            'computed_status' => $raffle->computedStatus(),
            'capacity' => $raffle->capacity,
            'sold_tickets' => $raffle->soldTicketsCount(),
            'remaining_capacity' => $raffle->remainingCapacity(true),
            'ticket_price' => $raffle->ticket_price,
            'winner' => $raffle->winnerTicket ? [
                'code' => $raffle->winnerTicket->code,
                'name' => optional($raffle->winnerTicket->user)->full_name,
            ] : null,
        ];
    }
}
