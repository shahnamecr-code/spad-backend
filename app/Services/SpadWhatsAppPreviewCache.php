<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Private, short-lived copies of the exact approved poster PNGs.
 *
 * The scheduled CLI can run Chromium even when PHP-FPM restricts subprocess
 * execution or when upstream APIs are slow. An authenticated preview request
 * reads only a fresh cached image; stale quotes never become "live" posters.
 */
class SpadWhatsAppPreviewCache
{
    private const DIRECTORY = 'whatsapp-market/admin-preview/v1/';
    private const MARKET_MAX_AGE = 900; // 15 minutes; cache job runs every five
    private const CITY_MAX_AGE = 14400; // 4 hours for slower Nemuda aggregates

    private function path(string $kind, ?string $province, ?string $city): string
    {
        if (!in_array($kind, ['market', 'city'], true)) {
            throw new RuntimeException('نوع پیش‌نمایش واتساپ ناشناخته است.', 422);
        }
        return self::DIRECTORY . $kind . '-' .
            hash('sha256', $kind === 'market' ? 'all-prices' :
                trim((string) $province) . '|' . trim((string) $city)) . '.png';
    }

    public function get(string $kind, ?string $province, ?string $city): ?string
    {
        $path = $this->path($kind, $province, $city);
        $disk = Storage::disk('local');
        try {
            if (!$disk->exists($path)) return null;
            $modified = (int) $disk->lastModified($path);
            $age = time() - $modified;
            $max = $kind === 'market' ? self::MARKET_MAX_AGE : self::CITY_MAX_AGE;
            if ($age < 0 || $age > $max) return null;
            $png = $disk->get($path);
            return is_string($png) && strlen($png) > 40000
                && substr($png, 0, 8) === "\x89PNG\r\n\x1a\n" ? $png : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Request creation of a fresh banner by the CLI-only PM2 worker, never
     * by launching Chromium from PHP-FPM.
     *
     * Only Super Admin's guarded preview route calls this; filenames contain
     * hashes, not place names, and the queue stays on the private local disk.
     */
    public function requestRefresh(string $kind, ?string $province, ?string $city): void
    {
        $this->path($kind, $province, $city); // Validate type.
        $province = trim((string) $province);
        $city = trim((string) $city);
        if ($kind === 'city' &&
            ($province === '' || $city === '' || mb_strlen($province) > 80 ||
             mb_strlen($city) > 80 ||
             preg_match('/[\x00-\x1F\x7F]/u', $province . $city))) {
            throw new RuntimeException('نام شهر برای ساخت پیش‌نمایش معتبر نیست.', 422);
        }

        $disk = Storage::disk('local');
        $identifier = hash('sha256', $kind === 'market'
            ? 'market' : $province . '|' . $city);
        $path = 'whatsapp-market/admin-preview/requests/' . $identifier . '.json';

        try {
            // One file per poster. Repeated button presses don't create work
            // storms or continually reset the same pending request.
            if ($disk->exists($path) && time() - (int) $disk->lastModified($path) < 180) {
                return;
            }
            $payload = json_encode([
                'kind' => $kind,
                'province' => $kind === 'city' ? $province : null,
                'city' => $kind === 'city' ? $city : null,
                'requested_at' => time(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!$payload || !$disk->put($path, $payload)) {
                throw new RuntimeException('ثبت درخواست نوسازی پیش‌نمایش انجام نشد.');
            }
        } catch (Throwable $e) {
            throw new RuntimeException('صف ساخت امن پیش‌نمایش روی سرور در دسترس نیست.', 503, $e);
        }
    }

    public function put(string $kind, ?string $province, ?string $city, string $png): void
    {
        if (strlen($png) <= 40000 || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('تصویر تولیدشده معتبر نیست.');
        }
        if (!Storage::disk('local')->put($this->path($kind, $province, $city), $png)) {
            throw new RuntimeException('ثبت تصویر پیش‌نمایش در حافظه موقت انجام نشد.');
        }
    }
}
