<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Support\SpadLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function shiraz()
    {
        $city = SpadLocation::city()->loadCount('regions');

        return response()->json([
            'id' => $city->id,
            'name' => $city->name,
            'lat' => $city->lat,
            'lng' => $city->lng,
            'province_id' => $city->province_id,
            'regions_count' => $city->regions_count,
            'streets_count' => $city->regions_count,
        ]);
    }

    /**
     * Backward-compatible endpoint. Province selection has been removed from SPAD UI.
     */
    public function provinces()
    {
        $city = SpadLocation::city();
        return $city->province()->select(['id', 'name'])->get();
    }

    public function provincesV1()
    {
        $city = SpadLocation::city();
        $province = $city->province()->select(['id', 'name'])->first();

        if (!$province) {
            return [];
        }

        $province->setRelation('cities', collect([
            $city->only(['id', 'name', 'province_id'])
        ]));

        return [$province];
    }

    public function getProvinceCities()
    {
        $city = SpadLocation::city();
        return [[
            'id' => $city->id,
            'name' => $city->name,
            'lat' => $city->lat,
            'lng' => $city->lng,
            'province_id' => $city->province_id,
        ]];
    }

    public function getCityRegions()
    {
        return SpadLocation::city()
            ->regions()
            ->select(['id', 'name', 'lat', 'lng'])
            ->orderBy('name', 'asc')
            ->get();
    }

    public function asyncPaginateCities()
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
                'province_id' => $city->province_id,
                'regions_count' => $city->regions_count,
            ]] : [],
            'total' => $matches ? 1 : 0,
            'page' => 1,
            'hasMore' => false,
        ]);
    }

    public function getRegionsByCityAndProvinces(Request $request)
    {
        $query = Region::query()
            ->where('city_id', SpadLocation::cityId())
            ->select(['id', 'name', 'city_id'])
            ->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return $query->get();
    }

    public function getPaginatedRegionsByCityAndProvinces(Request $request)
    {
        $query = Region::query()
            ->where('city_id', SpadLocation::cityId())
            ->select(['id', 'name', 'city_id'])
            ->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return $query->paginate();
    }
}
