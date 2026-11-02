<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PropertyHandoverSignatureController extends Controller {
 private function ready() {
  abort_unless(Schema::hasTable('spad_handover_signatures')
    && Schema::hasColumn('spad_handover_checklists','snapshot_hash'),503,
    'سامانه تأیید تحویل در حال راه‌اندازی است.');
 }
 private function owner(Request $r,$id) {
  $uid=optional($r->user())->id;
  abort_unless($uid,401,'برای تأیید ابتدا وارد حساب شوید.');
  $row=DB::table('spad_handover_checklists')->where('id',$id)->where('user_id',$uid)->first();
  abort_unless($row,404,'پرونده پیدا نشد.');
  return $row;
 }
 private function phone($number) {
  $s=strtr(trim((string)$number),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
  '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2',
  '٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $s=preg_replace('/[\s\-()]/','',$s);
  if(strpos($s,'+98')===0)$s='0'.substr($s,3);
  elseif(strpos($s,'0098')===0)$s='0'.substr($s,4);
  elseif(strpos($s,'98')===0&&strlen($s)===12)$s='0'.substr($s,2);
  abort_unless(preg_match('/^09\d{9}$/D',$s),422,'شماره موبایل معتبر ایرانی وارد کنید.');
  return $s;
 }
 private function mask($phone) {return substr($phone,0,4).'***'.substr($phone,-4);}
 private function template() {
  $id=config('sms.handover_template');
  abort_unless($id&&ctype_digit((string)$id)&&(int)$id>0,503,
   'الگوی پیامکی مخصوص امضای چک‌لیست هنوز در پنل پیامک اسپاد تنظیم نشده است.');
  return (int)$id;
 }
 private function summary($record) {
  $signatures=DB::table('spad_handover_signatures')->where('checklist_id',$record->id)
   ->get()->keyBy('party');
  $out=[];
  foreach(['from','to'] as $party) {
   $s=$signatures->get($party);
   if(!$s)continue;
   $out[$party]=[
    'party'=>$party,'mobile_masked'=>$this->mask($s->mobile),
    'verified_at'=>$s->verified_at,'last_sent_at'=>$s->last_sent_at,
    'invitation_url'=>'https://spad.estate/property-toolkit/handover/verify/'.Crypt::decryptString($s->invitation_cipher),
   ];
  }
  return ['status'=>$record->status,'snapshot_hash'=>$record->snapshot_hash,
   'signatures'=>$out,'completed_at'=>$record->completed_at];
 }
 public function status(Request $r,$id) {
  $this->ready();$row=$this->owner($r,$id);
  return response()->json(['data'=>$this->summary($row)])->header('Cache-Control','private, no-store');
 }
 public function start(Request $r,$id) {
  $this->ready();$this->template();
  $record=$this->owner($r,$id);
  if($record->status==='awaiting_signatures')return $this->status($r,$id);
  abort_unless($record->status==='draft',422,'این پرونده قابل شروع تأیید نیست.');
  $a=$this->phone($record->from_phone);
  $b=$this->phone($record->to_phone);
  abort_if($a===$b,422,'شماره دو طرف باید متفاوت باشد.');
  abort_unless(trim((string)$record->handover_from)!==''&&trim((string)$record->handover_to)!==''
    &&in_array($record->user_role,['owner','tenant'],true),422,
    'نقش کاربر و نام هر دو طرف را کامل کنید.');
  $items=json_decode($record->items?:'{}',true)?:[];
  $checked=count(array_filter($items,function($i){return in_array($i['status']??'pending',['ok','issue','na'],true);}));
  abort_if($checked<1,422,'پیش از ارسال کد تأیید، وضعیت چک‌لیست را ثبت کنید.');
  DB::transaction(function()use($record,$a,$b) {
   $row=DB::table('spad_handover_checklists')->where('id',$record->id)->lockForUpdate()->first();
   abort_unless($row&&$row->status==='draft',409,'پرونده پیش‌تر وارد مرحله تأیید شده است.');
   $photos=DB::table('spad_handover_photos')->where('checklist_id',$row->id)
    ->orderBy('id')->get(['id','item_key','bytes','mime']);
   $snapshot=[
    'id'=>$row->id,'type'=>$row->type,'user_role'=>$row->user_role,
    'title'=>$row->title,'address'=>$row->address,'unit'=>$row->unit,
    'contract_reference'=>$row->contract_reference,
    'handover_from'=>$row->handover_from,'handover_to'=>$row->handover_to,
    'from_phone'=>$a,'to_phone'=>$b,'handover_date'=>$row->handover_date,
    'handover_note'=>$row->handover_note,'details'=>$row->details,
    'items'=>$row->items,'photos'=>$photos->toArray()
   ];
   $digest=hash('sha256',json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   foreach(['from'=>$a,'to'=>$b] as $party=>$mobile) {
    $token=bin2hex(random_bytes(32));
    DB::table('spad_handover_signatures')->insert([
     'checklist_id'=>$row->id,'party'=>$party,'mobile'=>$mobile,
     'invitation_hash'=>hash('sha256',$token),
     'invitation_cipher'=>Crypt::encryptString($token),
     'created_at'=>now(),'updated_at'=>now()
    ]);
   }
   DB::table('spad_handover_checklists')->where('id',$row->id)->update([
    'from_phone'=>$a,'to_phone'=>$b,'status'=>'awaiting_signatures',
    'snapshot_hash'=>$digest,'signature_started_at'=>now(),'updated_at'=>now()
   ]);
  });
  return $this->status($r,$id);
 }
 private function sendCode($signature) {
  $this->template();
  if($signature->verified_at)abort(422,'این طرف قبلاً تأیید کرده است.');
  abort_if($signature->send_count>=5,429,'سقف ارسال کد برای این پرونده تکمیل شده است.');
  if($signature->last_sent_at)abort_if(now()->diffInSeconds($signature->last_sent_at)<90,429,
    'برای درخواست کد جدید کمی بعد تلاش کنید.');
  $otp=(string)random_int(100000,999999);
  // Do not expose the OTP in responses, logs or the database.
  $param=config('sms.handover_parameter','code');
  $template=$this->template();
  $result=sms()->send('hi',function($sms)use($signature,$template,$param,$otp){
   $sms->to([$signature->mobile]);
   $sms->template($template,[$param=>$otp]);
  });
  abort_if($result===false,503,'ارسال پیامک از طرف سرویس‌دهنده تأیید نشد.');
  DB::table('spad_handover_signatures')->where('id',$signature->id)
    ->whereNull('verified_at')->update([
     'otp_hash'=>Hash::make($otp),'expires_at'=>now()->addMinutes(10),
     'attempts'=>0,'send_count'=>DB::raw('send_count + 1'),
     'last_sent_at'=>now(),'updated_at'=>now()
    ]);
  return response()->json(['message'=>'کد یک‌بارمصرف با اعتبار ده دقیقه درخواست شد.',
   'mobile_masked'=>$this->mask($signature->mobile)]);
 }
 public function send(Request $r,$id,$party) {
  $this->ready();abort_unless(in_array($party,['from','to'],true),404);
  $row=$this->owner($r,$id);
  abort_unless($row->status==='awaiting_signatures',422,'پرونده در مرحله تأیید نیست.');
  $signature=DB::table('spad_handover_signatures')
    ->where('checklist_id',$row->id)->where('party',$party)->first();
  abort_unless($signature,404);
  return $this->sendCode($signature);
 }
 private function invitation($token) {
  abort_unless(is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token),404);
  $signature=DB::table('spad_handover_signatures')
   ->where('invitation_hash',hash('sha256',$token))->first();
  abort_unless($signature,404,'دعوت‌نامه پیدا نشد.');
  $row=DB::table('spad_handover_checklists')->where('id',$signature->checklist_id)->first();
  abort_unless($row,404);
  return [$signature,$row];
 }
 public function invitationInfo($token) {
  $this->ready();list($sig,$row)=$this->invitation($token);
  return response()->json(['data'=>[
   'title'=>$row->title,'type'=>$row->type,'party'=>$sig->party,
   'mobile_masked'=>$this->mask($sig->mobile),
   'verified_at'=>$sig->verified_at,'status'=>$row->status,
   'expires_at'=>$sig->expires_at,'snapshot_hash'=>$row->snapshot_hash,
  ]])->header('Cache-Control','no-store')->header('X-Robots-Tag','noindex, nofollow');
 }
 public function publicSend(Request $r,$token) {
  $this->ready();list($sig,$row)=$this->invitation($token);
  abort_unless($row->status==='awaiting_signatures',422,'پرونده دیگر در انتظار تأیید نیست.');
  return $this->sendCode($sig);
 }
 public function verify(Request $r,$token) {
  $this->ready();
  $data=$r->validate(['code'=>'required|string|regex:/^\d{6}$/D',
    'consent'=>'required|accepted']);
  list($sig,$row)=$this->invitation($token);
  abort_unless($row->status==='awaiting_signatures',422,'فرایند تأیید این پرونده بسته است.');
  abort_if($sig->verified_at,422,'این طرف پیش‌تر تأیید کرده است.');
  abort_if($sig->attempts>=5,429,'تعداد تلاش‌های تأیید بیش از حد مجاز است.');
  abort_unless($sig->otp_hash&&$sig->expires_at&&now()->lessThanOrEqualTo($sig->expires_at),
    422,'کد منقضی شده است؛ درخواست کد جدید ثبت کنید.');
  if(!Hash::check($data['code'],$sig->otp_hash)) {
   DB::table('spad_handover_signatures')->where('id',$sig->id)
    ->whereNull('verified_at')->increment('attempts');
   throw ValidationException::withMessages(['code'=>['کد تأیید صحیح نیست.']]);
  }
  DB::transaction(function()use($sig,$row,$r){
   $locked=DB::table('spad_handover_checklists')->where('id',$row->id)->lockForUpdate()->first();
   abort_unless($locked&&$locked->status==='awaiting_signatures',409,'وضعیت پرونده تغییر کرده است.');
   DB::table('spad_handover_signatures')->where('id',$sig->id)->whereNull('verified_at')
    ->update(['verified_at'=>now(),'verification_ip'=>substr((string)$r->ip(),0,45),
      'otp_hash'=>null,'expires_at'=>null,'updated_at'=>now()]);
   $count=DB::table('spad_handover_signatures')->where('checklist_id',$row->id)
    ->whereNotNull('verified_at')->count();
   if($count===2)DB::table('spad_handover_checklists')->where('id',$row->id)
    ->update(['status'=>'completed','completed_at'=>now(),'updated_at'=>now()]);
  });
  return response()->json(['message'=>'تأیید پیامکی این طرف ثبت شد.',
    'data'=>['status'=>DB::table('spad_handover_checklists')->where('id',$row->id)->value('status')]]);
 }
}
