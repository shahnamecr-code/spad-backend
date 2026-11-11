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
            'schedule.hourly_cities' => ['sometimes','boolean'],
            'schedule.hourly_from' => ['sometimes','integer','between:0,23'],
            'schedule.hourly_to' => ['sometimes','integer','between:0,23'],
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
        $data['schedule'] = array_merge(SpadWhatsAppMarketPublisher::DEFAULT_SCHEDULE, $data['schedule']);
        $data['schedule']['hourly_markets'] = (bool) $data['schedule']['hourly_markets'];
        $data['schedule']['hourly_cities'] = (bool) $data['schedule']['hourly_cities'];
        $data['schedule']['hourly_from'] = (int) $data['schedule']['hourly_from'];
        $data['schedule']['hourly_to'] = (int) $data['schedule']['hourly_to'];
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
        try {
            $png = $publisher->preview($data['kind'], $data['province'] ?? null, $data['city'] ?? null);
            return response($png, 200)
                ->header('Content-Type', 'image/png')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException && $e->getCode() === 202) {
                return response()->json([
                    'code' => 'WA_PREVIEW_BUILDING',
                    'message' => 'پیش‌نمایش در حال ساخت با قیمت معتبر است.',
                ], 202)->header('Cache-Control', 'private, no-store');
            }
            $message = (string) $e->getMessage();
            $code = 'WA_PREVIEW_CACHE_UNAVAILABLE';
            $friendly = 'آماده‌سازی بنر در سرور با مشکل مواجه شد. پس از بررسی سرویس ساخت تصویر دوباره تلاش کن.';
            if (str_contains($message, 'داده تازه') || str_contains($message, 'نرخ')
                || str_contains($message, 'قیمت واقعی') || str_contains($message, 'سرویس بازار')) {
                $code = 'WA_PREVIEW_PRICE_DATA_UNAVAILABLE';
                $friendly = 'نرخ معتبر و تازه از منبع قیمت دریافت نشده است. تا دریافت قیمت واقعی، بنر منتشر نمی‌شود.';
            } elseif (str_contains($message, 'شهر') || str_contains($message, 'نمودا')) {
                $code = 'WA_PREVIEW_CITY_UNAVAILABLE';
                $friendly = 'داده محله‌های این شهر فعلاً در دسترس نیست. نام استان و شهر را بررسی کنید.';
            }
            \Illuminate\Support\Facades\Log::warning('SPAD WhatsApp admin preview failed', [
                'error_code' => $code, 'exception_type' => get_class($e),
                'kind' => $data['kind'],
            ]);
            return response()->json(['message' => $friendly, 'code' => $code], 503)
                ->header('Cache-Control', 'no-store');
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
