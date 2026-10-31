<?php
namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\VirtualTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OnlineRenovationController extends Controller {
 private function ready(){
  abort_unless(Schema::hasTable('spad_renovation_projects')&&Schema::hasTable('spad_renovation_media')&&Schema::hasTable('spad_renovation_rates'),503,'سرویس بازسازی در حال راه‌اندازی است.');
 }
 private function uid(Request $r){$id=optional($r->user())->id;abort_unless($id,401,'ابتدا وارد حساب شوید.');return (int)$id;}
 private function admin(){abort_unless(auth('admin')->check()&&in_array((string)(auth('admin')->user()->role??''),['1','admin','superadmin',''],true),403);}
 private function project(Request $r,$id,$admin=false){
  $q=DB::table('spad_renovation_projects')->where('id',$id);
  if(!$admin)$q->where('user_id',$this->uid($r));
  $p=$q->first();abort_unless($p,404,'پروژه یافت نشد.');return $p;
 }
 private function row($p){
  $p->plan=json_decode($p->plan?:'[]',true)?:[];
  $p->invoice=json_decode($p->invoice?:'null',true);
  return $p;
 }
 public function catalog(){
  $this->ready();
  $tours=VirtualTour::query()->with(['advertisement','scenes'])->where('status','published')->orderByDesc('id')->limit(45)->get()
   ->filter(function($tour){return $tour->advertisement&&
      (int)$tour->advertisement->status===Advertisement::APPROVED&&
      !$tour->advertisement->is_investor_exclusive&&
      $tour->scenes->contains('process_status','ready');})
   ->map(function($tour){
    $scene=$tour->scenes->firstWhere('process_status','ready');
    return ['id'=>$tour->id,'advertisement_id'=>$tour->advertisement_id,'slug'=>$tour->slug,
      'title'=>$tour->title,'thumbnail'=>$scene&&$scene->thumbnail_path?
       url('/api/virtual-tours/assets/scenes/'.$scene->id.'/thumbnail'):null,
      'scene_count'=>$tour->scenes->where('process_status','ready')->count()];
   })->values();
  return response()->json(['data'=>$tours]);
 }
 public function rates(){
  $this->ready();
  return response()->json(['data'=>DB::table('spad_renovation_rates')->where('enabled',true)->get(['code','title','unit','price_toman','days']),
   'note'=>'نرخ‌ها صرفاً مبنای برآورد اولیه و قابل اصلاح توسط مدیر اسپاد هستند؛ قیمت قطعی پس از بازدید کارشناسی مشخص می‌شود.']);
 }
 public function mine(Request $r){$this->ready();$uid=$this->uid($r);
  return response()->json(['data'=>DB::table('spad_renovation_projects')->where('user_id',$uid)
   ->orderByDesc('id')->limit(100)->get()->map(function($p){return $this->row($p);})]);
 }
 public function show(Request $r,$id){$this->ready();$p=$this->project($r,$id);
  return response()->json(['data'=>$this->detail($p)]);
 }
 private function detail($p){
  $p=$this->row($p);
  $p->media=DB::table('spad_renovation_media')->where('project_id',$p->id)
   ->orderBy('id')->get(['id','kind','room','mime','bytes','created_at'])->map(function($m)use($p){
    $m->url=url('/api/online-renovation/'.$p->id.'/media/'.$m->id);return $m;
   });
  return $p;
 }
 public function create(Request $r){
  $this->ready();$uid=$this->uid($r);
  $d=$r->validate(['source_type'=>'required|in:site_property,user_upload','tour_id'=>'nullable|integer|min:1',
     'title'=>'required|string|min:3|max:150','area'=>'required|integer|min:10|max:2000']);
  $adId=null;$tourId=null;
  if($d['source_type']==='site_property'){
   $tour=VirtualTour::with('advertisement')->where('status','published')->findOrFail($d['tour_id']??0);
   abort_unless($tour->advertisement && (int)$tour->advertisement->status===Advertisement::APPROVED && !$tour->advertisement->is_investor_exclusive,404);
   $adId=$tour->advertisement_id;$tourId=$tour->id;
  }
  $id=DB::table('spad_renovation_projects')->insertGetId([
   'user_id'=>$uid,'advertisement_id'=>$adId,'tour_id'=>$tourId,'source_type'=>$d['source_type'],
   'title'=>$d['title'],'area'=>$d['area'],'status'=>'draft','design_style'=>'modern',
   'plan'=>'[]','created_at'=>now(),'updated_at'=>now()
  ]);
  return response()->json(['data'=>$this->detail($this->project($r,$id))],201);
 }
 public function update(Request $r,$id){
  $this->ready();$p=$this->project($r,$id);
  abort_if($p->status==='completed',422,'پروژه نهایی شده است؛ یک پروژه جدید ایجاد کنید.');
  $d=$r->validate(['title'=>'sometimes|string|min:3|max:150','area'=>'sometimes|integer|min:10|max:2000',
    'design_style'=>'sometimes|in:modern,minimal,luxury,classic',
    'plan'=>'sometimes|array|max:12','plan.*.code'=>'required_with:plan|string|max:40',
    'plan.*.qty'=>'required_with:plan|numeric|min:0|max:2000',
    'plan.*.room'=>'nullable|string|max:100']);
  if(isset($d['plan'])){
   $allowed=DB::table('spad_renovation_rates')->where('enabled',true)->pluck('code')->all();
   foreach($d['plan'] as $item)abort_unless(in_array($item['code'],$allowed,true),422,'آیتم بازسازی معتبر نیست.');
   $d['plan']=json_encode($d['plan'],JSON_UNESCAPED_UNICODE);
  }
  $d['updated_at']=now();
  DB::table('spad_renovation_projects')->where('id',$p->id)->update($d);
  return response()->json(['data'=>$this->detail($this->project($r,$id))]);
 }
 public function upload(Request $r,$id){
  $this->ready();$p=$this->project($r,$id);abort_if($p->status==='completed',422);
  $d=$r->validate(['image'=>'required|file|mimes:jpg,jpeg,png,webp|max:8192',
    'room'=>'nullable|string|max:100']);
  abort_if(DB::table('spad_renovation_media')->where('project_id',$id)->where('kind','source')->count()>=12,422,'حداکثر ۱۲ عکس مجاز است.');
  $file=$d['image'];$path=$file->store('private/renovation/'.$p->id,'local');
  abort_unless($path,503,'ذخیره فایل ناموفق بود.');
  $mid=DB::table('spad_renovation_media')->insertGetId(['project_id'=>$p->id,'user_id'=>$p->user_id,
   'kind'=>'source','room'=>$d['room']??'فضای اصلی','path'=>$path,'mime'=>$file->getMimeType(),
   'bytes'=>$file->getSize(),'created_at'=>now(),'updated_at'=>now()]);
  return response()->json(['data'=>['id'=>$mid,'url'=>url('/api/online-renovation/'.$id.'/media/'.$mid)]],201);
 }
 public function media(Request $r,$id,$mediaId){
  $this->ready();$p=$this->project($r,$id);
  $m=DB::table('spad_renovation_media')->where('id',$mediaId)->where('project_id',$p->id)->first();
  abort_unless($m&&Storage::disk('local')->exists($m->path),404);
  return response()->file(Storage::disk('local')->path($m->path),[
   'Content-Type'=>$m->mime,'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff'
  ]);
 }
 public function finalize(Request $r,$id){
  $this->ready();$p=$this->project($r,$id);
  $plan=json_decode($p->plan?:'[]',true)?:[];
  abort_unless(count($plan)>0,422,'ابتدا آیتم‌های بازسازی را انتخاب کنید.');
  $rates=DB::table('spad_renovation_rates')->where('enabled',true)->get()->keyBy('code');
  $lines=[];$sum=0;$days=0;
  foreach($plan as $item){
   $rate=$rates->get($item['code']??'');abort_unless($rate,422,'نرخ آیتم تغییر کرده است؛ دوباره ذخیره کنید.');
   $qty=min(2000,max(0,(float)($item['qty']??0)));
   $price=(int)round($qty*(int)$rate->price_toman);
   $sum+=$price;$days+=$qty>0?(int)$rate->days:0;
   $lines[]=['code'=>$rate->code,'title'=>$rate->title,'unit'=>$rate->unit,'qty'=>$qty,
     'unit_price_toman'=>(int)$rate->price_toman,'total_toman'=>$price,'estimated_days'=>(int)$rate->days];
  }
  abort_unless($sum>0,422,'تعرفه پروژه نیازمند تنظیم مدیر است.');
  $invoice=['number'=>'SPAD-RNV-'.str_pad((string)$p->id,6,'0',STR_PAD_LEFT),'lines'=>$lines,
   'total_toman'=>$sum,'estimated_days'=>$days,'currency'=>'تومان',
   'notice'=>'برآورد غیرقطعی بر اساس نرخ‌های ثبت‌شده در اسپاد؛ پس از بازدید و بررسی فنی قابل تغییر است.',
   'created_at'=>now()->toIso8601String()];
  DB::table('spad_renovation_projects')->where('id',$p->id)->update([
   'status'=>'completed','invoice'=>json_encode($invoice,JSON_UNESCAPED_UNICODE),
   'estimated_total_toman'=>$sum,'estimated_days'=>$days,'updated_at'=>now()
  ]);
  return response()->json(['data'=>$this->detail($this->project($r,$id))]);
 }
 public function export(Request $r,$id){
  $this->ready();$p=$this->project($r,$id);abort_unless($p->status==='completed',422);
  $d=$r->validate(['image'=>'required|file|mimes:png,jpg,jpeg|max:8192','room'=>'nullable|string|max:100']);
  abort_unless(extension_loaded('gd'),503,'موتور خروجی تصویری سرور آماده نیست.');
  $bytes=file_get_contents($d['image']->getRealPath());
  $image=@imagecreatefromstring($bytes);
  abort_unless($image,422,'تصویر معتبر نیست.');
  $w=imagesx($image);$h=imagesy($image);
  if($w<200||$h<200||$w>6000||$h>6000||$w*$h>22000000){imagedestroy($image);abort(422,'ابعاد تصویر نامعتبر است.');}
  imagealphablending($image,true);
  $bottom=min(94,max(48,(int)round($h*.1)));
  $shade=imagecolorallocatealpha($image,2,37,82,28);
  imagefilledrectangle($image,0,$h-$bottom,$w,$h,$shade);
  $white=imagecolorallocate($image,255,255,255);
  imagestring($image,5,22,$h-$bottom+12,'SPAD.ESTATE  |  ONLINE RENOVATION',$white);
  imagestring($image,3,22,$h-$bottom+36,'spad.estate  -  Project #'.$p->id,$white);
  $path='private/renovation/'.$p->id.'/output-'.bin2hex(random_bytes(9)).'.jpg';
  ob_start();imagejpeg($image,null,89);$rendered=ob_get_clean();imagedestroy($image);
  abort_unless(Storage::disk('local')->put($path,$rendered),503,'ثبت خروجی ناموفق بود.');
  $idOut=DB::table('spad_renovation_media')->insertGetId(['project_id'=>$p->id,'user_id'=>$p->user_id,
    'kind'=>'output','room'=>$d['room']??'خروجی نهایی','path'=>$path,'mime'=>'image/jpeg',
    'bytes'=>strlen($rendered),'created_at'=>now(),'updated_at'=>now()]);
  return response()->json(['data'=>['id'=>$idOut,'url'=>url('/api/online-renovation/'.$p->id.'/media/'.$idOut)]],201);
 }
 public function adminProjects(Request $r){$this->ready();$this->admin();
  $items=DB::table('spad_renovation_projects')->orderByDesc('id')->limit(250)->get()
   ->map(function($p){return $this->row($p);});
  return response()->json(['data'=>$items]);
 }
 public function adminRates(){ $this->ready();$this->admin();
  return response()->json(['data'=>DB::table('spad_renovation_rates')->orderBy('id')->get()]);
 }
 public function adminRate(Request $r,$code){$this->ready();$this->admin();
  $d=$r->validate(['price_toman'=>'required|integer|min:0|max:1000000000','days'=>'required|integer|min:0|max:120',
    'enabled'=>'required|boolean']);
  abort_unless(DB::table('spad_renovation_rates')->where('code',$code)->update(array_merge($d,['updated_at'=>now()])),404);
  return response()->json(['message'=>'تعرفه بازسازی ثبت شد.']);
 }
 public function adminUpdate(Request $r,$id){$this->ready();$this->admin();
  $d=$r->validate(['admin_quote_toman'=>'nullable|integer|min:0|max:100000000000',
    'admin_days'=>'nullable|integer|min:1|max:365','admin_note'=>'nullable|string|max:3000',
    'status'=>'required|in:draft,completed,reviewed']);
  abort_unless(DB::table('spad_renovation_projects')->where('id',$id)->update(array_merge($d,['updated_at'=>now()])),404);
  return response()->json(['message'=>'بررسی پروژه ذخیره شد.']);
 }
}
