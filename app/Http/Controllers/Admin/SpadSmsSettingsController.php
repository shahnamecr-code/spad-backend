<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SpadSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class SpadSmsSettingsController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->roles()->where('name','Super Admin')->exists(),403,
            'تنظیمات کلید پیامک فقط برای مدیر اصلی مجاز است.');
        abort_unless(Schema::hasTable('spad_sms_settings') &&
            Schema::hasColumn('spad_sms_settings','provider') &&
            Schema::hasTable('spad_sms_delivery_logs'),503,'مرکز پیامک هنوز به‌روزرسانی نشده است.');
    }
    public function index(Request $request)
    {
        $this->admin($request);
        return response()->json([
            'data'=>app(SpadSmsService::class)->settings(),
            'logs'=>DB::table('spad_sms_delivery_logs')->orderByDesc('id')->limit(40)
                ->get(['id','event','mobile_masked','status','error_code','created_at']),
        ])->header('Cache-Control','private, no-store');
    }
    public function save(Request $request)
    {
        $this->admin($request);
        $data=$request->validate([
            'enabled'=>'required|boolean',
            'api_key'=>'nullable|string|min:8|max:512',
            'clear_api_key'=>'sometimes|boolean',
            'sender'=>['nullable','string','max:30','regex:/^[0-9+]*$/D'],
            'daily_limit'=>'required|integer|min:10|max:10000',
            'events'=>'required|array',
            'events.*.enabled'=>'required|boolean',
            'events.*.pattern_code'=>['nullable','string','max:100','regex:/^[A-Za-z0-9_-]*$/D'],
        ]);
        $old=DB::table('spad_sms_settings')->where('id',1)->first();
        foreach(array_keys($data['events']) as $event) {
            if(!isset(SpadSmsService::EVENTS[$event]))
                return response()->json(['message'=>'رویداد پیامک ناشناخته است.'],422);
        }
        $events=[];
        $otp=['login_otp','kyc_otp','phone_change_otp','password_reset_otp','handover_otp','admin_signup_otp'];
        foreach(SpadSmsService::EVENTS as $event=>$label) {
            $r=$data['events'][$event]??[];
            $events[$event]=[
                'enabled'=>(bool)($r['enabled']??false),
                'pattern_code'=>trim((string)($r['pattern_code']??'')),
            ];
        }
        $key=$old && $old->provider==='iranpayamak'?$old->api_key_encrypted:null;
        if(!empty($data['clear_api_key']))$key=null;
        if(!empty($data['api_key']))$key=Crypt::encryptString($data['api_key']);
        if((bool)$data['enabled']) {
            if(!$key || empty($data['sender']))
                return response()->json(['message'=>'کلید API و خط فرستنده ایران پیامک الزامی است.'],422);
            $hasEvents=false;
            foreach($events as $name=>$rule) {
                if(!$rule['enabled'])continue;
                $hasEvents=true;
                if(in_array($name,$otp,true) && $rule['pattern_code']==='')
                    return response()->json(['message'=>'برای رویدادهای کد تأیید، کد الگوی خدماتی ضروری است.'],422);
            }
            if(!$hasEvents)return response()->json(['message'=>'حداقل یک رویداد را فعال کنید.'],422);
        }
        $values=[
            'provider'=>'iranpayamak',
            'enabled'=>(bool)$data['enabled'],
            'username'=>null,
            'api_key_encrypted'=>$key,
            'sender'=>trim($data['sender']??''),
            'daily_limit'=>(int)$data['daily_limit'],
            'events_json'=>json_encode($events,JSON_UNESCAPED_UNICODE),
            'updated_at'=>now(),
        ];
        if($old)DB::table('spad_sms_settings')->where('id',1)->update($values);
        else DB::table('spad_sms_settings')->insert(array_merge(['id'=>1,'created_at'=>now()],$values));
        return response()->json(['status'=>true,'message'=>'تنظیمات ایران پیامک ذخیره شد.'])
            ->header('Cache-Control','private, no-store');
    }
    public function test(Request $request)
    {
        $this->admin($request);
        $data=$request->validate(['mobile'=>'required|string|regex:/^09[0-9]{9}$/D']);
        $key='spad:sms:test:'.$request->user()->getKey();
        abort_if(RateLimiter::tooManyAttempts($key,2),429,'سقف آزمایش تکمیل شد؛ بعداً تلاش کنید.');
        RateLimiter::hit($key,900);
        $sms=app(SpadSmsService::class);
        abort_unless($sms->configured(),422,'ابتدا ایران پیامک را ذخیره و فعال کنید.');
        abort_unless($sms->eventEnabled('registration'),422,'برای آزمایش، رویداد «ثبت‌نام موفق» را فعال کنید.');
        $ok=$sms->notify('registration',$data['mobile'],[],
            'test:'.$request->user()->getKey().':'.uniqid('',true));
        return response()->json([
            'status'=>$ok,
            'message'=>$ok?'درخواست آزمایشی در ایران پیامک پذیرفته شد.':'درخواست آزمایشی رد شد؛ گزارش را بررسی کنید.',
        ],$ok?200:502);
    }
}
