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
            'provider'=>$settings->provider, 'api_connected'=>$settings->provider==='public_steel' || !empty($settings->encrypted_api_token),
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
              'is_fresh'=>$item->price_toman!==null && in_array($item->source_type,['api','public'],true) &&
                $item->source_updated_at && Carbon::parse($item->source_updated_at)->diffInMinutes($now)<=(int)$settings->stale_minutes
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
            'provider'=>'required|string|in:public_steel,persianapi',
            'title'=>'required|string|max:160','subtitle'=>'nullable|string|max:1500',
            'notice'=>'nullable|string|max:2000',
            'api_token'=>'nullable|string|max:2048',
            'clear_api_token'=>'nullable|boolean'
        ]);
        $settings=$this->settings();
        if(($data['sync_enabled']??false) && $data['provider']==='persianapi' && empty($settings->encrypted_api_token) && empty($data['api_token']))
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
        if(!$settings->sync_enabled)
            return ['ok'=>false,'not_configured'=>true,'message'=>'به‌روزرسانی خودکار توسط مدیر غیرفعال شده است.'];
        if($settings->provider==='public_steel') return $this->syncPublicSteel();
        if(empty($settings->encrypted_api_token))
            return ['ok'=>false,'not_configured'=>true,'message'=>'کلید API ارائه‌دهنده تنظیم نشده است.'];
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

    /**
     * Token-free, limited public source for precisely specified factory rebar.
     * Never assumes a price for the generic unbranded material catalog.
     * Rates refer to the source's own dated factory prices, not a national average.
     */
    private function syncPublicSteel()
    {
        $url='https://ahanmelal.com/rebar/ribbed-rebar-price';
        try {
            $response=Http::timeout(24)->withHeaders([
                'User-Agent'=>'Mozilla/5.0 (compatible; SPADMaterials/1.0)',
                'Accept'=>'text/html',
            ])->get($url);
            if(!$response->successful()) throw new \RuntimeException('Source HTTP '.$response->status());
            $html=$response->body();
            if(strlen($html)<40000 || strlen($html)>2500000 || strpos($html,'ذوب آهن اصفهان')===false)
                throw new \RuntimeException('Unrecognized steel price document');
            // The exact Isfahan plant group header delimits all its price rows.
            $from=strpos($html,'esfahan-zobahan-rebar');
            if($from===false)throw new \RuntimeException('Factory group missing');
            $next=strpos($html,'class="box-table-price filterTable"',$from+80);
            $block=substr($html,$from,$next===false?90000:min(90000,$next-$from));
            if(!preg_match('/آخرین بروزرسانی:\s*<span>\s*([۰-۹0-9\/]+)\s*<\/span>\s*<span>\s*ساعت\s*([۰-۹0-9:]+)\s*<\/span>/u',$block,$stamp))
                throw new \RuntimeException('Market timestamp missing');
            $fa=['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9'];
            $jalali=strtr($stamp[1],$fa);
            $clock=strtr($stamp[2],$fa);
            if(!preg_match('/^(14[0-9]{2})\/([0-9]{1,2})\/([0-9]{1,2})$/D',$jalali,$dt)
                || !preg_match('/^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/D',$clock))
                throw new \RuntimeException('Unrecognized market date');
            $gregorian=$this->jalaliToGregorian((int)$dt[1],(int)$dt[2],(int)$dt[3]);
            $quoted=Carbon::createFromFormat('Y-m-d H:i:s',$gregorian.' '.$clock,'Asia/Tehran')->utc();
            if($quoted->greaterThan(now()->addMinutes(10)) || $quoted->lessThan(now()->subDays(5)))
                throw new \RuntimeException('Source market timestamp stale or future-dated');
            $matches=[];
            preg_match_all('/<div class="tr trbody tr-row">\s*<div class=td>\s*میلگرد\s+(12|14|16|18)\s+ذوب آهن اصفهان\s*<\/div>(.{0,550}?)<div data-price=\d+ data-price2=(\d+) class="td price priceText">/su',$block,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
            $quotes=[];
            foreach($matches as $row) {
                $size=(int)$row[1][0];
                $middle=$row[2][0];
                $price=(int)$row[3][0];
                $position=$row[0][1];
                $nextRow=strpos($block,'<div class="tr trbody tr-row">',$position+50);
                $details=substr($block,$position,$nextRow===false?3000:min(3000,$nextRow-$position));
                if(!preg_match('/<div class=td>\s*A3\s*<\/div>/u',$middle))continue;
                if(!preg_match('/<span>\s*کیلوگرم\s*<\/span>\s*<br>\s*<hr>\s*<span>\s*کارخانه\s*<\/span>/u',$details))continue;
                if($price<50000 || $price>500000)continue;
                if(isset($quotes[$size]) && $quotes[$size]!==$price)throw new \RuntimeException('Ambiguous plant quote');
                $quotes[$size]=$price;
            }
            if(count($quotes)<3)throw new \RuntimeException('Not enough validated rebar prices');
            $updated=0;
            foreach($quotes as $size=>$price) {
                DB::transaction(function()use($size,$price,$url,$quoted,&$updated) {
                    $sku='public-ahanmelal-rebar-'.$size;
                    $old=DB::table('material_price_products')->where('sku',$sku)->lockForUpdate()->first();
                    if(!$old) {
                        $id=DB::table('material_price_products')->insertGetId([
                            'sku'=>$sku,'name'=>'میلگرد آجدار A3 سایز '.$size.' ذوب آهن اصفهان (کارخانه)',
                            'category'=>'آهن‌آلات و فولاد','brand'=>'ذوب آهن اصفهان',
                            'unit'=>'کیلوگرم','enabled'=>true,'featured'=>true,
                            'sort_order'=>12+$size,'created_at'=>now(),'updated_at'=>now()
                        ]);
                        $old=DB::table('material_price_products')->where('id',$id)->first();
                    }
                    // Historical records refer to the provider's timestamp, not the fetch time.
                    $different=$old->price_toman===null || (int)$old->price_toman!==$price;
                    $write=[
                        'source_type'=>'public','source_url'=>$url,
                        'source_updated_at'=>$quoted,'last_checked_at'=>now(),
                        'updated_at'=>now(),
                    ];
                    if($different) {
                        $previous=$old->price_toman===null?null:(int)$old->price_toman;
                        $write['price_toman']=$price;
                        $write['previous_price_toman']=$previous;
                        $write['change_percent']=$previous>0?round(100*($price-$previous)/$previous,2):null;
                        DB::table('material_price_history')->insert([
                            'product_id'=>$old->id,'price_toman'=>$price,'source_type'=>'public',
                            'quoted_at'=>$quoted,'created_at'=>now(),'updated_at'=>now()
                        ]);
                    }
                    DB::table('material_price_products')->where('id',$old->id)->update($write);
                    $updated++;
                });
            }
            DB::table('material_price_settings')->where('id',1)->update([
                'last_sync_at'=>now(),'last_sync_status'=>'موفق: '.$updated.' نرخ کارخانه‌ای تاریخ‌دار',
                'updated_at'=>now()
            ]);
            return ['ok'=>true,'updated'=>$updated,'message'=>'نرخ‌های کارخانه‌ای معتبر دریافت شدند.'];
        }catch(\Throwable $e){
            DB::table('material_price_settings')->where('id',1)->update([
                'last_sync_at'=>now(),'last_sync_status'=>'منبع عمومی موقتاً نامعتبر یا در دسترس نیست',
                'updated_at'=>now()
            ]);
            \Log::warning('SPAD public steel quote validation failed',['error_type'=>get_class($e)]);
            return ['ok'=>false,'message'=>'دریافت نرخ عمومی تأیید نشد. آخرین قیمت‌های معتبر بدون تغییر باقی ماندند.'];
        }
    }

    private function jalaliToGregorian(int $jy,int $jm,int $jd): string
    {
        if($jy<1300 || $jy>1550 || $jm<1 || $jm>12 || $jd<1 || $jd>31)
            throw new \RuntimeException('Invalid Jalali date');
        $jy+=1595;
        $days=-355668+365*$jy+intdiv($jy,33)*8+intdiv(($jy%33)+3,4)+$jd
            +($jm<7?($jm-1)*31:($jm-7)*30+186);
        $gy=400*intdiv($days,146097);
        $days=$days%146097;
        if($days>36524){
            $gy+=100*intdiv($days-1,36524);
            $days=($days-1)%36524;
            if($days>=365)$days++;
        }
        $gy+=4*intdiv($days,1461);
        $days=$days%1461;
        if($days>365){
            $gy+=intdiv($days-1,365);
            $days=($days-1)%365;
        }
        $gd=$days+1;
        $months=[0,31,($gy%4===0 && ($gy%100!==0 || $gy%400===0))?29:28,31,30,31,30,31,31,30,31,30,31];
        $gm=1;
        while($gm<=12 && $gd>$months[$gm]){$gd-=$months[$gm];$gm++;}
        return sprintf('%04d-%02d-%02d',$gy,$gm,$gd);
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
