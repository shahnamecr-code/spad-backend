<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PropertyAffairsController extends Controller {
 private function available(){
  abort_unless(Schema::hasTable('spad_property_affair_services')&&Schema::hasTable('spad_property_affair_requests'),503,'مرکز امور ملکی در حال راه‌اندازی است.');
 }
 private function admin(){abort_unless(auth('admin')->check(),403,'دسترسی مدیریت لازم است.');}
 private function service($slug,$published=true){
  $query=DB::table('spad_property_affair_services')->where('slug',$slug);
  if($published)$query->where('enabled',1);
  $s=$query->first();abort_unless($s,404,'این خدمت در دسترس نیست.');
  return $s;
 }
 private function format($row){
  $row->form_schema=json_decode($row->form_schema?:'[]',true)?:[];
  $row->price_toman=(int)$row->price_toman;
  $row->enabled=(bool)$row->enabled;
  $row->accepting_requests=(bool)$row->accepting_requests;
  $row->featured=(bool)$row->featured;
  return $row;
 }
 public function services(){
  $this->available();
  $list=DB::table('spad_property_affair_services')->where('enabled',1)
   ->orderBy('sort_order')->orderBy('id')->get()->map(function($s){return $this->format($s);});
  return response()->json(['data'=>$list]);
 }
 public function show($slug){$this->available();return response()->json(['data'=>$this->format($this->service($slug))]);}
 private function user(Request $r){
  $u=$r->user();abort_unless($u&&$u->id,401,'ابتدا وارد حساب اسپاد شوید.');
  return (int)$u->id;
 }
 public function submit(Request $r,$slug){
  $this->available();$uid=$this->user($r);$s=$this->service($slug);
  abort_unless($s->accepting_requests,422,'پذیرش درخواست برای این خدمت موقتاً بسته است.');
  $d=$r->validate(['fields'=>'required|array','accepted_terms'=>'required|accepted']);
  $schema=json_decode($s->form_schema?:'[]',true)?:[];
  $rules=[];$data=[];$errors=[];
  foreach($schema as $field){
   $key=$field['key']??'';if(!preg_match('/^[a-z][a-z0-9_]{1,39}$/D',$key))continue;
   $type=$field['type']??'text';
   $value=$d['fields'][$key]??null;
   $required=!empty($field['required']);
   if($required&&(!is_scalar($value)||trim((string)$value)==='')){$errors['fields.'.$key]=['این فیلد الزامی است.'];continue;}
   if($value===null||$value==='')continue;
   if(!is_string($value)&&!is_numeric($value)){$errors['fields.'.$key]=['مقدار فیلد نامعتبر است.'];continue;}
   $value=trim((string)$value);
   $max=$type==='textarea'?2000:300;
   if(mb_strlen($value)>$max){$errors['fields.'.$key]=['متن طولانی‌تر از حد مجاز است.'];continue;}
   if($type==='select'&&!in_array($value,$field['options']??[],true)){$errors['fields.'.$key]=['گزینه انتخاب‌شده معتبر نیست.'];continue;}
   if($type==='date'&&!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value)){$errors['fields.'.$key]=['تاریخ نامعتبر است.'];continue;}
   if($type==='number'&&!preg_match('/^\d{1,13}$/D',$value)){$errors['fields.'.$key]=['عدد نامعتبر است.'];continue;}
   if($key==='postal_code'&&!preg_match('/^[0-9]{10}$/D',$value)){$errors['fields.'.$key]=['کد پستی باید ۱۰ رقم باشد.'];continue;}
   $data[$key]=$value;
  }
  if(count($errors))throw ValidationException::withMessages($errors);
  $tracking='SPA-'.strtoupper(bin2hex(random_bytes(6)));
  $id=DB::transaction(function()use($uid,$s,$data,$tracking){
   $id=DB::table('spad_property_affair_requests')->insertGetId([
    'service_id'=>$s->id,'user_id'=>$uid,'tracking_code'=>$tracking,
    'form_data'=>json_encode($data,JSON_UNESCAPED_UNICODE),
    'status'=>'submitted','created_at'=>now(),'updated_at'=>now()
   ]);
   DB::table('spad_property_affair_events')->insert([
    'request_id'=>$id,'status'=>'submitted','actor_type'=>'user','actor_id'=>$uid,
    'note'=>'درخواست اولیه کاربر در اسپاد ثبت شد؛ هیچ ثبت‌نام رسمی بیرونی انجام نشده است.',
    'created_at'=>now(),'updated_at'=>now()
   ]);
   return $id;
  });
  return response()->json(['message'=>'درخواست شما در اسپاد ثبت شد و در صف بررسی است؛ ثبت‌نام در سامانه دولتی هنوز انجام نشده است.',
   'data'=>['id'=>$id,'tracking_code'=>$tracking,'status'=>'submitted']],201);
 }
 public function mine(Request $r){
  $this->available();$uid=$this->user($r);
  $rows=DB::table('spad_property_affair_requests as q')->join('spad_property_affair_services as s','s.id','=','q.service_id')
    ->where('q.user_id',$uid)->orderByDesc('q.id')->limit(120)
    ->get(['q.id','q.tracking_code','q.status','q.public_note','q.quoted_price_toman','q.created_at','q.updated_at','q.completed_at',
      's.title as service_title','s.slug as service_slug']);
  return response()->json(['data'=>$rows]);
 }
 private function authorizedRequest(Request $r,$id){
  $q=DB::table('spad_property_affair_requests')->where('id',$id)->first();
  abort_unless($q,404);
  abort_unless((int)$q->user_id===$this->user($r),403,'دسترسی به این درخواست مجاز نیست.');
  return $q;
 }
 public function myRequest(Request $r,$id){
  $this->available();$q=$this->authorizedRequest($r,$id);
  $q->form_data=json_decode($q->form_data,true)?:[];
  unset($q->admin_note);
  $q->documents=DB::table('spad_property_affair_documents')->where('request_id',$id)
    ->get(['id','original_name','mime','file_size','created_at']);
  $q->events=DB::table('spad_property_affair_events')->where('request_id',$id)
    ->where('actor_type','admin')->whereNotNull('note')->get(['status','note','created_at']);
  return response()->json(['data'=>$q]);
 }
 public function upload(Request $r,$id){
  $this->available();$q=$this->authorizedRequest($r,$id);
  abort_if(in_array($q->status,['completed','rejected','cancelled'],true),422,'برای درخواست بسته امکان بارگذاری وجود ندارد.');
  $d=$r->validate(['document'=>'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);
  abort_if(DB::table('spad_property_affair_documents')->where('request_id',$id)->count()>=5,422,'حداکثر پنج مدرک برای هر درخواست مجاز است.');
  $file=$d['document'];
  $path=$file->store('private/property-affairs/'.$q->id,'local');
  abort_unless($path,503,'ذخیره امن مدرک ممکن نشد.');
  try{$documentId=DB::table('spad_property_affair_documents')->insertGetId([
    'request_id'=>$q->id,'user_id'=>$q->user_id,'file_path'=>$path,
    'original_name'=>mb_substr($file->getClientOriginalName(),0,180),
    'mime'=>$file->getMimeType(),'file_size'=>$file->getSize(),
    'created_at'=>now(),'updated_at'=>now()
  ]);}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
  return response()->json(['message'=>'مدرک در فضای خصوصی اسپاد ذخیره شد.','data'=>['id'=>$documentId]],201);
 }
 public function adminServices(){
  $this->available();$this->admin();
  return response()->json(['data'=>DB::table('spad_property_affair_services')->orderBy('sort_order')->get()->map(function($s){return $this->format($s);})]);
 }
 private function servicePayload(Request $r){
  $d=$r->validate([
   'slug'=>'required|string|regex:/^[a-z][a-z0-9-]{2,89}$/D|max:90',
   'title'=>'required|string|min:4|max:170','subtitle'=>'nullable|string|max:300',
   'category'=>'required|string|max:70','icon'=>'nullable|string|max:30',
   'description'=>'nullable|string|max:4000','requirements'=>'nullable|string|max:2500',
   'official_url'=>'nullable|url|regex:/^https:\/\//i|max:400',
   'price_toman'=>'nullable|integer|min:0|max:1000000000',
   'enabled'=>'required|boolean','accepting_requests'=>'required|boolean',
   'featured'=>'required|boolean','sort_order'=>'required|integer|min:0|max:999',
   'form_schema'=>'required|array|min:1|max:15'
  ]);
  $keys=[];
  foreach($d['form_schema'] as $field){
   if(!is_array($field)||!preg_match('/^[a-z][a-z0-9_]{1,39}$/D',$field['key']??'')||
    !in_array($field['type']??'', ['text','textarea','select','date','number'],true)||
    !is_string($field['label']??null)||mb_strlen($field['label'])>100||!$field['label'])
    throw ValidationException::withMessages(['form_schema'=>'ساختار فیلدها معتبر نیست.']);
   if(in_array($field['key'],$keys,true))throw ValidationException::withMessages(['form_schema'=>'کلید فیلدها نباید تکراری باشد.']);
   $keys[]=$field['key'];
   if(($field['type']??'')==='select'&&(!is_array($field['options']??null)||count($field['options'])>30||
      !count($field['options'])||count(array_filter($field['options'],'is_string'))!==count($field['options'])))
    throw ValidationException::withMessages(['form_schema'=>'گزینه‌های انتخابی باید آرایه رشته باشند.']);
  }
  $d['form_schema']=json_encode($d['form_schema'],JSON_UNESCAPED_UNICODE);
  $d['price_toman']=$d['price_toman']??0;
  $d['icon']=$d['icon']??'home';
  return $d;
 }
 public function adminCreateService(Request $r){
  $this->available();$this->admin();$d=$this->servicePayload($r);
  $exists=DB::table('spad_property_affair_services')->where('slug',$d['slug'])->exists();
  abort_if($exists,422,'شناسه این خدمت قبلاً ثبت شده است.');
  $id=DB::table('spad_property_affair_services')->insertGetId(array_merge($d,['created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'خدمت جدید اضافه شد.','data'=>['id'=>$id]],201);
 }
 public function adminUpdateService(Request $r,$id){
  $this->available();$this->admin();$d=$this->servicePayload($r);
  $q=DB::table('spad_property_affair_services')->where('id',$id);
  abort_unless((clone $q)->exists(),404);
  abort_if(DB::table('spad_property_affair_services')->where('slug',$d['slug'])->where('id','!=',$id)->exists(),422,'شناسه خدمت تکراری است.');
  $q->update(array_merge($d,['updated_at'=>now()]));
  return response()->json(['message'=>'تنظیمات خدمت ذخیره شد.']);
 }
 public function adminRequests(Request $r){
  $this->available();$this->admin();
  $q=DB::table('spad_property_affair_requests as q')
   ->join('spad_property_affair_services as s','s.id','=','q.service_id')
   ->leftJoin('users as u','u.id','=','q.user_id')
   ->select('q.*','s.title as service_title','s.slug as service_slug','u.first_name','u.last_name','u.mobile');
  $status=$r->query('status');if($status&&in_array($status,['submitted','in_review','waiting_user','completed','rejected'],true))$q->where('q.status',$status);
  $rows=$q->orderByDesc('q.id')->limit(250)->get()->map(function($v){
   $v->form_data=json_decode($v->form_data,true)?:[];
   $v->document_count=DB::table('spad_property_affair_documents')->where('request_id',$v->id)->count();
   return $v;
  });
  return response()->json(['data'=>$rows]);
 }
 public function adminRequest(Request $r,$id){
  $this->available();$this->admin();
  $q=DB::table('spad_property_affair_requests')->where('id',$id)->first();abort_unless($q,404);
  $q->form_data=json_decode($q->form_data,true)?:[];
  $q->documents=DB::table('spad_property_affair_documents')->where('request_id',$id)
   ->get(['id','original_name','mime','file_size','created_at']);
  $q->events=DB::table('spad_property_affair_events')->where('request_id',$id)->orderByDesc('id')->limit(30)->get();
  return response()->json(['data'=>$q]);
 }
 public function adminReview(Request $r,$id){
  $this->available();$this->admin();
  $d=$r->validate(['status'=>'required|in:submitted,in_review,waiting_user,completed,rejected',
   'public_note'=>'nullable|string|max:2500','admin_note'=>'nullable|string|max:3000',
   'quoted_price_toman'=>'nullable|integer|min:0|max:1000000000']);
  DB::transaction(function()use($d,$id){
   $q=DB::table('spad_property_affair_requests')->where('id',$id)->lockForUpdate()->first();abort_unless($q,404);
   DB::table('spad_property_affair_requests')->where('id',$id)->update(array_merge($d,[
    'completed_at'=>$d['status']==='completed'?now():null,'updated_at'=>now()
   ]));
   DB::table('spad_property_affair_events')->insert([
    'request_id'=>$id,'status'=>$d['status'],'actor_type'=>'admin','actor_id'=>auth('admin')->id(),
    'note'=>$d['public_note']??null,'created_at'=>now(),'updated_at'=>now()
   ]);
  });
  return response()->json(['message'=>'وضعیت درخواست و یادداشت کارشناسی ثبت شد.']);
 }
 public function adminDocument($id,$documentId){
  $this->available();$this->admin();
  $f=DB::table('spad_property_affair_documents')->where('request_id',$id)->where('id',$documentId)->first();
  abort_unless($f&&Storage::disk('local')->exists($f->file_path),404);
  return Storage::disk('local')->download($f->file_path,$f->original_name,[
   'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff'
  ]);
 }
}
