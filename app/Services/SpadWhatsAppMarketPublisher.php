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
            'render_available' => app(SpadWhatsAppPosterRenderer::class)->available(),
            'poster_template' => 'approved_spad_artworks_v1',
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
        // Prefer CLI-generated fresh PNG; PHP-FPM must not need to launch Chrome
        // or call a remote price provider just to show a Super Admin a poster.
        $cache = app(SpadWhatsAppPreviewCache::class);
        $png = $cache->get($kind, $province, $city);
        if ($png !== null) return $png;

        // The first request still works where the web PHP process has the
        // necessary renderer permissions. The CLI warm job covers stricter hosts.
        $png = $this->buildPreviewFresh($kind, $province, $city);
        $cache->put($kind, $province, $city, $png);
        return $png;
    }

    /**
     * Only genuine fresh market data; a failure never produces invented prices.
     * The scheduled CLI calls this directly to bypass any existing cached PNG.
     */
    public function buildPreviewFresh(string $kind, ?string $province = null, ?string $city = null): string
    {
        $pages = $this->posterPages($kind, $province, $city);
        $poster = $pages[0];
        $poster['page'] = 1;
        $poster['total'] = count($pages);
        return app(SpadWhatsAppPosterRenderer::class)->render($poster);
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
                $page['page'] = $i + 1;
                $page['total'] = count($pages);
                $png = app(SpadWhatsAppPosterRenderer::class)->render($page);
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
            // The existing SPAD feed owns the monetary units and timestamps.
            // Never show a made-up price or reuse an old price from the artwork.
            $quotes = [];
            foreach ($data['items'] ?? [] as $quote) {
                $timestamp = (int) ($quote['timestamp'] ?? 0);
                $value = (float) ($quote['value'] ?? 0);
                $key = (string) ($quote['key'] ?? '');
                if (!$timestamp || $value <= 0 || time() - $timestamp > 7200
                    || $timestamp > time() + 300 || $key === '') continue;
                $factor = in_array($key, ['new_coin','old_coin','half_coin','quarter_coin'], true)
                    ? 1000 : 1;
                $quotes[$key] = [
                    'key' => $key, 'name' => (string) ($quote['label'] ?? $key),
                    'value' => $value * $factor,
                    'change' => is_numeric($quote['change'] ?? null)
                        ? (float) $quote['change'] * $factor : null,
                    'unit' => $key === 'gold_ounce' ? 'دلار' : 'تومان',
                    'timestamp' => $timestamp,
                ];
            }
            if (!isset($quotes['usd']) || !isset($quotes['gold_18'])) {
                throw new RuntimeException('داده تازه دلار و طلای ۱۸ عیار موجود نیست؛ بنر منتشر نشد.', 503);
            }
            $names = [
                'usd' => 'دلار آمریکا', 'eur' => 'یورو', 'aed' => 'درهم امارات',
                'gbp' => 'پوند انگلیس', 'try' => 'لیر ترکیه', 'usdt' => 'تتر',
                'gold_18' => 'طلای ۱۸ عیار', 'gold_24' => 'طلای ۲۴ عیار',
                'gold_ounce' => 'انس جهانی طلا', 'melted_gold' => 'طلای آبشده',
                'mesghal_gold' => 'مثقال طلا', 'new_coin' => 'سکه امامی',
                'old_coin' => 'سکه بهار آزادی', 'half_coin' => 'نیم سکه',
                'quarter_coin' => 'ربع سکه',
            ];
            $rows = [];
            foreach ($names as $key => $name) {
                // A placeholder explicitly says unavailable, and is never sent as a rate.
                $rows[] = isset($quotes[$key])
                    ? array_merge($quotes[$key], ['name' => $name])
                    : ['key' => $key, 'name' => $name, 'value' => null,
                       'change' => null, 'unit' => $key === 'gold_ounce' ? 'دلار' : 'تومان'];
            }
            $last = max(array_column($quotes, 'timestamp'));
            return [[
                'kind' => 'market',
                'title' => 'گزارش کامل قیمت ارز، طلا و سکه',
                'caption' => 'نرخ‌های معتبر بازار ارز، طلا و سکه | دستیار تمام هوشمند ملکی اسپاد | https://spad.estate',
                'source' => 'Iran Market Data',
                'updated_at' => date('c', $last),
                'rows' => $rows,
                'disclaimer' => 'نرخ‌های بازار ممکن است با نرخ نهایی معاملات متفاوت باشند.',
            ]];
        }
        if ($kind !== 'city' || !$province || !$city) {
            throw new RuntimeException('نام استان و شهر برای بنر ملکی ضروری است.', 422);
        }
        $market = app(NemudaPropertyMarket::class);
        $place = $market->matchCity($province, $city);
        if (!$place) {
            throw new RuntimeException('شناسه معتبر شهر در داده‌های نمودا پیدا نشد.', 422);
        }
        $children = $market->children((int) $place['id']);
        $rows = [];
        foreach ($children['places'] ?? [] as $item) {
            $value = (float) ($item['price_per_m2'] ?? 0);
            if ($value <= 0 || ($item['coverage'] ?? '') === 'none') continue;
            $rows[] = [
                'name' => trim((string) $item['name']), 'value' => $value,
                'unit' => 'تومان / مترمربع', 'timestamp' => null,
            ];
        }
        if (!$rows) {
            throw new RuntimeException('قیمت واقعی محله‌های شهر موجود نیست؛ بنر منتشر نشد.', 503);
        }
        usort($rows, function ($a, $b) { return $b['value'] <=> $a['value']; });
        $pages = [];
        foreach (array_chunk($rows, 12) as $chunk) {
            $pages[] = [
                'kind' => 'city', 'city' => $city, 'province' => $province,
                'title' => 'گزارش کامل قیمت آپارتمان ' . $city,
                'caption' => 'برآورد قیمت پیشنهادی هر مترمربع آپارتمان در محله‌های ' . $city .
                    ' | منبع: نمودا (CC BY 4.0) | دستیار تمام هوشمند ملکی اسپاد | https://spad.estate',
                'source' => 'نمودا · nemuda.com/data · CC BY 4.0',
                'updated_at' => $children['updated_at'] ?? null,
                'rows' => $chunk,
                'disclaimer' => 'برآورد قیمت پیشنهادی بازار؛ نرخ معامله یا کارشناسی قطعی ملک نیست.',
            ];
        }
        return $pages;
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
