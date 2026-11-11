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
        $settings = $publisher->settings();
        return response()->json([
            'data' => [
                'schedule' => $settings['schedule'],
                'cities' => $settings['cities'],
                'render_available' => $settings['render_available'],
            ],
            'timezone' => SpadWhatsAppMarketPublisher::ZONE,
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Personal-only panel: hourly schedule + cities. Meta group publishing stays off. */
    public function saveSchedule(Request $request, SpadWhatsAppMarketPublisher $publisher)
    {
        $this->guard($request);
        $data = $request->validate([
            'hourly_markets' => ['required','boolean'],
            'hourly_cities' => ['required','boolean'],
            'hourly_from' => ['required','integer','between:0,23'],
            'hourly_to' => ['required','integer','between:0,23'],
            'cities' => ['required','array','min:1','max:10'],
            'cities.*.province' => ['required','string','min:2','max:80'],
            'cities.*.city' => ['required','string','min:2','max:80'],
        ], [
            'cities.required' => 'حداقل یک شهر برای بنر قیمت ملک لازم است.',
            'cities.min' => 'حداقل یک شهر برای بنر قیمت ملک لازم است.',
            'cities.max' => 'حداکثر ۱۰ شهر را می‌توان اضافه کرد.',
            'cities.*.province.min' => 'نام استان کوتاه است.',
            'cities.*.city.min' => 'نام شهر کوتاه است.',
        ]);
        $cities = [];
        foreach ($data['cities'] as $c) {
            $key = trim($c['province']) . '|' . trim($c['city']);
            $cities[$key] = ['province' => trim($c['province']), 'city' => trim($c['city'])];
        }
        $publisher->saveSchedule($data, array_values($cities));
        $settings = $publisher->settings();
        return response()->json([
            'status' => true,
            'data' => ['schedule' => $settings['schedule'], 'cities' => $settings['cities']],
            'message' => 'زمان‌بندی ذخیره شد.',
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
}
