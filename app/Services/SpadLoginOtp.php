<?php
namespace App\Services;

use App\Models\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class SpadLoginOtp
{
    private function key(string $type, string $mobile, Request $request): string
    {
        return 'spad-otp:'.$type.':'.hash('sha256', $mobile.'|'.$request->ip());
    }
    public function send(string $mobile, Request $request, \Closure $legacySender): void
    {
        if (!preg_match('/^09[0-9]{9}$/D',$mobile)) throw new RuntimeException('شماره موبایل نامعتبر است.');
        $key = $this->key('send',$mobile,$request);
        $ipKey = 'spad-otp:ip:'.hash('sha256',(string)$request->ip());
        if (RateLimiter::tooManyAttempts($key,4) || RateLimiter::tooManyAttempts($ipKey,12))
            throw new RuntimeException('سقف درخواست پیامک تکمیل شده است.');
        $prev = LoginRequest::where('mobile',$mobile)->first();
        if ($prev && $prev->updated_at && $prev->updated_at->copy()->addSeconds(90)->isFuture())
            throw new RuntimeException('برای دریافت کد جدید ۹۰ ثانیه فاصله لازم است.');
        RateLimiter::hit($key,600);
        RateLimiter::hit($ipKey,600);
        $code = (string)random_int(100000,999999);
        $challenge = LoginRequest::updateOrCreate(['mobile'=>$mobile],['token'=>Hash::make($code)]);
        try {
            $sms = app(SpadSmsService::class);
            if ($sms->configured()) {
                $sms->sendOtp('login_otp', $mobile, $code);
            } else {
                // Controlled transition: keep existing login working until the
                // administrator enables MeliPayamak with a valid key.
                $gateway = app(ApiIrAccountServices::class);
                if ($gateway->enabled('sms_otp')) $gateway->sendOtp($mobile,$code);
                else if ($legacySender($code) === false) throw new RuntimeException('ارسال پیامک تأیید نشد.');
            }
        } catch (\Throwable $e) {
            LoginRequest::whereKey($challenge->id)->where('token',$challenge->token)->delete();
            throw new RuntimeException('ارسال کد انجام نشد؛ تنظیمات پیامک را بررسی کنید.');
        }
    }
    public function consume(string $mobile,string $code,Request $request): bool
    {
        $key=$this->key('verify',$mobile,$request);
        if(RateLimiter::tooManyAttempts($key,6))return false;
        RateLimiter::hit($key,600);
        if(!preg_match('/^[0-9]{6}$/D',$code))return false;
        $row=LoginRequest::where('mobile',$mobile)->first();
        if(!$row||!$row->updated_at||$row->updated_at->copy()->addSeconds(120)->isPast())return false;
        $hash=(string)$row->token;
        $hashed=strpos($hash,'$2y$')===0||strpos($hash,'$argon2')===0;
        if(!($hashed?Hash::check($code,$hash):hash_equals($hash,$code)))return false;
        if(LoginRequest::whereKey($row->id)->where('token',$hash)->delete()!==1)return false;
        RateLimiter::clear($key);
        return true;
    }
}
