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
  $v=$this->row($uid);
  if(!$v)return ['status'=>'not_submitted','level'=>0,'requested_level'=>1,'phone_verified'=>false,'shahkar_status'=>'not_checked','documents'=>[],'full_name'=>'','national_code_masked'=>null,'review_note'=>null,'provider_ready'=>(bool)config('services.spad_shahkar.key'),'sms_ready'=>(bool)config('sms.kyc_template')];
  $docs=DB::table('spad_user_verification_documents')->where('verification_id',$v->id)->get(['id','kind','filename','created_at']);
  $national=$v->national_code_encrypted?Crypt::decryptString($v->national_code_encrypted):'';
  return ['status'=>$v->status,'level'=>(int)$v->level,'requested_level'=>(int)$v->requested_level,
   'full_name'=>$v->full_name,'national_code_masked'=>$national?substr($national,0,3).'****'.substr($national,-3):null,
   'phone_masked'=>$v->phone?substr($v->phone,0,4).'***'.substr($v->phone,-4):null,
   'phone_verified'=>(bool)$v->phone_verified,'shahkar_status'=>$v->shahkar_status,
   'review_note'=>$v->review_note,'submitted_at'=>$v->updated_at,'documents'=>$docs,
   'provider_ready'=>(bool)config('services.spad_shahkar.key'),'sms_ready'=>(bool)config('sms.kyc_template')];
 }
 public function status(Request $r){return response()->json(['data'=>$this->exposed($this->owner($r))])->header('Cache-Control','private, no-store');}
 public function start(Request $r){
  $uid=$this->owner($r);$data=$r->validate(['full_name'=>'required|string|min:5|max:180',
   'national_code'=>'required|string|max:10','requested_level'=>'required|integer|between:1,3',
   'consent'=>'required|accepted']);
  $national=$this->national($data['national_code']);
  if(!$national)throw ValidationException::withMessages(['national_code'=>'کد ملی معتبر نیست.']);
  $mobile=$this->mobile($r->user()->mobile);
  $v=$this->row($uid);
  abort_if($v&&in_array($v->status,['pending','approved'],true),422,'پرونده فعلی در انتظار بررسی یا تأییدشده است.');
  $record=['full_name'=>$data['full_name'],'phone'=>$mobile,'national_code_encrypted'=>Crypt::encryptString($national),
    'national_code_hash'=>hash_hmac('sha256',$national,config('app.key')),
    'requested_level'=>$data['requested_level'],'phone_verified'=>false,'phone_verified_at'=>null,
    'phone_code_hash'=>null,'phone_code_expires_at'=>null,'phone_attempts'=>0,'phone_sends'=>0,'phone_last_sent_at'=>null,
    'shahkar_status'=>'not_checked','shahkar_checked_at'=>null,'status'=>'draft','review_note'=>null,'updated_at'=>now()];
  if($v)DB::table('spad_user_verifications')->where('id',$v->id)->update($record);
  else DB::table('spad_user_verifications')->insert(array_merge($record,['user_id'=>$uid,'created_at'=>now()]));
  return $this->status($r);
 }
 public function sendPhone(Request $r){
  $uid=$this->owner($r);$v=$this->row($uid);
  abort_unless($v&&$v->status==='draft',422,'ابتدا اطلاعات هویتی را ثبت کنید.');
  abort_if($v->phone_verified,422,'شماره تماس قبلاً تأیید شده است.');
  $template=config('sms.kyc_template');
  abort_unless($template&&ctype_digit((string)$template),503,'الگوی اختصاصی پیامک احراز هویت در سرور تنظیم نشده است.');
  abort_if($v->phone_sends>=5,429,'سقف درخواست پیامک تکمیل شده است.');
  if($v->phone_last_sent_at)abort_if(now()->diffInSeconds($v->phone_last_sent_at)<90,429,'برای ارسال مجدد کمی بعد تلاش کنید.');
  $code=(string)random_int(100000,999999);$param=config('sms.kyc_parameter','code');
  $result=sms()->send('hi',function($sms)use($v,$template,$param,$code){
   $sms->to([$v->phone]);$sms->template((int)$template,[$param=>$code]);
  });
  abort_if($result===false,503,'سرویس ارسال پیامک پاسخ موفق ارائه نکرد.');
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
  abort_unless($v&&$v->phone_verified&&$v->status==='draft',422,'ابتدا شماره موبایل حساب را تأیید کنید.');
  $key=config('services.spad_shahkar.key');
  abort_unless($key,503,'کلید معتبر وب‌سرویس شاهکار هنوز در سرور تنظیم نشده است.');
  if($v->shahkar_checked_at)abort_if(now()->diffInSeconds($v->shahkar_checked_at)<120,429,'لطفاً برای استعلام مجدد کمی بعد تلاش کنید.');
  $national=Crypt::decryptString($v->national_code_encrypted);
  try{
   $result=Http::timeout(12)->asForm()->post('https://nextpay.org/nx/inquiry/shahkar',[
    'inquiry_api'=>$key,'national_id'=>$national,'mobile'=>$v->phone
   ]);
   abort_unless($result->successful(),503,'سرویس شاهکار پاسخ معتبر نداد.');
   $json=$result->json();
   // Strict success semantics: unknown responses must not grant KYC.
   $matched=isset($json['code'])&&(int)$json['code']===200&&
     isset($json['data']['match'])&&$json['data']['match']===true;
   $negative=isset($json['code'])&&(int)$json['code']===200&&isset($json['data']['match'])&&$json['data']['match']===false;
   abort_unless($matched||$negative,503,'پاسخ شاهکار قابل تفسیر نیست؛ استعلام به‌صورت تأییدشده ثبت نشد.');
  }catch(\Illuminate\Http\Client\ConnectionException $e){abort(503,'ارتباط با شاهکار برقرار نشد.');}
  DB::table('spad_user_verifications')->where('id',$v->id)->update([
   'shahkar_status'=>$matched?'matched':'mismatch','shahkar_checked_at'=>now(),'updated_at'=>now()]);
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
      'v.phone_verified','v.shahkar_status','v.created_at','v.updated_at','u.mobile')
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
