<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\VirtualTour;
use App\Services\Renovation\RoomLayoutAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Renovation studio v2. Lists the published 360 tours and their rooms, serves
 * the studio catalog, keeps one analysed layout per room, prices a design as
 * an approximate pre-invoice, and stores designs as renovation projects so
 * the existing admin screens show them.
 */
class RenovationStudioController extends Controller
{
    private const UNIT_FA = ['m2' => 'متر مربع', 'm' => 'متر طول', 'item' => 'عدد', 'job' => 'سرویس'];
    private const MAX_QTY = ['m2' => 400, 'm' => 60, 'item' => 30, 'job' => 1];

    private function ready()
    {
        abort_unless(Schema::hasTable('spad_renovation_layouts') && Schema::hasColumn('spad_renovation_assets', 'render'),
            503, 'استودیو بازسازی در حال راه‌اندازی است.');
    }

    private function uid(Request $r): int
    {
        $id = optional($r->user())->id;
        abort_unless($id, 401, 'ابتدا وارد حساب شوید.');
        return (int) $id;
    }

    private function disk()
    {
        return Storage::disk(config('virtual-tour.disk', 'local'));
    }

    // ---------------------------------------------------------------- tours

    private function publishedTours()
    {
        return VirtualTour::query()->with(['advertisement', 'scenes'])->where('status', 'published')
            ->orderByDesc('id')->limit(80)->get()
            ->filter(fn($t) => $t->advertisement
                && (int) $t->advertisement->status === Advertisement::APPROVED
                && !$t->advertisement->is_investor_exclusive);
    }

    /** Rooms of a tour: ready-made package scenes or processed legacy scenes. */
    private function scenesOf(VirtualTour $tour): array
    {
        $settings = $tour->settings ?: [];
        if (!empty($settings['package_ready']) && is_string($settings['package_root'] ?? null)) {
            $root = $settings['package_root'];
            $base = url('/api/virtual-tours/packages/' . $tour->id) . '/';
            if (!$this->disk()->exists($root . '/scenes.js')) return [];
            $js = (string) $this->disk()->get($root . '/scenes.js');
            $start = strpos($js, '{');
            $end = strrpos($js, '}');
            $config = $start !== false && $end !== false ? json_decode(substr($js, $start, $end - $start + 1), true) : null;
            $files = is_array($settings['package_files'] ?? null) ? $settings['package_files'] : [];
            $out = [];
            foreach ((array) ($config['scenes'] ?? []) as $i => $s) {
                $src = (string) ($s['src'] ?? '');
                if ($src === '' || !in_array($src, $files, true)) continue;
                $mobile = (string) ($s['mobileSrc'] ?? '');
                $thumb = (string) ($s['thumb'] ?? '');
                $out[] = [
                    'key' => 'p' . (int) ($s['index'] ?? $i),
                    'name' => mb_substr((string) ($s['name'] ?? ('فضای ' . ($i + 1))), 0, 60),
                    'src' => $base . $src,
                    'mobile' => $mobile !== '' && in_array($mobile, $files, true) ? $base . $mobile : $base . $src,
                    'thumb' => $thumb !== '' && in_array($thumb, $files, true) ? $base . $thumb : null,
                    'yaw' => is_numeric($s['startYaw'] ?? null) ? (float) $s['startYaw'] : 0,
                ];
            }
            return $out;
        }
        $out = [];
        foreach ($tour->scenes->where('process_status', 'ready')->sortBy('sort_order') as $scene) {
            $asset = url('/api/virtual-tours/assets/scenes/' . $scene->id);
            $out[] = [
                'key' => 's' . $scene->id,
                'name' => mb_substr((string) ($scene->title ?: 'فضا'), 0, 60),
                'src' => $asset . ($scene->high_path ? '/high' : '/medium'),
                'mobile' => $asset . ($scene->medium_path ? '/medium' : '/high'),
                'thumb' => $scene->thumbnail_path ? $asset . '/thumbnail' : null,
                'yaw' => (float) ($scene->initial_yaw ?? 0),
            ];
        }
        return $out;
    }

    /** Path on disk of a room's panorama, smallest variant that is still detailed. */
    private function scenePanoramaPath(VirtualTour $tour, string $sceneKey): ?string
    {
        $settings = $tour->settings ?: [];
        if (str_starts_with($sceneKey, 'p') && !empty($settings['package_ready'])) {
            $root = $settings['package_root'];
            $js = (string) $this->disk()->get($root . '/scenes.js');
            $start = strpos($js, '{');
            $end = strrpos($js, '}');
            $config = json_decode(substr($js, $start, $end - $start + 1), true);
            $files = (array) ($settings['package_files'] ?? []);
            foreach ((array) ($config['scenes'] ?? []) as $i => $s) {
                if ('p' . (int) ($s['index'] ?? $i) !== $sceneKey) continue;
                foreach ([(string) ($s['mobileSrc'] ?? ''), (string) ($s['src'] ?? '')] as $file) {
                    if ($file !== '' && in_array($file, $files, true) && $this->disk()->exists($root . '/' . $file)) {
                        return $root . '/' . $file;
                    }
                }
            }
            return null;
        }
        if (str_starts_with($sceneKey, 's')) {
            $scene = $tour->scenes->firstWhere('id', (int) substr($sceneKey, 1));
            foreach (['high_path', 'medium_path', 'ultra_path'] as $col) {
                if ($scene && $scene->{$col} && $this->disk()->exists($scene->{$col})) return $scene->{$col};
            }
        }
        return null;
    }

    private function tourCard(VirtualTour $tour, bool $withScenes): ?array
    {
        $scenes = $this->scenesOf($tour);
        if (!$scenes) return null;
        $ad = $tour->advertisement;
        $card = [
            'id' => $tour->id,
            'advertisement_id' => $tour->advertisement_id,
            'title' => mb_substr((string) ($ad->title ?? $tour->title), 0, 120),
            'tour_title' => (string) $tour->title,
            'thumbnail' => $scenes[0]['thumb'] ?? null,
            'scene_count' => count($scenes),
            'area' => is_numeric($ad->area ?? null) ? (int) $ad->area : null,
        ];
        if ($withScenes) $card['scenes'] = $scenes;
        return $card;
    }

    public function tours()
    {
        $this->ready();
        $data = Cache::remember('spad:reno-studio:tours:v1', 600, function () {
            return $this->publishedTours()->map(fn($t) => $this->tourCard($t, false))->filter()->values()->all();
        });
        return response()->json(['data' => $data, 'analysis' => RoomLayoutAnalyzer::configured()]);
    }

    public function tour($tourId)
    {
        $this->ready();
        $tour = $this->publishedTours()->firstWhere('id', (int) $tourId);
        abort_unless($tour, 404, 'تور یافت نشد.');
        $card = $this->tourCard($tour, true);
        abort_unless($card, 404, 'این تور فضای آماده ندارد.');
        return response()->json(['data' => $card, 'analysis' => RoomLayoutAnalyzer::configured()]);
    }

    // -------------------------------------------------------------- catalog

    private function rates(): array
    {
        return DB::table('spad_renovation_rates')->where('enabled', true)->get()->keyBy('code')->all();
    }

    private function productRows()
    {
        return DB::table('spad_renovation_assets')->where('enabled', true)->whereNotNull('surface')
            ->whereNotNull('render')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function products()
    {
        $this->ready();
        $rates = $this->rates();
        $items = $this->productRows()->map(function ($a) use ($rates) {
            $render = json_decode((string) $a->render, true) ?: [];
            $rate = $rates[$render['rate'] ?? $a->rate_code] ?? null;
            return [
                'code' => $a->code, 'category' => $a->category, 'category_title' => $a->category_title,
                'title' => $a->title, 'subtitle' => $a->subtitle, 'surface' => $a->surface,
                'unit' => $render['unit'] ?? $a->unit, 'room_scope' => $a->room_scope,
                'price' => $a->unit_price_toman !== null ? (int) $a->unit_price_toman : null,
                'labor' => $rate ? (int) $rate->price_toman : 0,
                'waste' => (int) $a->waste_percent,
                'default_qty' => $a->default_qty !== null ? (float) $a->default_qty : null,
                'render' => $render,
            ];
        })->values();
        return response()->json(['data' => $items, 'units' => self::UNIT_FA]);
    }

    // --------------------------------------------------------------- layout

    public function layout($tourId, $sceneKey)
    {
        $this->ready();
        $row = DB::table('spad_renovation_layouts')->where('tour_id', (int) $tourId)->where('scene_key', $sceneKey)
            ->whereIn('source', ['claude', 'admin'])->orderByRaw("source = 'admin' desc")->first();
        return response()->json(['data' => [
            'layout' => $row ? json_decode($row->layout, true) : RoomLayoutAnalyzer::defaultLayout(),
            'source' => $row ? $row->source : 'default',
            'analysis' => RoomLayoutAnalyzer::configured(),
        ]]);
    }

    public function analyze($tourId, $sceneKey)
    {
        $this->ready();
        abort_unless(RoomLayoutAnalyzer::configured(), 503, 'تحلیل خودکار اتاق هنوز فعال نشده است؛ گوشه‌های دیوار را خودتان تنظیم کنید.');
        $existing = DB::table('spad_renovation_layouts')->where('tour_id', (int) $tourId)
            ->where('scene_key', $sceneKey)->where('source', 'claude')->first();
        // Layouts from the first version (walls only, no elements) are analysed again once.
        $old = $existing ? json_decode($existing->layout, true) : null;
        if ($old && (int) ($old['schema'] ?? 1) >= 2) {
            return response()->json(['data' => ['layout' => $old, 'source' => 'claude', 'analysis' => true]]);
        }
        $tour = $this->publishedTours()->firstWhere('id', (int) $tourId);
        abort_unless($tour, 404, 'تور یافت نشد.');
        $path = $this->scenePanoramaPath($tour, (string) $sceneKey);
        abort_unless($path, 404, 'تصویر این فضا یافت نشد.');

        @set_time_limit(320);
        $lock = Cache::lock('spad:reno-studio:analyze:' . $tourId . ':' . $sceneKey, 330);
        abort_unless($lock->get(), 409, 'تحلیل این فضا در حال انجام است؛ چند لحظه بعد دوباره امتحان کنید.');
        try {
            // The rooms before and after this one in the tour give context (shared walls, the kitchen seen from the hall...).
            $scenes = $this->scenesOf($tour);
            $keys = array_column($scenes, 'key');
            $at = array_search((string) $sceneKey, $keys, true);
            $neighbours = [];
            $name = '';
            if ($at !== false) {
                $name = (string) ($scenes[$at]['name'] ?? '');
                foreach ([$at - 1, $at + 1] as $j) {
                    if (!isset($scenes[$j])) continue;
                    $np = $this->scenePanoramaPath($tour, (string) $scenes[$j]['key']);
                    if ($np) $neighbours[] = ['name' => (string) ($scenes[$j]['name'] ?? ''), 'bytes' => (string) $this->disk()->get($np)];
                }
            }
            $result = (new RoomLayoutAnalyzer())->analyze((string) $this->disk()->get($path), $neighbours, $name);
            DB::table('spad_renovation_layouts')->updateOrInsert(
                ['tour_id' => (int) $tourId, 'scene_key' => $sceneKey, 'source' => 'claude'],
                ['layout' => json_encode($result['layout'], JSON_UNESCAPED_UNICODE), 'model' => $result['model'],
                 'updated_at' => now(), 'created_at' => now()]
            );
            return response()->json(['data' => ['layout' => $result['layout'], 'source' => 'claude', 'analysis' => true]]);
        } catch (\Throwable $e) {
            Log::warning('renovation studio analysis failed', ['tour' => $tourId, 'scene' => $sceneKey, 'error' => $e->getMessage()]);
            abort(503, 'تحلیل خودکار این فضا انجام نشد؛ گوشه‌های دیوار را خودتان تنظیم کنید.');
        } finally {
            optional($lock)->release();
        }
    }

    // ---------------------------------------------------------------- quote

    private function validatedItems(Request $r): array
    {
        $d = $r->validate([
            'tour_id' => 'nullable|integer',
            'items' => 'required|array|max:200',
            'items.*.scene' => 'required|string|max:60',
            'items.*.scene_name' => 'nullable|string|max:60',
            'items.*.target' => 'required|string|max:20',
            'items.*.product' => 'required|string|max:60',
            'items.*.qty' => 'required|numeric|min:0|max:400',
        ]);
        return $d['items'];
    }

    private function buildQuote(array $items): array
    {
        $products = $this->productRows()->keyBy('code');
        $rates = $this->rates();
        $mat = [];
        $labor = [];
        $demo = [];
        $rooms = [];
        $pending = 0;
        foreach ($items as $it) {
            $p = $products[$it['product']] ?? null;
            if (!$p) continue;
            $render = json_decode((string) $p->render, true) ?: [];
            $unit = $render['unit'] ?? $p->unit ?? 'item';
            $qty = min((float) $it['qty'], self::MAX_QTY[$unit] ?? 30);
            if ($qty <= 0) continue;
            $room = mb_substr((string) ($it['scene_name'] ?? ''), 0, 60);
            if ($room !== '') $rooms[$room] = true;

            $key = $p->code;
            $mat[$key] = $mat[$key] ?? ['code' => $p->code, 'title' => $p->category_title . '، ' . $p->title,
                'unit' => $unit, 'qty' => 0, 'unit_price' => $p->unit_price_toman !== null ? (int) $p->unit_price_toman : null,
                'waste' => (int) $p->waste_percent, 'rooms' => []];
            $mat[$key]['qty'] += $qty;
            if ($room !== '') $mat[$key]['rooms'][$room] = true;

            $rateCode = $render['rate'] ?? $p->rate_code;
            if ($rateCode && isset($rates[$rateCode])) {
                $labor[$rateCode] = ($labor[$rateCode] ?? 0) + $qty;
            }
            if ($p->surface === 'floor') $demo['demo-floor'] = ($demo['demo-floor'] ?? 0) + $qty;
            if ($p->surface === 'cabinet') $demo['demo-cabinet'] = ($demo['demo-cabinet'] ?? 0) + $qty;
            if ($p->surface === 'wall' && in_array($rateCode, ['wallpaper', 'wall-panel', 'tile-wall'], true)) {
                $demo['demo-wall'] = ($demo['demo-wall'] ?? 0) + $qty;
            }
        }

        $round = fn($v) => (int) (round($v / 10000) * 10000);
        $groups = [];
        $lines = [];
        $subtotal = 0;
        foreach ($mat as $m) {
            $qtyEff = $m['qty'] * (1 + $m['waste'] / 100);
            $total = $m['unit_price'] !== null ? $round($qtyEff * $m['unit_price']) : null;
            if ($total === null) $pending++;
            $lines[] = ['code' => $m['code'], 'title' => $m['title'], 'qty' => round($qtyEff, 1), 'unit' => $m['unit'],
                'unit_fa' => self::UNIT_FA[$m['unit']] ?? $m['unit'], 'unit_price' => $m['unit_price'], 'total' => $total,
                'note' => $m['waste'] ? ('با ' . $m['waste'] . '٪ پرت') : '', 'rooms' => array_keys($m['rooms'])];
            $subtotal += (int) $total;
        }
        $groups[] = ['key' => 'materials', 'title' => 'مصالح و کالا', 'lines' => $lines, 'subtotal' => $subtotal];

        $days = 0;
        $lead = 0;
        $build = function (array $quantities, string $key, string $title) use ($rates, $round, &$days, &$lead) {
            $lines = [];
            $sum = 0;
            foreach ($quantities as $code => $qty) {
                $rate = $rates[$code] ?? null;
                if (!$rate) continue;
                $qty = $rate->unit === 'job' ? 1 : $qty;
                $total = $round($qty * (int) $rate->price_toman);
                $lines[] = ['code' => $code, 'title' => $rate->title, 'qty' => round($qty, 1), 'unit' => $rate->unit,
                    'unit_fa' => self::UNIT_FA[$rate->unit] ?? $rate->unit, 'unit_price' => (int) $rate->price_toman, 'total' => $total];
                $sum += $total;
                $perDay = (float) ($rate->per_day ?? 0);
                $days += $perDay > 0 ? (int) ceil($qty / $perDay) : (int) $rate->days;
                $lead = max($lead, (int) ($rate->lead_days ?? 0));
            }
            return ['key' => $key, 'title' => $title, 'lines' => $lines, 'subtotal' => $sum];
        };
        if ($mat) $demo['cleanup'] = 1;
        $groups[] = $build($labor, 'labor', 'دستمزد اجرا');
        $groups[] = $build($demo, 'demolition', 'تخریب، آماده‌سازی و نظافت');

        $total = array_sum(array_column($groups, 'subtotal'));
        $workDays = $days ? $days + $lead : 0;
        return [
            'groups' => $groups,
            'total' => $total,
            'range' => [(int) (floor($total * 0.9 / 100000) * 100000), (int) (ceil($total * 1.15 / 100000) * 100000)],
            'days' => $workDays,
            'days_range' => $workDays ? [max(1, (int) floor($workDays * 0.85)), (int) ceil($workDays * 1.25)] : [0, 0],
            'pricing_pending' => $pending,
            'rooms' => array_keys($rooms),
            'notice' => 'این پیش‌فاکتور تقریبی است و بر اساس متراژ برآوردشده از تور و قیمت‌های روز کاتالوگ اسپاد محاسبه شده است. قیمت نهایی پس از بازدید کارشناس اسپاد اعلام می‌شود.',
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function quote(Request $r)
    {
        $this->ready();
        return response()->json(['data' => $this->buildQuote($this->validatedItems($r))]);
    }

    // -------------------------------------------------------------- designs

    private function legacyInvoice(array $q, int $id): array
    {
        $lines = [];
        foreach ($q['groups'] as $g) {
            foreach ($g['lines'] as $l) {
                $lines[] = ['code' => $l['code'], 'title' => $l['title'], 'qty' => $l['qty'], 'unit' => $l['unit_fa'],
                    'unit_price_toman' => $l['unit_price'] ?? 0, 'total_toman' => $l['total'], 'estimated_days' => 0];
            }
        }
        return array_merge($q, [
            'number' => 'SPAD-RS-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'lines' => $lines,
            'total_toman' => $q['total'],
            'estimated_days' => $q['days'],
            'unpriced_items' => $q['pricing_pending'],
            'pricing_pending' => $q['pricing_pending'] > 0,
        ]);
    }

    private function present($p): array
    {
        $state = json_decode((string) ($p->studio_state ?? ''), true) ?: null;
        return [
            'id' => (int) $p->id, 'title' => $p->title, 'tour_id' => $p->tour_id ? (int) $p->tour_id : null,
            'advertisement_id' => $p->advertisement_id ? (int) $p->advertisement_id : null,
            'status' => $p->status, 'state' => $state, 'quote' => json_decode((string) $p->invoice, true),
            'estimated_total_toman' => $p->estimated_total_toman !== null ? (int) $p->estimated_total_toman : null,
            'estimated_days' => $p->estimated_days !== null ? (int) $p->estimated_days : null,
            'admin_quote_toman' => $p->admin_quote_toman !== null ? (int) $p->admin_quote_toman : null,
            'admin_note' => $p->admin_note, 'created_at' => $p->created_at, 'updated_at' => $p->updated_at,
        ];
    }

    public function mine(Request $r)
    {
        $this->ready();
        $rows = DB::table('spad_renovation_projects')->where('user_id', $this->uid($r))->where('source_type', 'studio')
            ->orderByDesc('id')->limit(50)->get();
        return response()->json(['data' => $rows->map(fn($p) => $this->present($p))->values()]);
    }

    public function show(Request $r, $id)
    {
        $this->ready();
        $p = DB::table('spad_renovation_projects')->where('id', (int) $id)->where('user_id', $this->uid($r))
            ->where('source_type', 'studio')->first();
        abort_unless($p, 404, 'طرح یافت نشد.');
        return response()->json(['data' => $this->present($p)]);
    }

    public function save(Request $r, $id = null)
    {
        $this->ready();
        $uid = $this->uid($r);
        $d = $r->validate([
            'title' => 'required|string|max:150',
            'tour_id' => 'required|integer',
            'state' => 'required|array',
        ]);
        $items = $this->validatedItems($r);
        $state = json_encode($d['state'], JSON_UNESCAPED_UNICODE);
        abort_if(strlen($state) > 400000, 422, 'طرح بیش از حد بزرگ است.');
        $tour = VirtualTour::query()->where('id', (int) $d['tour_id'])->where('status', 'published')->first();
        abort_unless($tour, 404, 'تور یافت نشد.');

        $quote = $this->buildQuote($items);
        $plan = array_map(fn($i) => ['code' => $i['product'], 'qty' => (float) $i['qty'], 'room' => (string) ($i['scene_name'] ?? '')], $items);
        $fields = [
            'title' => $d['title'], 'tour_id' => $tour->id, 'advertisement_id' => $tour->advertisement_id,
            'studio_state' => $state, 'plan' => json_encode($plan, JSON_UNESCAPED_UNICODE),
            'estimated_total_toman' => $quote['total'], 'estimated_days' => $quote['days'],
            'area' => (int) min(9999, max(1, round(array_sum(array_map(fn($i) => $i['target'] === 'floor' ? (float) $i['qty'] : 0, $items))))),
            'updated_at' => now(),
        ];
        if ($id) {
            $p = DB::table('spad_renovation_projects')->where('id', (int) $id)->where('user_id', $uid)
                ->where('source_type', 'studio')->first();
            abort_unless($p, 404, 'طرح یافت نشد.');
            abort_if($p->status === 'completed', 409, 'این طرح برای اجرا ثبت شده و دیگر قابل تغییر نیست.');
            $fields['invoice'] = json_encode($this->legacyInvoice($quote, (int) $p->id), JSON_UNESCAPED_UNICODE);
            DB::table('spad_renovation_projects')->where('id', $p->id)->update($fields);
            $pid = (int) $p->id;
        } else {
            abort_if(DB::table('spad_renovation_projects')->where('user_id', $uid)->where('source_type', 'studio')
                ->where('created_at', '>', now()->subDay())->count() >= 30, 429, 'تعداد طرح‌های امروز شما به حد مجاز رسیده است.');
            $pid = (int) DB::table('spad_renovation_projects')->insertGetId(array_merge($fields, [
                'user_id' => $uid, 'source_type' => 'studio', 'status' => 'draft', 'design_style' => 'studio',
                'created_at' => now(),
            ]));
            DB::table('spad_renovation_projects')->where('id', $pid)
                ->update(['invoice' => json_encode($this->legacyInvoice($quote, $pid), JSON_UNESCAPED_UNICODE)]);
        }
        return response()->json(['data' => $this->present(DB::table('spad_renovation_projects')->find($pid))], $id ? 200 : 201);
    }

    public function submit(Request $r, $id)
    {
        $this->ready();
        $p = DB::table('spad_renovation_projects')->where('id', (int) $id)->where('user_id', $this->uid($r))
            ->where('source_type', 'studio')->first();
        abort_unless($p, 404, 'طرح یافت نشد.');
        abort_unless((int) $p->estimated_total_toman > 0, 422, 'برای ثبت درخواست اجرا، دست‌کم یک تغییر انتخاب کنید.');
        if ($p->status !== 'completed') {
            DB::table('spad_renovation_projects')->where('id', $p->id)->update(['status' => 'completed', 'updated_at' => now()]);
        }
        return response()->json(['data' => $this->present(DB::table('spad_renovation_projects')->find($p->id)),
            'message' => 'درخواست اجرای بازسازی ثبت شد؛ کارشناسان اسپاد برای بازدید و قیمت نهایی با شما تماس می‌گیرند.']);
    }
}
