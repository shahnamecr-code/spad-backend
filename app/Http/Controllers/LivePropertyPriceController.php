<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Setting;
use App\Support\SpadLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LivePropertyPriceController extends Controller
{
    public function index(Request $request)
    {
        $settings = $this->settings();
        if (($settings['live_property_price_enabled'] ?? '1') === '0') {
            return response()->json([
                'status' => false,
                'message' => 'صفحه قیمت لحظه‌ای ملک موقتاً غیرفعال است.',
            ], 404);
        }

        $minimumSamples = max(1, (int)($settings['live_property_price_min_samples'] ?? 3));
        $maxAgeDays = max(0, (int)($settings['live_property_price_max_age_days'] ?? 0));

        // SPAD is a Shiraz-only marketplace. The city is resolved on the server
        // and no client-supplied province/city can change the market scope.
        $city = SpadLocation::city();

        $regions = $city->regions()
            ->select(['id', 'name', 'city_id'])
            ->orderBy('name', 'asc')
            ->get();

        $adsQuery = Advertisement::query()
            ->publicCatalog()
            ->where('status', Advertisement::APPROVED)
            ->where('city_id', $city->id)
            ->whereNotNull('region_id')
            ->with([
                'properties' => function ($query) {
                    $query->with(['property:id,slug,title,type,unit']);
                },
            ])
            ->select(['id', 'city_id', 'region_id', 'created_at', 'updated_at']);

        if ($maxAgeDays > 0) {
            $adsQuery->where('updated_at', '>=', Carbon::now()->subDays($maxAgeDays));
        }

        $ads = $adsQuery->get();

        $regionBuckets = [];
        $cityPerMeter = [];
        $cityTotals = [];
        $cityAreas = [];
        $validAds = 0;

        foreach ($ads as $advertisement) {
            $market = $this->extractMarketValues($advertisement->properties);
            if (!$market['price_per_meter']) {
                continue;
            }

            $validAds++;
            $regionId = (int)$advertisement->region_id;
            if (!isset($regionBuckets[$regionId])) {
                $regionBuckets[$regionId] = [
                    'per_meter' => [],
                    'total' => [],
                    'area' => [],
                ];
            }

            $regionBuckets[$regionId]['per_meter'][] = $market['price_per_meter'];
            $cityPerMeter[] = $market['price_per_meter'];

            if ($market['total_price']) {
                $regionBuckets[$regionId]['total'][] = $market['total_price'];
                $cityTotals[] = $market['total_price'];
            }

            if ($market['area']) {
                $regionBuckets[$regionId]['area'][] = $market['area'];
                $cityAreas[] = $market['area'];
            }
        }

        $regionOutput = $regions->map(function ($region) use ($regionBuckets, $minimumSamples) {
            $bucket = $regionBuckets[$region->id] ?? [
                'per_meter' => [],
                'total' => [],
                'area' => [],
            ];

            $sampleCount = count($bucket['per_meter']);
            $medianPerMeter = $this->median($bucket['per_meter']);

            return [
                'id' => $region->id,
                'name' => $region->name,
                'sample_count' => $sampleCount,
                'data_status' => $sampleCount === 0
                    ? 'empty'
                    : ($sampleCount >= $minimumSamples ? 'sufficient' : 'limited'),
                'is_reliable' => $sampleCount >= $minimumSamples,
                'median_price_per_meter' => $medianPerMeter,
                'average_price_per_meter' => $this->average($bucket['per_meter']),
                'min_price_per_meter' => count($bucket['per_meter']) ? min($bucket['per_meter']) : null,
                'max_price_per_meter' => count($bucket['per_meter']) ? max($bucket['per_meter']) : null,
                'median_total_price' => $this->median($bucket['total']),
                'median_area' => $this->median($bucket['area']),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
            ],
            'summary' => [
                'sample_count' => $validAds,
                'total_active_ads' => $ads->count(),
                'regions_count' => $regions->count(),
                'streets_count' => $regions->count(),
                'regions_with_data' => collect($regionBuckets)->filter(function ($bucket) {
                    return count($bucket['per_meter']) > 0;
                })->count(),
                'streets_with_data' => collect($regionBuckets)->filter(function ($bucket) {
                    return count($bucket['per_meter']) > 0;
                })->count(),
                'median_price_per_meter' => $this->median($cityPerMeter),
                'average_price_per_meter' => $this->average($cityPerMeter),
                'median_total_price' => $this->median($cityTotals),
                'median_area' => $this->median($cityAreas),
            ],
            'regions' => $regionOutput,
            'meta' => [
                'minimum_samples' => $minimumSamples,
                'max_age_days' => $maxAgeDays,
                'source' => 'آگهی‌های فعال SPAD',
                'updated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    private function settings(): array
    {
        $codes = [
            'live_property_price_enabled',
            'live_property_price_min_samples',
            'live_property_price_max_age_days',
        ];

        return Setting::query()
            ->where('group', 'live_property_price')
            ->whereIn('code', $codes)
            ->get()
            ->pluck('user_show', 'code')
            ->toArray();
    }

    private function extractMarketValues(Collection $properties): array
    {
        $area = null;
        $directPerMeter = null;
        $totalPrice = null;

        foreach ($properties as $advertisementProperty) {
            $property = $advertisementProperty->property;
            if (!$property) {
                continue;
            }

            $title = trim((string)$property->title);
            $value = $this->numericValue($advertisementProperty);
            if (!$value || $value <= 0) {
                continue;
            }

            if ($this->containsAny($title, ['متراژ', 'مساحت'])) {
                if (!$area) {
                    $area = $value;
                }
                continue;
            }

            if (
                ($this->contains($title, 'قیمت') && $this->contains($title, 'متر')) ||
                $this->contains($title, 'متری')
            ) {
                if (!$directPerMeter) {
                    $directPerMeter = $value;
                }
                continue;
            }

            $isTotalPrice = !$this->contains($title, 'متر') && (
                $this->containsAny($title, ['قیمت کل', 'قیمت فروش', 'مبلغ کل']) ||
                $title === 'قیمت'
            );

            if ($isTotalPrice && !$totalPrice) {
                $totalPrice = $value;
            }
        }

        $pricePerMeter = $directPerMeter;
        if (!$pricePerMeter && $totalPrice && $area) {
            $pricePerMeter = $totalPrice / $area;
        }

        return [
            'area' => $area,
            'total_price' => $totalPrice,
            'price_per_meter' => $pricePerMeter,
        ];
    }

    private function numericValue($advertisementProperty): ?float
    {
        $property = $advertisementProperty->property;
        if (!$property) {
            return null;
        }

        switch ($property->type) {
            case 'integer':
                $raw = $advertisementProperty->integer_value;
                break;
            case 'text':
            case 'select':
            case 'radio':
                $raw = $advertisementProperty->text_value;
                break;
            default:
                return null;
        }

        if ($raw === null || $raw === '') {
            return null;
        }

        $rawString = strtr((string)$raw, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $normalized = preg_replace('/[^0-9.]/u', '', str_replace([',', '٬', '،', ' '], '', $rawString));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        $value = (float)$normalized;
        if ($value <= 0) {
            return null;
        }

        $unitText = '';
        if (function_exists('getUnitLabel')) {
            $unitText = (string)getUnitLabel($property->unit ?? '');
        }
        $scaleText = $rawString . ' ' . $unitText;

        if ($this->contains($scaleText, 'میلیارد')) {
            $value *= 1000000000;
        } elseif ($this->contains($scaleText, 'میلیون')) {
            $value *= 1000000;
        } elseif ($this->contains($scaleText, 'هزار')) {
            $value *= 1000;
        }

        return $value;
    }

    private function contains(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle) !== false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function median(array $values): ?float
    {
        $values = array_values(array_filter($values, function ($value) {
            return is_numeric($value) && $value > 0;
        }));

        $count = count($values);
        if ($count === 0) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float)$values[$middle];
        }

        return ((float)$values[$middle - 1] + (float)$values[$middle]) / 2;
    }

    private function average(array $values): ?float
    {
        $values = array_values(array_filter($values, function ($value) {
            return is_numeric($value) && $value > 0;
        }));

        if (count($values) === 0) {
            return null;
        }

        return array_sum($values) / count($values);
    }
}
