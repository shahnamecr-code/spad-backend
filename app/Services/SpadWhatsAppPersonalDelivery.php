<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Optional delivery to the linked personal WhatsApp account's OWN chat only.
 * Completely separate from Meta Groups API and disabled until Super Admin
 * both scans the QR code and explicitly enables scheduled self-delivery.
 */
class SpadWhatsAppPersonalDelivery
{
    private const ZONE = 'Asia/Tehran';
    // The daily cap follows the configured schedule (hourly mode needs more
    // than the original 12), but never exceeds a hard safety ceiling.
    private const MIN_PER_DAY = 12;
    private const HARD_MAX_PER_DAY = 150;
    // Room for manual test / "send now" batches on top of the scheduled ones.
    private const MANUAL_ALLOWANCE = 15;
    // A scheduler tick may send several queued posters, spaced for the bridge
    // (it rejects a send within 45s of the previous one).
    private const MAX_SENDS_PER_TICK = 4;
    private const SEND_GAP_SECONDS = 50;
    // A queued poster older than this is no longer "this hour's" report.
    private const PENDING_TTL_MINUTES = 50;

    public function readyForSettings(): bool
    {
        return Schema::hasTable('spad_wa_personal_config')
            && Schema::hasTable('spad_wa_personal_jobs');
    }

    public function settings(): array
    {
        $row = $this->readyForSettings()
            ? DB::table('spad_wa_personal_config')->where('id', 1)->first()
            : null;
        return [
            'enabled' => $row ? (bool) $row->enabled : false,
            'schema_ready' => $this->readyForSettings(),
            'uses_market_schedule' => true,
            'max_messages_per_day' => $this->dailyLimit(),
            'city_pages_per_session' => 1,
        ];
    }

    /**
     * Expected scheduled images per day for the saved schedule, plus a small
     * manual allowance, bounded to [MIN_PER_DAY, HARD_MAX_PER_DAY].
     */
    public function dailyLimit(?array $marketSettings = null): int
    {
        $settings = $marketSettings ?? app(SpadWhatsAppMarketPublisher::class)->settings();
        $schedule = array_merge(SpadWhatsAppMarketPublisher::DEFAULT_SCHEDULE,
            (array) ($settings['schedule'] ?? []));
        $cityCount = max(1, count((array) ($settings['cities'] ?? [])));
        $hours = SpadWhatsAppMarketPublisher::hourlySlotsPerDay($schedule);
        $market = count((array) $schedule['markets']) + (!empty($schedule['hourly_markets']) ? $hours : 0);
        $city = (count((array) $schedule['cities']) + (!empty($schedule['hourly_cities']) ? $hours : 0)) * $cityCount;
        return max(self::MIN_PER_DAY,
            min(self::HARD_MAX_PER_DAY, $market + $city + self::MANUAL_ALLOWANCE));
    }

    public function setEnabled(bool $enabled): array
    {
        if (!$this->readyForSettings()) {
            throw new RuntimeException('ساختار داده‌های واتساپ شخصی هنوز نصب نشده است.', 503);
        }
        if ($enabled && !app(SpadWhatsAppPersonalBridge::class)->status()['ready']) {
            throw new RuntimeException('ابتدا واتساپ شخصی را با QR متصل کن؛ سپس زمان‌بندی را فعال کن.', 422);
        }
        DB::table('spad_wa_personal_config')->updateOrInsert(
            ['id' => 1],
            ['enabled' => $enabled, 'updated_at' => now(), 'created_at' => now()]
        );
        return $this->settings();
    }

    public function recentJobs(): array
    {
        if (!$this->readyForSettings()) return [];
        return DB::table('spad_wa_personal_jobs')->orderByDesc('id')->limit(25)
            ->get(['id','kind','subject','status','error_code','created_at'])
            ->map(function ($job) { return (array) $job; })->all();
    }

    private function caption(string $kind, ?string $city): string
    {
        if ($kind === 'market') {
            return 'نرخ لحظه‌ای ارز و طلا و سکه | اسپاد' . "\n"
                . 'https://spad.estate';
        }
        return 'نرخ لحظه‌ای قیمت ملک (' . $city . ') | اسپاد' . "\n"
            . 'https://spad.estate';
    }

    private function sendJob(int $id, string $kind, ?string $province, ?string $city, bool $fresh): array
    {
        $publisher = app(SpadWhatsAppMarketPublisher::class);
        try {
            // The PM2 preview worker keeps genuinely fresh PNGs (FX <=15 min,
            // cities <=4 h). Use them first; render in this CLI only if absent.
            $cached = $fresh ? app(SpadWhatsAppPreviewCache::class)->get($kind, $province, $city) : null;
            $image = $fresh
                ? ($cached ?? $publisher->buildPreviewFresh($kind, $province, $city))
                : $publisher->preview($kind, $province, $city);
            $result = app(SpadWhatsAppPersonalBridge::class)->sendSelf(
                $image, $this->caption($kind, $city)
            );
            DB::table('spad_wa_personal_jobs')->where('id', $id)->update([
                'status' => 'accepted_local',
                'message_id' => mb_substr($result['message_id'] ?? '', 0, 175),
                'updated_at' => now(),
            ]);
            return ['accepted' => true, 'status' => 'accepted_local'];
        } catch (Throwable $e) {
            // No blind retries: a send may have gone through even if its ack
            // was lost. Never risk sending the same image multiple times.
            DB::table('spad_wa_personal_jobs')->where('id', $id)->update([
                'status' => 'failed',
                'error_code' => str_contains($e->getMessage(), 'داده تازه')
                    ? 'NO_FRESH_PRICE' : 'BRIDGE_OR_PROVIDER_ERROR',
                'updated_at' => now(),
            ]);
            Log::warning('SPAD personal WhatsApp delivery failed', [
                'kind' => $kind, 'error_type' => get_class($e), 'reason' => $this->reason($e),
            ]);
            throw new RuntimeException('ارسال آزمایشی به واتساپ شخصی تأیید نشد؛ اتصال و گزارش را بررسی کن.', 503, $e);
        }
    }

    public function sendManual(string $kind, ?string $province, ?string $city): array
    {
        if (!$this->readyForSettings()) {
            throw new RuntimeException('ماژول واتساپ شخصی هنوز نصب نشده است.', 503);
        }
        if ($kind !== 'market' && ($kind !== 'city' || !$province || !$city)) {
            throw new RuntimeException('برای بنر آپارتمان، استان و شهر الزامی است.', 422);
        }
        if (!app(SpadWhatsAppPersonalBridge::class)->status()['ready']) {
            throw new RuntimeException('واتساپ هنوز متصل نشده است؛ QR را در پنل اسکن کن.', 422);
        }
        $id = DB::table('spad_wa_personal_jobs')->insertGetId([
            'slot_key' => 'self:manual:' . Str::uuid(),
            'kind' => $kind,
            'subject' => $kind === 'market' ? 'ارز، طلا و سکه' : 'آپارتمان ' . $city,
            'province' => $province, 'city' => $city,
            'status' => 'sending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->sendJob((int) $id, $kind, $province, $city, false);
    }

    /**
     * "Send now": queue the gold/FX banner and every saved city's property
     * banner outside the schedule. The first one whose fresh poster is
     * already cached goes out immediately; the scheduler sends the rest
     * within a few minutes, spaced for the bridge.
     */
    public function sendNowAll(): array
    {
        if (!$this->readyForSettings()) {
            throw new RuntimeException('ماژول واتساپ شخصی هنوز نصب نشده است.', 503);
        }
        if (!app(SpadWhatsAppPersonalBridge::class)->status()['ready']) {
            throw new RuntimeException('واتساپ هنوز متصل نشده است؛ ابتدا QR را در پنل اسکن کن.', 422);
        }
        $settings = app(SpadWhatsAppMarketPublisher::class)->settings();
        $items = [['kind' => 'market', 'province' => null, 'city' => null]];
        foreach (($settings['cities'] ?? []) as $place) {
            $p = trim((string) ($place['province'] ?? ''));
            $c = trim((string) ($place['city'] ?? ''));
            if ($p !== '' && $c !== '') $items[] = ['kind' => 'city', 'province' => $p, 'city' => $c];
        }
        $items = array_slice($items, 0, 10);
        $batch = (string) Str::uuid();
        $jobs = [];
        foreach ($items as $i => $item) {
            $id = DB::table('spad_wa_personal_jobs')->insertGetId([
                'slot_key' => 'self:now:' . $batch . ':' . $i,
                'kind' => $item['kind'],
                'subject' => $item['kind'] === 'market' ? 'ارز، طلا و سکه' : 'آپارتمان ' . $item['city'],
                'province' => $item['province'], 'city' => $item['city'],
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $jobs[] = ['id' => (int) $id] + $item;
        }
        $sentNow = null;
        if (!$this->recentlySent()) {
            $cache = app(SpadWhatsAppPreviewCache::class);
            foreach ($jobs as $job) {
                $png = $cache->get($job['kind'], $job['province'], $job['city']);
                if ($png === null) continue; // scheduler renders it fresh
                if (!$this->claim($job['id'])) break;
                try {
                    $result = app(SpadWhatsAppPersonalBridge::class)->sendSelf(
                        $png, $this->caption($job['kind'], $job['city']));
                    DB::table('spad_wa_personal_jobs')->where('id', $job['id'])->update([
                        'status' => 'accepted_local',
                        'message_id' => mb_substr($result['message_id'] ?? '', 0, 175),
                        'updated_at' => now(),
                    ]);
                    $sentNow = $job['kind'] === 'market' ? 'ارز، طلا و سکه' : 'آپارتمان ' . $job['city'];
                } catch (Throwable $e) {
                    // The send may or may not have happened; never resend blindly.
                    DB::table('spad_wa_personal_jobs')->where('id', $job['id'])->update([
                        'status' => 'failed', 'error_code' => 'BRIDGE_OR_PROVIDER_ERROR', 'updated_at' => now(),
                    ]);
                    Log::warning('SPAD personal send-now first poster failed', [
                        'type' => get_class($e), 'reason' => $this->reason($e),
                    ]);
                }
                break;
            }
        }
        return ['queued' => count($jobs), 'sent_now' => $sentNow,
            'remaining' => count($jobs) - ($sentNow ? 1 : 0)];
    }

    /** Innermost error text (bridge / WhatsApp Web reason), short; never contains secrets. */
    private function reason(Throwable $e): string
    {
        while ($e->getPrevious()) $e = $e->getPrevious();
        return mb_substr(preg_replace('/\d{6,}/', '<n>', $e->getMessage()), 0, 220);
    }

    /** Atomically take a pending job so the panel and the scheduler never both send it. */
    private function claim(int $id): bool
    {
        return DB::table('spad_wa_personal_jobs')->where('id', $id)->where('status', 'pending')
            ->update(['status' => 'sending', 'updated_at' => now()]) === 1;
    }

    private function recentlySent(): bool
    {
        return DB::table('spad_wa_personal_jobs')->where('status', 'accepted_local')
            ->where('updated_at', '>', now()->subSeconds(self::SEND_GAP_SECONDS))->exists();
    }

    public function runScheduled(): array
    {
        if (!$this->readyForSettings()) {
            return ['status' => 'disabled'];
        }
        if (!app(SpadWhatsAppPersonalBridge::class)->status()['ready']) {
            return ['status' => 'not_connected'];
        }
        // "Send now" batches are delivered even when the hourly schedule is
        // off; only queuing NEW scheduled posters requires it to be enabled.
        if (empty($this->settings()['enabled'])) {
            return $this->drainPending('disabled_schedule');
        }
        $now = Carbon::now(self::ZONE);
        $today = $now->format('Y-m-d');
        $clock = $now->format('H:i');
        $settings = app(SpadWhatsAppMarketPublisher::class)->settings();
        $limit = $this->dailyLimit($settings);
        $existing = DB::table('spad_wa_personal_jobs')
            ->whereDate('created_at', Carbon::now()->toDateString())->count();
        if ($existing >= $limit) {
            return $this->drainPending('daily_limit');
        }
        $schedule = $settings['schedule'] ?? SpadWhatsAppMarketPublisher::DEFAULT_SCHEDULE;
        $items = [];
        if (SpadWhatsAppMarketPublisher::isDue($schedule, 'market', $now)) {
            $items[] = ['kind'=>'market','province'=>null,'city'=>null];
        }
        if (SpadWhatsAppMarketPublisher::isDue($schedule, 'city', $now)) {
            foreach (($settings['cities'] ?? SpadWhatsAppMarketPublisher::DEFAULT_CITIES) as $city) {
                $p = trim((string) ($city['province'] ?? ''));
                $c = trim((string) ($city['city'] ?? ''));
                if ($p && $c) $items[]=['kind'=>'city','province'=>$p,'city'=>$c];
            }
        }
        // Queue only at the requested scheduled minute. One outbound image
        // per cron tick avoids flooding the user's own chat.
        foreach ($items as $item) {
            if ($existing >= $limit) break;
            $key = 'self:' . $today . ':' . $clock . ':' . $item['kind'] . ':' .
                sha1(($item['province'] ?? '') . '|' . ($item['city'] ?? ''));
            $inserted = DB::table('spad_wa_personal_jobs')->insertOrIgnore([
                'slot_key' => $key,
                'kind' => $item['kind'],
                'subject' => $item['kind'] === 'market' ? 'ارز، طلا و سکه' : 'آپارتمان ' . $item['city'],
                'province' => $item['province'], 'city' => $item['city'],
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $existing += $inserted ? 1 : 0;
        }
        return $this->drainPending('scheduled');
    }

    /**
     * Send up to MAX_SENDS_PER_TICK queued posters, oldest first, at least
     * SEND_GAP_SECONDS apart (the host cron runs every five minutes, so one
     * per tick would spread an hour's three posters over ten minutes).
     */
    private function drainPending(string $mode): array
    {
        // If the scheduler or bridge stalled, do not flush a backlog of
        // earlier hours into the chat later; the next hour sends fresh ones.
        DB::table('spad_wa_personal_jobs')->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(self::PENDING_TTL_MINUTES))
            ->update(['status' => 'expired', 'updated_at' => now()]);
        $sent = 0; $failed = 0;
        for ($i = 0; $i < self::MAX_SENDS_PER_TICK; $i++) {
            $next = DB::table('spad_wa_personal_jobs')
                ->where('status', 'pending')->orderBy('id')->first();
            if (!$next) break;
            $last = DB::table('spad_wa_personal_jobs')->where('status', 'accepted_local')
                ->max('updated_at');
            $since = $last ? now()->diffInSeconds(Carbon::parse($last), true) : PHP_INT_MAX;
            if ($since < self::SEND_GAP_SECONDS) sleep(self::SEND_GAP_SECONDS - $since + 1);
            if (!$this->claim((int) $next->id)) continue;
            try {
                $this->sendJob((int) $next->id, (string) $next->kind, $next->province, $next->city, true);
                $sent++;
            } catch (Throwable $e) {
                $failed++; // marked failed inside sendJob; never retried
            }
        }
        return ['status' => $sent || $failed ? 'processed' : 'no_pending',
            'mode' => $mode, 'sent' => $sent, 'failed' => $failed];
    }
}
