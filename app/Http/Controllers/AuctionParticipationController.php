<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletGateway;
use App\Models\WalletSetting;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AuctionParticipationController extends Controller
{
 private function available() {
  abort_unless(Schema::hasTable('spad_auction_participations'),503,'بخش مالی مزایده در حال راه‌اندازی است.');
  $settings=DB::table('spad_community_settings')->where('id',1)->first();
  abort_unless($settings && $settings->auctions_enabled,503,'سرویس مزایده موقتاً غیرفعال است.');
  return $settings;
 }
 private function requireAdmin() {abort_unless(auth('admin')->check(),403,'دسترسی مدیریت لازم است.');}
 private function auction($id) {
  $x=DB::table('spad_property_auctions')->where('id',$id)->first();
  abort_unless($x,404,'مزایده یافت نشد.');
  return $x;
 }
 public function status(Request $request,$id,WalletService $wallets) {
  $settings=$this->available();
  $auction=$this->auction($id);
  abort_unless($auction->status==='published',404);
  $user=$request->user();
  $uid=(int)$user->id;
  $entry=DB::table('spad_auction_participations')->where('auction_id',$id)->where('user_id',$uid)->first();
  $hasBid=DB::table('spad_auction_bids')->where('auction_id',$id)->where('user_id',$uid)->exists();
  $wallet=$wallets->walletFor($user);
  return response()->json(['data'=>[
   'deposit_required'=>(bool)$settings->auction_deposit_enabled && !$hasBid && (!$entry || $entry->status!=='held'),
   'deposit_enabled'=>(bool)$settings->auction_deposit_enabled,
   'deposit_toman'=>(int)$settings->auction_deposit_toman,
   'terms'=>(string)($settings->auction_deposit_terms?:'ودیعه برای تأیید حضور در مزایده از کیف پول کسر و تا بازپرداخت توسط مدیریت در سوابق مالی نگهداری می‌شود.'),
   'participation'=>$entry?[
    'id'=>(int)$entry->id,'status'=>$entry->status,'amount_toman'=>(int)$entry->amount_toman,
    'refunded_at'=>$entry->refunded_at,
   ]:null,
   'has_previous_bids'=>$hasBid,
   'wallet'=>[
    'balance'=>(int)$wallet->balance,'status'=>$wallet->status,
    'enabled'=>WalletSetting::valueOf('wallet_enabled','1')==='1',
    'topup_enabled'=>WalletSetting::valueOf('topup_enabled','1')==='1',
    'min_topup'=>(int)WalletSetting::valueOf('min_topup',100000),
    'max_topup'=>(int)WalletSetting::valueOf('max_topup',100000000),
    'gateways'=>WalletGateway::query()->where('enabled',true)->get(['id','title','driver','is_default']),
   ],
  ]]);
 }
 public function join(Request $request,$id,WalletService $wallets) {
  $user=$request->user();
  $uid=(int)$user->id;
  $result=DB::transaction(function()use($id,$uid,$user,$wallets){
   // Serializes concurrent enrollments for this auction; wallet debit has its own row lock.
   $auction=DB::table('spad_property_auctions')->where('id',$id)->lockForUpdate()->first();
   abort_unless($auction && $auction->status==='published',404,'مزایده فعال یافت نشد.');
   abort_if((int)$auction->owner_user_id===$uid,422,'مالک نمی‌تواند در مزایده خود شرکت کند.');
   abort_if(!$auction->ends_at||Carbon::parse($auction->ends_at)->lte(now()),422,'مهلت شرکت در این مزایده پایان یافته است.');
   $settings=$this->available();
   $entry=DB::table('spad_auction_participations')->where('auction_id',$id)->where('user_id',$uid)->first();
   if($entry && $entry->status==='held')return ['status'=>'held','amount_toman'=>(int)$entry->amount_toman,'already_joined'=>true];
   $previousBid=DB::table('spad_auction_bids')->where('auction_id',$id)->where('user_id',$uid)->exists();
   if($previousBid)return ['status'=>'previous_bid','amount_toman'=>0,'already_joined'=>true];
   abort_unless((bool)$settings->auction_deposit_enabled,422,'دریافت ودیعه برای این مزایده فعال نیست؛ می‌توانید بدون پرداخت پیشنهاد بدهید.');
   $amount=(int)$settings->auction_deposit_toman;
   abort_unless($amount>=1000 && $amount<=100000000000,422,'مبلغ ودیعه در تنظیمات مدیریت معتبر نیست.');
   abort_unless(WalletSetting::valueOf('wallet_enabled','1')==='1',422,'کیف پول فعلاً غیرفعال است.');
   abort_if($entry,422,'سابقه مشارکت قبلاً ثبت شده است؛ برای بررسی با پشتیبانی تماس بگیرید.');
   try{
    $transaction=$wallets->debit($user,$amount,[
     'type'=>'auction_deposit','reference'=>'auction-deposit:'.$id.':'.$uid,
     'title'=>'ودیعه شرکت در مزایده شماره '.$id,
     'description'=>'کسر ودیعه قابل پیگیری از کیف پول؛ بازگشت وجه پس از بررسی مدیریت',
     'source_type'=>'spad_property_auctions','source_id'=>$id,
     'meta'=>['auction_id'=>(int)$id,'deposit_amount_toman'=>$amount]
    ]);
   }catch(RuntimeException $e){
    abort(422,$e->getMessage());
   }
   DB::table('spad_auction_participations')->insert([
    'auction_id'=>$id,'user_id'=>$uid,'amount_toman'=>$amount,'status'=>'held',
    'wallet_debit_id'=>$transaction->id,'created_at'=>now(),'updated_at'=>now()
   ]);
   return ['status'=>'held','amount_toman'=>$amount,'already_joined'=>false];
  },3);
  return response()->json(['data'=>$result,'message'=>$result['already_joined']?'مشارکت شما پیش‌تر ثبت شده است.':'ودیعه از کیف پول کسر شد و مشارکت شما ثبت گردید.']);
 }
 public function adminList($id) {
  $this->requireAdmin();$this->available();$this->auction($id);
  $rows=DB::table('spad_auction_participations as p')
   ->leftJoin('users as u','u.id','=','p.user_id')
   ->where('p.auction_id',$id)->orderByDesc('p.id')->limit(250)
   ->get(['p.id','p.auction_id','p.user_id','p.amount_toman','p.status','p.refunded_at','p.refund_reason','p.created_at',
    'u.first_name','u.last_name','u.mobile']);
  return response()->json(['data'=>$rows,'total_held_toman'=>(int)DB::table('spad_auction_participations')->where('auction_id',$id)->where('status','held')->sum('amount_toman')]);
 }
 public function adminRefund(Request $request,$id,$participantId,WalletService $wallets) {
  $this->requireAdmin();
  $d=$request->validate(['reason'=>'required|string|min:8|max:1000']);
  $adminId=(int)auth('admin')->id();
  $result=DB::transaction(function()use($id,$participantId,$wallets,$d,$adminId){
   $this->auction($id);
   $entry=DB::table('spad_auction_participations')->where('id',$participantId)
    ->where('auction_id',$id)->lockForUpdate()->first();
   abort_unless($entry,404);
   abort_unless($entry->status==='held',422,'این ودیعه قبلاً بازپرداخت شده یا قابل بازپرداخت نیست.');
   $user=User::query()->findOrFail($entry->user_id);
   // Exactly one wallet credit per participation, even after retries and network failures.
   $tx=$wallets->credit($user,(int)$entry->amount_toman,[
    'type'=>'refund','reference'=>'auction-deposit-refund:'.$entry->id,
    'source_type'=>'spad_auction_participations','source_id'=>$entry->id,
    'title'=>'بازگشت ودیعه مزایده شماره '.$id,'description'=>$d['reason'],
    'admin_id'=>$adminId,'allow_inactive'=>true
   ]);
   DB::table('spad_auction_participations')->where('id',$entry->id)->update([
    'status'=>'refunded','wallet_refund_id'=>$tx->id,
    'refund_reason'=>$d['reason'],'refunded_at'=>now(),
    'refunded_by_admin_id'=>$adminId,'updated_at'=>now()
   ]);
   return ['id'=>(int)$entry->id,'wallet_refund_id'=>(int)$tx->id];
  },3);
  return response()->json(['message'=>'ودیعه به کیف پول کاربر بازگشت داده شد و سند مالی ثبت گردید.','data'=>$result]);
 }
}
