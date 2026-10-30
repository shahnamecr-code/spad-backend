<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpadCommunityController extends Controller
{
 private function config()
 {
  abort_unless(Schema::hasTable('spad_community_settings'),503,'سرویس در حال راه‌اندازی است.');
  return DB::table('spad_community_settings')->where('id',1)->first();
 }
 private function active($service)
 {
  $s=$this->config();
  abort_unless((bool) $s->{$service.'_enabled'},503,'این سرویس در حال حاضر غیرفعال است.');
  return $s;
 }
 private function uid(Request $r)
 {
  $u=$r->user();
  abort_unless($u && $u->id,401,'برای استفاده از این خدمت وارد حساب کاربری شوید.');
  return (int)$u->id;
 }
 private function admin()
 {
  abort_unless(auth('admin')->check(),403,'دسترسی مدیریت لازم است.');
 }
 private function urlRule() {return ['nullable','url','max:600','regex:/^https:\/\//i'];}
 private function building($id,$uid)
 {
  $building=DB::table('spad_buildings')->where('id',$id)->first();
  abort_unless($building,404);
  $unit=DB::table('spad_building_units')->where('building_id',$id)->where('user_id',$uid)
   ->where('status','active')->first();
  abort_unless($building->owner_user_id==$uid || $unit,403,'به اطلاعات این ساختمان دسترسی ندارید.');
  return [$building,$unit];
 }
 private function ownerBuilding($id,$uid)
 {
  [$b]=$this->building($id,$uid);
  abort_unless($b->owner_user_id==$uid,403,'فقط مدیر ساختمان مجاز است.');
  return $b;
 }
 public function settings()
 {
  $s=$this->config();
  return response()->json(['data'=>[
   'buildings_enabled'=>(bool)$s->buildings_enabled,
   'builders_enabled'=>(bool)$s->builders_enabled,
   'auctions_enabled'=>(bool)$s->auctions_enabled,
   'auction_notice'=>$s->auction_notice
  ]]);
 }
 public function myBuildings(Request $r)
 {
  $this->active('buildings');
  $uid=$this->uid($r);
  $ids=DB::table('spad_building_units')->where('user_id',$uid)->where('status','active')->pluck('building_id');
  $rows=DB::table('spad_buildings')->where(function($q)use($uid,$ids){
   $q->where('owner_user_id',$uid)->orWhereIn('id',$ids);
  })->orderByDesc('id')->limit(100)->get();
  return response()->json(['data'=>$rows]);
 }
 public function createBuilding(Request $r)
 {
  $this->active('buildings');$uid=$this->uid($r);
  $d=$r->validate([
   'name'=>'required|string|min:3|max:180','city'=>'required|string|max:110',
   'address'=>'required|string|min:10|max:600','unit_count'=>'required|integer|min:1|max:500',
   'default_charge_toman'=>'nullable|integer|min:0|max:999999999999'
  ]);
  $id=DB::table('spad_buildings')->insertGetId(array_merge($d,[
   'owner_user_id'=>$uid,'default_charge_toman'=>$d['default_charge_toman']??0,
   'status'=>'pending','created_at'=>now(),'updated_at'=>now()
  ]));
  return response()->json(['id'=>$id,'message'=>'ساختمان برای بررسی مدیریت اسپاد ثبت شد.'],201);
 }
 public function buildingDetail(Request $r,$id)
 {
  $this->active('buildings');
  $uid=$this->uid($r);[$building,$myUnit]=$this->building($id,$uid);
  $manager=(int)$building->owner_user_id===$uid;
  $units=DB::table('spad_building_units')->where('building_id',$id)
   ->orderBy('id')->get();
  // Residents must never see other residents' names, IDs or payment history.
  if(!$manager)$units=$units->filter(function($x)use($uid){return (int)$x->user_id===$uid;})->values();
  $unitIds=$units->pluck('id');
  $charges=DB::table('spad_building_charges')->where('building_id',$id)
   ->when(!$manager,function($q)use($unitIds){$q->whereIn('unit_id',$unitIds);})
   ->orderByDesc('id')->limit(500)->get();
  $expenses=DB::table('spad_building_expenses')->where('building_id',$id)->orderByDesc('expense_date')->limit(100)->get();
  $notices=DB::table('spad_building_notices')->where('building_id',$id)->orderByDesc('id')->limit(60)->get();
  $tickets=DB::table('spad_building_tickets')->where('building_id',$id)
   ->when(!$manager,function($q)use($uid){$q->where('user_id',$uid);})
   ->orderByDesc('id')->limit(100)->get();
  return response()->json(['data'=>compact('building','manager','units','charges','expenses','notices','tickets')]);
 }
 public function addUnit(Request $r,$id)
 {
  $uid=$this->uid($r);$b=$this->ownerBuilding($id,$uid);
  abort_unless($b->status==='approved',422,'ابتدا ساختمان باید تأیید شود.');
  $d=$r->validate(['label'=>'required|string|max:80','resident_name'=>'nullable|string|max:150']);
  abort_if(DB::table('spad_building_units')->where('building_id',$id)->count()>=$b->unit_count,422,'تعداد واحدهای مجاز تکمیل شده است.');
  $exists=DB::table('spad_building_units')->where('building_id',$id)->where('label',$d['label'])->exists();
  abort_if($exists,422,'این شماره واحد قبلاً ثبت شده است.');
  $unit=DB::table('spad_building_units')->insertGetId(array_merge($d,[
   'building_id'=>$id,'status'=>'vacant','created_at'=>now(),'updated_at'=>now()
  ]));
  return response()->json(['id'=>$unit],201);
 }
 public function claimUnit(Request $r,$id)
 {
  $this->active('buildings');$uid=$this->uid($r);
  $d=$r->validate(['unit_id'=>'required|integer']);
  $b=DB::table('spad_buildings')->where('id',$id)->where('status','approved')->first();
  abort_unless($b,404);
  $unit=DB::table('spad_building_units')->where('building_id',$id)->where('id',$d['unit_id'])->first();
  abort_unless($unit,404);abort_if($unit->user_id && (int)$unit->user_id!==$uid,422,'این واحد در اختیار شخص دیگری است.');
  abort_if($b->owner_user_id==$uid,422,'مدیر ساختمان نیازی به درخواست عضویت ندارد.');
  DB::table('spad_building_units')->where('id',$unit->id)->where('status','vacant')
   ->update(['user_id'=>$uid,'status'=>'pending','updated_at'=>now()]);
  return response()->json(['message'=>'درخواست عضویت برای تأیید مدیر ساختمان ارسال شد.']);
 }
 public function reviewUnit(Request $r,$id,$unitId)
 {
  $uid=$this->uid($r);$this->ownerBuilding($id,$uid);
  $d=$r->validate(['status'=>'required|in:active,vacant']);
  $unit=DB::table('spad_building_units')->where('id',$unitId)->where('building_id',$id)->first();
  abort_unless($unit,404);
  DB::table('spad_building_units')->where('id',$unitId)->update([
   'status'=>$d['status'],'user_id'=>$d['status']==='vacant'?null:$unit->user_id,
   'updated_at'=>now()
  ]);
  return response()->json(['message'=>'وضعیت عضویت ثبت شد.']);
 }
 public function addCharge(Request $r,$id)
 {
  $this->active('buildings');$uid=$this->uid($r);$b=$this->ownerBuilding($id,$uid);
  abort_unless($b->status==='approved',422);
  $d=$r->validate(['unit_id'=>'required|integer','title'=>'required|string|max:170',
   'amount_toman'=>'required|integer|min:1|max:999999999999','due_date'=>'nullable|date_format:Y-m-d']);
  abort_unless(DB::table('spad_building_units')->where('building_id',$id)->where('id',$d['unit_id'])->exists(),422,'واحد متعلق به این ساختمان نیست.');
  DB::table('spad_building_charges')->insert(array_merge($d,['building_id'=>$id,'status'=>'unpaid','recorded_by'=>$uid,'created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'بدهی شارژ ثبت شد. پرداخت خودکار انجام نشده است.'],201);
 }
 public function markCharge(Request $r,$id,$chargeId)
 {
  $uid=$this->uid($r);$this->ownerBuilding($id,$uid);
  $d=$r->validate(['status'=>'required|in:paid,unpaid']);
  $affected=DB::table('spad_building_charges')->where('id',$chargeId)->where('building_id',$id)->update([
   'status'=>$d['status'],'paid_at'=>$d['status']==='paid'?now():null,'recorded_by'=>$uid,'updated_at'=>now()
  ]);abort_unless($affected,404);
  return response()->json(['message'=>'وضعیت پرداخت دستی ثبت شد؛ سند بانکی به صورت خودکار تأیید نشده است.']);
 }
 public function addExpense(Request $r,$id)
 {
  $uid=$this->uid($r);$b=$this->ownerBuilding($id,$uid);
  abort_unless($b->status==='approved',422);
  $d=$r->validate(['title'=>'required|string|max:170','amount_toman'=>'required|integer|min:1|max:999999999999',
   'expense_date'=>'required|date_format:Y-m-d','description'=>'nullable|string|max:2000']);
  DB::table('spad_building_expenses')->insert(array_merge($d,['building_id'=>$id,'created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'هزینه ساختمان ثبت شد.'],201);
 }
 public function addNotice(Request $r,$id)
 {
  $uid=$this->uid($r);$b=$this->ownerBuilding($id,$uid);
  abort_unless($b->status==='approved',422);
  $d=$r->validate(['title'=>'required|string|max:170','body'=>'required|string|max:4000']);
  DB::table('spad_building_notices')->insert(array_merge($d,['building_id'=>$id,'created_at'=>now(),'updated_at'=>now()]));
  return response()->json(['message'=>'اطلاعیه منتشر شد.'],201);
 }
 public function addTicket(Request $r,$id)
 {
  $this->active('buildings');$uid=$this->uid($r);[$b,$unit]=$this->building($id,$uid);
  $d=$r->validate(['subject'=>'required|string|max:150','message'=>'required|string|max:3000']);
  DB::table('spad_building_tickets')->insert(array_merge($d,[
   'building_id'=>$id,'user_id'=>$uid,'unit_id'=>$unit?$unit->id:null,
   'status'=>'open','created_at'=>now(),'updated_at'=>now()
  ]));
  return response()->json(['message'=>'تیکت به مدیریت ساختمان ارسال شد.'],201);
 }
 public function replyTicket(Request $r,$id,$ticketId)
 {
  $uid=$this->uid($r);$this->ownerBuilding($id,$uid);
  $d=$r->validate(['status'=>'required|in:open,resolved','manager_reply'=>'required|string|max:3000']);
  abort_unless(DB::table('spad_building_tickets')->where('id',$ticketId)->where('building_id',$id)->update(array_merge($d,['updated_at'=>now()])),404);
  return response()->json(['message'=>'پاسخ ثبت شد.']);
 }

 public function builders()
 {
  $this->active('builders');
  $rows=DB::table('spad_builder_profiles')->where('published',true)
   ->orderBy('rank_priority')->orderByDesc('verified')->orderByDesc('completed_projects')
   ->orderBy('id')->limit(100)->get();
  return response()->json(['data'=>$rows,'ranking_notice'=>'ترتیب نمایش بر اساس اولویت تأییدشده مدیریت و مشخصات ثبت‌شده است؛ امتیاز مشتریان یا جایگاه آماری مستقل ادعا نمی‌شود.']);
 }
 public function builder($id)
 {
  $this->active('builders');$item=DB::table('spad_builder_profiles')->where('published',true)->where('id',$id)->first();abort_unless($item,404);
  $projects=DB::table('spad_builder_projects')->where('builder_id',$id)->orderByDesc('completed_year')->limit(100)->get();
  return response()->json(['data'=>['builder'=>$item,'projects'=>$projects]]);
 }
 public function myBuilder(Request $r)
 {
  $this->active('builders');$uid=$this->uid($r);
  $item=DB::table('spad_builder_profiles')->where('user_id',$uid)->first();
  $projects=$item?DB::table('spad_builder_projects')->where('builder_id',$item->id)->orderByDesc('id')->get():[];
  return response()->json(['data'=>['builder'=>$item,'projects'=>$projects]]);
 }
 public function saveMyBuilder(Request $r)
 {
  $this->active('builders');$uid=$this->uid($r);
  $d=$r->validate(['name'=>'required|string|min:3|max:180','city'=>'required|string|max:110',
   'headline'=>'nullable|string|max:220','biography'=>'nullable|string|max:7000',
   'experience_years'=>'required|integer|min:0|max:90','completed_projects'=>'required|integer|min:0|max:10000',
   'logo_url'=>$this->urlRule()]);
  $old=DB::table('spad_builder_profiles')->where('user_id',$uid)->first();
  DB::table('spad_builder_profiles')->updateOrInsert(['user_id'=>$uid],array_merge($d,[
   'published'=>false,'verified'=>false,'rank_priority'=>$old?$old->rank_priority:1000,
   'updated_at'=>now(),'created_at'=>$old?$old->created_at:now()
  ]));
  return response()->json(['message'=>'رزومه برای بررسی و تأیید مدیریت ثبت شد.']);
 }
 public function addMyProject(Request $r)
 {
  $this->active('builders');$uid=$this->uid($r);
  $b=DB::table('spad_builder_profiles')->where('user_id',$uid)->first();abort_unless($b,404,'ابتدا رزومه سازنده را ثبت کنید.');
  abort_if(DB::table('spad_builder_projects')->where('builder_id',$b->id)->count()>=40,422,'حداکثر ۴۰ نمونه‌کار قابل ثبت است.');
  $d=$r->validate(['title'=>'required|string|max:180','city'=>'required|string|max:110',
   'description'=>'nullable|string|max:3000','image_url'=>$this->urlRule(),'completed_year'=>'nullable|integer|min:1300|max:2200']);
  DB::table('spad_builder_projects')->insert(array_merge($d,['builder_id'=>$b->id,'created_at'=>now(),'updated_at'=>now()]));
  DB::table('spad_builder_profiles')->where('id',$b->id)->update(['published'=>false,'verified'=>false,'updated_at'=>now()]);
  return response()->json(['message'=>'نمونه‌کار برای بازبینی ارسال شد.'],201);
 }
 public function deleteMyProject(Request $r,$id)
 {
  $uid=$this->uid($r);$b=DB::table('spad_builder_profiles')->where('user_id',$uid)->first();abort_unless($b,404);
  abort_unless(DB::table('spad_builder_projects')->where('builder_id',$b->id)->where('id',$id)->delete(),404);
  DB::table('spad_builder_profiles')->where('id',$b->id)->update(['published'=>false,'verified'=>false,'updated_at'=>now()]);
  return response()->json(['message'=>'نمونه‌کار حذف شد؛ رزومه نیازمند بازبینی است.']);
 }

 public function auctions()
 {
  $this->active('auctions');
  $items=DB::table('spad_property_auctions')->where('status','published')
   ->orderBy('ends_at')->limit(100)->get();
  $items=$items->map(function($x){
   $this->decorateAuction($x);unset($x->owner_user_id,$x->provisional_winner_user_id);
   return $x;
  });
  return response()->json(['data'=>$items,'notice'=>$this->config()->auction_notice]);
 }
 private function decorateAuction($x)
 {
  $stats=DB::table('spad_auction_bids')->where('auction_id',$x->id)
   ->selectRaw('COUNT(*) as bid_count, MAX(amount_toman) as high_bid')->first();
  $x->bid_count=(int)($stats->bid_count??0);
  $x->highest_bid_toman=$stats->high_bid===null?null:(int)$stats->high_bid;
  $x->is_live=$x->status==='published'&&$x->starts_at&&$x->ends_at&&
   Carbon::parse($x->starts_at)->lte(now())&&Carbon::parse($x->ends_at)->gt(now());
  return $x;
 }
 public function auction($id)
 {
  $this->active('auctions');
  $x=DB::table('spad_property_auctions')->where('id',$id)->where('status','published')->first();
  abort_unless($x,404);$this->decorateAuction($x);
  unset($x->owner_user_id,$x->provisional_winner_user_id);
  return response()->json(['data'=>$x,'notice'=>$this->config()->auction_notice]);
 }
 public function myAuctions(Request $r)
 {
  $this->active('auctions');$uid=$this->uid($r);
  $owned=DB::table('spad_property_auctions')->where('owner_user_id',$uid)->orderByDesc('id')->limit(100)->get();
  $ids=DB::table('spad_auction_bids')->where('user_id',$uid)->distinct()->pluck('auction_id');
  $participated=DB::table('spad_property_auctions')->whereIn('id',$ids)->orderByDesc('id')->limit(100)->get();
  return response()->json(['data'=>['owned'=>$owned,'participated'=>$participated]]);
 }
 public function proposeAuction(Request $r)
 {
  $s=$this->active('auctions');$uid=$this->uid($r);
  $d=$r->validate(['title'=>'required|string|min:8|max:220','city'=>'required|string|max:110',
   'district'=>'nullable|string|max:180','description'=>'required|string|min:30|max:7000',
   'image_url'=>$this->urlRule(),'opening_bid_toman'=>'required|integer|min:1000000|max:999999999999999']);
  DB::table('spad_property_auctions')->insert(array_merge($d,[
   'owner_user_id'=>$uid,'minimum_increment_toman'=>$s->minimum_bid_increment,
   'status'=>'review','created_at'=>now(),'updated_at'=>now()
  ]));
  return response()->json(['message'=>'درخواست مزایده ثبت شد و پس از بررسی مدارک و تنظیم زمان، توسط مدیر منتشر می‌شود.'],201);
 }
 public function bid(Request $r,$id)
 {
  $this->active('auctions');$uid=$this->uid($r);
  $d=$r->validate(['amount_toman'=>'required|integer|min:1|max:999999999999999']);
  $result=DB::transaction(function()use($id,$uid,$d){
   $x=DB::table('spad_property_auctions')->where('id',$id)->lockForUpdate()->first();abort_unless($x,404);
   abort_unless($x->status==='published'&&$x->starts_at&&$x->ends_at&&
    Carbon::parse($x->starts_at)->lte(now())&&Carbon::parse($x->ends_at)->gt(now()),422,'مزایده در حال برگزاری نیست.');
   abort_if((int)$x->owner_user_id===$uid,422,'ثبت پیشنهاد برای صاحب ملک مجاز نیست.');
   $max=DB::table('spad_auction_bids')->where('auction_id',$id)->max('amount_toman');
   $minimum=$max===null?(int)$x->opening_bid_toman:(int)$max+(int)$x->minimum_increment_toman;
   abort_if((int)$d['amount_toman']<$minimum,422,'حداقل پیشنهاد مجاز '.number_format($minimum).' تومان است.');
   DB::table('spad_auction_bids')->insert([
    'auction_id'=>$id,'user_id'=>$uid,'amount_toman'=>$d['amount_toman'],
    'created_at'=>now(),'updated_at'=>now()
   ]);
   // Anti-sniping extension: last-minute accepted bids add two minutes.
   if(Carbon::parse($x->ends_at)->diffInSeconds(now())<120) {
    DB::table('spad_property_auctions')->where('id',$id)->update([
     'ends_at'=>Carbon::parse($x->ends_at)->addMinutes(2),'updated_at'=>now()
    ]);
   }
   return ['amount_toman'=>(int)$d['amount_toman'],'minimum_next_toman'=>(int)$d['amount_toman']+(int)$x->minimum_increment_toman];
  },3);
  return response()->json(['data'=>$result,'message'=>'پیشنهاد ثبت شد؛ تأیید نهایی معامله منوط به تشریفات قانونی است.'],201);
 }
 public function adminSettings()
 {
  $this->admin();return response()->json(['data'=>$this->config()]);
 }
 public function updateSettings(Request $r)
 {
  $this->admin();$d=$r->validate([
   'buildings_enabled'=>'required|boolean','builders_enabled'=>'required|boolean','auctions_enabled'=>'required|boolean',
   'minimum_bid_increment'=>'required|integer|min:1000000|max:999999999999',
   'auction_notice'=>'required|string|max:1500'
  ]);
  DB::table('spad_community_settings')->where('id',1)->update(array_merge($d,['updated_at'=>now()]));
  return response()->json(['data'=>$this->config()]);
 }
 public function adminBuildings()
 {
  $this->admin();
  return response()->json(['data'=>DB::table('spad_buildings')->orderByDesc('id')->limit(500)->get()]);
 }
 public function adminBuildingStatus(Request $r,$id)
 {
  $this->admin();$d=$r->validate(['status'=>'required|in:pending,approved,rejected,suspended']);
  abort_unless(DB::table('spad_buildings')->where('id',$id)->update(array_merge($d,['updated_at'=>now()])),404);
  return response()->json(['message'=>'وضعیت ساختمان ثبت شد.']);
 }
 public function adminBuilders()
 {
  $this->admin();
  return response()->json(['data'=>DB::table('spad_builder_profiles')->orderBy('rank_priority')->orderByDesc('id')->limit(500)->get()]);
 }
 public function adminBuilderStatus(Request $r,$id)
 {
  $this->admin();$d=$r->validate([
   'published'=>'required|boolean','verified'=>'required|boolean',
   'rank_priority'=>'required|integer|min:1|max:10000'
  ]);
  abort_unless(DB::table('spad_builder_profiles')->where('id',$id)->update(array_merge($d,['updated_at'=>now()])),404);
  return response()->json(['message'=>'تأیید، انتشار و اولویت نمایش سازنده ذخیره شد.']);
 }
 public function adminAuctions()
 {
  $this->admin();
  return response()->json(['data'=>DB::table('spad_property_auctions')->orderByDesc('id')->limit(500)->get()]);
 }
 public function adminAuctionStatus(Request $r,$id)
 {
  $this->admin();
  $d=$r->validate([
   'status'=>'required|in:review,published,paused,closed,rejected',
   'starts_at'=>'nullable|date_format:Y-m-d H:i:s','ends_at'=>'nullable|date_format:Y-m-d H:i:s',
   'minimum_increment_toman'=>'required|integer|min:1000000|max:999999999999'
  ]);
  return DB::transaction(function()use($r,$id,$d){
   $a=DB::table('spad_property_auctions')->where('id',$id)->lockForUpdate()->first();
   abort_unless($a,404);
   $bidCount=DB::table('spad_auction_bids')->where('auction_id',$id)->count();
   if($bidCount>0) {
    abort_if((int)$d['minimum_increment_toman']!==(int)$a->minimum_increment_toman,422,'پس از دریافت پیشنهاد، تغییر گام مزایده مجاز نیست.');
    abort_if($d['starts_at']!==(string)$a->starts_at || $d['ends_at']!==(string)$a->ends_at,422,'زمان‌بندی پس از ثبت پیشنهاد تغییرپذیر نیست.');
    abort_if(in_array($d['status'],['review','rejected'],true),422,'مزایده دارای پیشنهاد قابل بازگشت به بازبینی نیست.');
   }
   if($d['status']==='published'){
    abort_unless($d['starts_at'] && $d['ends_at'],422,'تاریخ شروع و پایان الزامی است.');
    abort_unless(Carbon::parse($d['ends_at'])->gt(now()) && Carbon::parse($d['ends_at'])->gt(Carbon::parse($d['starts_at'])),422,'بازه زمانی نامعتبر است.');
   }
   $winner=null;
   if($d['status']==='closed') {
    abort_unless($a->ends_at && Carbon::parse($a->ends_at)->lte(now()),422,'قبل از پایان زمان مزایده امکان اعلام اتمام وجود ندارد.');
    $best=DB::table('spad_auction_bids')->where('auction_id',$id)->orderByDesc('amount_toman')->orderBy('id')->first();
    $winner=$best?$best->user_id:null;
   }
   DB::table('spad_property_auctions')->where('id',$id)->update(array_merge($d,[
    'provisional_winner_user_id'=>$winner,'updated_at'=>now()
   ]));
   return response()->json(['message'=>'وضعیت مزایده ثبت شد. برنده صرفاً موقت است و معامله به قرارداد جداگانه نیاز دارد.']);
  },3);
 }
 public function adminAuctionBids($id)
 {
  $this->admin();
  return response()->json(['data'=>DB::table('spad_auction_bids')->where('auction_id',$id)
   ->orderByDesc('amount_toman')->limit(300)->get()]);
 }
}
