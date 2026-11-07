<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SpadPasswordRecoveryController extends Controller
{
    private function phone(string $number): string
    {
        $number = strtr(trim($number), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        ]);
        if (strpos($number, '+98')===0) $number='0'.substr($number,3);
        if (!preg_match('/^09\d{9}$/D',$number)) {
            throw ValidationException::withMessages(['mobile'=>'شماره موبایل معتبر نیست.']);
        }
        return $number;
    }

    private function cacheKey(string $mobile): string
    {
        return 'spad:password-recovery:' . hash_hmac('sha256', $mobile, config('app.key'));
    }

    public function requestCode(Request $request, SpadSmsService $sms)
    {
        $data=$request->validate(['mobile'=>'required|string|max:25']);
        $mobile=$this->phone($data['mobile']);
        abort_unless($sms->configured() && $sms->eventEnabled('password_reset_otp'),503,
            'بازیابی رمز با پیامک هنوز فعال نشده است.');
        $userKey='spad:recover:mobile:'.hash('sha256',$mobile);
        $ipKey='spad:recover:ip:'.hash('sha256',(string)$request->ip());
        abort_if(RateLimiter::tooManyAttempts($userKey,3) ||
            RateLimiter::tooManyAttempts($ipKey,10),429,
            'تعداد درخواست‌ها بیش از حد مجاز است؛ بعداً تلاش کنید.');
        RateLimiter::hit($userKey,3600);
        RateLimiter::hit($ipKey,3600);
        $message='اگر این شماره در اسپاد ثبت شده باشد، کد بازیابی به آن ارسال خواهد شد.';
        if (!$user=User::where('mobile',$mobile)->first()) {
            return response()->json(['message'=>$message])->header('Cache-Control','no-store');
        }
        $code=(string) random_int(100000,999999);
        $key=$this->cacheKey($mobile);
        $hash=Hash::make($code);
        Cache::put($key, ['hash'=>$hash,'uid'=>$user->id], now()->addMinutes(5));
        try {
            $sms->sendOtp('password_reset_otp',$mobile,$code);
        } catch (\Throwable $e) {
            Cache::forget($key);
            abort(503,'ارسال کد بازیابی ممکن نشد؛ لطفاً بعداً تلاش کنید.');
        }
        return response()->json(['message'=>$message])->header('Cache-Control','no-store');
    }

    public function confirm(Request $request, SpadSmsService $sms)
    {
        $data=$request->validate([
            'mobile'=>'required|string|max:25',
            'code'=>'required|string|regex:/^\d{6}$/D',
            'password'=>'required|string|min:8|max:72|confirmed',
        ]);
        $mobile=$this->phone($data['mobile']);
        $key='spad:recover:verify:'.hash('sha256',$mobile.'|'.$request->ip());
        abort_if(RateLimiter::tooManyAttempts($key,6),429,
            'تلاش‌های بازیابی بیش از حد مجاز است.');
        RateLimiter::hit($key,900);
        $challenge=Cache::get($this->cacheKey($mobile));
        if (!is_array($challenge) || empty($challenge['hash']) ||
            !Hash::check($data['code'],$challenge['hash'])) {
            return response()->json(['message'=>'کد بازیابی صحیح نیست یا منقضی شده است.'],422);
        }
        $user=User::whereKey($challenge['uid'])->where('mobile',$mobile)->first();
        if (!$user) return response()->json(['message'=>'درخواست بازیابی معتبر نیست.'],422);
        // Consume the OTP before committing a new password; double submissions
        // cannot reuse it.
        Cache::forget($this->cacheKey($mobile));
        $user->password=$data['password']; // Model's existing password mutator.
        $user->save();
        $user->tokens()->delete();
        RateLimiter::clear($key);
        $sms->notify('password_changed',$mobile,[],'password-reset:'.$user->id.':'.now()->timestamp);
        return response()->json(['message'=>'رمز عبور تغییر کرد؛ لطفاً دوباره وارد شوید.'])
            ->header('Cache-Control','no-store');
    }
}
