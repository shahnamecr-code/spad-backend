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
    // Room for a few manual test sends on top of the scheduled ones.
    private const MANUAL_ALLOWANCE = 6;
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
            return 'گزارش به‌روزشده قیمت ارز، طلا و سکه | اسپاد' . "\n"
                . 'https://spad.estate';
        }
        return 'گزارش برآورد قیمت آپارتمان ' . $city
            . ' | منبع داده: نمودا، CC BY 4.0 | اسپاد' . "\n"
            . 'https://spad.estate';
    }

    private function sendJob(int $id, string $kind, ?string $province, ?string $city, bool $fresh): array
    {
        $publisher = app(SpadWhatsAppMarketPublisher::class);
        try {
            $image = $fresh
                ? $publisher->buildPreviewFresh($kind, $province, $city)
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
                'error_code' => 'BRIDGE_OR_PROVIDER_ERROR',
                'updated_at' => now(),
            ]);
            Log::warning('SPAD personal WhatsApp delivery failed', [
                'kind' => $kind, 'error_type' => get_class($e),
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
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->sendJob((int) $id, $kind, $province, $city, false);
    }

    public function runScheduled(): array
    {
        if (!$this->readyForSettings() || empty($this->settings()['enabled'])) {
            return ['status' => 'disabled'];
        }
        if (!app(SpadWhatsAppPersonalBridge::class)->status()['ready']) {
            return ['status' => 'not_connected'];
        }
        $now = Carbon::now(self::ZONE);
        $today = $now->format('Y-m-d');
        $clock = $now->format('H:i');
        $settings = app(SpadWhatsAppMarketPublisher::class)->settings();
        $limit = $this->dailyLimit($settings);
        $existing = DB::table('spad_wa_personal_jobs')
            ->whereDate('created_at', Carbon::now()->toDateString())->count();
        if ($existing >= $limit) {
            return ['status' => 'daily_limit'];
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
        // If the scheduler or bridge stalled, do not flush a backlog of
        // earlier hours into the chat later; the next hour sends fresh ones.
        DB::table('spad_wa_personal_jobs')->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(self::PENDING_TTL_MINUTES))
            ->update(['status' => 'expired', 'updated_at' => now()]);
        // Hourly mode queues several posters at HH:00. The bridge rejects a
        // send within 45s of the previous one, and failures are never retried,
        // so leave a gap here and let the next minute's tick send the next one.
        $justSent = DB::table('spad_wa_personal_jobs')->where('status', 'accepted_local')
            ->where('updated_at', '>', now()->subSeconds(50))->exists();
        if ($justSent) return ['status' => 'spacing'];
        $next = DB::table('spad_wa_personal_jobs')
            ->where('status', 'pending')->orderBy('id')->first();
        if (!$next) return ['status'=>'no_pending'];
        $result = $this->sendJob((int) $next->id, (string) $next->kind,
            $next->province, $next->city, true);
        return ['status' => $result['status'], 'kind' => $next->kind];
    }
}
