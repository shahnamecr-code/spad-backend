<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SpadSmsSettingsController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->roles()->where('name','Super Admin')->exists(),
            403, 'تنظیمات کلید پیامک فقط برای مدیر اصلی مجاز است.'
        );
        abort_unless(Schema::hasTable('spad_sms_settings') && Schema::hasTable('spad_sms_delivery_logs'),
            503, 'بخش پیامک هنوز نصب نشده است.');
    }

    public function index(Request $request)
    {
        $this->admin($request);
        $settings = app(SpadSmsService::class)->settings();
        return response()->json([
            'data' => $settings,
            'logs' => DB::table('spad_sms_delivery_logs')
                ->orderByDesc('id')->limit(40)
                ->get(['id','event','mobile_masked','status','error_code','created_at']),
        ])->header('Cache-Control','private, no-store');
    }

    public function save(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'username' => 'nullable|string|max:120',
            'api_key' => 'nullable|string|min:8|max:512',
            'sender' => 'nullable|string|max:30|regex:/^[0-9+]*$/',
            'daily_limit' => 'required|integer|min:10|max:10000',
            'events' => 'required|array',
            'events.*.enabled' => 'required|boolean',
            'events.*.body_id' => 'nullable|integer|min:0|max:2147483647',
        ]);
        $old = DB::table('spad_sms_settings')->where('id',1)->first();
        $incoming = $data['events'] ?? [];
        foreach (array_keys($incoming) as $key) {
            if (!isset(SpadSmsService::EVENTS[$key])) {
                return response()->json(['message'=>'نوع پیامک ناشناخته است.'],422);
            }
        }
        $events = [];
        foreach (SpadSmsService::EVENTS as $key => $label) {
            $event = $incoming[$key] ?? [];
            $events[$key] = [
                'enabled' => (bool) ($event['enabled'] ?? false),
                'body_id' => max(0,(int)($event['body_id'] ?? 0)),
            ];
        }
        $key = $old->api_key_encrypted ?? null;
        if (!empty($data['api_key'])) $key = Crypt::encryptString($data['api_key']);
        if ((bool)$data['enabled']) {
            if (!$key || !trim($data['username'])) {
                return response()->json(['message'=>'نام کاربری و API Key ملی پیامک ضروری است.'],422);
            }
            $hasEvents = false;
            foreach ($events as $rule) {
                if (!$rule['enabled']) continue;
                $hasEvents = true;
                if (!$rule['body_id'] && !$data['sender']) {
                    return response()->json([
                        'message'=>'برای رویدادهای فعال، شناسه الگوی خدماتی یا خط فرستنده ضروری است.',
                    ],422);
                }
            }
            if (!$hasEvents) return response()->json(['message'=>'حداقل یک رویداد پیامکی را فعال کنید.'],422);
        }
        $values = [
            'enabled'=>(bool)$data['enabled'],
            'username'=>trim($data['username']),
            'api_key_encrypted'=>$key,
            'sender'=>trim($data['sender'] ?? ''),
            'daily_limit'=>(int)$data['daily_limit'],
            'events_json'=>json_encode($events,JSON_UNESCAPED_UNICODE),
            'updated_at'=>now(),
        ];
        if ($old) {
            DB::table('spad_sms_settings')->where('id',1)->update($values);
        } else {
            DB::table('spad_sms_settings')->insert(array_merge(['id'=>1,'created_at'=>now()],$values));
        }
        return response()->json(['status'=>true,'message'=>'تنظیمات ملی پیامک ذخیره شد؛ کلید قابل بازیابی از پنل نیست.'])
            ->header('Cache-Control','private, no-store');
    }

    public function test(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['mobile'=>'required|string|regex:/^09[0-9]{9}$/D']);
        $key='spad:sms:test:'.auth('admin')->id();
        abort_if(RateLimiter::tooManyAttempts($key,2),429,'سقف آزمایش پیامک تکمیل شد؛ بعداً تلاش کنید.');
        RateLimiter::hit($key,900);
        $sms = app(SpadSmsService::class);
        abort_unless($sms->configured(),422,'ابتدا تنظیمات اتصال را ذخیره و فعال کنید.');
        abort_unless($sms->eventEnabled('registration'),422,'برای آزمایش، رویداد «ثبت‌نام موفق» را فعال کنید.');
        $ok = $sms->notify('registration',$data['mobile'],[],'test:'.auth('admin')->id().':'.uniqid('',true));
        return response()->json(['status'=>$ok,
            'message'=>$ok?'درخواست آزمایشی توسط ملی پیامک پذیرفته شد.':'درخواست آزمایشی پذیرفته نشد؛ گزارش ارسال را بررسی کنید.',
        ],$ok?200:502);
    }
}
