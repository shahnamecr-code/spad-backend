<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApiIrAccountServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccountMobileController extends Controller
{
    private function sessionKey(Request $request): string
    {
        // A challenge belongs to the authenticated user AND this bearer-token session.
        $bearer = (string) $request->bearerToken();
        abort_if($bearer === '', 401, 'برای تغییر شماره، دوباره وارد حساب شوید.');
        return 'spad:account-mobile:'.$request->user()->getKey().':'.
            hash_hmac('sha256', $bearer, (string) config('app.key'));
    }

    private function mobile(string $mobile): string
    {
        $value = fix_mobile($mobile);
        abort_unless(preg_match('/^09[0-9]{9}$/D', $value), 422, 'شماره همراه معتبر نیست.');
        return $value;
    }

    private function masked(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -4);
    }

    private function checkSendLimit(Request $request, string $stage): void
    {
        $uid = $request->user()->getKey();
        $key = 'spad:account-mobile:send:'.$stage.':'.$uid;
        $ip = 'spad:account-mobile:ip:'.hash('sha256', (string) $request->ip());
        $cooldown = 'spad:account-mobile:cooldown:'.$stage.':'.$uid;
        abort_if(
            RateLimiter::tooManyAttempts($key, 4) ||
            RateLimiter::tooManyAttempts($ip, 10) ||
            RateLimiter::tooManyAttempts($cooldown, 1),
            429,
            'برای ارسال کد مجدد کمی صبر کنید یا بعداً تلاش کنید.'
        );
        // Reserve before calling a billable external service. A failure must not cause a retry storm.
        RateLimiter::hit($key, 86400);
        RateLimiter::hit($ip, 3600);
        RateLimiter::hit($cooldown, 90);
    }

    private function sendOtp(string $mobile, string $code): void
    {
        try {
            $gateway = app(ApiIrAccountServices::class);
            abort_unless($gateway->enabled('sms_otp'), 503, 'سرویس پیامکی حساب در دسترس نیست.');
            $gateway->sendOtp($mobile, $code);
        } catch (\Throwable $e) {
            report($e);
            abort(503, 'ارسال پیامک تأیید انجام نشد. اتصال API.ir را بررسی کنید.');
        }
    }

    private function validateCode(Request $request): string
    {
        $data = $request->validate(['code' => ['required','string','regex:/^[0-9]{6}$/D']]);
        return (string) $data['code'];
    }

    private function verifyCode(Request $request, string $stage, $hash, string $code): void
    {
        $key = $this->sessionKey($request).':tries:'.$stage;
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'تعداد تلاش مجاز تکمیل شده است.');
        RateLimiter::hit($key, 900);
        abort_unless(is_string($hash) && Hash::check($code, $hash), 422, 'کد نادرست یا منقضی شده است.');
        RateLimiter::clear($key);
    }

    public function start(Request $request)
    {
        $key = $this->sessionKey($request);
        $old = $this->mobile((string) $request->user()->mobile);
        $this->checkSendLimit($request, 'current');
        $code = (string) random_int(100000, 999999);
        $this->sendOtp($old, $code);
        Cache::put($key, [
            'stage' => 'current_sent',
            'old' => $old,
            'current_hash' => Hash::make($code),
        ], now()->addMinutes(10));
        return response()->json(['status' => true, 'stage' => 'current_sent',
            'phone_masked' => $this->masked($old),
            'message' => 'کد تأیید شماره فعلی ارسال شد.']);
    }

    public function verifyCurrent(Request $request)
    {
        $key = $this->sessionKey($request);
        $state = Cache::get($key);
        abort_unless(is_array($state) && ($state['stage'] ?? '') === 'current_sent', 422, 'ابتدا کد شماره فعلی را دریافت کنید.');
        abort_unless(($state['old'] ?? '') === $this->mobile((string) $request->user()->mobile), 409, 'شماره حساب تغییر کرده است.');
        $this->verifyCode($request, 'current', $state['current_hash'] ?? null, $this->validateCode($request));
        Cache::put($key, ['stage' => 'current_verified', 'old' => $state['old']], now()->addMinutes(10));
        return response()->json(['status' => true, 'stage' => 'current_verified',
            'message' => 'شماره فعلی تأیید شد. شماره جدید را وارد کنید.']);
    }

    public function sendNew(Request $request)
    {
        $input = $request->validate(['mobile' => 'required|string|max:20']);
        $mobile = $this->mobile((string) $input['mobile']);
        $key = $this->sessionKey($request);
        $state = Cache::get($key);
        abort_unless(is_array($state) &&
            in_array(($state['stage'] ?? ''), ['current_verified','new_sent'], true), 422,
            'ابتدا شماره فعلی را تأیید کنید.');
        $old = $this->mobile((string) $request->user()->mobile);
        abort_unless(($state['old'] ?? '') === $old, 409, 'شماره حساب تغییر کرده است.');
        abort_if($mobile === $old, 422, 'شماره جدید باید با شماره فعلی متفاوت باشد.');
        abort_if(User::where('mobile', $mobile)->exists(), 422, 'این شماره قبلاً به حساب دیگری متصل شده است.');
        $this->checkSendLimit($request, 'new');
        $code = (string) random_int(100000, 999999);
        $this->sendOtp($mobile, $code);
        Cache::put($key, ['stage' => 'new_sent','old' => $old,
            'new' => $mobile, 'new_hash' => Hash::make($code)], now()->addMinutes(10));
        return response()->json(['status' => true, 'stage' => 'new_sent',
            'phone_masked' => $this->masked($mobile),
            'message' => 'کد تأیید شماره جدید ارسال شد.']);
    }

    public function confirm(Request $request)
    {
        $key = $this->sessionKey($request);
        $state = Cache::get($key);
        abort_unless(is_array($state) && ($state['stage'] ?? '') === 'new_sent', 422, 'ابتدا شماره جدید را تأیید کنید.');
        $this->verifyCode($request, 'new', $state['new_hash'] ?? null, $this->validateCode($request));
        // Only one request may consume the verified OTP challenge.
        abort_unless(Cache::pull($key), 409, 'این کد قبلاً استفاده شده است.');
        $old = $state['old'];
        $new = $state['new'];
        DB::transaction(function () use ($request, $old, $new) {
            $user = User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->mobile((string) $user->mobile) === $old, 409, 'شماره حساب تغییر کرده است.');
            if (User::where('mobile', $new)->exists()) {
                throw ValidationException::withMessages(['mobile' => 'این شماره متعلق به حساب دیگری است.']);
            }
            $user->mobile = $new;
            $user->phone_verified_at = now();
            $user->save();
            // The former number's authentication tokens must not remain usable.
            $user->tokens()->delete();
            // KYC V4 compares its stored phone to the current one and requires re-verification.
        });
        return response()->json(['status' => true,
            'message' => 'شماره همراه تغییر کرد. برای امنیت حساب دوباره وارد شوید و احراز هویت را تکمیل کنید.']);
    }
}
