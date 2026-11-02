<?php
namespace App\Http\Controllers;
use App\Models\PartnerPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuildingOperationsController extends Controller {
 private function ready(){abort_unless(Schema::hasTable('spad_building_facilities')&&Schema::hasTable('spad_building_poll_votes'),503,'ابزارهای هوشمند ساختمان هنوز نصب نشده‌اند.');}
 private function building(Request $r,$panelId,$buildingId){
  $this->ready();abort_unless($r->user(),401);
  $panel=PartnerPanel::where('id',$panelId)->where('user_id',$r->user()->id)
   ->where('type',PartnerPanel::TYPE_BUILDING_MANAGER)->where('status',PartnerPanel::STATUS_ACTIVE)->first();
  abort_unless($panel,403);
  abort_unless(DB::table('spad_building_manager_verifications')->where('partner_panel_id',$panel->id)->where('status','approved')->exists(),403,'احراز هویت مدیر لازم است.');
  $b=DB::table('spad_buildings')->where('id',$buildingId)->where('partner_panel_id',$panel->id)
   ->where('owner_user_id',$r->user()->id)->where('status','approved')->first();
  abort_unless($b,403,'دسترسی مدیریتی ساختمان تأیید نشده است.');
  return $b;
 }
 private function row($table,$id,$buildingId){
  $row=DB::table($table)->where('building_id',$buildingId)->where('id',$id)->first();abort_unless($row,404);
  return $row;
 }
 public function index(Request $r,$panelId,$buildingId){
  $b=$this->building($r,$panelId,$buildingId);
  $get=function($table)use($b){return DB::table($table)->where('building_id',$b->id)->orderByDesc('id')->limit(120)->get();};
  return response()->json(['data'=>['facilities'=>$get('spad_building_facilities'),
   'maintenance'=>$get('spad_building_maintenance'),'polls'=>$get('spad_building_polls'),
   'reservations'=>$get('spad_building_reservations'),
   'vote_results'=>DB::table('spad_building_poll_votes')->where('building_id',$b->id)
    ->select('poll_id','vote',DB::raw('count(*) as count'))->groupBy('poll_id','vote')->get()]]);
 }
 public function facility(Request $r,$panelId,$buildingId){
  $b=$this->building($r,$panelId,$buildingId);
  $d=$r->validate(['title'=>'required|string|min:3|max:150','description'=>'nullable|string|max:800']);
  abort_if(DB::table('spad_building_facilities')->where('building_id',$b->id)->count()>=35,422);
  DB::table('spad_building_facilities')->insert(array_merge($d,['building_id'=>$b->id,'active'=>true,'created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'فضای مشترک افزوده شد.'],201);
 }
 public function maintenance(Request $r,$panelId,$buildingId){
  $b=$this->building($r,$panelId,$buildingId);
  $d=$r->validate(['title'=>'required|string|min:3|max:180','category'=>'required|in:elevator,fire,water,electricity,cleaning,heating,other',
   'due_date'=>'required|date_format:Y-m-d','notes'=>'nullable|string|max:2000',
   'estimated_cost_toman'=>'nullable|integer|min:0|max:999999999999']);
  DB::table('spad_building_maintenance')->insert(array_merge($d,['building_id'=>$b->id,'status'=>'open','created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'برنامه نگهداری ثبت شد.'],201);
 }
 public function maintenanceStatus(Request $r,$panelId,$buildingId,$id){
  $b=$this->building($r,$panelId,$buildingId);$this->row('spad_building_maintenance',$id,$b->id);
  $d=$r->validate(['status'=>'required|in:open,in_progress,done']);
  DB::table('spad_building_maintenance')->where('id',$id)->where('building_id',$b->id)->update(['status'=>$d['status'],'updated_at'=>now()]);
  return response()->json(['message'=>'وضعیت سرویس به‌روزرسانی شد.']);
 }
 public function poll(Request $r,$panelId,$buildingId){
  $b=$this->building($r,$panelId,$buildingId);
  $d=$r->validate(['question'=>'required|string|min:8|max:350','closes_at'=>'required|date|after:now']);
  abort_if(DB::table('spad_building_polls')->where('building_id',$b->id)->where('status','open')->count()>=10,422);
  DB::table('spad_building_polls')->insert(array_merge($d,['building_id'=>$b->id,'status'=>'open','created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'نظرسنجی ساختمان ایجاد شد.'],201);
 }
 public function closePoll(Request $r,$panelId,$buildingId,$id){
  $b=$this->building($r,$panelId,$buildingId);$this->row('spad_building_polls',$id,$b->id);
  DB::table('spad_building_polls')->where('id',$id)->where('building_id',$b->id)->update(['status'=>'closed','updated_at'=>now()]);
  return response()->json(['message'=>'رأی‌گیری بسته شد.']);
 }
 private function requireIdentityLevelOne(Request $r){
  abort_unless(Schema::hasTable('spad_user_verifications'),503,'بخش احراز هویت در حال راه‌اندازی است.');
  $ok=DB::table('spad_user_verifications')->where('user_id',optional($r->user())->id)
   ->where('status','approved')->where('level','>=',1)->exists();
  abort_unless($ok,403,'برای رأی‌گیری یا رزرو مشاعات، ابتدا احراز هویت سطح یک را از پنل کاربری تکمیل کنید.');
 }
 private function residentUnit(Request $r,$buildingId){
  $this->ready();abort_unless($r->user(),401);$phone=preg_replace('/\D/','',(string)$r->user()->mobile);
  abort_unless(preg_match('/^09\d{9}$/D',$phone),403);
  $b=DB::table('spad_buildings')->where('id',$buildingId)->where('status','approved')->first();abort_unless($b,404);
  $unit=DB::table('spad_building_units')->where('building_id',$b->id)
   ->where(function($q)use($phone){$q->where('owner_phone',$phone)->orWhere('tenant_phone',$phone);})->first();
  abort_unless($unit,403,'شماره حساب شما به هیچ واحد این ساختمان مرتبط نیست.');
  return $unit;
 }
 public function residentView(Request $r,$buildingId){
  $unit=$this->residentUnit($r,$buildingId);
  $get=function($table)use($buildingId){return DB::table($table)->where('building_id',$buildingId)->orderByDesc('id')->limit(75)->get();};
  return response()->json(['data'=>[
   'unit'=>['id'=>$unit->id,'label'=>$unit->label],
   'facilities'=>$get('spad_building_facilities'),
   'polls'=>$get('spad_building_polls'),
   'my_votes'=>DB::table('spad_building_poll_votes')->where('unit_id',$unit->id)->where('user_id',$r->user()->id)->get(),
   'my_reservations'=>DB::table('spad_building_reservations')->where('unit_id',$unit->id)->orderByDesc('id')->limit(50)->get()
  ]]);
 }
 public function vote(Request $r,$buildingId,$pollId){
  $this->requireIdentityLevelOne($r);
  $unit=$this->residentUnit($r,$buildingId);
  $d=$r->validate(['vote'=>'required|in:yes,no,abstain']);
  $poll=$this->row('spad_building_polls',$pollId,$buildingId);
  abort_unless($poll->status==='open'&&now()->lt($poll->closes_at),422,'مهلت رأی‌گیری تمام شده است.');
  DB::table('spad_building_poll_votes')->updateOrInsert(['poll_id'=>$poll->id,'unit_id'=>$unit->id],[
   'building_id'=>$buildingId,'user_id'=>$r->user()->id,'vote'=>$d['vote'],'updated_at'=>now(),'created_at'=>now()
  ]);
  return response()->json(['message'=>'رأی واحد ثبت شد.']);
 }
 public function reserve(Request $r,$buildingId,$facilityId){
  $this->requireIdentityLevelOne($r);
  $unit=$this->residentUnit($r,$buildingId);
  $facility=$this->row('spad_building_facilities',$facilityId,$buildingId);
  abort_unless($facility->active,422);
  $d=$r->validate(['starts_at'=>'required|date|after:now','ends_at'=>'required|date|after:starts_at']);
  $start=\Carbon\Carbon::parse($d['starts_at']);$end=\Carbon\Carbon::parse($d['ends_at']);
  abort_if($start->diffInMinutes($end)>360,422,'حداکثر مدت رزرو شش ساعت است.');
  return DB::transaction(function()use($r,$buildingId,$facility,$unit,$start,$end){
   // Lock facility row so concurrent overlapping reservations cannot both pass.
   DB::table('spad_building_facilities')->where('id',$facility->id)->lockForUpdate()->first();
   $conflict=DB::table('spad_building_reservations')->where('facility_id',$facility->id)
    ->where('status','approved')->where('starts_at','<',$end)->where('ends_at','>',$start)->exists();
   abort_if($conflict,422,'این بازه قبلاً رزرو شده است.');
   DB::table('spad_building_reservations')->insert(['building_id'=>$buildingId,'facility_id'=>$facility->id,
    'unit_id'=>$unit->id,'user_id'=>$r->user()->id,'starts_at'=>$start,'ends_at'=>$end,
    'status'=>'approved','created_at'=>now(),'updated_at'=>now()]);
   return response()->json(['message'=>'رزرو ثبت شد؛ پرداختی دریافت نشده است.'],201);
  });
 }
}
