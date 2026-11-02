<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadCrmDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpadCrmProController extends Controller {
 const C='spad_crm_contacts';
 const STAGES=['new','contacted','qualified','visit','negotiation','won','lost'];
 const INTERESTS=['buy','sell','rent','invest','other'];
 private function guard(Request $request){
  abort_unless($request->user() && $request->user()->roles()->where('name','Super Admin')->exists(),403,'فقط مدیریت اصلی اجازه دسترسی به CRM را دارد.');
  abort_unless(Schema::hasTable(self::C),503,'بخش CRM هنوز نصب نشده است.');
 }
 private function normalize($v){
  $v=strtr(trim((string)$v),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $v=preg_replace('/[\s\-]/','',$v);
  if(strpos($v,'+98')===0)$v='0'.substr($v,3);
  if(strpos($v,'98')===0 && strlen($v)===12)$v='0'.substr($v,2);
  return $v;
 }
 private function audit($id,$admin,$type,$body=null,$extra=[]){
  DB::table('spad_crm_activities')->insert([
   'contact_id'=>$id,'admin_id'=>$admin,'type'=>$type,
   'content'=>$body,'metadata'=>json_encode($extra,JSON_UNESCAPED_UNICODE),
   'created_at'=>now(),'updated_at'=>now()
  ]);
 }
 private function row($r){
  return [
   'id'=>$r->id,'full_name'=>$r->full_name,'mobile'=>$r->mobile,'email'=>$r->email,
   'stage'=>$r->stage,'interest'=>$r->interest,'source'=>$r->source,'city'=>$r->city,
   'budget'=>$r->budget,'preferred_location'=>$r->preferred_location,
   'priority'=>$r->priority,'assigned_to'=>$r->assigned_to,'note'=>$r->note,
   'sms_consent'=>(bool)$r->sms_consent,'voice_consent'=>(bool)$r->voice_consent,
   'consent_recorded_at'=>$r->consent_recorded_at,'consent_source'=>$r->consent_source,
   'opted_out_at'=>$r->opted_out_at,'last_contact_at'=>$r->last_contact_at,'created_at'=>$r->created_at
  ];
 }
 public function overview(Request $request){
  $this->guard($request);
  $stage=DB::table(self::C)->whereNull('deleted_at')
   ->select('stage',DB::raw('COUNT(*) as total'))->groupBy('stage')->pluck('total','stage');
  $tasks=DB::table('spad_crm_followups')->where('status','pending')->where('due_at','<=',now())->count();
  $today=DB::table('spad_crm_outbound')->whereDate('created_at',today())->count();
  return response()->json(['data'=>[
   'total'=>DB::table(self::C)->whereNull('deleted_at')->count(),
   'stage'=>$stage,'overdue'=>$tasks,'outbound_today'=>$today,
   'providers'=>SpadCrmDeliveryService::readiness()
  ]])->header('Cache-Control','private, no-store');
 }
 public function index(Request $request){
  $this->guard($request);
  $data=$request->validate([
   'q'=>'nullable|string|max:100','stage'=>['nullable',Rule::in(self::STAGES)],
   'page'=>'nullable|integer|min:1|max:100000','per_page'=>'nullable|integer|min:5|max:50'
  ]);
  $q=DB::table(self::C)->whereNull('deleted_at');
  if(!empty($data['q'])){$term=str_replace(['%','_'],['\\%','\\_'],trim($data['q']));
   $q->where(function($w)use($term){$w->where('full_name','like',"%$term%")->orWhere('mobile','like',"%$term%")->orWhere('city','like',"%$term%");});
  }
  if(!empty($data['stage']))$q->where('stage',$data['stage']);
  $records=$q->orderByDesc('updated_at')->paginate($data['per_page']??15);
  return response()->json(['data'=>array_map([$this,'row'],$records->items()),'total'=>$records->total(),'last_page'=>$records->lastPage(),'current_page'=>$records->currentPage()])
   ->header('Cache-Control','private, no-store');
 }
 private function fields(Request $request,$creating=false){
  return $request->validate([
   'full_name'=>'required|string|min:2|max:150','mobile'=>'required|string|max:24',
   'email'=>'nullable|email|max:190','stage'=>['required',Rule::in(self::STAGES)],
   'interest'=>['required',Rule::in(self::INTERESTS)],
   'source'=>'nullable|string|max:50','city'=>'nullable|string|max:90',
   'budget'=>'nullable|string|max:90','preferred_location'=>'nullable|string|max:170',
   'note'=>'nullable|string|max:3000','priority'=>'required|integer|min:1|max:3',
   'sms_consent'=>'required|boolean','voice_consent'=>'required|boolean',
   'consent_source'=>'nullable|string|max:120'
  ]);
 }
 public function store(Request $request){
  $this->guard($request);$data=$this->fields($request,true);$data['mobile']=$this->normalize($data['mobile']);
  abort_unless(preg_match('/^09[0-9]{9}$/D',$data['mobile']),422,'شماره همراه باید معتبر باشد.');
  if(!empty($data['sms_consent'])||!empty($data['voice_consent']))
   abort_unless(mb_strlen(trim($data['consent_source']??''))>=8,422,'مدرک یا منبع رضایت مشتری را با جزئیات وارد کنید.');
  abort_if(DB::table(self::C)->where('mobile',$data['mobile'])->exists(),422,'این شماره قبلاً ثبت شده است.');
  $data['created_by']=$request->user()->id;
  $data['assigned_to']=$request->user()->id;
  $data['consent_recorded_at']=($data['sms_consent']||$data['voice_consent'])?now():null;
  $data['created_at']=now();$data['updated_at']=now();
  $id=DB::table(self::C)->insertGetId($data);
  $this->audit($id,$request->user()->id,'created','پرونده مشتری ثبت شد.');
  return response()->json(['data'=>$this->row(DB::table(self::C)->where('id',$id)->first())],201);
 }
 public function show(Request $request,$id){
  $this->guard($request);
  $r=DB::table(self::C)->where('id',$id)->whereNull('deleted_at')->first();abort_unless($r,404);
  return response()->json(['data'=>$this->row($r),
   'activities'=>DB::table('spad_crm_activities')->where('contact_id',$id)->orderByDesc('id')->limit(60)->get(),
   'tasks'=>DB::table('spad_crm_followups')->where('contact_id',$id)->orderBy('due_at')->limit(60)->get(),
   'outbound'=>DB::table('spad_crm_outbound')->where('contact_id',$id)->orderByDesc('id')->limit(30)->get(),
  ])->header('Cache-Control','private, no-store');
 }
 public function update(Request $request,$id){
  $this->guard($request);
  $old=DB::table(self::C)->where('id',$id)->whereNull('deleted_at')->first();abort_unless($old,404);
  $data=$this->fields($request);$data['mobile']=$this->normalize($data['mobile']);
  abort_unless(preg_match('/^09[0-9]{9}$/D',$data['mobile']),422,'شماره همراه باید معتبر باشد.');
  abort_if(DB::table(self::C)->where('mobile',$data['mobile'])->where('id','!=',$id)->exists(),422,'این شماره به مشتری دیگری تعلق دارد.');
  if(($data['sms_consent']||$data['voice_consent'])){
   abort_unless(mb_strlen(trim($data['consent_source']??''))>=8,422,'منبع رضایت باید مشخص باشد.');
   abort_if($old->opted_out_at,422,'این مخاطب لغو عضویت کرده؛ رفع لغو فقط پس از ثبت رضایت مجدد از مسیر جداگانه مجاز است.');
  }
  if(($old->sms_consent!=$data['sms_consent'])||($old->voice_consent!=$data['voice_consent']))
   $data['consent_recorded_at']=($data['sms_consent']||$data['voice_consent'])?now():null;
  $data['updated_at']=now();
  DB::table(self::C)->where('id',$id)->update($data);
  $this->audit($id,$request->user()->id,'updated','اطلاعات مشتری و تنظیمات ارتباط به‌روزرسانی شد.');
  return response()->json(['data'=>$this->row(DB::table(self::C)->where('id',$id)->first())]);
 }
 public function optOut(Request $request,$id){
  $this->guard($request);
  $changed=DB::table(self::C)->where('id',$id)->whereNull('deleted_at')
   ->update(['sms_consent'=>false,'voice_consent'=>false,'opted_out_at'=>now(),'updated_at'=>now()]);
  abort_unless($changed,404);
  $this->audit($id,$request->user()->id,'opt_out','عدم تمایل به دریافت پیامک و تماس خودکار ثبت شد.');
  return response()->json(['message'=>'عدم تمایل مشتری ثبت شد. ارسال پیام و تماس مسدود است.']);
 }
 public function importCustomers(Request $request) {
  $this->guard($request);
  $data=$request->validate(['after_id'=>'nullable|integer|min:0','limit'=>'nullable|integer|min:1|max:100']);
  $after=(int)($data['after_id']??0);$limit=(int)($data['limit']??100);
  // Explicit, limited admin-initiated import; never infer marketing consent
  // or modify/delete any existing website user.
  $users=\App\Models\User::query()->select(['id','first_name','last_name','mobile'])
   ->where('id','>',$after)->whereNotNull('mobile')->orderBy('id')->limit($limit)->get();
  $created=0;$skipped=0;
  foreach($users as $user) {
   $mobile=$this->normalize($user->mobile);
   if(!preg_match('/^09[0-9]{9}$/D',$mobile)){$skipped++;continue;}
   if(DB::table(self::C)->where('mobile',$mobile)->exists()){$skipped++;continue;}
   $id=DB::table(self::C)->insertGetId([
    'site_user_id'=>$user->id,'full_name'=>trim($user->first_name.' '.$user->last_name)?:'کاربر اسپاد',
    'mobile'=>$mobile,'source'=>'spad_website','stage'=>'new','interest'=>'other',
    'priority'=>2,'assigned_to'=>$request->user()->id,'created_by'=>$request->user()->id,
    'sms_consent'=>false,'voice_consent'=>false,'created_at'=>now(),'updated_at'=>now()
   ]);
   $this->audit($id,$request->user()->id,'imported','پرونده کاربر سایت، بدون مجوز پیامک یا تماس، وارد CRM شد.');
   $created++;
  }
  return response()->json(['message'=>'واردسازی محدود انجام شد؛ رضایت ارتباطی به هیچ کاربری داده نشده است.',
   'created'=>$created,'skipped'=>$skipped,'next_cursor'=>$users->count()?$users->last()->id:$after,
   'has_more'=>$users->count()===$limit]);
 }
 public function note(Request $request,$id){
  $this->guard($request);
  abort_unless(DB::table(self::C)->where('id',$id)->whereNull('deleted_at')->exists(),404);
  $v=$request->validate(['content'=>'required|string|min:2|max:3000','type'=>['nullable',Rule::in(['note','meeting','call_log','visit','offer'])]]);
  $this->audit($id,$request->user()->id,$v['type']??'note',$v['content']);
  return response()->json(['message'=>'در پرونده ثبت شد.'],201);
 }
 public function addTask(Request $request,$id){
  $this->guard($request);
  abort_unless(DB::table(self::C)->where('id',$id)->whereNull('deleted_at')->exists(),404);
  $v=$request->validate(['title'=>'required|string|min:3|max:170','kind'=>['required',Rule::in(['call','visit','meeting','document','other'])],
  'due_at'=>'required|date|after:now','description'=>'nullable|string|max:1200']);
  $v['contact_id']=$id;$v['admin_id']=$request->user()->id;$v['status']='pending';
  $v['created_at']=now();$v['updated_at']=now();
  $task=DB::table('spad_crm_followups')->insertGetId($v);
  $this->audit($id,$request->user()->id,'task_created',$v['title'],['task_id'=>$task]);
  return response()->json(['message'=>'پیگیری ثبت شد.','id'=>$task],201);
 }
 public function finishTask(Request $request,$id,$taskId){
  $this->guard($request);
  $task=DB::table('spad_crm_followups')->where('id',$taskId)->where('contact_id',$id)->first();
  abort_unless($task,404);
  if($task->status==='pending'){
   DB::table('spad_crm_followups')->where('id',$taskId)->update(['status'=>'done','done_at'=>now(),'updated_at'=>now()]);
   $this->audit($id,$request->user()->id,'task_done',$task->title);
  }
  return response()->json(['message'=>'پیگیری انجام‌شده ثبت شد.']);
 }
 public function outbound(Request $request,$id){
  $this->guard($request);
  $v=$request->validate([
   'channel'=>['required',Rule::in(['sms','voice'])],
   'message'=>'required|string|min:10|max:650',
   'request_id'=>'required|uuid'
  ]);
  $channel=$v['channel'];$uid=$request->user()->id;
  $existing=DB::table('spad_crm_outbound')->where('request_id',$v['request_id'])->first();
  if($existing){
   abort_unless((int)$existing->contact_id===(int)$id && $existing->channel===$channel,409,'شناسه درخواست برای عملیات دیگری استفاده شده است.');
   return response()->json(['message'=>'این درخواست قبلاً ثبت شده؛ برای جلوگیری از تماس مجدد تکرار نمی‌شود.','outbound_id'=>$existing->id,'status'=>$existing->status]);
  }
  $ready=SpadCrmDeliveryService::readiness();
  abort_unless($ready[$channel],503,'وب‌سرویس این کانال هنوز فعال نشده است.');
  // One admin-confirmed contact; no bulk dispatch, no unattended campaigns.
  // Enforce service hours in Iran and a conservative daily quota.
  $clock=now()->setTimezone('Asia/Tehran');$hour=(int)$clock->format('G');
  abort_if($hour<9||$hour>=19,422,'ارسال ارتباط فقط بین ساعت ۹ تا ۱۹ به وقت ایران مجاز است.');
  $outboundId=DB::transaction(function()use($id,$v,$channel,$uid){
   $r=DB::table(self::C)->where('id',$id)->whereNull('deleted_at')->lockForUpdate()->first();
   abort_unless($r,404);
   abort_if($r->opted_out_at||!$r->{$channel.'_consent'}||!$r->consent_recorded_at,422,'مشتری برای این کانال رضایت معتبر ثبت نکرده است.');
   abort_unless(preg_match('/^09[0-9]{9}$/D',$r->mobile),422,'شماره مخاطب معتبر نیست.');
   // Safeguard against repeated clicks: cannot attempt same text twice in 24h.
   abort_if(DB::table('spad_crm_outbound')->where('contact_id',$id)->where('channel',$channel)
     ->where('message',$v['message'])->where('created_at','>=',now()->subDay())->exists(),422,'این متن در ۲۴ ساعت گذشته برای این مشتری استفاده شده است.');
   abort_if(DB::table('spad_crm_outbound')->where('contact_id',$id)->whereDate('created_at',today())->count()>=3,429,'سقف سه ارتباط روزانه با این مشتری پر شده است.');
   abort_if(DB::table('spad_crm_outbound')->whereDate('created_at',today())->count()>=30,429,'سقف سی درخواست ارتباط روزانه سامانه پر شده است.');
   return DB::table('spad_crm_outbound')->insertGetId([
    'contact_id'=>$id,'admin_id'=>$uid,'channel'=>$channel,'message'=>$v['message'],'request_id'=>$v['request_id'],
    'status'=>'processing','provider'=>$channel==='sms'?'SMS.ir':'خودگو',
    'created_at'=>now(),'updated_at'=>now()
   ]);
  });
  try{
   $mobile=DB::table(self::C)->where('id',$id)->value('mobile');
   $result=SpadCrmDeliveryService::send($channel,$mobile,$v['message']);
   DB::table('spad_crm_outbound')->where('id',$outboundId)->update([
    'status'=>'accepted','provider_ref'=>$result['reference'],'attempted_at'=>now(),'updated_at'=>now()
   ]);
   DB::table(self::C)->where('id',$id)->update(['last_contact_at'=>now(),'updated_at'=>now()]);
   $this->audit($id,$uid,'outbound_accepted',null,['channel'=>$channel,'outbound_id'=>$outboundId]);
   return response()->json(['message'=>'درخواست توسط ارائه‌دهنده پذیرفته شد؛ تحویل پیام یا پاسخ تماس هنوز تأیید نشده است.','outbound_id'=>$outboundId]);
  }catch(\Throwable $e){
   // Provider timeout may mean a call was made. Never automatically retry.
   $code=$e->getMessage();
   if(!in_array($code,['sms_provider_rejected','voice_provider_rejected','provider_not_configured']))$code='provider_response_unknown';
   DB::table('spad_crm_outbound')->where('id',$outboundId)->update([
    'status'=>$code==='provider_response_unknown'?'unknown':'failed','failure_code'=>$code,
    'attempted_at'=>now(),'updated_at'=>now()
   ]);
   $this->audit($id,$uid,'outbound_issue',null,['channel'=>$channel,'outbound_id'=>$outboundId,'code'=>$code]);
   return response()->json(['message'=>$code==='provider_response_unknown'?'وضعیت ارائه‌دهنده نامشخص است؛ برای جلوگیری از تماس تکراری، ارسال مجدد خودکار انجام نشد.':'ارائه‌دهنده درخواست را نپذیرفت.','code'=>$code],502);
  }
 }
}
