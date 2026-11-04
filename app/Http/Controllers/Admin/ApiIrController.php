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
                'restricted' => !empty($info['restricted']),
                'price' => $service ? (int) $service->price : 0,
                'enabled' => $service ? (bool) $service->enabled : false,
                'installed' => (bool) $service,
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
                ->where('verification_provider','api_ir')->count();
            $identityStats['draft'] = (clone $query)->where('status','draft')->count();
            $identityStats['rejected'] = (clone $query)->where('status','rejected')->count();
            $identityStats['legacy'] = (clone $query)->where('status','approved')
                ->where(function ($q) {
                    $q->whereNull('verification_provider')->orWhere('verification_provider','!=','api_ir');
                })->count();
        }
        $feature = app(\App\Services\ApiIrAccountServices::class);
        return response()->json(['data' => [
            'identity_version'=>3,
            'identity_stats'=>$identityStats,
            'identity_checks'=>[
                ['code'=>'sms_otp','name'=>'تأیید پیامکی','operation'=>'SmsOTP',
                 'enabled'=>$feature->enabled('sms_otp'),'status'=>'configuration_only'],
                ['code'=>'person_info','name'=>'استعلام مشخصات هویتی','operation'=>'PersonInfo',
                 'enabled'=>$feature->enabled('person_info'),'status'=>'configuration_only'],
            ],
            'identity_note'=>'تأیید مالکیت سیم‌کارت به کد ملی انجام نمی‌شود؛ نتیجه سرویس‌ها بدون ارسال درخواست هزینه‌دار آزمایش نشده است.',
            'last_config_change'=>$row ? $row->updated_at : null,
            'enabled' => $row ? (bool) $row->enabled : false,
            'has_key' => $row && !empty($row->secrets_encrypted),
            'configured' => $row && $row->mode === 'configured',
            'account_features' => app(\App\Services\ApiIrAccountServices::class)->settings(),
            'identity_ready' => (function () {
                $service=app(\App\Services\ApiIrAccountServices::class);
                return $service->enabled('sms_otp') && $service->enabled('person_info');
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
            'description' => 'هفت استعلام رسمی در اسپاد با یک کلید محرمانه',
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
        $data=$request->validate(['sms_otp'=>'required|boolean','person_info'=>'required|boolean',
            'otp_template'=>'required|integer|between:0,4']);
        $row=$this->row();
        abort_unless($row,422,'ابتدا اتصال api.ir را ثبت کنید.');
        if($data['sms_otp']||$data['person_info'])
            abort_unless($row->enabled&&$row->mode==='configured'&&$row->secrets_encrypted,
                422,'ابتدا کلید و اتصال اصلی api.ir را فعال کنید.');
        $public=json_decode((string)$row->config_public,true);
        if(!is_array($public))$public=[];
        $public['sms_otp_enabled']=$data['sms_otp']?'1':'0';
        $public['person_info_enabled']=$data['person_info']?'1':'0';
        // Shahkar is intentionally not part of KYC V3. Leave its old records intact.
        $public['shahkar_apiir_enabled']='0';
        $public['otp_template']=(string)$data['otp_template'];
        DB::table('spad_web_service_connections')->where('id',$row->id)->update([
            'config_public'=>json_encode($public,JSON_UNESCAPED_UNICODE),
            'updated_by'=>$request->user()->id,'updated_at'=>now()]);
        $this->audit($request,$row->id,'apiir_account_features','تنظیمات ورود و احراز هویت تغییر یافت.');
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
