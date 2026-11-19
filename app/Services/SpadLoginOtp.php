<?php

namespace App\Services;

use App\Models\LoginRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SpadLoginOtp
{
    public const RESEND_SECONDS = 90;
    public const EXPIRES_SECONDS = 120;

    public function send(string $mobile, Request $request): void
    {
        abort_unless(preg_match('/^09[0-9]{9}$/D', $mobile), 422, 'شماره موبایل نامعتبر است.');

        $lockKey = hash_hmac('sha256', $mobile, (string) config('app.key'));
        $lock = Cache::lock('spad-otp-send:' . $lockKey, 30);
        if (!$lock->get()) {
            SpadLoginRateLimits::reject('otp_request_pending', 'ارسال پیامک در حال انجام است. لحظه‌ای صبر کنید.', 2);
        }

        try {
            $previous = LoginRequest::where('mobile', $mobile)->first();
            if ($previous && $previous->updated_at) {
                $wait = $previous->updated_at->getTimestamp() + self::RESEND_SECONDS - now()->getTimestamp();
                if ($wait > 0) {
                    SpadLoginRateLimits::reject(
                        'otp_cooldown',
                        'کد قبلی هنوز تازه است. برای دریافت کد جدید کمی صبر کنید.',
                        $wait
                    );
                }
            }

            SpadLoginRateLimits::attempt('otp_send', $mobile, (string) $request->ip());
            $code = (string) random_int(100000, 999999);
            $challenge = LoginRequest::updateOrCreate(['mobile' => $mobile], ['token' => Hash::make($code)]);
            try {
                app(SpadSmsService::class)->sendOtp('login_otp', $mobile, $code);
            } catch (\Throwable $e) {
                LoginRequest::whereKey($challenge->id)->where('token', $challenge->token)->delete();
                throw new HttpResponseException(response()->json([
                    'status' => false,
                    'code' => 'otp_provider_unavailable',
                    'message' => 'ارسال پیامک ورود انجام نشد. کمی بعد دوباره تلاش کنید.',
                ], 503)->header('Cache-Control', 'private, no-store'));
            }
        } finally {
            $lock->release();
        }
    }

    public function consume(string $mobile, string $code, Request $request): bool
    {
        if (!preg_match('/^[0-9]{6}$/D', $code)) return false;
        $key = SpadLoginRateLimits::attempt('otp_verify', $mobile);
        $row = LoginRequest::where('mobile', $mobile)->first();
        if (!$row || !$row->updated_at ||
            $row->updated_at->copy()->addSeconds(self::EXPIRES_SECONDS)->isPast()) {
            return false;
        }

        $hash = (string) $row->token;
        $hashed = strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0;
        if (!($hashed ? Hash::check($code, $hash) : hash_equals($hash, $code))) return false;
        if (LoginRequest::whereKey($row->id)->where('token', $hash)->delete() !== 1) return false;

        SpadLoginRateLimits::clear($key);
        return true;
    }
}
