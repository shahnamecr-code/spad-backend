<?php
namespace App\Http\Controllers;

use App\Services\ApiIrAccountServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SpadIdentityController extends Controller
{
    // Only API.IR SmsOTP, Shahkar and PersonInfo can confer a verified identity.
    // Legacy verifications remain on disk for audit but require re-verification.
    private function ready(): void
    {
        abort_unless(Schema::hasTable('spad_user_verifications') &&
            Schema::hasColumn('spad_user_verifications', 'verification_provider'), 503,
            'نسخه جدید احراز هویت هنوز روی سرور نصب نشده است.');
    }

    private function userId(Request $request): int
    {
        $this->ready();
        abort_unless($request->user(), 401);
        return (int) $request->user()->id;
    }

    private function record(int $uid)
    {
        return DB::table('spad_user_verifications')->where('user_id', $uid)->first();
    }

    private function digits(string $value): string
    {
        return strtr(trim($value), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private function national(string $raw): ?string
    {
        $v = $this->digits($raw);
        if (!preg_match('/^[0-9]{10}$/D', $v) || preg_match('/^([0-9])\1{9}$/D', $v)) return null;
        $sum = 0;
        for ($i=0;$i<9;$i++) $sum += (int)$v[$i] * (10 - $i);
        $rest = $sum % 11;
        return (int)$v[9] === ($rest < 2 ? $rest : 11 - $rest) ? $v : null;
    }

    private function mobile(string $raw): string
    {
        $v = $this->digits($raw);
        if (strpos($v, '+98') === 0) $v = '0'.substr($v, 3);
        if (!preg_match('/^09[0-9]{9}$/D', $v))
            throw ValidationException::withMessages(['mobile'=>'شماره همراه حساب معتبر نیست.']);
        return $v;
    }

    private function birthDate(string $raw): string
    {
        $v = $this->digits($raw);
        if (!preg_match('/^(13|14)[0-9]{2}\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/D', $v))
            throw ValidationException::withMessages(['birth_date'=>'تاریخ تولد شمسی را مانند ۱۳۷۵/۰۵/۲۱ وارد کنید.']);
        $parts = array_map('intval', explode('/', $v));
        if (($parts[1] > 6 && $parts[2] > 30) || ($parts[1] === 12 && $parts[2] > 30))
            throw ValidationException::withMessages(['birth_date'=>'روز تاریخ تولد معتبر نیست.']);
        return sprintf('%04d/%d/%d', $parts[0], $parts[1], $parts[2]);
    }

    private function normalizeName(string $name): string
    {
        $value = strtr(trim($name), ['ي'=>'ی','ك'=>'ک','‌'=>' ','ـ'=>'']);
        return preg_replace('/\s+/u', ' ', $value);
    }

    private function gateway(): ApiIrAccountServices
    {
        return app(ApiIrAccountServices::class);
    }

    private function availability(): array
    {
        $gateway = $this->gateway();
        return [
            'sms_otp'=>$gateway->enabled('sms_otp'),
            // Deprecated key retained for older clients; no Shahkar request is made.
            'shahkar'=>$gateway->enabled('person_info'),
            'person_info'=>$gateway->enabled('person_info'),
        ];
    }

    private function ensureAvailable(): void
    {
        $checks = $this->availability();
        abort_unless(!in_array(false, $checks, true), 503,
            'ابتدا مدیر اسپاد باید سرویس‌های پیامک و مشخصات هویتی api.ir را فعال کند.');
    }

    private function effectiveStatus($record, string $mobile): string
    {
        if (!$record) return 'not_submitted';
        if ($record->verification_provider !== 'api_ir' &&
            $record->status === 'approved') return 'needs_reverification';
        if ($record->status === 'approved' &&
            $record->phone !== $mobile) return 'needs_reverification';
        if ($record->status === 'pending') return 'needs_reverification';
        return (string)$record->status;
    }

    private function publicStatus(int $uid, string $mobile): array
    {
        $v = $this->record($uid);
        $status = $this->effectiveStatus($v, $mobile);
        $ready = $this->availability();
        $national = $v && $v->national_code_encrypted && $status !== 'not_submitted'
            ? Crypt::decryptString($v->national_code_encrypted) : null;
        return [
            'status'=>$status,
            'level'=>$status === 'approved' ? 1 : 0,
            'requested_level'=>1,
            'full_name'=>$v && $status !== 'not_submitted' ? $v->full_name : '',
            'national_code_masked'=>$national ? substr($national, 0, 3).'****'.substr($national,-3) : null,
            'phone_masked'=>substr($mobile,0,4).'***'.substr($mobile,-4),
            'phone_verified'=>(bool)($v && $v->phone_verified && $v->phone === $mobile),
            'shahkar_status'=>$v ? $v->shahkar_status : 'not_checked',
            'person_info_status'=>$v ? $v->person_info_status : 'not_checked',
            'birth_date_set'=>(bool)($v && $v->birth_date_encrypted),
            'submitted_at'=>$v ? $v->updated_at : null,
            'review_note'=>$v ? $v->review_note : null,
            'person_info_required'=>true,
            'provider_ready'=>$ready['person_info'],
            'sms_ready'=>$ready['sms_otp'],
            'availability'=>$ready,
            'api_ir_only'=>true,
            'documents'=>[],
        ];
    }

    public function status(Request $request)
    {
        $uid=$this->userId($request);
        $mobile=$this->mobile((string)$request->user()->mobile);
        return response()->json(['data'=>$this->publicStatus($uid,$mobile)])
            ->header('Cache-Control','private, no-store');
    }

    public function start(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $data=$request->validate([
            'full_name'=>'required|string|min:5|max:180',
            'national_code'=>'required|string|max:12',
            'birth_date'=>'required|string|max:20',
            'consent'=>'required|accepted',
        ]);
        $name=$this->normalizeName($data['full_name']);
        abort_unless(mb_strlen($name)>=5,422,'نام و نام خانوادگی را کامل وارد کنید.');
        $national=$this->national($data['national_code']);
        if (!$national) throw ValidationException::withMessages(['national_code'=>'کد ملی واردشده معتبر نیست.']);
        $birth=$this->birthDate($data['birth_date']);
        $mobile=$this->mobile((string)$request->user()->mobile);
        $rateKey='spad-kyc-start:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($rateKey,6),429,'سقف اصلاح پرونده هویتی تکمیل شده است.');
        $current=$this->record($uid);
        $status=$this->effectiveStatus($current,$mobile);
        abort_if($status==='approved',422,'احراز هویت شما قبلاً تکمیل شده است.');
        RateLimiter::hit($rateKey,86400);
        $fields=[
            'full_name'=>$name,'phone'=>$mobile,
            'national_code_encrypted'=>Crypt::encryptString($national),
            'national_code_hash'=>hash_hmac('sha256',$national,config('app.key')),
            'birth_date_encrypted'=>Crypt::encryptString($birth),
            'requested_level'=>1,'level'=>0,
            'phone_verified'=>false,'phone_verified_at'=>null,
            'phone_code_hash'=>null,'phone_code_expires_at'=>null,
            'phone_attempts'=>0,'phone_sends'=>0,'phone_last_sent_at'=>null,
            'shahkar_status'=>'not_checked','shahkar_checked_at'=>null,
            'person_info_status'=>'not_checked','person_info_checked_at'=>null,
            'verification_provider'=>null,
            'status'=>'draft','review_note'=>null,'reviewed_at'=>null,'reviewed_by'=>null,
            'updated_at'=>now()
        ];
        DB::transaction(function()use($uid,$fields,$mobile){
            $v=DB::table('spad_user_verifications')->where('user_id',$uid)->lockForUpdate()->first();
            if($v){
                abort_if($v->status==='approved' && $v->verification_provider==='api_ir' &&
                    $v->phone===$mobile,422,'احراز هویت شما قبلاً تکمیل شده است.');
                DB::table('spad_user_verifications')->where('id',$v->id)->update($fields);
            }else{
                DB::table('spad_user_verifications')->insert(array_merge($fields,[
                    'user_id'=>$uid,'created_at'=>now()
                ]));
            }
        });
        return $this->status($request);
    }

    public function sendPhone(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $mobile=$this->mobile((string)$request->user()->mobile);
        $ipKey='spad-kyc-sms-ip:'.hash('sha256',(string)$request->ip());
        $userKey='spad-kyc-sms-user:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($ipKey,20) ||
            RateLimiter::tooManyAttempts($userKey,6),429,'درخواست پیامک بیش از حد مجاز است.');
        $code=(string)random_int(100000,999999);
        $hash=Hash::make($code);
        DB::transaction(function()use($uid,$mobile,$hash){
            $v=DB::table('spad_user_verifications')->where('user_id',$uid)->lockForUpdate()->first();
            abort_unless($v && $v->status==='draft' && $v->phone===$mobile,422,'ابتدا پرونده هویتی را تکمیل کنید.');
            abort_if($v->phone_verified,422,'شماره همراه قبلاً تأیید شده است.');
            abort_if((int)$v->phone_sends>=5,429,'سقف ارسال کد برای این پرونده تکمیل شده است.');
            if($v->phone_last_sent_at)
                abort_if(now()->diffInSeconds($v->phone_last_sent_at)<90,429,'برای ارسال مجدد، ۹۰ ثانیه فاصله لازم است.');
            DB::table('spad_user_verifications')->where('id',$v->id)->update([
                'phone_code_hash'=>$hash,
                'phone_code_expires_at'=>now()->addMinutes(10),
                'phone_attempts'=>0,
                'phone_sends'=>DB::raw('phone_sends + 1'),
                'phone_last_sent_at'=>now(),'updated_at'=>now()
            ]);
        });
        RateLimiter::hit($userKey,86400);
        RateLimiter::hit($ipKey,86400);
        try{
            $this->gateway()->sendOtp($mobile,$code);
        }catch(\Throwable $e){
            DB::table('spad_user_verifications')->where('user_id',$uid)
                ->where('phone_code_hash',$hash)
                ->update(['phone_code_hash'=>null,'phone_code_expires_at'=>null]);
            abort(503,'ارسال پیامک api.ir انجام نشد؛ مجوز سرویس و اعتبار حساب را بررسی کنید.');
        }
        return response()->json(['message'=>'کد تأیید شش‌رقمی ارسال شد.'])
            ->header('Cache-Control','no-store');
    }

    public function verifyPhone(Request $request)
    {
        $uid=$this->userId($request);
        $data=$request->validate(['code'=>'required|string|regex:/^[0-9]{6}$/D']);
        $v=$this->record($uid);
        $mobile=$this->mobile((string)$request->user()->mobile);
        abort_unless($v && $v->status==='draft' && $v->phone===$mobile &&
            $v->phone_code_hash && $v->phone_code_expires_at,422,
            'کد فعال برای این حساب وجود ندارد.');
        abort_if((int)$v->phone_attempts>=5,429,'تعداد تلاش مجاز برای کد تکمیل شده است.');
        abort_if(now()->greaterThan($v->phone_code_expires_at),422,'کد منقضی شده است.');
        // Atomically claim an attempt before checking the hash.
        DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('phone_code_hash',$v->phone_code_hash)
            ->increment('phone_attempts');
        if(!Hash::check($data['code'],$v->phone_code_hash))
            throw ValidationException::withMessages(['code'=>'کد تأیید صحیح نیست.']);
        $affected=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('phone_code_hash',$v->phone_code_hash)
            ->where('phone_verified',false)->update([
                'phone_verified'=>true,'phone_verified_at'=>now(),
                'phone_code_hash'=>null,'phone_code_expires_at'=>null,'updated_at'=>now()
            ]);
        abort_unless($affected===1,422,'کد قبلاً استفاده شده است.');
        return $this->status($request);
    }

    // Legacy /shahkar URL remains an alias for older frontend bundles; its only
    // provider operation is PersonInfo. No Shahkar match is fabricated in storage.
    public function shahkar(Request $request)
    {
        return $this->personInfo($request);
    }

    public function personInfo(Request $request)
    {
        $uid=$this->userId($request);
        $this->ensureAvailable();
        $v=$this->record($uid);
        $mobile=$this->mobile((string)$request->user()->mobile);
        abort_unless($v && $v->status==='draft' && $v->phone===$mobile &&
            $v->phone_verified,422,'ابتدا شماره همراه را با OTP تأیید کنید.');
        abort_unless($v->birth_date_encrypted,422,'تاریخ تولد شمسی ثبت نشده است.');
        abort_if($v->person_info_status==='matched',422,'مشخصات ثبت‌احوالی قبلاً تأیید شده است.');
        $key='spad-kyc-person:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key,3),429,'سقف روزانه استعلام ثبت‌احوال تکمیل شده است.');
        if($v->person_info_checked_at)
            abort_if(now()->diffInSeconds($v->person_info_checked_at)<180,429,'برای استعلام مجدد ثبت‌احوال کمی بعد اقدام کنید.');
        $affected=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('person_info_status','!=','matched')
            ->where(function($q){$q->whereNull('person_info_checked_at')
                ->orWhere('person_info_checked_at','<=',now()->subSeconds(180));})
            ->update(['person_info_checked_at'=>now()]);
        abort_unless($affected===1,429,'استعلام مشخصات دیگری در جریان است.');
        RateLimiter::hit($key,86400);
        $national=Crypt::decryptString($v->national_code_encrypted);
        $birth=Crypt::decryptString($v->birth_date_encrypted);
        try{$data=$this->gateway()->personInfo($national,$birth);}
        catch(\Throwable $e){
            if ((int)$e->getCode()===429)
                return response()->json(['message'=>'سرویس ثبت‌احوال api.ir فعلاً درخواست جدید نمی‌پذیرد. لطفاً بعداً تلاش کنید.','error'=>'provider_rate_limited'],429)
                    ->header('Cache-Control','no-store');
            abort(503,'استعلام مشخصات هویتی api.ir نتیجه معتبر نداد؛ مجوز و اعتبار سرویس را بررسی کنید.');
        }
        $providerNational=(string)($data['nationalCode']??'');
        $fullName=$this->normalizeName((string)($data['firstName']??'').' '.(string)($data['lastName']??''));
        $matched=($data['alive']??null)===true &&
            hash_equals($national,$providerNational) &&
            $this->normalizeName($v->full_name)===$fullName;
        $update=[
            'person_info_status'=>$matched?'matched':'mismatch',
            'updated_at'=>now()
        ];
        if($matched){
            $update['status']='approved';
            $update['level']=1;
            $update['requested_level']=1;
            $update['verification_provider']='api_ir';
            $update['reviewed_at']=now();
            $update['review_note']=null;
        }
        $changed=DB::table('spad_user_verifications')->where('id',$v->id)
            ->where('status','draft')->where('phone_verified',true)
            ->where('phone',$mobile)
            ->where('national_code_hash',hash_hmac('sha256',$national,config('app.key')))
            ->update($update);
        abort_unless($changed===1,409,'پرونده در حین استعلام تغییر کرده است؛ دوباره وضعیت را بررسی کنید.');
        return $this->status($request);
    }

    // Existing clients can call the old paths; no manual KYC can grant approval.
    public function updateBirthDate(Request $request)
    {
        $this->userId($request);
        abort(410,'برای اصلاح مشخصات، از گزینه شروع دوباره احراز هویت استفاده کنید.');
    }
    public function upload(Request $request)
    {
        $this->userId($request);
        abort(410,'بارگذاری دستی مدرک در احراز هویت جدید استفاده نمی‌شود.');
    }
    public function submit(Request $request)
    {
        $this->userId($request);
        abort(410,'نتیجه احراز هویت پس از تأیید پیامک و استعلام مشخصات هویتی api.ir به‌صورت خودکار تعیین می‌شود.');
    }

    private function admin(): void
    {
        abort_unless(auth('admin')->check(),403);
        $this->ready();
    }

    public function adminList()
    {
        $this->admin();
        $rows=DB::table('spad_user_verifications as v')
            ->leftJoin('users as u','u.id','=','v.user_id')
            ->select('v.id','v.user_id','v.full_name','v.requested_level','v.level',
                'v.status','v.verification_provider','v.phone_verified','v.shahkar_status',
                'v.person_info_status','v.shahkar_checked_at','v.person_info_checked_at',
                'v.reviewed_at','v.created_at','v.updated_at','u.mobile')
            ->orderByDesc('v.updated_at')->limit(200)->get();
        return response()->json(['data'=>$rows])
            ->header('Cache-Control','private, no-store');
    }

    public function adminReview(Request $request,$id)
    {
        $this->admin();
        $data=$request->validate([
            'decision'=>'required|in:reject',
            'note'=>'required|string|min:5|max:1000'
        ]);
        $v=DB::table('spad_user_verifications')->where('id',$id)->first();
        abort_unless($v&&$v->status!=='rejected',422,'پرونده قابل رد نیست.');
        DB::table('spad_user_verifications')->where('id',$v->id)->update([
            'status'=>'rejected','level'=>0,'verification_provider'=>null,
            'review_note'=>$data['note'],'reviewed_by'=>auth('admin')->id(),
            'reviewed_at'=>now(),'updated_at'=>now()
        ]);
        return response()->json(['message'=>'پرونده رد شد. تأیید دستی در نسخه api.ir وجود ندارد.']);
    }

    public function adminDocument($id,$kind)
    {
        $this->admin();
        abort(410,'احراز هویت جدید تنها از سرویس‌های api.ir استفاده می‌کند.');
    }
}
