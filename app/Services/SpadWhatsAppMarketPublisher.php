<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SpadWhatsAppMarketPublisher
{
    const ZONE = 'Asia/Tehran';
    const DEFAULT_SCHEDULE = ['markets' => ['10:00', '18:00'], 'cities' => ['11:00'], 'hourly_markets' => false];
    const DEFAULT_CITIES = [
        ['province' => 'تهران', 'city' => 'تهران'],
        ['province' => 'فارس', 'city' => 'شیراز'],
    ];

    public function settings(): array
    {
        $row = $this->configRow();
        return [
            'enabled' => $row ? (bool) $row->enabled : false,
            'schedule' => $this->decode($row->schedule_json ?? null, self::DEFAULT_SCHEDULE),
            'cities' => $this->decode($row->cities_json ?? null, self::DEFAULT_CITIES),
            'groups' => $this->decode($row->groups_json ?? null, []),
            'phone_number_id' => (string) ($row->phone_number_id ?? ''),
            'token_configured' => $row && !empty($row->access_token_encrypted),
            'render_available' => class_exists(\Imagick::class),
            'transport' => 'official_meta_groups_only',
            'channel_supported' => false,
        ];
    }

    public function save(array $data): void
    {
        $previous = $this->configRow();
        $token = $previous->access_token_encrypted ?? null;
        if (!empty($data['clear_token'])) $token = null;
        if (isset($data['access_token']) && trim($data['access_token']) !== '') {
            $token = Crypt::encryptString(trim($data['access_token']));
        }
        $schedule = $data['schedule'];
        $cities = $data['cities'];
        $groups = array_values(array_unique($data['groups']));
        if ($data['enabled'] && (!$token || empty($data['phone_number_id']) || !$groups)) {
            throw new RuntimeException('برای فعال‌سازی، کلید متا، شناسه شماره تجاری و گروه‌های مجاز ضروری‌اند.', 422);
        }
        if ($data['enabled'] && empty($data['official_groups_acknowledged'])) {
            throw new RuntimeException('ابتدا شرایط گروه‌های رسمی Meta را تأیید کنید.', 422);
        }
        $values = [
            'enabled' => (bool) $data['enabled'],
            'schedule_json' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
            'cities_json' => json_encode($cities, JSON_UNESCAPED_UNICODE),
            'groups_json' => json_encode($groups, JSON_UNESCAPED_UNICODE),
            'access_token_encrypted' => $token,
            'phone_number_id' => trim($data['phone_number_id'] ?? ''),
            'updated_at' => now(),
        ];
        if ($previous) {
            DB::table('spad_wa_market_config')->where('id', 1)->update($values);
        } else {
            DB::table('spad_wa_market_config')->insert(['id' => 1, 'created_at' => now()] + $values);
        }
    }

    public function logs(): array
    {
        return DB::table('spad_wa_market_publications')->orderByDesc('id')->limit(40)
            ->get(['id','slot_key','kind','subject','status','poster_path','error_message',
                'delivery_json','created_at','updated_at'])
            ->map(function ($r) {
                $r->delivery_json = $this->decode($r->delivery_json, []);
                // Never expose access token or other credentials in logs.
                $r->poster_url = $r->poster_path ? Storage::disk('public')->url($r->poster_path) : null;
                return $r;
            })->all();
    }

    public function run(bool $dryRun = false): array
    {
        if (!Schema::hasTable('spad_wa_market_config') || !Schema::hasTable('spad_wa_market_publications')) {
            return ['status' => 'not_migrated', 'scheduled' => []];
        }
        $settings = $this->settings();
        if (!$settings['enabled']) return ['status' => 'disabled', 'scheduled' => []];
        $minute = Carbon::now(self::ZONE)->format('H:i');
        $day = Carbon::now(self::ZONE)->format('Y-m-d');
        $jobs = [];
        if (in_array($minute, $settings['schedule']['markets'] ?? [], true)
            || (!empty($settings['schedule']['hourly_markets']) && substr($minute, 3, 2) === '00')) {
            $jobs[] = ['key' => $day . ':market:' . $minute, 'kind' => 'market',
                'province' => null, 'city' => null, 'time' => $minute];
        }
        if (in_array($minute, $settings['schedule']['cities'] ?? [], true)) {
            foreach ($settings['cities'] as $place) {
                $jobs[] = [
                    'key' => $day . ':city:' . $minute . ':' . sha1($place['province'] . '|' . $place['city']),
                    'kind' => 'city', 'province' => $place['province'],
                    'city' => $place['city'], 'time' => $minute,
                ];
            }
        }
        if ($dryRun) return ['status' => 'dry_run', 'scheduled' => $jobs];
        $result = ['status' => 'processed', 'scheduled' => count($jobs), 'new' => 0, 'errors' => []];
        foreach ($jobs as $job) {
            try {
                $result['new'] += $this->processJob($job, $settings) ? 1 : 0;
            } catch (Throwable $e) {
                $result['errors'][] = $job['key'] . ': ' . $e->getMessage();
                Log::warning('SPAD WhatsApp market publication failed', [
                    'slot' => $job['key'], 'error_type' => get_class($e),
                ]);
            }
        }
        return $result;
    }

    public function preview(string $kind, ?string $province = null, ?string $city = null): string
    {
        $pages = $this->posterPages($kind, $province, $city);
        return $this->svg($pages[0], 1, count($pages));
    }

    public function runNow(string $kind, ?string $province, ?string $city): array
    {
        $settings = $this->settings();
        if (!$settings['enabled']) throw new RuntimeException('انتشار واتساپ در تنظیمات غیرفعال است.', 422);
        // Explicit manual send, unique at minute precision; never blindly retry a partially sent poster.
        $stamp = Carbon::now(self::ZONE);
        $job = [
            'key' => $stamp->format('Y-m-d:H:i') . ':manual:' . $kind . ':' . sha1(($province ?? '') . '|' . ($city ?? '')),
            'kind' => $kind, 'province' => $province, 'city' => $city,
        ];
        return ['created' => $this->processJob($job, $settings), 'slot_key' => $job['key']];
    }

    private function processJob(array $job, array $settings): bool
    {
        $pages = $this->posterPages($job['kind'], $job['province'], $job['city']);
        foreach ($pages as $i => $page) {
            $slot = $job['key'] . ':p' . ($i + 1);
            $inserted = DB::table('spad_wa_market_publications')->insertOrIgnore([
                'slot_key' => $slot, 'kind' => $job['kind'], 'subject' => $page['title'],
                'status' => 'generating', 'created_at' => now(), 'updated_at' => now(),
            ]);
            // Unique key is a cross-worker idempotency fence.
            if (!$inserted) continue;
            $id = DB::table('spad_wa_market_publications')->where('slot_key', $slot)->value('id');
            try {
                $svg = $this->svg($page, $i + 1, count($pages));
                $png = $this->renderPng($svg);
                $path = 'whatsapp-market/' . Carbon::now(self::ZONE)->format('Y/m/d') . '/' . sha1($slot) . '.png';
                if (!Storage::disk('public')->put($path, $png)) {
                    throw new RuntimeException('ذخیره تصویر بنر در فضای عمومی انجام نشد.');
                }
                DB::table('spad_wa_market_publications')->where('id', $id)->update([
                    'status' => 'sending', 'poster_path' => $path,
                    'meta_json' => json_encode(['source' => $page['source'],
                        'updated_at' => $page['updated_at'], 'rows' => count($page['rows'])],
                        JSON_UNESCAPED_UNICODE), 'updated_at' => now(),
                ]);
                $url = url(Storage::disk('public')->url($path));
                if (strpos($url, 'https://') !== 0) {
                    throw new RuntimeException('آدرس عمومی تصویر باید HTTPS باشد؛ ارسال لغو شد.');
                }
                $delivery = [];
                foreach ($settings['groups'] as $group) {
                    try {
                        $messageId = $this->sendGroup($group, $url, $page['caption'], $settings);
                        $delivery[] = ['group' => substr($group, 0, 5) . '…',
                            'status' => 'accepted_by_meta', 'message_id' => $messageId];
                    } catch (Throwable $sendError) {
                        $delivery[] = ['group' => substr($group, 0, 5) . '…',
                            'status' => 'failed', 'reason' => 'Meta did not accept the message'];
                    }
                }
                $accepted = count(array_filter($delivery, function ($item) {
                    return $item['status'] === 'accepted_by_meta';
                }));
                DB::table('spad_wa_market_publications')->where('id', $id)->update([
                    'status' => $accepted === count($delivery) ? 'accepted' : ($accepted ? 'partial' : 'failed'),
                    'delivery_json' => json_encode($delivery, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            } catch (Throwable $e) {
                DB::table('spad_wa_market_publications')->where('id', $id)->update([
                    'status' => 'failed', 'error_message' => mb_substr($e->getMessage(), 0, 400),
                    'updated_at' => now(),
                ]);
                throw $e;
            }
        }
        return true;
    }

    private function posterPages(string $kind, ?string $province, ?string $city): array
    {
        if ($kind === 'market') {
            $data = app(MarketPriceService::class)->getTickerData();
            $fresh = [];
            foreach ($data['items'] ?? [] as $quote) {
                $timestamp = (int) ($quote['timestamp'] ?? 0);
                $value = (float) ($quote['value'] ?? 0);
                if (!$timestamp || $value <= 0 || time() - $timestamp > 7200 || $timestamp > time() + 300) continue;
                $key = $quote['key'] ?? '';
                $coin = in_array($key, ['new_coin','old_coin','half_coin','quarter_coin'], true);
                $ounce = $key === 'gold_ounce';
                $fresh[] = ['name' => (string) $quote['label'],
                    'value' => ($coin ? $value * 1000 : $value),
                    'unit' => $ounce ? 'دلار' : 'تومان', 'timestamp' => $timestamp];
            }
            if (!$fresh) throw new RuntimeException('نرخ ارز و طلا تازه و معتبر نیست؛ بنر منتشر نشد.', 503);
            $last = max(array_column($fresh, 'timestamp'));
            return [[
                'title' => 'نبض بازار ارز، طلا و سکه',
                'caption' => 'نرخ‌های بازار ارز و طلا | دستیار تمام هوشمند ملکی اسپاد | https://spad.estate',
                'source' => 'Iran Market Data', 'updated_at' => date('c', $last),
                'rows' => array_slice($fresh, 0, 15),
                'disclaimer' => 'نرخ‌ها اطلاع‌رسانی بازار هستند و ممکن است با قیمت معامله متفاوت باشند.',
            ]];
        }
        if ($kind !== 'city' || !$province || !$city) {
            throw new RuntimeException('نام استان و شهر برای بنر ملکی ضروری است.', 422);
        }
        $market = app(NemudaPropertyMarket::class);
        $place = $market->matchCity($province, $city);
        if (!$place) throw new RuntimeException('شناسه معتبر برای شهر ' . $city . ' در داده نمودا پیدا نشد.', 422);
        $children = $market->children((int) $place['id']);
        $rows = [];
        foreach (($children['places'] ?? []) as $item) {
            $value = (float) ($item['price_per_m2'] ?? 0);
            if ($value <= 0 || ($item['coverage'] ?? '') === 'none') continue;
            $rows[] = [
                'name' => trim((string) $item['name']),
                'value' => $value, 'unit' => 'تومان / مترمربع',
                'timestamp' => null,
            ];
        }
        if (!$rows) throw new RuntimeException('قیمت واقعی محله‌های ' . $city . ' موجود نیست؛ بنر منتشر نشد.', 503);
        usort($rows, function ($a, $b) { return $b['value'] <=> $a['value']; });
        $pages = [];
        foreach (array_chunk($rows, 12) as $chunk) {
            $pages[] = [
                'title' => 'گزارش قیمت محله‌های ' . $city,
                'caption' => 'گزارش قیمت پیشنهادی هر مترمربع ملک در ' . $city .
                    ' | منبع: نمودا (CC BY 4.0) | دستیار تمام هوشمند ملکی اسپاد | https://spad.estate',
                'source' => 'نمودا · nemuda.com/data · CC BY 4.0',
                'updated_at' => $children['updated_at'] ?? null,
                'rows' => $chunk,
                'disclaimer' => 'برآورد قیمت پیشنهادی بازار؛ نرخ معامله یا کارشناسی قطعی ملک نیست.',
            ];
        }
        return $pages;
    }

    private function svg(array $data, int $page, int $total): string
    {
        $esc = function ($str) { return htmlspecialchars((string) $str, ENT_QUOTES | ENT_XML1, 'UTF-8'); };
        $rows = '';
        foreach ($data['rows'] as $i => $row) {
            $y = 326 + $i * 69;
            $shade = $i % 2 ? '#122b49' : '#173657';
            $number = number_format(round($row['value']));
            $rows .= '<rect x="76" y="' . ($y - 37) . '" width="928" height="61" rx="13" fill="' . $shade . '"/>' .
                '<text x="965" y="' . $y . '" text-anchor="end" font-size="26" fill="#f5fbff" direction="rtl">' . $esc($row['name']) . '</text>' .
                '<text x="125" y="' . $y . '" text-anchor="start" font-size="27" fill="#95d9ff">' . $esc($number) . '</text>' .
                '<text x="365" y="' . $y . '" text-anchor="end" font-size="16" fill="#b9d4e3" direction="rtl">' . $esc($row['unit']) . '</text>';
        }
        $height = max(820, 445 + count($data['rows']) * 69);
        $date = $data['updated_at'] ? $esc((string) $data['updated_at']) : 'زمان به‌روزرسانی منبع اعلام نشده';
        $disclaimer = $esc($data['disclaimer']);
        $title = $esc($data['title']);
        $source = $esc($data['source']);
        $pageText = $total > 1 ? " · صفحه $page از $total" : '';
        return '<svg xmlns="http://www.w3.org/2000/svg" width="1080" height="' . $height . '" viewBox="0 0 1080 ' . $height . '">' .
            '<defs><linearGradient id="bg" x2="1" y2="1"><stop stop-color="#071a34"/><stop offset="1" stop-color="#073a62"/></linearGradient></defs>' .
            '<rect width="1080" height="' . $height . '" fill="url(#bg)"/>' .
            '<rect x="30" y="30" width="1020" height="' . ($height - 60) . '" rx="32" fill="none" stroke="#337aa3" stroke-width="2"/>' .
            '<g font-family="Vazirmatn, Noto Sans Arabic, DejaVu Sans, sans-serif">' .
            '<text x="952" y="114" text-anchor="end" font-size="38" font-weight="bold" fill="#ffffff" direction="rtl">دستیار تمام هوشمند ملکی اسپاد</text>' .
            '<text x="952" y="174" text-anchor="end" font-size="32" fill="#e0eff9" direction="rtl">' . $title . $esc($pageText) . '</text>' .
            '<path d="M 80 218 H 1000" stroke="#3aa8d7" stroke-width="3"/>' .
            '<text x="952" y="264" text-anchor="end" font-size="19" fill="#c0d9e8" direction="rtl">قیمت‌های ثبت‌شده · ' . $source . '</text>' .
            $rows .
            '<text x="960" y="' . ($height - 153) . '" text-anchor="end" font-size="18" fill="#bdd5e5" direction="rtl">زمان داده: ' . $date . '</text>' .
            '<text x="960" y="' . ($height - 108) . '" text-anchor="end" font-size="18" fill="#e7cfa1" direction="rtl">' . $disclaimer . '</text>' .
            '<rect x="73" y="' . ($height - 82) . '" width="934" height="54" rx="15" fill="#087db9"/>' .
            '<text x="540" y="' . ($height - 46) . '" text-anchor="middle" font-size="29" fill="#fff">SPAD.ESTATE</text>' .
            '</g></svg>';
    }

    private function renderPng(string $svg): string
    {
        if (!class_exists(\Imagick::class)) {
            throw new RuntimeException('موتور ساخت PNG (Imagick/SVG) روی سرور نصب نیست؛ هیچ تصویری ارسال نشد.');
        }
        try {
            $img = new \Imagick();
            $img->setBackgroundColor('transparent');
            $img->readImageBlob($svg);
            $img->setImageFormat('png');
            $blob = $img->getImagesBlob();
            $img->clear();
            if (strlen($blob) < 3000) throw new RuntimeException('PNG خروجی غیرمعتبر یا ناقص است.');
            return $blob;
        } catch (Throwable $e) {
            throw new RuntimeException('رندر بنر فارسی به PNG انجام نشد؛ ارسال متوقف شد.');
        }
    }

    private function sendGroup(string $groupId, string $url, string $caption, array $settings): string
    {
        $row = $this->configRow();
        if (!$row || !$row->access_token_encrypted || !$settings['phone_number_id']) {
            throw new RuntimeException('دسترسی رسمی متا تنظیم نشده است.', 422);
        }
        $token = Crypt::decryptString($row->access_token_encrypted);
        $response = Http::withToken($token)->acceptJson()->timeout(20)
            ->post('https://graph.facebook.com/v24.0/' . rawurlencode($settings['phone_number_id']) . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'group',
                'to' => $groupId,
                'type' => 'image',
                'image' => ['link' => $url, 'caption' => mb_substr($caption, 0, 1024)],
            ]);
        if (!$response->successful() || !$response->json('messages.0.id')) {
            throw new RuntimeException('درخواست ارسال گروهی توسط Meta پذیرفته نشد.');
        }
        return (string) $response->json('messages.0.id');
    }

    private function configRow()
    {
        if (!Schema::hasTable('spad_wa_market_config')) return null;
        return DB::table('spad_wa_market_config')->where('id', 1)->first();
    }

    private function decode($value, array $default): array
    {
        if (!is_string($value) || $value === '') return $default;
        $data = json_decode($value, true);
        return is_array($data) ? $data : $default;
    }
}
