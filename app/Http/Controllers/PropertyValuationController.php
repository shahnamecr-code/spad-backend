<?php

namespace App\Http\Controllers;

use App\Services\ApiIrClient;
use App\Services\NemudaPropertyMarket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class PropertyValuationController extends Controller
{
    public function summary(NemudaPropertyMarket $market)
    {
        try {
            return response()->json(['status' => true, 'data' => $market->summary()]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'داده‌های نمودا موقتاً در دسترس نیستند.'], 503);
        }
    }

    public function children(Request $request, NemudaPropertyMarket $market, int $placeId)
    {
        if ($placeId < 1) abort(404);
        try {
            return response()->json(['status' => true, 'data' => $market->children($placeId)]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'فهرست شهرها یا محله‌ها قابل دریافت نیست.'], 503);
        }
    }

    /**
     * The only billable API.ir call in this flow. Authenticated users alone
     * can request their postal address; reuse a 15-minute per-user receipt.
     */
    public function postalLookup(Request $request, ApiIrClient $api, NemudaPropertyMarket $market)
    {
        $input = $request->validate([
            'postal_code' => ['required', 'regex:/^[0-9]{10}$/D'],
            'consent' => ['required', 'accepted'],
        ]);
        $user = $request->user();
        $postal = (string) $input['postal_code'];
        $recentKey = 'spad:valuation:recent:' . $user->id . ':' .
            hash_hmac('sha256', $postal, config('app.key'));
        $existing = Cache::get($recentKey);
        if (is_array($existing)) return response()->json(['status' => true, 'data' => $existing]);

        try {
            $address = $api->execute('property-apiir-postal-code-pro', ['postalCode' => $postal], $user);
            if (!is_array($address)) throw new RuntimeException('پاسخ کدپستی معتبر نیست.', 502);
            $province = trim((string) ($address['province'] ?? ''));
            $city = trim((string) ($address['city'] ?? ''));
            if (!$province || !$city) throw new RuntimeException('استان و شهر در پاسخ کدپستی مشخص نبود.', 422);

            // Never return arbitrary third-party fields, national identifiers or
            // raw response metadata; the minimum address required for review only.
            $details = [];
            foreach (['province', 'city', 'town', 'district', 'street', 'street2',
                      'number', 'floor', 'sideFloor', 'buildingName', 'address'] as $field) {
                if (isset($address[$field]) && is_scalar($address[$field]))
                    $details[$field] = mb_substr(trim((string) $address[$field]), 0, 240);
            }
            if (isset($address['lat']) && is_numeric($address['lat']) &&
                isset($address['long']) && is_numeric($address['long'])) {
                $lat = (float) $address['lat'];
                $long = (float) $address['long'];
                if ($lat >= 24 && $lat <= 40 && $long >= 43 && $long <= 64) {
                    $details['lat'] = $lat;
                    $details['long'] = $long;
                }
            }

            $token = Str::random(48);
            $record = [
                'user_id' => (int) $user->id,
                'postal_code' => $postal,
                'details' => $details,
                'province' => $province,
                'city' => $city,
            ];
            Cache::put('spad:valuation:receipt:' . $token, $record, now()->addMinutes(15));
            // The user can review the address before selecting valuation details.
            $cityPlace = null;
            $neighborhoods = [];
            try {
                $cityPlace = $market->matchCity($province, $city);
                if ($cityPlace) {
                    $children = $market->children((int) $cityPlace['id']);
                    $neighborhoods = array_slice($children['places'] ?? [], 0, 250);
                }
            } catch (\Throwable $ignored) {
                // Do not repeat a billable postal lookup because a FREE market
                // request is unavailable. The estimate can resolve later.
            }
            $reply = [
                'lookup_token' => $token,
                'address' => $details,
                'city_place' => $cityPlace ? [
                    'id' => (int) $cityPlace['id'], 'name' => $cityPlace['name'],
                ] : null,
                'neighborhoods' => $neighborhoods,
                'expires_in_seconds' => 900,
                'match_level' => $cityPlace ? 'city' : 'unmatched',
            ];
            Cache::put($recentKey, $reply, now()->addMinutes(15));
            return response()->json(['status' => true, 'data' => $reply]);
        } catch (RuntimeException $e) {
            $status = (int) $e->getCode();
            if ($status < 400 || $status > 599) $status = 503;
            return response()->json(['status' => false, 'message' => $e->getMessage()], $status);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'استعلام کدپستی فعلاً انجام نشد.'], 503);
        }
    }

    public function estimate(Request $request, NemudaPropertyMarket $market)
    {
        $data = $request->validate([
            'lookup_token' => ['required', 'string', 'size:48', 'regex:/^[A-Za-z0-9]+$/D'],
            'place_id' => ['nullable', 'integer', 'min:1'],
            'property_kind' => ['required', 'in:apartment,house,land,shop,office'],
            'area_m2' => ['required', 'numeric', 'between:10,10000'],
            'rooms' => ['nullable', 'in:1,2,3plus'],
            'age' => ['nullable', 'in:new,mid,old'],
            'parking' => ['nullable', 'in:yes,no'],
            'elevator' => ['nullable', 'in:yes,no'],
        ]);
        $record = Cache::get('spad:valuation:receipt:' . $data['lookup_token']);
        if (!is_array($record) || ($record['user_id'] ?? null) !== (int) $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'اعتبار استعلام کدپستی پایان یافته؛ دوباره کدپستی را بررسی کنید.'], 410);
        }

        try {
            $cityPlace = $market->matchCity($record['province'], $record['city']);
            if (!$cityPlace) {
                return response()->json(['status' => false,
                    'message' => 'برای شهر این کدپستی هنوز تطبیق مطمئن با پوشش نمودا پیدا نشد؛ قیمت حدسی نمایش داده نمی‌شود.'], 422);
            }
            $placeId = (int) ($data['place_id'] ?? $cityPlace['id']);
            $placeName = $cityPlace['name'];
            $level = 'city';
            if ($placeId !== (int) $cityPlace['id']) {
                $children = $market->children((int) $cityPlace['id']);
                $match = collect($children['places'] ?? [])->firstWhere('id', $placeId);
                if (!$match) {
                    return response()->json(['status' => false, 'message' => 'محله انتخاب‌شده متعلق به شهر تأییدشده نیست.'], 422);
                }
                $placeName = $match['name'];
                $level = 'neighborhood';
            }
            $kind = $data['property_kind'];
            $area = (float) $data['area_m2'];
            $facets = [
                'dealType' => 'sell',
                'propertyKind' => $kind,
                'area' => $this->areaBucket($kind, $area),
            ];
            if (in_array($kind, ['apartment', 'office', 'house'], true) && !empty($data['age']))
                $facets['age'] = $data['age'];
            if ($kind === 'apartment' && !empty($data['rooms']))
                $facets['rooms'] = $data['rooms'];
            if (in_array($kind, ['apartment', 'office'], true) && !empty($data['parking']))
                $facets['parking'] = $data['parking'];
            if ($kind === 'apartment' && !empty($data['elevator']))
                $facets['elevator'] = $data['elevator'];

            $provider = $market->place($placeId, $facets);
            $prices = $market->priceDetails($provider);
            if (!$prices || $prices['coverage'] === 'none') {
                return response()->json(['status' => false,
                    'message' => 'برای این نوع ملک و مشخصات، داده کافی در نمودا منتشر نشده است. برای برآورد دقیق‌تر فیلترهای اختیاری را کاهش دهید.'], 422);
            }

            // Market quartiles multiplied by the specific verified area are
            // indicative asking-price bands, NOT a formal appraisal.
            $estimate = $prices['price_per_m2'] * $area;
            $low = $prices['range_low'] ? $prices['range_low'] * $area : null;
            $high = $prices['range_high'] ? $prices['range_high'] * $area : null;
            return response()->json(['status' => true, 'data' => [
                'place_id' => $placeId,
                'place_name' => $placeName,
                'match_level' => $level,
                'province' => $record['province'],
                'city' => $record['city'],
                'property_kind' => $kind,
                'area_m2' => $area,
                'estimated_total_toman' => round($estimate),
                'range_low_toman' => $low ? round($low) : null,
                'range_high_toman' => $high ? round($high) : null,
                'price_per_m2_toman' => round($prices['price_per_m2']),
                'data_points' => $prices['data_points'],
                'coverage' => $prices['coverage'],
                'computed_at' => $prices['computed_at'],
                'window_days' => $prices['window_days'],
                'source' => 'نمودا',
                'source_url' => 'https://nemuda.com/',
                'caution' => 'برآورد از میانه قیمت‌های پیشنهادی بازار و متراژ اعلام‌شده است؛ قیمت معامله یا کارشناسی قطعی ملک نیست.',
            ]]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false,
                'message' => 'داده‌های بازار برای این ملک در دسترس نیستند؛ هیچ قیمت ساختگی نمایش داده نمی‌شود.'], 503);
        }
    }

    private function areaBucket(string $kind, float $area): string
    {
        $cutoffs = [
            'apartment' => [[60, 'lt60'], [80, '60-80'], [100, '80-100'], [150, '100-150'], [INF, 'gte150']],
            'house' => [[100, 'lt100'], [150, '100-150'], [250, '150-250'], [INF, 'gte250']],
            'land' => [[250, 'lt250'], [500, '250-500'], [1000, '500-1000'], [10000, '1000-10000'], [INF, 'gte10000']],
            'shop' => [[20, 'lt20'], [40, '20-40'], [80, '40-80'], [INF, 'gte80']],
            'office' => [[60, 'lt60'], [100, '60-100'], [200, '100-200'], [INF, 'gte200']],
        ];
        foreach ($cutoffs[$kind] as [$limit, $code]) {
            if ($area < $limit) return $code;
        }
        return $cutoffs[$kind][count($cutoffs[$kind]) - 1][1];
    }
}
