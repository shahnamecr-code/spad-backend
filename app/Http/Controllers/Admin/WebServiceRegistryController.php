<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class WebServiceRegistryController extends Controller
{
 private const CATEGORIES = ['sms','identity','bills','inquiries','market','property','other'];
 private const PRESETS = [
  ['code'=>'sms-verification','title'=>'پیامک تأیید هویت و ورود','category'=>'sms','provider'=>'SMS.ir','description'=>'الگوهای کد ورود، احراز هویت و تأیید تحویل ملک'],
  ['code'=>'shahkar','title'=>'تطبیق شاهکار','category'=>'identity','provider'=>'NextPay / Shahkar','description'=>'تطبیق کد ملی و مالکیت شماره همراه'],
  ['code'=>'utility-bills','title'=>'استعلام قبوض خدماتی','category'=>'bills','provider'=>null,'description'=>'آب، برق، گاز، تلفن و تسویه قبوض'],
  ['code'=>'property-inquiries','title'=>'استعلامات ملکی','category'=>'inquiries','provider'=>null,'description'=>'سند، کدپستی، اصالت مدارک و سایر استعلامات مجاز'],
  ['code'=>'gold-currency','title'=>'قیمت ارز، طلا و سکه','category'=>'market','provider'=>null,'description'=>'دریافت نرخ معتبر بازار و مدیریت منابع جایگزین'],
  ['code'=>'materials','title'=>'قیمت مصالح ساختمانی','category'=>'market','provider'=>null,'description'=>'تأمین داده‌های قیمت و تغییرات مصالح'],
  ['code'=>'postal','title'=>'استعلام کد پستی و نشانی','category'=>'property','provider'=>null,'description'=>'تطبیق نشانی ملک با داده‌های مجاز و رسمی'],
  ['code'=>'valuation','title'=>'ارزش‌گذاری هوشمند ملک','category'=>'property','provider'=>null,'description'=>'تأمین داده‌های مدل قیمت‌گذاری و اعتبارسنجی'],
  ['code'=>'banking','title'=>'بانک و خدمات وام مسکن','category'=>'inquiries','provider'=>null,'description'=>'استعلام شرایط تسهیلات، اقساط و نرخ اوراق']
 ];
 private function guard(Request $request)
 {
  abort_unless($request->user() && $request->user()->roles()->where('name','Super Admin')->exists(),403,'فقط مدیر اصلی مجاز است.');
  abort_unless(Schema::hasTable('spad_web_service_connections') && Schema::hasTable('spad_web_service_audit'),503,'مرکز وب‌سرویس‌ها هنوز نصب نشده است.');
 }
 private function audit(Request $request,$id,$action,$note='')
 {
  DB::table('spad_web_service_audit')->insert([
   'connection_id'=>$id,'admin_id'=>$request->user()->id,
   'action'=>$action,'note'=>$note,'created_at'=>now(),'updated_at'=>now()
  ]);
 }
 private function expose($row)
 {
  return [
   'id'=>$row->id,'code'=>$row->code,'title'=>$row->title,'category'=>$row->category,
   'provider'=>$row->provider,'endpoint'=>$row->endpoint,'description'=>$row->description,
   'enabled'=>(bool)$row->enabled,'mode'=>$row->mode,
   'health_status'=>$row->health_status,'last_note'=>$row->last_note,
   'last_checked_at'=>$row->last_checked_at,'updated_at'=>$row->updated_at,
   'has_secret'=>!empty($row->secrets_encrypted),
   'config_public'=>json_decode($row->config_public?:'{}',true)?:[]
  ];
 }
 private function environment()
 {
  return [
   'sms_login'=>(bool)config('sms.login_template'),
   'sms_kyc'=>(bool)config('sms.kyc_template'),
   'sms_handover'=>(bool)config('sms.handover_template'),
   'shahkar'=>(bool)config('services.spad_shahkar.key')
  ];
 }
 public function index(Request $request)
 {
  $this->guard($request);
  $rows=DB::table('spad_web_service_connections')->orderBy('category')->orderBy('id')->get();
  $byCode=$rows->keyBy('code');
  $data=[];
  foreach(self::PRESETS as $preset) {
   $existing=$byCode->get($preset['code']);
   if($existing) {
    $data[]=$this->expose($existing);
    $byCode->forget($preset['code']);
   } else $data[]=array_merge($preset,[
    'id'=>null,'endpoint'=>null,'enabled'=>false,'mode'=>'planned',
    'health_status'=>'not_tested','last_note'=>null,'last_checked_at'=>null,
    'updated_at'=>null,'has_secret'=>false,'config_public'=>[]
   ]);
  }
  foreach($byCode as $r)$data[]=$this->expose($r);
  return response()->json([
   'data'=>$data,
   'environment'=>$this->environment(),
   'notice'=>'اطلاعات API موجود از تنظیمات امن سرور خوانده می‌شود. ثبت کلید در این مرکز بدون اتصال آداپتر اختصاصی، به‌معنای فعال‌شدن وب‌سرویس یا تأیید سلامت آن نیست.'
  ])->header('Cache-Control','private, no-store');
 }
 private function validatePayload(Request $request,$new=false)
 {
  $fields=[
   'title'=>'required|string|min:3|max:160',
   'category'=>['required',Rule::in(self::CATEGORIES)],
   'provider'=>'nullable|string|max:120',
   'endpoint'=>'nullable|string|max:500|regex:/^https:\/\//i',
   'description'=>'nullable|string|max:1200',
   'enabled'=>'required|boolean',
   'mode'=>['required',Rule::in(['planned','configured'])],
   'config_public'=>'nullable|array|max:12',
   'config_public.*'=>'nullable|string|max:250',
   'secret'=>'nullable|string|max:5000'
  ];
  if($new)$fields['code']='required|alpha_dash|max:80|unique:spad_web_service_connections,code';
  return $request->validate($fields);
 }
 private function payload($validated,$request,$old=null)
 {
  $config=[];
  foreach($validated['config_public']??[] as $key=>$value) {
   if(!is_string($key)||!preg_match('/^[a-z][a-z0-9_]{0,39}$/D',$key)
    ||preg_match('/key|secret|token|password|authorization|credential/i',$key))abort(422,'تنظیمات محرمانه را فقط در فیلد کلید امن ثبت کنید.');
   $config[$key]=$value;
  }
  $row=[
   'title'=>$validated['title'],'category'=>$validated['category'],
   'provider'=>$validated['provider']??null,'endpoint'=>$validated['endpoint']??null,
   'description'=>$validated['description']??null,'enabled'=>(bool)$validated['enabled'],
   'mode'=>$validated['mode'],
   'config_public'=>json_encode($config,JSON_UNESCAPED_UNICODE),
   'updated_by'=>$request->user()->id,'updated_at'=>now()
  ];
  if(!empty($validated['secret']))$row['secrets_encrypted']=Crypt::encryptString($validated['secret']);
  if(!$old) {
   $row['code']=$validated['code'];
   $row['created_at']=now();
  }
  return $row;
 }
 public function upsert(Request $request)
 {
  $this->guard($request);
  $data=$this->validatePayload($request,true);
  abort_if(DB::table('spad_web_service_connections')->count()>=80,422,'حداکثر هشتاد وب‌سرویس قابل ثبت است.');
  $row=$this->payload($data,$request);
  $id=DB::table('spad_web_service_connections')->insertGetId($row);
  $this->audit($request,$id,'created');
  return response()->json(['data'=>$this->expose(DB::table('spad_web_service_connections')->where('id',$id)->first())],201);
 }
 public function update(Request $request,$id)
 {
  $this->guard($request);
  $old=DB::table('spad_web_service_connections')->where('id',$id)->first();
  abort_unless($old,404);
  $data=$this->validatePayload($request);
  DB::table('spad_web_service_connections')->where('id',$id)->update($this->payload($data,$request,$old));
  $this->audit($request,$id,'updated',!empty($data['secret'])?'کلید محرمانه چرخش یافت.':'تنظیمات عمومی تغییر یافت.');
  return response()->json(['data'=>$this->expose(DB::table('spad_web_service_connections')->where('id',$id)->first())]);
 }
 public function check(Request $request,$id)
 {
  $this->guard($request);
  $row=DB::table('spad_web_service_connections')->where('id',$id)->first();
  abort_unless($row,404);
  // Configuration audit only: never send stored credentials to arbitrary URLs.
  // Real provider health checks require a vetted, provider-specific adapter.
  $ready=$row->enabled && $row->mode==='configured' && !empty($row->provider)
    && (!empty($row->endpoint)||!empty($row->secrets_encrypted));
  $status=$ready?'configuration_ready':'incomplete';
  $note=$ready?'پارامترهای اولیه ثبت شده‌اند؛ پاسخ وب‌سرویس آزمایش نشده است.':'تنظیمات اولیه ناقص یا غیرفعال است.';
  DB::table('spad_web_service_connections')->where('id',$id)->update([
   'health_status'=>$status,'last_note'=>$note,'last_checked_at'=>now(),'updated_at'=>now()
  ]);
  $this->audit($request,$id,'configuration_checked',$status);
  return response()->json(['data'=>$this->expose(DB::table('spad_web_service_connections')->where('id',$id)->first())]);
 }
 public function destroy(Request $request,$id)
 {
  $this->guard($request);
  $row=DB::table('spad_web_service_connections')->where('id',$id)->first();
  abort_unless($row,404);
  // Keep an audit record but erase encrypted credentials and config.
  DB::table('spad_web_service_connections')->where('id',$id)->delete();
  $this->audit($request,$id,'deleted');
  return response()->json(['message'=>'تنظیمات سرویس حذف شد؛ اتصال موجود سرور تغییری نکرده است.']);
 }
}
