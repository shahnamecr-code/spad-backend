<?php
namespace App\Http\Controllers;

use App\Services\ApiIrAccountServices;
use App\Services\SpadBiometricAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SpadBiometricController extends Controller
{
    private function setup(): void
    {
        abort_unless(Schema::hasTable('spad_biometric_verifications') &&
            Schema::hasTable('spad_user_verifications'),503,
            'موتور احراز هویت ویدئویی هنوز نصب نشده است.');
    }

    public function status(Request $request, SpadBiometricAccess $access)
    {
        $this->setup();
        abort_unless($request->user(),401);
        $result=$access->status($request->user());
        $challenge=Cache::get('spad-video-verify-challenge:'.(int)$request->user()->id);
        $result['speech_text']=is_array($challenge)?($challenge['text']??null):null;
        $uid=(int)$request->user()->id;
        $key='spad-video-live:'.$uid;
        $result['attempts_remaining']=max(0,3-RateLimiter::attempts($key));
        $row=DB::table('spad_biometric_verifications')->where('user_id',$uid)->first();
        $elapsed=$row&&$row->checked_at?
            Carbon::parse($row->checked_at)->diffInSeconds(now(),false):600;
        $result['retry_after']=max(0,600-max(0,(int)$elapsed));
        if ($result['attempts_remaining']===0)
            $result['retry_after']=max($result['retry_after'],RateLimiter::availableIn($key));
        return response()->json(['data'=>$result])
            ->header('Cache-Control','private, no-store');
    }

    /**
     * Explicit action only: obtaining a speech challenge may consume API.ir
     * credit. Reuse a challenge while valid instead of paying twice.
     */
    public function challenge(Request $request, SpadBiometricAccess $access,
        ApiIrAccountServices $gateway)
    {
        $this->setup();
        $user=$request->user();
        abort_unless($user,401);
        $state=$access->status($user);
        abort_unless($state['base_verified'],403,
            'ابتدا احراز هویت پایه را تکمیل کنید.');
        abort_unless($state['biometric_enabled'],503,
            'سرویس احراز ویدئویی در API.ir فعال نیست.');
        abort_if($state['biometric_verified'],422,'احراز ویدئویی معتبر قبلاً انجام شده است.');
        $uid=(int)$user->id;
        $key='spad-video-verify-challenge:'.$uid;
        $existing=Cache::get($key);
        if (is_array($existing) && !empty($existing['text'])) {
            return response()->json(['data'=>['speech_text'=>$existing['text']]])
                ->header('Cache-Control','private, no-store');
        }
        $limit='spad-video-verify-challenge-attempts:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($limit,3),429,
            'سقف روزانه دریافت متن گفتار تکمیل شده است.');
        RateLimiter::hit($limit,86400);
        try {
            $text=$gateway->videoVerifySpeechText();
        } catch (\Throwable $e) {
            if ((int)$e->getCode()===429)
                abort(429,'محدودیت درخواست API.ir فعال است؛ متن گفتار دریافت نشد.');
            abort(503,'دریافت متن گفتار از API.ir به نتیجه معتبر نرسید.');
        }
        Cache::put($key,['text'=>$text],now()->addMinutes(10));
        return response()->json(['data'=>['speech_text'=>$text]])
            ->header('Cache-Control','private, no-store');
    }

    public function verify(Request $request, SpadBiometricAccess $access,
        ApiIrAccountServices $gateway)
    {
        $this->setup();
        $user=$request->user();
        abort_unless($user,401);
        $data=$request->validate([
            'birth_date'=>['required','string',
                'regex:/^(13|14)[0-9]{2}\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/D'],
            'serial_number'=>['required','string','regex:/^[A-Za-z0-9]{6,20}$/D'],
            'video'=>['required','file','max:5120','mimetypes:video/webm,video/mp4,video/quicktime'],
            'consent'=>['required','accepted'],
        ]);
        abort_unless($access->enabled(),503,'سرویس VideoVerify در API.ir فعال نیست.');
        $uid=(int)$user->id;
        $challengeKey='spad-video-verify-challenge:'.$uid;
        $challenge=Cache::get($challengeKey);
        abort_unless(is_array($challenge) && !empty($challenge['text']),422,
            'ابتدا متن تصادفی گفتار را دریافت کنید و در ویدئو با صدای واضح بخوانید.');
        $speechText=(string)$challenge['text'];
        $key='spad-video-live:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key,3),429,
            'سقف روزانه سه بار برای احراز ویدئویی تکمیل شده است.');
        $file=$data['video'];
        abort_unless($file->isValid() &&
            $file->getSize()>=10000 && $file->getSize()<=5242880,422,
            'حجم یا فایل ویدئویی معتبر نیست.');
        $v=DB::table('spad_user_verifications')->where('user_id',$uid)->first();
        abort_unless($v && $v->status==='approved' && (int)$v->level>=1 &&
            $v->verification_provider==='api_ir_shahkarpro' &&
            $v->phone_verified && $v->shahkar_status==='matched' &&
            $v->phone===(string)$user->mobile && $v->national_code_encrypted &&
            $v->national_code_hash,403,'ابتدا احراز هویت پایه اسپاد را تکمیل کنید.');
        abort_if($access->verified($user),422,'احراز ویدئویی معتبر قبلاً ثبت شده است.');

        $national=Crypt::decryptString($v->national_code_encrypted);
        $hash=(string)$v->national_code_hash;
        $phoneHash=hash_hmac('sha256',(string)$user->mobile,config('app.key'));
        DB::transaction(function() use($uid,$hash,$phoneHash){
            $row=DB::table('spad_biometric_verifications')
                ->where('user_id',$uid)->lockForUpdate()->first();
            if($row){
                abort_if($row->checked_at &&
                    Carbon::parse($row->checked_at)->greaterThan(now()->subMinutes(10)),
                    429,'برای استعلام مجدد باید ده دقیقه فاصله باشد.');
                DB::table('spad_biometric_verifications')->where('id',$row->id)->update([
                    'base_national_hash'=>$hash,'phone_hash'=>$phoneHash,
                    'status'=>'processing','checked_at'=>now(),
                    'verified_at'=>null,'expires_at'=>null,'updated_at'=>now(),
                ]);
            }else{
                DB::table('spad_biometric_verifications')->insert([
                    'user_id'=>$uid,'base_national_hash'=>$hash,'phone_hash'=>$phoneHash,
                    'provider'=>'api_ir_videoverify','status'=>'processing',
                    'checked_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
        RateLimiter::hit($key,86400);
        // Consume before provider call; retrying a failed/uncertain paid
        // verification requires a fresh explicit challenge.
        Cache::forget($challengeKey);
        // Read into memory only for this single authorized provider request.
        // The uploaded PHP temp file is never persisted by SPAD.
        $video=file_get_contents($file->getRealPath());
        abort_unless($video!==false && strlen($video)<=5242880,422,
            'فایل ویدئویی قابل خواندن نیست.');
        $base64=base64_encode($video);
        unset($video);
        try{
            $matched=$gateway->videoVerify($national,(string)$data['birth_date'],
                (string)$data['serial_number'],$base64,$speechText);
        }catch(\Throwable $e){
            unset($base64);
            DB::table('spad_biometric_verifications')->where('user_id',$uid)
                ->where('base_national_hash',$hash)
                ->where('status','processing')->update([
                    'status'=>'error','updated_at'=>now()]);
            if ((int)$e->getCode()===429)
                abort(429,'محدودیت سرویس بایومتریک API.ir فعال است؛ استعلام را تکرار نکنید.');
            abort(503,'پاسخ قابل‌تأییدی از VideoVerify دریافت نشد؛ اعتبار سرویس و متن گفتار بررسی شود.');
        }
        unset($base64);
        // If the account KYC was reset while the paid API call was running,
        // never approve biometric status for the previous identity.
        $fresh=DB::table('spad_user_verifications')->where('user_id',$uid)->first();
        abort_unless($fresh && $fresh->status==='approved' &&
            $fresh->verification_provider==='api_ir_shahkarpro' &&
            hash_equals($hash,(string)$fresh->national_code_hash) &&
            $fresh->phone===(string)$user->mobile,409,
            'هویت پایه در طول استعلام تغییر کرد. نتیجه قابل ثبت نیست.');
        DB::table('spad_biometric_verifications')->where('user_id',$uid)
            ->where('base_national_hash',$hash)->where('phone_hash',$phoneHash)
            ->where('status','processing')->update([
                'status'=>$matched?'matched':'mismatch',
                'verified_at'=>$matched?now():null,
                'expires_at'=>$matched?now()->addDays(180):null,
                'updated_at'=>now(),
            ]);
        return $this->status($request,$access);
    }
}
