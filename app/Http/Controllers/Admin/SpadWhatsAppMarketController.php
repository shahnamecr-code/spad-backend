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
        try {
            $svg = $publisher->preview($data['kind'], $data['province'] ?? null, $data['city'] ?? null);
            return response($svg, 200)->header('Content-Type', 'image/svg+xml; charset=utf-8')
                ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox")
                ->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'بنر با اطلاعات واقعی فعلاً قابل ساخت نیست؛ صحت منبع و نام شهر را بررسی کنید.'], 503);
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
