<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadWhatsAppPersonalBridge;
use App\Services\SpadWhatsAppPersonalDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SpadWhatsAppPersonalController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()
            && $request->user()->roles()->where('name', 'Super Admin')->exists(),
            403, 'اتصال واتساپ شخصی فقط برای مدیر اصلی اسپاد قابل مدیریت است.');
    }

    public function status(Request $request, SpadWhatsAppPersonalBridge $bridge, SpadWhatsAppPersonalDelivery $delivery)
    {
        $this->guard($request);
        return response()->json([
            'connection' => $bridge->status(),
            'preferences' => $delivery->settings(),
            'jobs' => $delivery->recentJobs(),
            'risk_notice' => 'اتصال QR از واسط غیررسمی واتساپ وب استفاده می‌کند و احتمال محدودشدن حساب وجود دارد.',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function pairingQr(Request $request, SpadWhatsAppPersonalBridge $bridge)
    {
        $this->guard($request);
        try {
            $value = $bridge->pairingQr();
            return response()->json($value)->header('Cache-Control', 'private, no-store');
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'سرویس اتصال شخصی هنوز آماده ساخت QR نیست. وضعیت سرویس را بررسی کن.',
            ], 503)->header('Cache-Control','no-store');
        }
    }

    public function preferences(Request $request, SpadWhatsAppPersonalDelivery $delivery)
    {
        $this->guard($request);
        $data = $request->validate(['enabled' => ['required','boolean']]);
        try {
            $settings = $delivery->setEnabled((bool) $data['enabled']);
            return response()->json(['data'=>$settings,
                'message'=>'تنظیمات ارسال به گفت‌وگوی شخصی ذخیره شد.']);
        } catch (RuntimeException $e) {
            return response()->json(['message'=>$e->getMessage()],422);
        }
    }

    /** Send the gold/FX banner and all saved cities' banners now, outside the schedule. */
    public function sendAllNow(Request $request, SpadWhatsAppPersonalDelivery $delivery)
    {
        $this->guard($request);
        $request->validate(['confirmed' => ['required','accepted']]);
        try {
            $r = $delivery->sendNowAll();
            $first = $r['sent_now'] ? 'بنر «' . $r['sent_now'] . '» همین الان ارسال شد. ' : '';
            $rest = $r['remaining'] > 0
                ? $r['remaining'] . ' بنر دیگر در چند دقیقه‌ی آینده، با فاصله حدود یک دقیقه، می‌رسد.'
                : '';
            return response()->json(['status' => true, 'data' => $r,
                'message' => trim($first . $rest) ?: 'بنرها در صف ارسال قرار گرفتند.']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() === 422 ? 422 : 503);
        }
    }

    public function sendNow(Request $request, SpadWhatsAppPersonalDelivery $delivery)
    {
        $this->guard($request);
        $data = $request->validate([
            'kind' => ['required','in:market,city'],
            'province' => ['required_if:kind,city','nullable','string','max:80'],
            'city' => ['required_if:kind,city','nullable','string','max:80'],
            'confirmed' => ['required','accepted'],
        ]);
        try {
            $result = $delivery->sendManual(
                $data['kind'], $data['province'] ?? null, $data['city'] ?? null);
            return response()->json([
                'status' => $result['accepted'] ?? false,
                'message' => 'درخواست ارسال به گفت‌وگوی شخصی توسط اتصال پذیرفته شد؛ رسیدن به گوشی را بررسی کن.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message'=>$e->getMessage()],503);
        }
    }
}
