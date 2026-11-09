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
