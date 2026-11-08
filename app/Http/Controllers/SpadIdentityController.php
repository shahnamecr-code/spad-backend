<?php
namespace App\Http\Controllers;

use App\Services\ApiIrAccountServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SpadIdentityController extends Controller
{
    // KYC V4: only API.ir SmsOTP + ShahkarPro can issue level one.
    // Historical rows stay available for audit; older approval methods are
    // not treated as verified by this controller.
    private const PROVIDER = 'api_ir_shahkarpro';

    private function userId(Request $request): int
    {
        abort_unless(
            Schema::hasTable('spad_user_verifications') &&
            Schema::hasColumn('spad_user_verifications', 'verification_provider'),
            503, 'سامانه احراز هویت جدید هنوز به طور کامل نصب نشده است.'
        );
        abort_unless($request->user(), 401);
        return (int) $request->user()->id;
    }

    private function digits(string $value): string
    {
        return strtr(trim($value), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private function national(string $value): ?string
    {
        $value = $this->digits($value);
        if (!preg_match('/^[0-9]{10}$/D', $value) || preg_match('/^([0-9])\1{9}$/D', $value)) return null;
        $sum = 0;
        for ($i = 0; $i < 9; $i++) $sum += (int) $value[$i] * (10 - $i);
        $mod = $sum % 11;
        return (int) $value[9] === ($mod < 2 ? $mod : 11 - $mod) ? $value : null;
    }

    private function mobile(string $value): string
    {
        $value = $this->digits($value);
        if (strpos($value, '+98') === 0) $value = '0'.substr($value,3);
        if (!preg_match('/^09[0-9]{9}$/D', $value))
            throw ValidationException::withMessages(['mobile'=>'شماره همراه حساب معتبر نیست.']);
        return $value;
    }

    private function gateway(): ApiIrAccountServices
    {
        return app(ApiIrAccountServices::class);
    }

    private function availability(): array
    {
        $sms=app(\App\Services\SpadSmsService::class);
        return [
            'sms_otp'=>$sms->eventEnabled('kyc_otp'),
            'shahkar_pro'=>$this->gateway()->enabled('shahkar_pro'),
        ];
    }

    private function ensureAvailable(): void
    {
        abort_unless(!in_array(false, $this->availability(), true), 503,
            'احراز هویت به سرویس پیامک تأیید و شاهکار پرو نیاز دارد. تنظیمات آنها را بررسی کنید.');
    }

    private function record(int $uid)
    {
        return DB::table('spad_user_verifications')->where('user_id',$uid)->first();
    }

    private function verified($v, string $mobile): bool
    {
        return (bool) ($v && $v->status === 'approved' &&
            $v->verification_provider === self::PROVIDER &&
            $v->phone === $mobile && $v->phone_verified &&
            $v->shahkar_status === 'matched' && (int)$v->level >= 1);
    }

    private function statusOf($v, string $mobile): string
    {
        if (!$v) return 'not_submitted';
        if ($this->verified($v,$mobile)) return 'approved';
        if ($v->status === 'approved' || $v->status === 'pending' ||
            ($v->status === 'draft' && $v->verification_provider &&
             $v->verification_provider !== self::PROVIDER) ||
            ($v->phone && $v->phone !== $mobile)) return 'needs_reverification';
        return (string)$v->status;
    }

    private function secondsLeft($timestamp, int $duration): int
    {
        if (!$timestamp) return 0;
        try {
            $elapsed = Carbon::parse($timestamp)->diffInSeconds(now(), false);
            return max(0, $duration - max(0,(int)$elapsed));
        } catch (\Throwable $e) { return 0; }
    }

    private function usage(int $uid, $v): array
    {
        $smsKey='spad-kyc-v4-sms-user:'.$uid;
        $proKey='spad-kyc-v4-pro:'.$uid;
        $smsDaily=max(0,6-RateLimiter::attempts($smsKey));
        $proDaily=max(0,3-RateLimiter::attempts($proKey));
        return [
            'sms_remaining'=>min($smsDaily, max(0,5-(int)($v->phone_sends??0))),
            'pro_remaining'=>$proDaily,
            'sms_retry_after'=>max($this->secondsLeft($v->phone_last_sent_at??null,90),
                $smsDaily===0?RateLimiter::availableIn($smsKey):0),
            'pro_retry_after'=>max($this->secondsLeft($v->shahkar_checked_at??null,180),
                $proDaily===0?RateLimiter::availableIn($proKey):0),
            'otp_attempts_remaining'=>max(0,5-(int)($v->phone_attempts??0)),
        ];
    }

    private function publicStatus(int $uid, string $mobile): array
    {
        $v=$this->record($uid);
        $state=$this->statusOf($v,$mobile);
        $national=null;
        if ($v && !empty($v->national_code_encrypted) && $state !== 'not_submitted') {
            try { $national=Crypt::decryptString($v->national_code_encrypted); }
            catch (\Throwable $e) { $national=null; }
        }
        $isVerified=$state==='approved';
        return [
            'flow_version'=>4,
            'method'=>'sms_otp_shahkar_pro',
            'status'=>$state,
            'level'=>$isVerified?1:0,
            'phone_masked'=>substr($mobile,0,4).'***'.substr($mobile,-4),
            'national_code_masked'=>$national?substr($national,0,3).'****'.substr($national,-3):null,
            'phone_verified'=>(bool)($v && $v->status==='draft' && $v->phone_verified && $v->phone===$mobile) || $isVerified,
            'shahkar_pro_status'=>$v && $v->verification_provider===self::PROVIDER
                ? ($v->shahkar_status??'not_checked') : 'not_checked',
            'availability'=>$this->availability(),
            'usage'=>$this->usage($uid, $v),
            'current_step'=>$isVerified?'complete':
                ($state!=='draft'?'details':
                    (!$v->phone_verified?'otp':'shahkar_pro')),
            'submitted_at'=>$v?$v->updated_at:null,
            'review_note'=>$state==='rejected'?$v->review_note:null,
        ];
    }

    public function status(Request $request)
    {
        $uid=$this->userId($request);
        return response()->json(['data'=>$this->publicStatus(
            $uid,$this->mobile((string)$request->user()->mobile)
        )])->header('Cache-Control','private, no-store');
    }

    public function start(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $input=$request->validate([
            'national_code'=>'required|string|max:12',
            'consent'=>'required|accepted',
        ]);
        $national=$this->national((string)$input['national_code']);
        if (!$national)
            throw ValidationException::withMessages(['national_code'=>'کد ملی معتبر نیست.']);
        $mobile=$this->mobile((string)$request->user()->mobile);
        $key='spad-kyc-v4-start:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key,5),429,
            'سقف ثبت پرونده در امروز تکمیل شده است.');
        $current=$this->record($uid);
        abort_if($this->verified($current,$mobile),422,
            'احراز هویت این حساب قبلاً تکمیل شده است.');

        DB::transaction(function () use ($uid,$national,$mobile) {
            $v=DB::table('spad_user_verifications')->where('user_id',$uid)->lockForUpdate()->first();
            abort_if($this->verified($v,$mobile),422,
                'این حساب قبلاً تأیید شده است.');
            $fields=[
                'national_code_encrypted'=>Crypt::encryptString($national),
                'national_code_hash'=>hash_hmac('sha256',$national,config('app.key')),
                'full_name'=>null,
                'birth_date_encrypted'=>null,
                'phone'=>$mobile,
                'phone_verified'=>false,'phone_verified_at'=>null,
                'phone_code_hash'=>null,'phone_code_expires_at'=>null,
                'phone_attempts'=>0,
                // Do not erase per-user daily RateLimiter quotas by restarting.
                'phone_sends'=>0,'phone_last_sent_at'=>null,
                'shahkar_status'=>'not_checked','shahkar_checked_at'=>null,
                'person_info_status'=>'not_checked','person_info_checked_at'=>null,
                'verification_provider'=>self::PROVIDER,
                'status'=>'draft','level'=>0,'requested_level'=>1,
                'review_note'=>null,'reviewed_at'=>null,'reviewed_by'=>null,
                'updated_at'=>now(),
            ];
            if ($v) DB::table('spad_user_verifications')->where('id',$v->id)->update($fields);
            else DB::table('spad_user_verifications')->insert(array_merge($fields,
                ['user_id'=>$uid,'created_at'=>now()]));
        });
        RateLimiter::hit($key,86400);
        return $this->status($request);
    }

    public function sendPhone(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $mobile=$this->mobile((string)$request->user()->mobile);
        $ipKey='spad-kyc-v4-sms-ip:'.hash('sha256',(string)$request->ip());
        $key='spad-kyc-v4-sms-user:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key,6) ||
            RateLimiter::tooManyAttempts($ipKey,20),429,
            'محدودیت روزانه ارسال پیامک تکمیل شده است.');
        $code=(string)random_int(100000,999999);
        $hash=Hash::make($code);
        DB::transaction(function() use($uid,$mobile,$hash){
            $v=DB::table('spad_user_verifications')->where('user_id',$uid)->lockForUpdate()->first();
            abort_unless($v && $v->status==='draft' &&
                $v->verification_provider===self::PROVIDER &&
                $v->phone===$mobile,422,'ابتدا پرونده احراز هویت را ثبت کنید.');
            abort_if($v->phone_verified,422,'شماره موبایل قبلاً تأیید شده است.');
            abort_if((int)$v->phone_sends>=5,429,'سقف پیامک برای این پرونده تکمیل شده است.');
            abort_if($this->secondsLeft($v->phone_last_sent_at,90)>0,429,
                'برای ارسال مجدد پیامک، ۹۰ ثانیه فاصله لازم است.');
            DB::table('spad_user_verifications')->where('id',$v->id)->update([
                'phone_code_hash'=>$hash,'phone_code_expires_at'=>now()->addMinutes(10),
                'phone_attempts'=>0,'phone_sends'=>DB::raw('phone_sends+1'),
                'phone_last_sent_at'=>now(),'updated_at'=>now(),
            ]);
        });
        RateLimiter::hit($key,86400);
        RateLimiter::hit($ipKey,86400);
        try {
            $sms=app(\App\Services\SpadSmsService::class);
            $sms->sendOtp('kyc_otp',$mobile,$code);
        }
        catch (\Throwable $e) {
            DB::table('spad_user_verifications')->where('user_id',$uid)
                ->where('phone_code_hash',$hash)->update([
                    'phone_code_hash'=>null,'phone_code_expires_at'=>null,
                ]);
            abort(503,'ارسال پیامک احراز هویت ناموفق بود؛ تنظیمات سرویس را بررسی کنید.');
        }
        return response()->json(['message'=>'کد یک‌بارمصرف ارسال شد.'])
            ->header('Cache-Control','private, no-store');
    }

    public function verifyPhone(Request $request)
    {
        $uid=$this->userId($request);
        $data=$request->validate(['code'=>'required|string|regex:/^[0-9]{6}$/D']);
        $mobile=$this->mobile((string)$request->user()->mobile);
        $v=$this->record($uid);
        abort_unless($v && $v->status==='draft' &&
            $v->verification_provider===self::PROVIDER && $v->phone===$mobile &&
            $v->phone_code_hash && $v->phone_code_expires_at,422,
            'کد فعالی برای این حساب وجود ندارد.');
        abort_if(now()->greaterThan($v->phone_code_expires_at),422,'کد منقضی شده است.');
        $affected=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('phone_code_hash',$v->phone_code_hash)
            ->where('phone_attempts','<',5)
            ->increment('phone_attempts');
        abort_unless($affected===1,429,'سقف تلاش برای وارد کردن کد تکمیل شده است.');
        if (!Hash::check((string)$data['code'],$v->phone_code_hash))
            throw ValidationException::withMessages(['code'=>'کد تأیید صحیح نیست.']);
        $updated=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('status','draft')->where('phone_code_hash',$v->phone_code_hash)
            ->where('phone_verified',false)->update([
                'phone_verified'=>true,'phone_verified_at'=>now(),
                'phone_code_hash'=>null,'phone_code_expires_at'=>null,
                'updated_at'=>now(),
            ]);
        abort_unless($updated===1,422,'این کد قبلاً استفاده شده است.');
        return $this->status($request);
    }

    public function shahkarPro(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $mobile=$this->mobile((string)$request->user()->mobile);
        $v=$this->record($uid);
        abort_unless($v && $v->status==='draft' &&
            $v->verification_provider===self::PROVIDER &&
            $v->phone===$mobile && $v->phone_verified,422,
            'ابتدا شماره همراه حساب را با پیامک تأیید کنید.');
        abort_if($v->shahkar_status==='matched',422,'تطبیق شاهکار پرو قبلاً انجام شده است.');
        $key='spad-kyc-v4-pro:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key,3),429,
            'سقف سه استعلام روزانه شاهکار پرو تکمیل شده است.');
        abort_if($this->secondsLeft($v->shahkar_checked_at,180)>0,429,
            'برای استعلام مجدد ۱۸۰ ثانیه فاصله لازم است.');
        // Reserve before calling a billable endpoint. Concurrent requests cannot
        // bypass this atomic reservation. Do not auto-retry failed/unknown calls.
        $reserved=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('status','draft')->where('verification_provider',self::PROVIDER)
            ->where('phone_verified',true)->where('shahkar_status','!=','matched')
            ->where(function($q){
                $q->whereNull('shahkar_checked_at')
                  ->orWhere('shahkar_checked_at','<=',now()->subSeconds(180));
            })->update(['shahkar_checked_at'=>now(),'updated_at'=>now()]);
        abort_unless($reserved===1,429,'استعلام دیگری در حال پردازش است.');
        RateLimiter::hit($key,86400);
        $national=Crypt::decryptString($v->national_code_encrypted);
        try { $matched=$this->gateway()->shahkarPro($national,$mobile); }
        catch (\Throwable $e) {
            if ((int)$e->getCode()===429)
                return response()->json([
                    'message'=>'سرویس ShahkarPro در API.ir درخواست جدید نمی‌پذیرد. بعداً اقدام کنید.',
                    'error'=>'provider_rate_limited',
                ],429)->header('Cache-Control','private, no-store');
            abort(503,'استعلام شاهکار پرو به نتیجه معتبر نرسید؛ مجوز و اعتبار API.ir بررسی شود.');
        }
        $fields=[
            'shahkar_status'=>$matched?'matched':'mismatch',
            'updated_at'=>now(),
        ];
        if($matched) {
            $fields['status']='approved';
            $fields['level']=1;
            $fields['requested_level']=1;
            $fields['verification_provider']=self::PROVIDER;
            $fields['reviewed_at']=now();
            $fields['review_note']=null;
        }
        $updated=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('status','draft')->where('verification_provider',self::PROVIDER)
            ->where('phone',$mobile)->where('phone_verified',true)
            ->where('national_code_hash',hash_hmac('sha256',$national,config('app.key')))
            ->update($fields);
        abort_unless($updated===1,409,'پرونده در حین استعلام تغییر کرده است.');
        if ($matched) {
            app(\App\Services\SpadSmsService::class)->notify(
                'kyc_approved',$mobile,[], 'kyc-approved:'.$uid.':'.$v->id
            );
        }
        return $this->status($request);
    }

    // Old clients must refresh; never silently send a paid ShahkarPro request
    // from a historical PersonInfo or Shahkar endpoint.
    public function legacyEndpoint(Request $request)
    {
        $this->userId($request);
        abort(410,'نسخه جدید احراز هویت فعال است؛ صفحه را تازه‌سازی کنید.');
    }

    private function admin(): void
    {
        abort_unless(auth('admin')->check(),403);
        abort_unless(Schema::hasTable('spad_user_verifications'),503);
    }

    public function adminList()
    {
        $this->admin();
        $biometric=Schema::hasTable('spad_biometric_verifications');
        $query=DB::table('spad_user_verifications as v')
            ->leftJoin('users as u','u.id','=','v.user_id')
            ->select('v.id','v.user_id','v.full_name','v.level',
                'v.status','v.verification_provider','v.phone_verified',
                'v.shahkar_status','v.shahkar_checked_at','v.reviewed_at',
                'v.created_at','v.updated_at','u.mobile');
        if($biometric)
            $query->leftJoin('spad_biometric_verifications as b','b.user_id','=','v.user_id')
                ->addSelect('b.status as biometric_status',
                    'b.verified_at as biometric_verified_at',
                    'b.expires_at as biometric_expires_at');
        $rows=$query->orderByDesc('v.updated_at')->limit(200)->get();
        return response()->json(['data'=>$rows])
            ->header('Cache-Control','private, no-store');
    }

    public function adminReview(Request $request,$id)
    {
        $this->admin();
        $data=$request->validate(['decision'=>'required|in:reject',
            'note'=>'required|string|min:5|max:1000']);
        $v=DB::table('spad_user_verifications')->where('id',$id)->first();
        abort_unless($v && $v->status!=='rejected',422,'پرونده قابل رد نیست.');
        DB::table('spad_user_verifications')->where('id',$v->id)->update([
            'status'=>'rejected','level'=>0,'verification_provider'=>null,
            'review_note'=>$data['note'],'reviewed_by'=>auth('admin')->id(),
            'reviewed_at'=>now(),'updated_at'=>now(),
        ]);
        $recipient=\App\Models\User::find($v->user_id);
        if ($recipient) app(\App\Services\SpadSmsService::class)->notify(
            'kyc_rejected',(string)$recipient->mobile,[],
            'kyc-rejected:'.$v->id.':'.now()->timestamp
        );
        return response()->json(['message'=>'اعتبار پرونده لغو شد؛ تأیید دستی مجاز نیست.']);
    }

    public function adminDocument($id,$kind)
    {
        $this->admin();
        abort(410,'این نسخه احراز هویت نیازی به آپلود مدرک ندارد.');
    }
}
