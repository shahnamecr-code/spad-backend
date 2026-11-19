<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\RateLimiter;

class SpadLoginRateLimits
{
    private const RULES = [
        'otp_send' => [4, 600, 'otp_send_throttled', 'سقف درخواست پیامک برای این شماره تکمیل شده است. کمی بعد دوباره تلاش کنید.'],
        'otp_send_origin' => [120, 600, 'otp_send_throttled', 'تعداد درخواست پیامک از این اتصال زیاد است. کمی بعد دوباره تلاش کنید.'],
        'otp_verify' => [6, 600, 'otp_verify_throttled', 'تعداد تلاش‌های ناموفق برای تأیید کد زیاد است. کمی بعد دوباره تلاش کنید.'],
        'password' => [12, 60, 'password_throttled', 'تعداد تلاش‌های ناموفق ورود با رمز عبور زیاد است. کمی بعد دوباره تلاش کنید.'],
    ];

    private static function key(string $operation, string $identity): string
    {
        return 'spad-login:' . $operation . ':' . hash_hmac('sha256', $identity, (string) config('app.key'));
    }

    /** Reserve an attempt before checking credentials, so simultaneous guesses count too. */
    public static function attempt(string $operation, string $mobile, ?string $sendOrigin = null): string
    {
        $key = self::key($operation, $mobile);
        $buckets = [$key => self::RULES[$operation]];
        if ($sendOrigin !== null) {
            $buckets[self::key('otp_send_origin', $sendOrigin)] = self::RULES['otp_send_origin'];
        }

        foreach ($buckets as $bucket => [$maximum, $window, $code, $message]) {
            if (RateLimiter::tooManyAttempts($bucket, $maximum)) {
                self::reject($code, $message, RateLimiter::availableIn($bucket));
            }
        }
        foreach ($buckets as $bucket => [$maximum, $window, $code, $message]) {
            if (RateLimiter::hit($bucket, $window) > $maximum) {
                self::reject($code, $message, RateLimiter::availableIn($bucket));
            }
        }
        return $key;
    }

    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    public static function reject(string $code, string $message, int $retryAfter): void
    {
        $retryAfter = max(1, $retryAfter);
        throw new HttpResponseException(response()->json([
            'status' => false, 'code' => $code, 'message' => $message, 'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter)->header('Cache-Control', 'private, no-store'));
    }
}
