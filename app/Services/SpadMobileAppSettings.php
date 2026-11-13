<?php

namespace App\Services;

use App\Models\SpadAppSetting;

/**
 * Settings of the mobile app. Values are stored as text and read back with
 * the type of their default, so a missing row always falls back safely.
 */
class SpadMobileAppSettings
{
    public const DEFAULTS = [
        'maintenance_enabled' => false,
        'maintenance_message' => 'اپلیکیشن اسپاد در حال به‌روزرسانی است. لطفاً کمی بعد دوباره مراجعه کنید.',
        'latest_version' => '',
        'min_version' => '',
        'update_url' => '',
        'update_message' => 'نسخه تازه اپلیکیشن اسپاد آماده است.',
        'announcement_enabled' => false,
        'announcement_text' => '',
        'announcement_link' => '',
        'show_market_ticker' => true,
        'show_intro' => true,
        'support_phone' => '',
        'notifications_enabled' => true,
        'notification_check_minutes' => 30,
    ];

    public static function all(): array
    {
        $stored = SpadAppSetting::query()->pluck('value', 'key')->all();
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = array_key_exists($key, $stored) && $stored[$key] !== null
                ? self::cast($stored[$key], $default)
                : $default;
        }
        return $settings;
    }

    public static function save(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            if (is_bool(self::DEFAULTS[$key])) {
                $value = $value ? '1' : '0';
            }
            SpadAppSetting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        return self::all();
    }

    /** What the app is allowed to read without signing in. */
    public static function publicConfig(): array
    {
        $s = self::all();
        return [
            'maintenance' => [
                'enabled' => $s['maintenance_enabled'],
                'message' => $s['maintenance_message'],
            ],
            'update' => [
                'latest_version' => $s['latest_version'],
                'min_version' => $s['min_version'],
                'url' => $s['update_url'],
                'message' => $s['update_message'],
            ],
            'announcement' => [
                'enabled' => $s['announcement_enabled'] && $s['announcement_text'] !== '',
                'text' => $s['announcement_text'],
                'link' => $s['announcement_link'],
            ],
            'features' => [
                'market_ticker' => $s['show_market_ticker'],
                'intro' => $s['show_intro'],
            ],
            'support_phone' => $s['support_phone'],
            'notifications' => [
                'enabled' => $s['notifications_enabled'],
                'check_minutes' => $s['notification_check_minutes'],
            ],
        ];
    }

    private static function cast($value, $default)
    {
        if (is_bool($default)) {
            return in_array($value, ['1', 1, true, 'true'], true);
        }
        if (is_int($default)) {
            return (int) $value;
        }
        return (string) $value;
    }
}
