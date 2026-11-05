<?php

namespace App\Http\Controllers;

use App\Services\NemudaPropertyMarket;
use App\Services\SpadPropertyPortfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpadPropertyAssetController extends Controller
{
    private function guardTables(): void
    {
        if (!Schema::hasTable('spad_user_property_assets') ||
            !Schema::hasTable('spad_user_property_prices')) {
            abort(503, 'سرویس دارایی‌های ملکی در حال آماده‌سازی است.');
        }
    }

    public function index(Request $request, SpadPropertyPortfolio $service, NemudaPropertyMarket $market)
    {
        $this->guardTables();
        $userId = (int) $request->user()->id;
        $assets = DB::table('spad_user_property_assets')
            ->where('user_id', $userId)->orderByDesc('id')
            ->limit(SpadPropertyPortfolio::MAX_ASSETS)->get();

        // Refresh at most TWO stale holdings on a visit. No API.ir calls here.
        $refreshed = 0;
        foreach ($assets as $asset) {
            if ($refreshed >= 2) break;
            if (!$asset->last_checked_at ||
                \Carbon\Carbon::parse($asset->last_checked_at)->lt(now()->subHours(SpadPropertyPortfolio::REFRESH_HOURS))) {
                $service->refresh($asset, $market);
                $refreshed++;
            }
        }
        $assets = DB::table('spad_user_property_assets')
            ->where('user_id', $userId)->orderByDesc('id')
            ->limit(SpadPropertyPortfolio::MAX_ASSETS)->get();

        $values = $assets->map(function ($asset) use ($service) {
            return $service->present($asset);
        });
        $total = $values->sum(function ($asset) {
            return $asset['estimated_total_toman'] ?? 0;
        });
        return response()->json([
            'status' => true,
            'data' => [
                'items' => $values->values(),
                'total_estimated_toman' => (int) $total,
                'asset_count' => $values->count(),
                'refresh_interval_hours' => SpadPropertyPortfolio::REFRESH_HOURS,
                'market_disclaimer' => 'ارزش دارایی‌ها صرفاً برآورد بازار است؛ مالکیت ثبت‌شده تأیید نشده و ارزش کارشناسی رسمی نیست.',
                'currency' => 'تومان',
                'generated_at' => now()->toIso8601String(),
            ]
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, SpadPropertyPortfolio $service)
    {
        $this->guardTables();
        $data = $request->validate([
            'quote_token' => ['required', 'string', 'size:48', 'regex:/^[A-Za-z0-9]+$/D'],
            'nickname' => ['nullable', 'string', 'max:100'],
        ]);
        $quote = Cache::get('spad:asset:quote:' . $data['quote_token']);
        if (!is_array($quote) || (int) ($quote['user_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['status' => false,
                'message' => 'اعتبار برآورد به پایان رسیده است؛ برای ذخیره ملک یک برآورد جدید انجام دهید.'], 410);
        }
        // Neither postcode nor property value is accepted from the browser.
        // They are read from the previously computed, user-bound server quote.
        try {
            $asset = $service->save($quote, (int) $request->user()->id, (string) ($data['nickname'] ?? ''));
            return response()->json(['status' => true, 'data' => $asset,
                'message' => 'ملک به دارایی‌های شما اضافه شد.'], 201)
                ->header('Cache-Control', 'private, no-store');
        } catch (\RuntimeException $e) {
            $status = (int) $e->getCode();
            return response()->json(['status' => false, 'message' => $e->getMessage()],
                $status >= 400 && $status <= 499 ? $status : 503);
        } catch (\Throwable $e) {
            return response()->json(['status' => false,
                'message' => 'ذخیره دارایی در حال حاضر انجام نشد؛ لطفاً دوباره تلاش کنید.'], 503);
        }
    }

    public function refresh(Request $request, SpadPropertyPortfolio $service, NemudaPropertyMarket $market, int $id)
    {
        $this->guardTables();
        $asset = DB::table('spad_user_property_assets')->where('user_id', (int) $request->user()->id)
            ->where('id', $id)->first();
        if (!$asset) abort(404);
        $service->refresh($asset, $market, true);
        $updated = DB::table('spad_user_property_assets')->where('id', $id)
            ->where('user_id', (int) $request->user()->id)->first();
        return response()->json([
            'status' => true,
            'data' => $service->present($updated),
            'message' => $updated->refresh_error
                ? 'داده تازه در دسترس نیست؛ آخرین برآورد معتبر نمایش داده می‌شود.'
                : 'آخرین اطلاعات منتشرشده بازار بررسی شد.',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function rename(Request $request, SpadPropertyPortfolio $service, int $id)
    {
        $this->guardTables();
        $input = $request->validate(['nickname' => ['required', 'string', 'min:2', 'max:100']]);
        $userId = (int) $request->user()->id;
        $affected = DB::table('spad_user_property_assets')
            ->where('user_id', $userId)->where('id', $id)
            ->update(['nickname' => trim($input['nickname']), 'updated_at' => now()]);
        if (!$affected) abort(404);
        $asset = DB::table('spad_user_property_assets')->where('user_id', $userId)->where('id', $id)->first();
        return response()->json(['status' => true, 'data' => $service->present($asset)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request, int $id)
    {
        $this->guardTables();
        $deleted = DB::table('spad_user_property_assets')
            ->where('user_id', (int) $request->user()->id)
            ->where('id', $id)->delete();
        if (!$deleted) abort(404);
        return response()->json(['status' => true, 'message' => 'ملک از فهرست دارایی‌های شما حذف شد.'])
            ->header('Cache-Control', 'private, no-store');
    }
}
