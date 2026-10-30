<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MaterialPricesController extends Controller
{
    private const CATEGORIES = ['آهن‌آلات و فولاد','سیمان، گچ و ملات','آجر، بلوک و سفال','بتن و سنگدانه','کاشی، سرامیک و سنگ','عایق و آب‌بندی','لوله و تأسیسات','برق و کابل','چوب و دکوراسیون','رنگ و پوشش','درب، پنجره و نما','سایر مصالح'];
    private const URL = 'https://local.framework.persianapi.com/web-service/retailMarket/materials';

    private function settings()
    {
        abort_unless(Schema::hasTable('material_price_settings'), 503, 'سرویس قیمت مصالح هنوز آماده نشده است.');
        return DB::table('material_price_settings')->where('id',1)->first();
    }
    private function guardAdmin()
    {
        abort_unless(auth('admin')->check(),403,'دسترسی مدیریت لازم است.');
    }
    private function settingsOutput($settings)
    {
        return [
            'enabled'=>(bool)$settings->enabled, 'sync_enabled'=>(bool)$settings->sync_enabled,
            'provider'=>$settings->provider, 'api_connected'=>!empty($settings->encrypted_api_token),
            'refresh_minutes'=>(int)$settings->refresh_minutes, 'stale_minutes'=>(int)$settings->stale_minutes,
            'provider_price_divisor'=>(int)$settings->provider_price_divisor,
            'title'=>$settings->title,'subtitle'=>$settings->subtitle,'notice'=>$settings->notice,
            'last_sync_at'=>$settings->last_sync_at,'last_sync_status'=>$settings->last_sync_status
        ];
    }
    public function index()
    {
        $settings=$this->settings();
        if (!$settings->enabled) return response()->json(['data'=>array_merge($this->settingsOutput($settings),['materials'=>[],'categories'=>[],'generated_at'=>now()->toIso8601String()])]);
        $items=DB::table('material_price_products')->where('enabled',true)->orderBy('sort_order')->orderBy('id')->limit(1500)->get();
        $cats=[];
        $now=now();
        $materials=$items->map(function($item) use (&$cats,$settings,$now) {
            $cats[$item->category]=($cats[$item->category]??0)+1;
            return [
              'id'=>$item->id, 'sku'=>$item->sku,'name'=>$item->name,'category'=>$item->category,
              'brand'=>$item->brand,'unit'=>$item->unit,'price_toman'=>$item->price_toman===null?null:(int)$item->price_toman,
              'previous_price_toman'=>$item->previous_price_toman===null?null:(int)$item->previous_price_toman,
              'change_percent'=>$item->change_percent===null?null:(float)$item->change_percent,
              'source_type'=>$item->source_type,'source_url'=>$item->source_url,'source_updated_at'=>$item->source_updated_at,
              'featured'=>(bool)$item->featured,
              'is_fresh'=>$item->price_toman!==null && $item->source_type==='api' &&
                $item->last_checked_at && Carbon::parse($item->last_checked_at)->diffInMinutes($now)<=(int)$settings->stale_minutes
            ];
        });
        $categories=[];
        foreach(self::CATEGORIES as $name) {
            if(isset($cats[$name]))$categories[]=['name'=>$name,'count'=>$cats[$name]];
            unset($cats[$name]);
        }
        foreach($cats as $name=>$count)$categories[]=['name'=>$name,'count'=>$count];
        return response()->json(['data'=>array_merge($this->settingsOutput($settings),[
            'materials'=>$materials,'categories'=>$categories,'generated_at'=>$now->toIso8601String()
        ])])->header('Cache-Control','private, no-cache');
    }
    public function adminIndex()
    {
        $this->guardAdmin();
        $settings=$this->settings();
        return response()->json(['data'=>[
            'settings'=>$this->settingsOutput($settings),
            'materials'=>DB::table('material_price_products')->orderBy('sort_order')->orderBy('id')->limit(2000)->get(),
            'categories'=>self::CATEGORIES
        ]]);
    }
    public function adminSaveSettings(Request $request)
    {
        $this->guardAdmin();
        $data=$request->validate([
            'enabled'=>'required|boolean','sync_enabled'=>'required|boolean',
            'refresh_minutes'=>'required|integer|min:15|max:1440',
            'stale_minutes'=>'required|integer|min:15|max:10080',
            'provider_price_divisor'=>'required|integer|in:1,10',
            'title'=>'required|string|max:160','subtitle'=>'nullable|string|max:1500',
            'notice'=>'nullable|string|max:2000',
            'api_token'=>'nullable|string|max:2048',
            'clear_api_token'=>'nullable|boolean'
        ]);
        $settings=$this->settings();
        if(($data['sync_enabled']??false) && empty($settings->encrypted_api_token) && empty($data['api_token']))
            return response()->json(['message'=>'برای همگام‌سازی خودکار، کلید API ارائه‌دهنده الزامی است.'],422);
        $write=collect($data)->except(['api_token','clear_api_token'])->all();
        if(!empty($data['clear_api_token'])){
            $write['encrypted_api_token']=null;
            $write['sync_enabled']=false;
        } elseif(!empty($data['api_token'])) {
            $write['encrypted_api_token']=Crypt::encryptString($data['api_token']);
        }
        $write['updated_at']=now();
        DB::table('material_price_settings')->where('id',1)->update($write);
        return response()->json(['data'=>$this->settingsOutput($this->settings()),'message'=>'تنظیمات ذخیره شد.']);
    }
    public function adminSaveProduct(Request $request, $id=null)
    {
        $this->guardAdmin();
        $data=$request->validate([
            'name'=>'required|string|max:200','sku'=>'required|string|max:100',
            'category'=>['required','string','max:100'],
            'unit'=>'required|string|max:60','brand'=>'nullable|string|max:130',
            'price_toman'=>'nullable|integer|min:1|max:999999999999',
            'featured'=>'required|boolean','enabled'=>'required|boolean',
            'sort_order'=>'required|integer|min:0|max:1000000',
            'external_id'=>'nullable|string|max:160'
        ]);
        $hasId=$id!==null;
        if($hasId)abort_unless(DB::table('material_price_products')->where('id',$id)->exists(),404);
        $duplicate=DB::table('material_price_products')->where('sku',$data['sku']);
        if($hasId)$duplicate->where('id','!=',$id);
        if($duplicate->exists())return response()->json(['message'=>'کد محصول تکراری است.'],422);
        if(!empty($data['external_id'])) {
            $duplicateExt=DB::table('material_price_products')->where('external_id',$data['external_id']);
            if($hasId)$duplicateExt->where('id','!=',$id);
            if($duplicateExt->exists())return response()->json(['message'=>'شناسه منبع برای کالای دیگری ثبت شده است.'],422);
        }
        $saved=DB::transaction(function()use($data,$id,$hasId){
            $old=$hasId?DB::table('material_price_products')->where('id',$id)->lockForUpdate()->first():null;
            $price=$data['price_toman']??null;
            $write=$data;
            unset($write['price_toman']);
            $write['updated_at']=now();
            if(!$hasId){
                $write['created_at']=now();
                $id=DB::table('material_price_products')->insertGetId($write);
            }else{
                DB::table('material_price_products')->where('id',$id)->update($write);
            }
            if($price!==null && (!$old || (int)$old->price_toman!==(int)$price)) {
                $change=$old && $old->price_toman>0?round(((int)$price-(int)$old->price_toman)*100/(int)$old->price_toman,2):null;
                DB::table('material_price_products')->where('id',$id)->update([
                    'price_toman'=>$price,'previous_price_toman'=>$old?$old->price_toman:null,
                    'change_percent'=>$change,'source_type'=>'manual','source_url'=>null,'source_updated_at'=>now(),
                    'last_checked_at'=>now(),'updated_at'=>now()
                ]);
                DB::table('material_price_history')->insert([
                    'product_id'=>$id,'price_toman'=>$price,'source_type'=>'manual','quoted_at'=>now(),
                    'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            return $id;
        });
        return response()->json(['data'=>DB::table('material_price_products')->where('id',$saved)->first()]);
    }
    public function adminHistory($id)
    {
        $this->guardAdmin();
        return response()->json(['data'=>DB::table('material_price_history')->where('product_id',$id)->orderByDesc('id')->limit(100)->get()]);
    }
    public function adminSync()
    {
        $this->guardAdmin();
        $result=$this->sync();
        return response()->json(['data'=>$result], $result['ok']?200:422);
    }
    public function sync()
    {
        $settings=$this->settings();
        if(!$settings->sync_enabled || empty($settings->encrypted_api_token))
            return ['ok'=>false,'not_configured'=>true,'message'=>'اتصال API فعال نیست یا توکن تنظیم نشده است.'];
        try{
            $token=Crypt::decryptString($settings->encrypted_api_token);
            $response=Http::timeout(18)->withToken($token)->acceptJson()->get(self::URL);
            if(!$response->successful())throw new \RuntimeException('API HTTP '.(int)$response->status());
            $rows=$response->json();
            if(isset($rows['data']) && is_array($rows['data']))$rows=$rows['data'];
            if(!is_array($rows) || count($rows)<1 || count($rows)>5000)throw new \RuntimeException('پاسخ API فاقد داده معتبر است.');
            $count=0;$ignored=0;
            foreach($rows as $record) {
                if(!is_array($record)){ $ignored++;continue; }
                $externalId=(string)($record['id']??'');
                $title=trim((string)($record['title']??''));
                $price=$this->parsePrice($record['price']??null,(int)$settings->provider_price_divisor);
                if(!preg_match('/^[a-zA-Z0-9_\\-]{1,150}$/D',$externalId) || !$title || mb_strlen($title)>200 || $price===null){$ignored++;continue;}
                $brand=trim((string)($record['brand']??''));
                $category=$this->mapCategory((string)($record['category']??''));
                DB::transaction(function()use($externalId,$title,$brand,$category,$price,&$count){
                    $item=DB::table('material_price_products')->where('external_id',$externalId)->lockForUpdate()->first();
                    if(!$item) {
                        $id=DB::table('material_price_products')->insertGetId([
                            'sku'=>'persianapi-'.substr(hash('sha256',$externalId),0,20),
                            'external_id'=>$externalId,'name'=>$title,'category'=>$category,
                            'brand'=>mb_substr($brand,0,130),'unit'=>'واحد نامشخص',
                            'source_type'=>'pending','enabled'=>true,'featured'=>false,
                            'sort_order'=>500,'created_at'=>now(),'updated_at'=>now()
                        ]);
                        $item=DB::table('material_price_products')->where('id',$id)->first();
                    }
                    $write=['last_checked_at'=>now(),'updated_at'=>now()];
                    if((int)$item->price_toman!==$price){
                        $write['price_toman']=$price;
                        $write['previous_price_toman']=$item->price_toman;
                        $write['change_percent']=$item->price_toman>0?round(($price-(int)$item->price_toman)*100/(int)$item->price_toman,2):null;
                        $write['source_type']='api';
                        $write['source_url']='https://workspace.persianapi.com/بازار-مصالح-ساختمانی-15660544e0';
                        $write['source_updated_at']=now();
                        DB::table('material_price_history')->insert(['product_id'=>$item->id,'price_toman'=>$price,'source_type'=>'api','quoted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                    }
                    DB::table('material_price_products')->where('id',$item->id)->update($write);
                    $count++;
                });
            }
            if(!$count)throw new \RuntimeException('هیچ نرخ قابل تأییدی در پاسخ API نبود.');
            DB::table('material_price_settings')->where('id',1)->update(['last_sync_at'=>now(),'last_sync_status'=>'موفق: '.$count.' مورد؛ ردشده: '.$ignored,'updated_at'=>now()]);
            return ['ok'=>true,'updated'=>$count,'ignored'=>$ignored,'message'=>'همگام‌سازی کامل شد.'];
        }catch(\Throwable $e){
            // Never overwrite existing valid quotes when a provider fails.
            DB::table('material_price_settings')->where('id',1)->update(['last_sync_at'=>now(),'last_sync_status'=>'خطا در ارتباط یا اعتبار داده','updated_at'=>now()]);
            \Log::warning('SPAD material price provider failure', ['type'=>get_class($e)]);
            return ['ok'=>false,'message'=>'دریافت یا اعتبارسنجی داده قیمت مصالح انجام نشد؛ قیمت‌های قبلی بدون تغییر ماندند.'];
        }
    }
    private function parsePrice($raw,int $divisor)
    {
        if(is_int($raw))$str=(string)$raw;
        elseif(is_string($raw))$str=trim($raw);
        else return null;
        $str=strtr($str,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $str=str_replace([',','٬',' ','\u{200C}'],'',$str);
        if(!preg_match('/^[0-9]{2,13}$/D',$str))return null;
        $price=(int)round((int)$str/max(1,$divisor));
        return ($price>=100 && $price<=999999999999)?$price:null;
    }
    private function mapCategory(string $name)
    {
        foreach(self::CATEGORIES as $cat)if($cat===$name)return $cat;
        if(preg_match('/میلگرد|آهن|فولاد|ورق|تیرآهن|پروفیل/u',$name))return self::CATEGORIES[0];
        if(preg_match('/سیمان|گچ|ملات/u',$name))return self::CATEGORIES[1];
        if(preg_match('/آجر|بلوک|سفال/u',$name))return self::CATEGORIES[2];
        if(preg_match('/بتن|شن|ماسه/u',$name))return self::CATEGORIES[3];
        if(preg_match('/کاشی|سرامیک|سنگ/u',$name))return self::CATEGORIES[4];
        return 'سایر مصالح';
    }
}
