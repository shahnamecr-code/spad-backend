<?php
namespace App\Services;

use App\Models\Advertisement;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sale-price matching and consent-safe, one-off SMS outbox.
 * Never call the provider during the HTTP request that publishes a listing.
 */
class SpadCustomerSmsAutomation
{
    const DELTA_TOMAN=1000000000;

    private static function ready(): bool
    {
        return Schema::hasTable('spad_customer_sms_outbox')
            && Schema::hasTable('spad_crm_contacts')
            && Schema::hasColumn('spad_crm_contacts','budget_toman');
    }

    private static function digits($value): string
    {
        return strtr((string)$value,[
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'
        ]);
    }

    private static function normalized($value): string
    {
        return mb_strtolower(preg_replace('/[\s\x{200c}\x{200d}_-]+/u','',self::digits($value)));
    }

    private static function sale(Advertisement $ad): bool
    {
        $category=$ad->category;
        $path='';
        for($i=0;$i<6 && $category;$i++){
            $path.=' '.self::normalized($category->slug.' '.$category->name);
            $category=$category->parent ? Category::find($category->parent) : null;
        }
        if(preg_match('/(رهن|اجاره|پیشفروش|روزانه|rent|lease|daily|auction)/ui',$path))return false;
        if(preg_match('/(خرید|فروش|buy|sale|purchase)/ui',$path))return true;
        // Never assume an apartment or villa category is a sale without an
        // explicit sale/buy transaction field.
        $deal=DB::table('advertisement_properties')
            ->where('advertisement_id',$ad->id)
            ->whereIn('property_slug',['transaction','transaction_type','deal_type','type_of_transaction'])
            ->pluck('text_value')->filter()->implode(' ');
        $deal=self::normalized($deal);
        return $deal!=='' && !preg_match('/(اجاره|رهن|پیشفروش|rent|lease)/ui',$deal)
            && (bool)preg_match('/(خرید|فروش|buy|sale|purchase)/ui',$deal);
    }

    public static function priceToman(Advertisement $ad): ?int
    {
        if((int)$ad->status!==Advertisement::APPROVED || !self::sale($ad))return null;
        $rows=DB::table('advertisement_properties as ap')
            ->join('properties as p','p.slug','=','ap.property_slug')
            ->where('ap.advertisement_id',$ad->id)
            ->get(['ap.property_slug','ap.integer_value','ap.text_value','p.title']);
        $prices=[];
        foreach($rows as $property) {
            $slug=strtolower((string)$property->property_slug);
            $title=self::normalized($property->title);
            $named=(bool)preg_match('/^(قیمتکل|قیمتفروش|قیمتملک|قیمتنهایی|مبلغکل|مبلغفروش|بهایکل|ارزشکل)(تومان|ریال)?$/u',$title);
            $known=in_array($slug,['price','total_price','sale_price','full_price','property_price','sale_total_price','total_cost'],true);
            if(!$named && !$known)continue;
            if(preg_match('/(متری|هرمتر|per_meter|unit_price)/ui',$title.' '.$slug))continue;
            $raw=$property->integer_value!==null ? $property->integer_value : $property->text_value;
            $value=preg_replace('/[^0-9]/','',self::digits($raw));
            if($value==='' || strlen($value)>15)continue;
            $price=(int)$value;
            if(mb_strpos($title,'ریال')!==false)$price=(int)round($price/10);
            if($price<100000000 || $price>100000000000000)continue;
            $prices[]=$price;
        }
        $prices=array_values(array_unique($prices));
        return count($prices)===1?$prices[0]:null;
    }

    public static function enqueuePublished(Advertisement $ad): int
    {
        if(!self::ready() || !app(SpadSmsService::class)->eventEnabled('property_match'))return 0;
        $price=self::priceToman($ad);
        if(!$price)return 0;
        $min=max(1000000,$price-self::DELTA_TOMAN);
        $max=$price+self::DELTA_TOMAN;
        $inserted=0;
        DB::table('spad_crm_contacts')->whereNull('deleted_at')->whereNull('opted_out_at')
            ->where('sms_consent',true)->whereNotNull('consent_recorded_at')
            ->where('interest','buy')->whereBetween('budget_toman',[$min,$max])
            ->chunkById(150,function($contacts)use($ad,&$inserted){
                $rows=[];
                foreach($contacts as $c) {
                    if(!preg_match('/^09[0-9]{9}$/D',(string)$c->mobile))continue;
                    if($c->city && !preg_match('/شیراز/u',(string)$c->city))continue;
                    $rows[]=[
                        'contact_id'=>$c->id,'advertisement_id'=>$ad->id,
                        'kind'=>'property_match','status'=>'pending',
                        'dedup_hash'=>hash('sha256','match:'.$ad->id.':'.$c->id),
                        'created_at'=>now(),'updated_at'=>now()
                    ];
                }
                if($rows)$inserted+=DB::table('spad_customer_sms_outbox')->insertOrIgnore($rows);
            });
        return $inserted;
    }

    public static function processPending(int $limit=8): array
    {
        if(!self::ready())return ['processed'=>0,'reason'=>'not_installed'];
        $sms=app(SpadSmsService::class);
        if(!$sms->configured())return ['processed'=>0,'reason'=>'provider_disabled'];
        $hour=(int)now()->timezone('Asia/Tehran')->format('G');
        if($hour<9 || $hour>=19)return ['processed'=>0,'reason'=>'outside_service_hours'];
        $sentToday=DB::table('spad_customer_sms_outbox')->whereDate('attempted_at',now()->toDateString())->count();
        if($sentToday>=100)return ['processed'=>0,'reason'=>'daily_cap'];
        $pending=DB::table('spad_customer_sms_outbox')->where('status','pending')
            ->orderBy('id')->limit(min(max(1,$limit),100-$sentToday))->get();
        $done=0;
        foreach($pending as $r){
            $claimed=DB::table('spad_customer_sms_outbox')->where('id',$r->id)->where('status','pending')
                ->update(['status'=>'sending','attempted_at'=>now(),'updated_at'=>now()]);
            if(!$claimed)continue;
            $status='skipped';$error=null;
            try {
                $c=DB::table('spad_crm_contacts')->where('id',$r->contact_id)
                    ->whereNull('deleted_at')->whereNull('opted_out_at')
                    ->where('sms_consent',true)->whereNotNull('consent_recorded_at')->first();
                if(!$c || !preg_match('/^09[0-9]{9}$/D',(string)$c->mobile)) {
                    $error='NO_CONSENT';
                }elseif(DB::table('spad_customer_sms_outbox')->where('contact_id',$r->contact_id)
                    ->where('id','!=',$r->id)->whereDate('attempted_at',now()->toDateString())
                    ->whereIn('status',['sending','accepted','unknown','failed'])->count()>=2) {
                    $error='CONTACT_DAILY_LIMIT';
                }elseif($r->kind==='property_match'){
                    $ad=Advertisement::find($r->advertisement_id);
                    $price=$ad?self::priceToman($ad):null;
                    if(!$price || !$c->budget_toman || $c->interest!=='buy'
                        || abs((int)$c->budget_toman-$price)>self::DELTA_TOMAN){
                        $error='NO_LONGER_MATCHES';
                    }elseif(!$sms->eventEnabled('property_match')){
                        $error='EVENT_DISABLED';
                    }else {
                        $ok=$sms->notify('property_match',$c->mobile,[
                            'id'=>(string)$ad->id,'price'=>number_format($price)
                        ],'match:'.$ad->id.':'.$c->id);
                        $status=$ok?'accepted':'unknown';
                        if(!$ok)$error='NOT_CONFIRMED';
                    }
                }elseif($r->kind==='manual'){
                    if(!$sms->eventEnabled('manual_customer_sms'))$error='EVENT_DISABLED';
                    else {
                        $ok=$sms->sendManual($c->mobile,(string)$r->message,'campaign:'.$r->dedup_hash);
                        $status=$ok?'accepted':'unknown';
                    }
                }else $error='UNKNOWN_KIND';
            }catch(\Throwable $e){
                // A timeout may follow a successful provider acceptance:
                // never retry automatically or expose provider response secrets.
                $status='unknown';$error='PROVIDER_OR_NETWORK';
            }
            DB::table('spad_customer_sms_outbox')->where('id',$r->id)->update([
                'status'=>$status,'error_code'=>$error,'updated_at'=>now()
            ]);
            $done++;
        }
        return ['processed'=>$done,'reason'=>'completed'];
    }
}
