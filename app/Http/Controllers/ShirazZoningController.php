<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ShirazZoningController extends Controller
{
    public function lookup(Request $request)
    {
        $data = $request->validate([
            'method' => 'required|in:map,renewal,postal',
            'renewal_code' => 'exclude_unless:method,renewal|required|string|regex:/^[0-9]+(?:-[0-9]+)*$/D|min:3|max:35',
            'postal_code' => 'exclude_unless:method,postal|required|string|digits:10',
            'lat' => 'exclude_unless:method,map|required|numeric|between:29.35,29.91',
            'lng' => 'exclude_unless:method,map|required|numeric|between:52.15,52.86',
        ]);

        $url = config('services.shiraz_zoning.url');
        $location = $url ? parse_url($url) : false;
        $host = strtolower($location['host'] ?? '');
        if (($location['scheme'] ?? '') !== 'https' ||
            !preg_match('/^(?:[a-z0-9-]+\.)*shiraz\.ir$/D', $host) ||
            isset($location['user']) || isset($location['pass'])) {
            return $this->unavailable();
        }

        $client = Http::acceptJson()->asJson()->withoutRedirecting()
            ->withOptions(['connect_timeout' => 3])->timeout(9);
        if ($token = config('services.shiraz_zoning.token')) {
            $client->withToken($token);
        }

        try {
            $response = $client->post($url, $data);
            if ($response->status() === 404) {
                return response()->json(['message' => 'ضابطهٔ رسمی برای این پلاک پیدا نشد.'], 404);
            }
            if (!$response->successful() || !is_array($response->json('data'))) {
                return $this->unavailable();
            }
            $result = Validator::make($response->json('data'), [
                'parcel_id' => 'required|string|max:60',
                'land_use' => 'required|string|max:140',
                'zone' => 'nullable|string|max:100',
                'density_percent' => 'nullable|numeric|min:0',
                'coverage_percent' => 'nullable|numeric|between:0,100',
                'max_floors' => 'nullable|integer|min:0',
                'setback' => 'nullable|string|max:500',
                'source_reference' => 'required|string|max:150',
                'updated_at' => 'required|date',
            ]);
            if ($result->fails()) {
                return $this->unavailable();
            }

            return response()->json(['data' => $result->validated()])
                ->header('Cache-Control', 'private, no-store');
        } catch (Throwable $e) {
            return $this->unavailable();
        }
    }

    private function unavailable()
    {
        return response()->json([
            'message' => 'پاسخ مستقیم شهرداری فعلاً در دسترس نیست. برای بررسی پلاک به سامانه شهروندسپاری مراجعه کنید.',
        ], 503)->header('Cache-Control', 'no-store');
    }
}
