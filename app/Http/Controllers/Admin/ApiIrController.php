<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InquiryService;
use App\Services\ApiIrClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiIrController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user() && $request->user()->roles()->where('name', 'Super Admin')->exists(), 403);
        abort_unless(Schema::hasTable('spad_web_service_connections') &&
            Schema::hasTable('spad_web_service_audit') && Schema::hasTable('inquiry_services'), 503,
            'جداول مرکز api.ir هنوز نصب نشده‌اند.');
    }

    private function row()
    {
        return DB::table('spad_web_service_connections')->where('code', 'api-ir')->first();
    }

    private function audit(Request $request, $id, $action, $note): void
    {
        DB::table('spad_web_service_audit')->insert([
            'connection_id' => $id, 'admin_id' => $request->user()->id,
            'action' => $action, 'note' => $note, 'created_at' => now(), 'updated_at' => now()
        ]);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $row = $this->row();
        $catalog = ApiIrClient::catalog();
        $services = InquiryService::whereIn('slug', array_keys($catalog))->get()->keyBy('slug');
        $items = [];
        foreach ($catalog as $slug => $info) {
            $service = $services->get($slug);
            $items[] = [
                'slug' => $slug, 'title' => $info['title'],
                'operation' => $info['operation'],
                'required_fields'=>array_keys(array_fill_keys(
                    \App\Services\ApiIrInquirySchema::providerKeys()[$slug]??[],true)),
                'customer_fields'=>array_map(function($field){
                    return ['key'=>$field['key'],'label'=>$field['label'],
                        'required'=>(bool)$field['required']];
                },\App\Services\ApiIrInquirySchema::for($slug)),
                'restricted' => !empty($info['restricted']),
                'price' => $service ? (int) $service->price : 0,
                'enabled' => $service ? (bool) $service->enabled : false,
                'installed' => (bool) $service,
                'required_fields' => array_map(function($f) {
                    return ['key'=>$f['key'],'label'=>$f['label'],
                        'required'=>!empty($f['required']),'type'=>$f['type']];
                }, \App\Services\ApiIrInquirySchema::for($slug)),
                'provider_json_keys' => \App\Services\ApiIrInquirySchema::providerKeys()[$slug]??[],
            ];
        }
        $identityStats = [
            'total'=>0, 'approved'=>0, 'draft'=>0, 'rejected'=>0, 'legacy'=>0,
        ];
        if (Schema::hasTable('spad_user_verifications') &&
            Schema::hasColumn('spad_user_verifications', 'verification_provider')) {
            $query = DB::table('spad_user_verifications');
            $identityStats['total'] = (clone $query)->count();
            $identityStats['approved'] = (clone $query)->where('status','approved')
                ->where('verification_provider','api_ir_shahkarpro')->count();
            $identityStats['draft'] = (clone $query)->where('status','draft')->count();
            $identityStats['rejected'] = (clone $query)->where('status','rejected')->count();
            $identityStats['legacy'] = (clone $query)->where('status','approved')
                ->where(function ($q) {
                    $q->whereNull('verification_provider')->orWhere('verification_provider','!=','api_ir_shahkarpro');
                })->count();
        }
        $feature = app(\App\Services\ApiIrAccountServices::class);
        $biometric=app(\App\Services\SpadBiometricAccess::class);
        $tierTwoCount=Schema::hasTable('spad_biometric_verifications')
            ? DB::table('spad_biometric_verifications')
                ->where('status','matched')->where('expires_at','>',now())->count() : 0;
        return response()->json(['data' => [
            'biometric_verified_count'=>$tierTwoCount,
            'biometric_policy'=>[
                'video_live'=>$feature->enabled('video_live'),
                'video_live_configured'=>!empty($feature->settings()['video_live']),
                'special_services'=>$biometric->specialServices(),
                'available_services'=>$biometric->catalog(),
                'storage_note'=>'ویدئو و سریال کارت ملی در اسپاد ذخیره نمی‌شوند؛ تنها نتیجه و زمان اعتبار سطح ویژه ثبت می‌شود.',
            ],
            'identity_version'=>4,
            'identity_stats'=>$identityStats,
            'identity_checks'=>[
                ['code'=>'sms_otp','name'=>'تأیید پیامکی','operation'=>'SmsOTP',
                 'enabled'=>$feature->enabled('sms_otp'),'status'=>'configuration_only'],
                ['code'=>'shahkar_pro','name'=>'تطبیق مالکیت سیم‌کارت و کد ملی','operation'=>'ShahkarPro',
                 'enabled'=>$feature->enabled('shahkar_pro'),'status'=>'configuration_only'],
                ['code'=>'video_live','name'=>'بایومتریک چهره، زنده‌سنجی و تطبیق گفتار سطح دو','operation'=>'VideoVerify',
                 'enabled'=>$feature->enabled('video_live'),'status'=>'configuration_only'],
            ],
            'identity_note'=>'احراز هویت پایه پس از تأیید OTP و تطبیق شاهکار پرو انجام می‌شود؛ نام و تاریخ تولد در این سرویس تأیید نمی‌شوند.',
            'last_config_change'=>$row ? $row->updated_at : null,
            'enabled' => $row ? (bool) $row->enabled : false,
            'has_key' => $row && !empty($row->secrets_encrypted),
            'configured' => $row && $row->mode === 'configured',
            'account_features' => app(\App\Services\ApiIrAccountServices::class)->settings(),
            'identity_ready' => (function () {
                $service=app(\App\Services\ApiIrAccountServices::class);
                return $service->enabled('sms_otp') && $service->enabled('shahkar_pro');
            })(),
            'services' => $items,
            'notice' => 'بررسی اتصال بدون مصرف اعتبار است؛ موفقیت واقعی هر سرویس پس از ورود کلید و ارسال درخواست مجاز قابل بررسی است.'
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request)
    {
        $this->guard($request);
        $data = $request->validate(['token' => 'nullable|string|min:12|max:5000', 'enabled' => 'required|boolean']);
        $row = $this->row();
        $hasKey = !empty($data['token']) || ($row && !empty($row->secrets_encrypted));
        abort_if($data['enabled'] && !$hasKey, 422, 'ابتدا کلید API را وارد کنید.');
        $fields = [
            'title' => 'اتصال یکپارچه API.IR', 'category' => 'inquiries',
            'provider' => 'api.ir', 'endpoint' => 'https://s.api.ir',
            'description' => 'استعلام‌های رسمی و احراز هویت چندسطحی اسپاد با یک اتصال محرمانه',
            'enabled' => (bool) $data['enabled'],
            'mode' => $hasKey ? 'configured' : 'planned',
            'health_status' => 'not_tested',
            'last_note' => 'کلید فقط در سرور نگهداری می‌شود؛ تست واقعی هنوز انجام نشده است.',
            'updated_by' => $request->user()->id, 'updated_at' => now()
        ];
        if (!empty($data['token'])) $fields['secrets_encrypted'] = Crypt::encryptString(trim($data['token']));
        if ($row) {
            DB::table('spad_web_service_connections')->where('id', $row->id)->update($fields);
            $id = $row->id;
        } else {
            $fields['code'] = 'api-ir';
            $fields['created_at'] = now();
            $id = DB::table('spad_web_service_connections')->insertGetId($fields);
        }
        $this->audit($request, $id, 'apiir_configured', !empty($data['token']) ? 'کلید رمزنگاری و تنظیم شد.' : 'وضعیت اتصال تغییر یافت.');
        return $this->index($request);
    }

    public function saveAccountFeatures(Request $request)
    {
        $this->guard($request);
        $data=$request->validate(['sms_otp'=>'required|boolean','shahkar_pro'=>'required|boolean',
            'otp_template'=>'required|integer|between:0,4']);
        $row=$this->row();
        abort_unless($row,422,'ابتدا اتصال api.ir را ثبت کنید.');
        if($data['sms_otp']||$data['shahkar_pro'])
            abort_unless($row->enabled&&$row->mode==='configured'&&$row->secrets_encrypted,
                422,'ابتدا کلید و اتصال اصلی api.ir را فعال کنید.');
        $public=json_decode((string)$row->config_public,true);
        if(!is_array($public))$public=[];
        $public['sms_otp_enabled']=$data['sms_otp']?'1':'0';
        $public['shahkar_pro_enabled']=$data['shahkar_pro']?'1':'0';
        // Retired KYC methods stay available for audit only, never as fallback.
        $public['shahkar_apiir_enabled']='0';
        $public['otp_template']=(string)$data['otp_template'];
        DB::table('spad_web_service_connections')->where('id',$row->id)->update([
            'config_public'=>json_encode($public,JSON_UNESCAPED_UNICODE),
            'updated_by'=>$request->user()->id,'updated_at'=>now()]);
        $this->audit($request,$row->id,'apiir_account_features','تنظیمات احراز هویت نسخه شاهکار پرو ذخیره شد.');
        return $this->index($request);
    }
    public function saveBiometricPolicy(Request $request)
    {
        $this->guard($request);
        $data=$request->validate([
            'video_live'=>'required|boolean',
            'special_services'=>'present|array|max:200',
            'special_services.*'=>'required|string|max:160',
        ]);
        $access=app(\App\Services\SpadBiometricAccess::class);
        $allowed=array_column($access->catalog(),'code');
        $selected=array_values(array_unique($data['special_services']));
        abort_if(count(array_diff($selected,$allowed))>0,422,
            'بعضی خدمات انتخاب‌شده فاقد کنترل دسترسی سمت سرور هستند.');
        abort_unless(Schema::hasTable('spad_biometric_verifications'),503,
            'ابتدا جدول احراز هویت ویدئویی نصب شود.');
        $row=$this->row();
        abort_unless($row,422,'اتصال API.ir تنظیم نشده است.');
        if ($data['video_live']) {
            abort_unless($row->enabled && $row->mode==='configured' && $row->secrets_encrypted,
                422,'ابتدا اتصال اصلی API.ir را فعال کنید.');
            abort_unless(app(\App\Services\ApiIrAccountServices::class)->enabled('shahkar_pro'),
                422,'احراز هویت پایه شاهکار پرو باید فعال باشد.');
        }
        $config=json_decode((string)$row->config_public,true);
        if(!is_array($config))$config=[];
        $config['video_live_enabled']=$data['video_live']?'1':'0';
        $config['biometric_special_services']=$selected;
        DB::table('spad_web_service_connections')->where('id',$row->id)->update([
            'config_public'=>json_encode($config,JSON_UNESCAPED_UNICODE),
            'updated_by'=>$request->user()->id,'updated_at'=>now(),
        ]);
        $this->audit($request,$row->id,'apiir_biometric_policy',
            'فهرست خدمات ویژه و وضعیت VideoLive تغییر یافت؛ تعداد خدمات: '.count($selected));
        return $this->index($request);
    }

    public function service(Request $request, string $slug)
    {
        $this->guard($request);
        abort_unless(ApiIrClient::supports($slug), 404);
        $data = $request->validate([
            'enabled' => 'required|boolean', 'price' => 'required|integer|min:0|max:999999999'
        ]);
        $row = $this->row();
        $model = InquiryService::where('slug', $slug)->firstOrFail();
        if ($data['enabled']) {
            abort_unless($row && $row->enabled && $row->mode === 'configured' && $row->secrets_encrypted,
                422, 'ابتدا اتصال و کلید api.ir را فعال کنید.');
            abort_unless((int) $data['price'] > 0, 422,
                'برای جلوگیری از مصرف اعتبار رایگان، تعرفه فروش این استعلام را مشخص کنید.');
        }
        $model->update(['price' => (int) $data['price'], 'enabled' => (bool) $data['enabled']]);
        $this->audit($request, $row ? $row->id : 0, 'apiir_service_updated', $slug . ' ' . ($data['enabled'] ? 'enabled' : 'disabled'));
        return $this->index($request);
    }
}
