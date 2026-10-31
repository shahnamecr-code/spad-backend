<?php
namespace App\Http\Controllers;

use App\Models\PartnerPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BuildingManagerIdentityController extends Controller {
 private function panel(Request $r,$panelId){
  $panel=PartnerPanel::query()->where('id',$panelId)->where('user_id',optional($r->user())->id)
   ->where('type',PartnerPanel::TYPE_BUILDING_MANAGER)->where('status',PartnerPanel::STATUS_ACTIVE)->first();
  abort_unless($panel,403,'دسترسی به پنل مدیر ساختمان مجاز نیست.');
  return $panel;
 }
 private function ready(){
  abort_unless(Schema::hasTable('spad_building_manager_verifications'),503,'سامانه احراز هویت در حال راه‌اندازی است.');
 }
 private function maskIban($iban){
  return substr($iban,0,6).'••••••••••••••••'.substr($iban,-4);
 }
 private function ibanValid($iban){
  if(!preg_match('/^IR[0-9]{24}$/',$iban))return false;
  $rearranged=substr($iban,4).'1827'.substr($iban,2,2);
  $rem=0;
  for($i=0;$i<strlen($rearranged);$i++)$rem=($rem*10+(int)$rearranged[$i])%97;
  return $rem===1;
 }
 public function status(Request $r,$panelId){
  $p=$this->panel($r,$panelId);$this->ready();
  $v=DB::table('spad_building_manager_verifications')->where('partner_panel_id',$p->id)->first();
  $iban=$v?Crypt::decryptString($v->iban_encrypted):null;
  return response()->json(['data'=>[
   'status'=>$v?$v->status:'not_submitted',
   'full_name'=>$v?$v->full_name:'',
   'iban_masked'=>$iban?$this->maskIban($iban):null,
   'identity_document_present'=>(bool)$v,
   'support_document_present'=>(bool)($v&&$v->support_document_path),
   'review_note'=>$v&&$v->status==='rejected'?$v->review_note:null,
   'submitted_at'=>$v?$v->created_at:null,
   'reviewed_at'=>$v?$v->reviewed_at:null
  ]]);
 }
 public function submit(Request $r,$panelId){
  $p=$this->panel($r,$panelId);$this->ready();
  $d=$r->validate([
   'full_name'=>'required|string|min:5|max:180',
   'iban'=>'required|string|max:32',
   'identity_document'=>'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
   'support_document'=>'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
  ]);
  $iban=strtoupper(preg_replace('/\s+/','',$d['iban']));
  if(!$this->ibanValid($iban))throw ValidationException::withMessages(['iban'=>'شماره شبای بانکی معتبر نیست؛ ۲۴ رقم پس از IR را بررسی کنید.']);
  $existing=DB::table('spad_building_manager_verifications')->where('partner_panel_id',$p->id)->first();
  abort_if($existing&&$existing->status==='approved',422,'پروفایل تأییدشده برای تغییر به بررسی پشتیبانی نیاز دارد.');
  abort_if($existing&&$existing->status==='pending',422,'مدارک قبلاً ثبت و در انتظار بررسی هستند.');
  // Private local disk: documents must never be exposed by /storage or public URLs.
  $prefix='private/building-manager-verifications/'.$p->id;
  $identity=$r->file('identity_document')->store($prefix,'local');
  abort_unless($identity,503,'ذخیره سند هویتی ممکن نشد.');
  $support=null;
  try{
   if($r->hasFile('support_document')){
    $support=$r->file('support_document')->store($prefix,'local');
    abort_unless($support,503,'ذخیره سند تکمیلی ممکن نشد.');
   }
   DB::transaction(function()use($p,$d,$iban,$identity,$support,$existing){
    $data=[
     'user_id'=>$p->user_id,'full_name'=>$d['full_name'],'iban_encrypted'=>Crypt::encryptString($iban),
     'identity_document_path'=>$identity,
     'identity_document_name'=>substr($d['identity_document']->getClientOriginalName(),0,180),
     'support_document_path'=>$support,
     'support_document_name'=>isset($d['support_document'])?substr($d['support_document']->getClientOriginalName(),0,180):null,
     'status'=>'pending','review_note'=>null,'reviewed_by_admin_id'=>null,'reviewed_at'=>null,'updated_at'=>now()
    ];
    if($existing)DB::table('spad_building_manager_verifications')->where('id',$existing->id)->update($data);
    else DB::table('spad_building_manager_verifications')->insert(array_merge($data,['partner_panel_id'=>$p->id,'created_at'=>now()]));
   });
  }catch(\Throwable $e){
   Storage::disk('local')->delete(array_filter([$identity,$support]));
   throw $e;
  }
  if($existing)Storage::disk('local')->delete(array_filter([$existing->identity_document_path,$existing->support_document_path]));
  return response()->json(['message'=>'مدارک به‌صورت خصوصی ثبت شد؛ پس از بررسی مدیریت اسپاد دسترسی مدیریتی فعال می‌شود.'],201);
 }
 public function adminList(){
  abort_unless(auth('admin')->check(),403);$this->ready();
  $data=DB::table('spad_building_manager_verifications as v')
   ->join('partner_panels as p','p.id','=','v.partner_panel_id')
   ->leftJoin('users as u','u.id','=','v.user_id')
   ->select(['v.id','v.partner_panel_id','v.user_id','v.full_name','v.status','v.review_note','v.created_at','v.reviewed_at',
    'v.support_document_path','u.first_name','u.last_name','u.mobile','p.title'])
   ->orderByRaw("CASE WHEN v.status='pending' THEN 0 WHEN v.status='rejected' THEN 1 ELSE 2 END")
   ->orderByDesc('v.id')->limit(200)->get()->map(function($v){
    $v->support_document_present=(bool)$v->support_document_path;unset($v->support_document_path);
    return $v;
   });
  return response()->json(['data'=>$data]);
 }
 public function adminShow($id){
  abort_unless(auth('admin')->check(),403);$this->ready();
  $v=DB::table('spad_building_manager_verifications')->where('id',$id)->first();abort_unless($v,404);
  return response()->json(['data'=>[
   'id'=>$v->id,'full_name'=>$v->full_name,'status'=>$v->status,
   'iban'=>Crypt::decryptString($v->iban_encrypted),
   'identity_document_present'=>true,'support_document_present'=>(bool)$v->support_document_path,
   'review_note'=>$v->review_note
  ]]);
 }
 public function adminDocument($id,$kind){
  abort_unless(auth('admin')->check(),403);$this->ready();
  abort_unless(in_array($kind,['identity','support'],true),404);
  $v=DB::table('spad_building_manager_verifications')->where('id',$id)->first();abort_unless($v,404);
  $path=$kind==='identity'?$v->identity_document_path:$v->support_document_path;
  abort_unless($path&&Storage::disk('local')->exists($path),404);
  return Storage::disk('local')->download($path,$kind==='identity'?$v->identity_document_name:$v->support_document_name,[
   'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store'
  ]);
 }
 public function adminReview(Request $r,$id){
  abort_unless(auth('admin')->check(),403);$this->ready();
  $d=$r->validate(['status'=>'required|in:approved,rejected','review_note'=>'required_if:status,rejected|nullable|string|min:5|max:1000']);
  $count=DB::table('spad_building_manager_verifications')->where('id',$id)->where('status','pending')->update([
   'status'=>$d['status'],'review_note'=>$d['review_note']??null,
   'reviewed_by_admin_id'=>auth('admin')->id(),'reviewed_at'=>now(),'updated_at'=>now()
  ]);
  abort_unless($count,422,'این درخواست در انتظار بررسی نیست یا قبلاً بررسی شده است.');
  return response()->json(['message'=>$d['status']==='approved'?'احراز هویت مدیر ساختمان تأیید شد.':'درخواست احراز هویت رد شد.']);
 }
}
