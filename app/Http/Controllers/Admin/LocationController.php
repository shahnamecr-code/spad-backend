<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityRequest;
use App\Http\Requests\ProvinceRequest;
use App\Http\Requests\RegionRequest;
use App\Models\Advertisement;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use App\Support\SpadLocation;
use Grimzy\LaravelMysqlSpatial\Types\Geometry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function provinces()
    {
        return SpadLocation::city()->province()->get();
    }

    public function cities()
    {
        return collect([SpadLocation::city()]);
    }

    public function allCities()
    {
        $city = SpadLocation::city()->loadCount('regions');
        $search = trim((string) request('search', ''));
        $matches = $search === '' || mb_strpos($city->name, $search) !== false;

        return response()->json([
            'data' => $matches ? [[
                'id' => $city->id,
                'name' => $city->name,
                'lat' => $city->lat,
                'lng' => $city->lng,
                'regions_count' => $city->regions_count,
            ]] : [],
            'total' => $matches ? 1 : 0,
            'page' => 1,
            'hasMore' => false,
        ]);
    }

    public function allRegions()
    {
        $page = max(1, request('page', 1));
        $perPage = 24;
        $query = Region::query()
            ->where('city_id', SpadLocation::cityId())
            ->with('city');

        if (request()->filled('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }

        $count = $query->count();
        $rows = $query->orderBy('name')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->name,
                    'value' => $item->id,
                ];
            });

        return response()->json([
            'data' => $rows,
            'total' => $count,
            'page' => $page,
            'hasMore' => $page * $perPage < $count,
        ]);
    }

    public function cityAllRegions()
    {
        return SpadLocation::city()
            ->regions()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    // Province APIs are kept only for backwards compatibility. The admin UI no longer exposes them.
    public function province_index(Request $request)
    {
        return SpadLocation::city()->province()->paginate((int) request('per_page', 20));
    }

    public function province_store(ProvinceRequest $request): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'مدیریت استان در نسخه شیراز غیرفعال است.'], 422);
    }

    public function province_show(Province $province): Province
    {
        return SpadLocation::city()->province;
    }

    public function province_update(ProvinceRequest $request, Province $province): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'مدیریت استان در نسخه شیراز غیرفعال است.'], 422);
    }

    public function province_delete(Province $province): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'مدیریت استان در نسخه شیراز غیرفعال است.'], 422);
    }

    public function province_cities()
    {
        return collect([SpadLocation::city()]);
    }

    public function city_index()
    {
        return City::query()
            ->whereKey(SpadLocation::cityId())
            ->paginate((int) request('per_page', 20));
    }

    public function city_store(CityRequest $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'SPAD فقط برای شهر شیراز فعال است و افزودن شهر جدید امکان‌پذیر نیست.',
        ], 422);
    }

    public function city_show(City $city): City
    {
        $shiraz = SpadLocation::city();
        abort_if((int) $city->id !== (int) $shiraz->id, 404);
        return $shiraz;
    }

    public function city_update(CityRequest $request, City $city): JsonResponse
    {
        $shiraz = SpadLocation::city();
        abort_if((int) $city->id !== (int) $shiraz->id, 404);

        $data = $request->validated();
        $data['name'] = SpadLocation::CITY_NAME;
        $data['province_id'] = $shiraz->province_id;

        if (isset($data['area']) && $data['area']) {
            $data['area'] = Geometry::fromJson(json_encode($data['area']));
        }

        $shiraz->update($data);

        return response()->json(['status' => true, 'message' => 'اطلاعات شهر شیراز به‌روزرسانی شد.']);
    }

    public function city_delete(City $city): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'شهر شیراز شهر ثابت SPAD است و قابل حذف نیست.',
        ], 422);
    }

    public function cities_regions(Request $request, City $city)
    {
        $shiraz = SpadLocation::city();
        abort_if((int) $city->id !== (int) $shiraz->id, 404);

        $query = $shiraz->regions()->orderBy('name');
        $request->whenFilled('search', function ($search) use ($query) {
            $query->where('name', 'like', '%' . $search . '%');
        });

        return $request->get('type') === 'all'
            ? $query->get()
            : $query->paginate((int) request('per_page', 20));
    }

    public function region_index()
    {
        return Region::query()
            ->where('city_id', SpadLocation::cityId())
            ->with('city')
            ->orderBy('name')
            ->paginate((int) request('per_page', 20));
    }

    public function region_store(RegionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['city_id'] = SpadLocation::cityId();

        if (isset($data['area']) && $data['area']) {
            $data['area'] = Geometry::fromJson(json_encode($data['area']));
        }

        Region::create($data);
        return response()->json(['status' => true, 'message' => 'خیابان با موفقیت افزوده شد.']);
    }

    public function region_show(Region $region): Region
    {
        abort_if((int) $region->city_id !== SpadLocation::cityId(), 404);
        $region->load('city:id,province_id,name');
        return $region;
    }

    public function region_update(RegionRequest $request, Region $region): JsonResponse
    {
        abort_if((int) $region->city_id !== SpadLocation::cityId(), 404);
        $data = $request->validated();
        $data['city_id'] = SpadLocation::cityId();

        if (isset($data['area']) && $data['area']) {
            $data['area'] = Geometry::fromJson(json_encode($data['area']));
        }

        $region->update($data);
        return response()->json(['status' => true, 'message' => 'خیابان با موفقیت به‌روزرسانی شد.']);
    }

    public function region_delete(Region $region): JsonResponse
    {
        abort_if((int) $region->city_id !== SpadLocation::cityId(), 404);

        if (Advertisement::query()->where('region_id', $region->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'این خیابان در آگهی‌ها استفاده شده و قابل حذف نیست. ابتدا آگهی‌های مرتبط را به خیابان دیگری منتقل کنید.',
            ], 422);
        }

        $region->delete();
        return response()->json(['status' => true, 'message' => 'خیابان با موفقیت حذف شد.']);
    }
}
