<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\ApiIrAccountServices;
use Illuminate\Support\Facades\RateLimiter;

class SpadIdentityController extends Controller {
 private function ready(){abort_unless(Schema::hasTable('spad_user_verifications')&&Schema::hasTable('spad_user_verification_documents'),503,'سامانه احراز هویت هنوز نصب نشده است.');}
 private function owner(Request $r){$this->ready();abort_unless($r->user(),401);return (int)$r->user()->id;}
 private function row($uid){return DB::table('spad_user_verifications')->where('user_id',$uid)->first();}
 private function national($v){
  $v=strtr((string)$v,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
  if(!preg_match('/^\d{10}$/D',$v)||preg_match('/^(\d)\1{9}$/D',$v))return null;
  $sum=0;for($i=0;$i<9;$i++)$sum+=(int)$v[$i]*(10-$i);
  $rest=$sum%11;$digit=$rest<2?$rest:11-$rest;
  return (int)$v[9]===$digit?$v:null;
 }
 private function mobile($value){
  $v=strtr((string)$value,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
  if(strpos($v,'+98')===0)$v='0'.substr($v,3);
  if(!preg_match('/^09\d{9}$/D',$v))throw ValidationException::withMessages(['mobile'=>'شماره تماس معتبر نیست.']);
  return $v;
 }
 private function exposed($uid){
  $gateway=app(ApiIrAccountServices::class);$required=$gateway->enabled('person_info');
  $smsReady=$gateway->enabled('sms_otp')||(bool)config('sms.kyc_template');
  $v=$this->row($uid);
  if(!$v)return ['status'=>'not_submitted','level'=>0,'requested_level'=>1,'phone_verified'=>false,
   'shahkar_status'=>'not_checked','person_info_status'=>'not_checked','person_info_required'=>$required,
   'birth_date_set'=>false,'documents'=>[],'full_name'=>'','national_code_masked'=>null,'review_note'=>null,
   'provider_ready'=>$gateway->enabled('shahkar')||(bool)config('services.spad_shahkar.key'),'sms_ready'=>$smsReady];
  $docs=DB::table('spad_user_verification_documents')->where('verification_id',$v->id)->get(['id','kind','filename','created_at']);
  $national=$v->national_code_encrypted?Crypt::decryptString($v->national_code_encrypted):'';
  return ['status'=>$v->status,'level'=>(int)$v->level,'requested_level'=>(int)$v->requested_level,
   'full_name'=>$v->full_name,'national_code_masked'=>$national?substr($national,0,3).'****'.substr($national,-3):null,
   'phone_masked'=>$v->phone?substr($v->phone,0,4).'***'.substr($v->phone,-4):null,
   'phone_verified'=>(bool)$v->phone_verified,'shahkar_status'=>$v->shahkar_status,
   'person_info_status'=>$v->person_info_status??'not_checked','person_info_required'=>$required,
   'birth_date_set'=>!empty($v->birth_date_encrypted),'review_note'=>$v->review_note,
   'submitted_at'=>$v->updated_at,'documents'=>$docs,
   'provider_ready'=>$gateway->enabled('shahkar')||(bool)config('services.spad_shahkar.key'),'sms_ready'=>$smsReady];
 }
 private function birthDate($date){
  $v=strtr(trim((string)$date),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
  if(!preg_match('/^(13|14)[0-9]{2}\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/D',$v))
   throw ValidationException::withMessages(['birth_date'=>'تاریخ تولد شمسی را مانند ۱۳۷۵/۵/۲۱ وارد کنید.']);
  return $v;
 }
 private function normalizeName($name){
  $s=strtr(trim((string)$name),['ي'=>'ی','ك'=>'ک','‌'=>' ','ـ'=>'']);
  return preg_replace('/\s+/u',' ',$s);
 }

 public function status(Request $r){return response()->json(['data'=>$this->exposed($this->owner($r))])->header('Cache-Control','private, no-store');}
 public function start(Request $r){
  $uid=$this->owner($r);$data=$r->validate(['full_name'=>'required|string|min:5|max:180',
   'national_code'=>'required|string|max:10','requested_level'=>'required|integer|between:1,3',
   'consent'=>'required|accepted','birth_date'=>'nullable|string|max:20']);
  if(app(ApiIrAccountServices::class)->enabled('person_info')&&!$r->filled('birth_date'))
   throw ValidationException::withMessages(['birth_date'=>'تاریخ تولد برای استعلام مشخصات هویتی ضروری است.']);
  $birth=$r->filled('birth_date')?$this->birthDate($data['birth_date']):null;
  $national=$this->national($data['national_code']);
  if(!$national)throw ValidationException::withMessages(['national_code'=>'کد ملی معتبر نیست.']);
  $mobile=$this->mobile($r->user()->mobile);
  $v=$this->row($uid);
  abort_if($v&&in_array($v->status,['pending','approved'],true),422,'پرونده فعلی در انتظار بررسی یا تأییدشده است.');
  $record=['full_name'=>$data['full_name'],'phone'=>$mobile,'national_code_encrypted'=>Crypt::encryptString($national),
    'national_code_hash'=>hash_hmac('sha256',$national,config('app.key')),
    'requested_level'=>$data['requested_level'],'phone_verified'=>false,'phone_verified_at'=>null,
    'phone_code_hash'=>null,'phone_code_expires_at'=>null,'phone_attempts'=>0,'phone_sends'=>0,'phone_last_sent_at'=>null,
    'shahkar_status'=>'not_checked','shahkar_checked_at'=>null,
    'birth_date_encrypted'=>$birth?Crypt::encryptString($birth):null,
    'person_info_status'=>'not_checked','person_info_checked_at'=>null,'status'=>'draft','review_note'=>null,'updated_at'=>now()];
  if($v)DB::table('spad_user_verifications')->where('id',$v->id)->update($record);
  else DB::table('spad_user_verifications')->insert(array_merge($record,['user_id'=>$uid,'created_at'=>now()]));
  return $this->status($r);
 }
 public function sendPhone(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->status==='draft',422,'ابتدا اطلاعات هویتی را ثبت کنید.');
  abort_if($v->phone_verified,422,'شماره تماس قبلاً تأیید شده است.');
  $apiOtp=app(ApiIrAccountServices::class)->enabled('sms_otp');
  $template=config('sms.kyc_template');
  if(!$apiOtp)abort_unless($template&&ctype_digit((string)$template),503,'الگوی اختصاصی پیامک احراز هویت در سرور تنظیم نشده است.');
  abort_if($v->phone_sends>=5,429,'سقف درخواست پیامک تکمیل شده است.');
  if($v->phone_last_sent_at)abort_if(now()->diffInSeconds($v->phone_last_sent_at)<90,429,'برای ارسال مجدد کمی بعد تلاش کنید.');
  $code=(string)random_int(100000,999999);$param=config('sms.kyc_parameter','code');
  try{
   if($apiOtp)app(ApiIrAccountServices::class)->sendOtp($v->phone,$code);
   else{
    $result=sms()->send('hi',function($sms)use($v,$template,$param,$code){
     $sms->to([$v->phone]);$sms->template((int)$template,[$param=>$code]);
    });
    if($result===false)throw new \RuntimeException('ارسال پیامک ناموفق بود.');
   }
  }catch(\Throwable $e){abort(503,'ارسال پیامک تأیید ممکن نشد.');}
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'phone_code_hash'=>Hash::make($code),'phone_code_expires_at'=>now()->addMinutes(10),
   'phone_attempts'=>0,'phone_sends'=>DB::raw('phone_sends + 1'),'phone_last_sent_at'=>now(),'updated_at'=>now()]);
  return response()->json(['message'=>'کد شش‌رقمی برای شماره حساب ارسال شد.']);
 }
 public function verifyPhone(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  $d=$r->validate(['code'=>'required|string|regex:/^\d{6}$/D']);
  abort_unless($v&&$v->status==='draft'&&$v->phone_code_hash&&$v->phone_code_expires_at,422);
  abort_if($v->phone_attempts>=5,429,'تعداد تلاش مجاز به پایان رسیده است.');
  abort_if(now()->greaterThan($v->phone_code_expires_at),422,'کد منقضی شده است.');
  if(!Hash::check($d['code'],$v->phone_code_hash)){
   DB::table('spad_user_verifications')->where('id',$v->id)->increment('phone_attempts');
   throw ValidationException::withMessages(['code'=>'کد پیامکی صحیح نیست.']);
  }
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'phone_verified'=>true,'phone_verified_at'=>now(),
   'phone_code_hash'=>null,'phone_code_expires_at'=>null,'updated_at'=>now()]);
  return $this->status($r);
 }
 public function shahkar(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->phone_verified&&$v->status==='draft',422,
   'ابتدا شماره موبایل حساب را تأیید کنید.');
  abort_if($v->shahkar_status==='matched',422,'تطبیق شاهکار قبلاً تأیید شده است.');
  // Prevent repeated paid checks for the same account, including across sessions.
  $rateKey='spad-shahkar:'.hash('sha256',(string)$uid);
  abort_if(RateLimiter::tooManyAttempts($rateKey,4),429,
   'سقف درخواست شاهکار تکمیل شده است. لطفاً بعداً اقدام کنید.');
  if($v->shahkar_checked_at)abort_if(now()->diffInSeconds($v->shahkar_checked_at)<120,429,
   'لطفاً برای استعلام مجدد کمی بعد تلاش کنید.');

  $apiIr=app(ApiIrAccountServices::class);
  $useApiIr=$apiIr->enabled('shahkar');
  $key=config('services.spad_shahkar.key');
  abort_unless($useApiIr||$key,503,'هیچ ارائه‌دهنده شاهکار در سرور فعال نیست.');
  RateLimiter::hit($rateKey,86400);
  $national=Crypt::decryptString($v->national_code_encrypted);
  try{
   if($useApiIr){
    // Strict boolean: a negative result is mismatch, never a successful verification.
    $matched=$apiIr->shahkar($national,$v->phone);
   }else{
    // Preserve previously configured service until the admin enables api.ir.
    $result=Http::timeout(12)->asForm()->post('https://nextpay.org/nx/inquiry/shahkar',[
     'inquiry_api'=>$key,'national_id'=>$national,'mobile'=>$v->phone
    ]);
    if(!$result->successful())throw new \RuntimeException('ارائه‌دهنده پاسخ موفق نداد.');
    $json=$result->json();
    if(!is_array($json)||!isset($json['code'])||(int)$json['code']!==200||
       !isset($json['data'])||!is_array($json['data'])||
       !array_key_exists('match',$json['data'])||!is_bool($json['data']['match']))
     throw new \RuntimeException('پاسخ شاهکار معتبر نیست.');
    $matched=$json['data']['match'];
   }
  }catch(\Throwable $e){
   // No credentials, phone numbers, or national ID in public errors/logs.
   abort(503,'استعلام شاهکار انجام نشد. وضعیت دسترسی وب‌سرویس را بررسی کنید.');
  }
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'shahkar_status'=>$matched?'matched':'mismatch',
   'shahkar_checked_at'=>now(),'updated_at'=>now()
  ]);
  return $this->status($r);
 }
 public function updateBirthDate(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->status==='draft',422,'تنها پرونده در حال تکمیل قابل ویرایش است.');
  $d=$r->validate(['birth_date'=>'required|string|max:20','consent'=>'required|accepted']);
  $birth=$this->birthDate($d['birth_date']);
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'birth_date_encrypted'=>Crypt::encryptString($birth),
   'person_info_status'=>'not_checked','person_info_checked_at'=>null,'updated_at'=>now()]);
  return $this->status($r);
 }
 public function personInfo(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless(app(ApiIrAccountServices::class)->enabled('person_info'),503,'استعلام ثبت‌احوال هنوز فعال نشده است.');
  abort_unless($v&&$v->status==='draft'&&$v->phone_verified&&$v->shahkar_status==='matched',422,
   'ابتدا موبایل و تطبیق شاهکار را تأیید کنید.');
  abort_unless($v->birth_date_encrypted,422,'ابتدا تاریخ تولد را وارد کنید.');
  abort_if($v->person_info_status==='matched',422,'مشخصات هویتی قبلاً بررسی شده‌اند.');
  $rateKey='spad-person-info:'.hash('sha256',(string)$uid);
  abort_if(RateLimiter::tooManyAttempts($rateKey,3),429,'سقف روزانه استعلام هویتی تکمیل شده است.');
  RateLimiter::hit($rateKey,86400);
  if($v->person_info_checked_at)abort_if(now()->diffInSeconds($v->person_info_checked_at)<180,429,'درخواست قبلی اخیراً ثبت شده است.');
  $national=Crypt::decryptString($v->national_code_encrypted);
  $birth=Crypt::decryptString($v->birth_date_encrypted);
  try{$info=app(ApiIrAccountServices::class)->personInfo($national,$birth);}
  catch(\Throwable $e){abort(503,'استعلام ثبت‌احوال انجام نشد؛ مجوز سرویس و اتصال را بررسی کنید.');}
  $name=$this->normalizeName(($info['firstName']??'').' '.($info['lastName']??''));
  $matched=$info['alive']===true
   &&hash_equals($national,(string)$info['nationalCode'])
   &&$this->normalizeName($v->full_name)===$name;
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'person_info_status'=>$matched?'matched':'mismatch',
   'person_info_checked_at'=>now(),'updated_at'=>now()]);
  // Only status persists; never store or return raw identity provider response.
  return $this->status($r);
 }
 public function upload(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->status==='draft',422,'مدارک تنها در مرحله پیش‌نویس قابل تغییر است.');
  $d=$r->validate(['kind'=>'required|in:national_card,face_photo,supporting',
   'document'=>'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
  $old=DB::table('spad_user_verification_documents')->where('verification_id',$v->id)->where('kind',$d['kind'])->first();
  $file=$r->file('document');$path=$file->store('private/spad-kyc/'.$uid,'local');
  abort_unless($path,503,'ذخیره امن سند ممکن نشد.');
  $payload=['user_id'=>$uid,'verification_id'=>$v->id,'kind'=>$d['kind'],
   'path'=>$path,'filename'=>mb_substr($file->getClientOriginalName(),0,190),
   'mime'=>$file->getMimeType(),'bytes'=>$file->getSize(),'updated_at'=>now()];
  try{
   if($old)DB::table('spad_user_verification_documents')->where('id',$old->id)->update($payload);
   else DB::table('spad_user_verification_documents')->insert(array_merge($payload,['created_at'=>now()]));
  }catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
  if($old)Storage::disk('local')->delete($old->path);
  return $this->status($r);
 }
 public function submit(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->status==='draft',422,'پرونده برای ارسال آماده نیست.');
  abort_unless($v->phone_verified&&$v->shahkar_status==='matched',422,'تأیید پیامکی و تطبیق مثبت شاهکار ضروری است.');
  if(app(ApiIrAccountServices::class)->enabled('person_info'))
   abort_unless($v->person_info_status==='matched',422,'استعلام مشخصات هویتی باید مثبت باشد.');
  $docs=DB::table('spad_user_verification_documents')->where('verification_id',$v->id)->pluck('kind')->all();
  abort_unless(in_array('national_card',$docs,true),422,'تصویر مدرک شناسایی الزامی است.');
  if($v->requested_level>=2)abort_unless(in_array('face_photo',$docs,true),422,'برای سطح ۲ عکس تطبیقی ضروری است.');
  if($v->requested_level>=3)abort_unless(in_array('supporting',$docs,true),422,'برای سطح ۳ مدرک تکمیلی لازم است.');
  DB::table('spad_user_verifications')->where('id',$v->id)->update(['status'=>'pending','review_note'=>null,'updated_at'=>now()]);
  return $this->status($r);
 }
 private function admin(){abort_unless(auth('admin')->check(),403);$this->ready();}
 public function adminList(){
  $this->admin();
  $rows=DB::table('spad_user_verifications as v')->leftJoin('users as u','u.id','=','v.user_id')
    ->select('v.id','v.user_id','v.full_name','v.requested_level','v.level','v.status',
      'v.phone_verified','v.shahkar_status','v.person_info_status','v.created_at','v.updated_at','u.mobile')
    ->orderByRaw("CASE WHEN v.status='pending' THEN 0 ELSE 1 END")->orderByDesc('v.updated_at')->limit(200)->get();
  return response()->json(['data'=>$rows]);
 }
 public function adminReview(Request $r,$id){
  $this->admin();$d=$r->validate(['decision'=>'required|in:approve,reject',
    'level'=>'required_if:decision,approve|nullable|integer|between:1,3',
    'note'=>'required_if:decision,reject|nullable|string|max:1000']);
  $v=DB::table('spad_user_verifications')->where('id',$id)->first();abort_unless($v&&$v->status==='pending',422);
  if($d['decision']==='approve'){
   abort_unless($v->phone_verified&&$v->shahkar_status==='matched',422,'استعلام شاهکار و شماره تماس تأیید نشده است.');
   abort_if($d['level']>$v->requested_level,422,'سطح مصوب از سطح درخواست‌شده بیشتر است.');
   if(app(ApiIrAccountServices::class)->enabled('person_info'))
    abort_unless($v->person_info_status==='matched',422,'نتیجه ثبت‌احوال تأیید نشده است.');
  }
  DB::table('spad_user_verifications')->where('id',$id)->where('status','pending')->update([
   'status'=>$d['decision']==='approve'?'approved':'rejected',
   'level'=>$d['decision']==='approve'?$d['level']:0,'review_note'=>$d['note']??null,
   'reviewed_by'=>auth('admin')->id(),'reviewed_at'=>now(),'updated_at'=>now()]);
  return response()->json(['message'=>'بررسی احراز هویت ثبت شد.']);
 }
 public function adminDocument($id,$kind){
  $this->admin();abort_unless(in_array($kind,['national_card','face_photo','supporting'],true),404);
  $doc=DB::table('spad_user_verification_documents')->where('verification_id',$id)->where('kind',$kind)->first();
  abort_unless($doc&&Storage::disk('local')->exists($doc->path),404);
  return Storage::disk('local')->download($doc->path,$doc->filename,[
   'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
 }
}
