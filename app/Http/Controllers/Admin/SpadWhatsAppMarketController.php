<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadWhatsAppMarketPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SpadWhatsAppMarketController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->roles()->where('name', 'Super Admin')->exists(),
            403, 'این بخش فقط در دسترس مدیر اصلی اسپاد است.'
        );
        abort_unless(Schema::hasTable('spad_wa_market_config')
            && Schema::hasTable('spad_wa_market_publications'), 503,
            'ابتدا ساختار داده‌های انتشار واتساپ باید روی سرور نصب شود.');
    }

    public function index(Request $request, SpadWhatsAppMarketPublisher $publisher)
    {
        $this->guard($request);
        return response()->json([
            'data' => $publisher->settings(),
            'logs' => $publisher->logs(),
            'notes' => [
                'only_meta_official_groups' => true,
                'requires_official_business_account' => true,
                'channel_api_available' => false,
                'max_group_members' => 8,
                'timezone' => SpadWhatsAppMarketPublisher::ZONE,
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, SpadWhatsAppMarketPublisher $publisher)
    {
        $this->guard($request);
        $data = $request->validate([
            'enabled' => ['required','boolean'],
            'schedule' => ['required','array'],
            'schedule.markets' => ['required','array','min:1','max:6'],
            'schedule.markets.*' => ['required','string','regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/D'],
            'schedule.cities' => ['required','array','min:1','max:6'],
            'schedule.cities.*' => ['required','string','regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/D'],
            'schedule.hourly_markets' => ['required','boolean'],
            'cities' => ['required','array','min:1','max:30'],
            'cities.*.province' => ['required','string','min:2','max:80'],
            'cities.*.city' => ['required','string','min:2','max:80'],
            'groups' => ['required','array','max:20'],
            'groups.*' => ['required','string','min:3','max:250','regex:/^[A-Za-z0-9_:.\-=]+$/D'],
            'phone_number_id' => ['nullable','string','regex:/^[0-9]{5,40}$/D'],
            'access_token' => ['nullable','string','min:12','max:2048'],
            'clear_token' => ['sometimes','boolean'],
            'official_groups_acknowledged' => ['sometimes','boolean'],
        ]);
        $data['schedule']['markets'] = array_values(array_unique($data['schedule']['markets']));
        $data['schedule']['cities'] = array_values(array_unique($data['schedule']['cities']));
        $data['cities'] = array_values(array_unique($data['cities'], SORT_REGULAR));
        try {
            $publisher->save($data);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'status' => true, 'data' => $publisher->settings(),
            'message' => 'تنظیمات انتشار اسپاد ثبت شد.',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function preview(Request $request, SpadWhatsAppMarketPublisher $publisher)
    {
        $this->guard($request);
        $data = $request->validate([
            'kind' => ['required','in:market,city'],
            'province' => ['required_if:kind,city','nullable','string','max:80'],
            'city' => ['required_if:kind,city','nullable','string','max:80'],
        ]);
        $kind = $data['kind'];
        $province = $data['province'] ?? null;
        $city = $data['city'] ?? null;
        try {
            // Never launch Node/Chromium from PHP-FPM. CLI has the renderer
            // privileges; website PHP workers only access authenticated cache.
            $png = app(\App\Services\SpadWhatsAppPreviewCache::class)
                ->get($kind, $province, $city);
            if ($png !== null) {
                return response($png, 200)
                    ->header('Content-Type', 'image/png')
                    ->header('X-Content-Type-Options', 'nosniff')
                    ->header('Cache-Control', 'private, no-store');
            }

            $queue = app(\App\Services\SpadWhatsAppPreviewBuilder::class)
                ->enqueue($kind, $province, $city);
            return response()->json([
                'status' => $queue['status'],
                'message' => $queue['status'] === 'temporarily_unavailable'
                    ? 'منبع داده یا موتور تصویر موقتاً آماده نیست؛ بعداً دوباره بررسی کن.'
                    : 'درخواست ساخت تصویر با قیمت واقعی ثبت شد؛ پیش‌نمایش پس از آماده‌شدن نمایش داده می‌شود.',
                'retry_after' => $queue['retry_after'],
            ], 202)->header('Cache-Control', 'private, no-store')
                ->header('Retry-After', (string) $queue['retry_after']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SPAD WhatsApp web preview cache/queue failed', [
                'kind' => $kind,
                'exception_type' => get_class($e),
            ]);
            return response()->json([
                'code' => 'WA_PREVIEW_CACHE_OR_QUEUE_UNAVAILABLE',
                'message' => 'پیش‌نمایش فعلاً آماده نیست. وضعیت حافظه و صف ساخت تصویر در سرور باید بررسی شود.',
            ], 503)->header('Cache-Control', 'no-store');
        }
    }

    public function runNow(Request $request, SpadWhatsAppMarketPublisher $publisher)
    {
        $this->guard($request);
        $data = $request->validate([
            'kind' => ['required','in:market,city'],
            'province' => ['required_if:kind,city','nullable','string','max:80'],
            'city' => ['required_if:kind,city','nullable','string','max:80'],
            'confirmed' => ['required','accepted'],
        ]);
        try {
            $result = $publisher->runNow($data['kind'], $data['province'] ?? null, $data['city'] ?? null);
            return response()->json(['status' => true, 'data' => $result,
                'message' => 'فرآیند ارسال اجرا شد؛ وضعیت پذیرش هر گروه در گزارش‌ها مشخص می‌شود.']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'ارسال ناموفق بود؛ گزارش‌ها و دسترسی سرویس را بررسی کنید.'], 503);
        }
    }
}
