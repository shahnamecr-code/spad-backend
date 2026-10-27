<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateAllAdsToShirazAndSeedStreets extends Migration
{
    public function up()
    {
        $shiraz = DB::table('cities')->where('name', 'شیراز')->first();

        if (!$shiraz) {
            throw new RuntimeException('SPAD migration aborted: city "شیراز" was not found in cities table.');
        }

        $streets = require database_path('data/shiraz_streets.php');

        DB::transaction(function () use ($shiraz, $streets) {
            // 1) Make Shiraz the only default city again (idempotent safeguard).
            DB::table('cities')->update(['is_default' => false]);
            DB::table('cities')->where('id', $shiraz->id)->update(['is_default' => true]);

            // 2) Seed/normalise the canonical Shiraz street list.
            // Existing matching records keep their exact map coordinates/area.
            $existing = DB::table('regions')
                ->where('city_id', $shiraz->id)
                ->select(['id', 'name', 'lat', 'lng'])
                ->get();

            $canonicalRegionIds = [];

            foreach ($streets as $street) {
                $acceptedNames = array_merge([$street['name']], $street['aliases'] ?? []);
                $acceptedNormalized = array_values(array_unique(array_map([$this, 'normalize'], $acceptedNames)));

                $match = $existing->first(function ($region) use ($acceptedNormalized) {
                    return in_array($this->normalize($region->name), $acceptedNormalized, true);
                });

                if ($match) {
                    DB::table('regions')->where('id', $match->id)->update([
                        'name' => $street['name'],
                    ]);
                    $regionId = (int) $match->id;
                } else {
                    $regionId = (int) DB::table('regions')->insertGetId([
                        'name' => $street['name'],
                        'lat' => (float) $shiraz->lat,
                        'lng' => (float) $shiraz->lng,
                        'city_id' => (int) $shiraz->id,
                        'area' => null,
                    ]);

                    $existing->push((object) [
                        'id' => $regionId,
                        'name' => $street['name'],
                        'lat' => (float) $shiraz->lat,
                        'lng' => (float) $shiraz->lng,
                    ]);
                }

                $canonicalRegionIds[$this->normalize($street['name'])] = $regionId;
            }

            // 3) Move ALL current ads to Shiraz.
            // If an ad already belonged to Shiraz and its street is still a valid Shiraz
            // region, we preserve it. Streets from other cities are intentionally cleared
            // so an old Tabriz/Tehran/etc. region can never be presented as a Shiraz street.
            $validShirazRegionIds = DB::table('regions')
                ->where('city_id', $shiraz->id)
                ->pluck('id')
                ->map(function ($id) { return (int) $id; })
                ->all();

            $validLookup = array_fill_keys($validShirazRegionIds, true);

            DB::table('advertisements')
                ->select(['id', 'city_id', 'region_id'])
                ->orderBy('id')
                ->chunkById(250, function ($ads) use ($shiraz, $validLookup) {
                    foreach ($ads as $ad) {
                        $keepRegion = (int) $ad->city_id === (int) $shiraz->id
                            && $ad->region_id
                            && isset($validLookup[(int) $ad->region_id]);

                        DB::table('advertisements')
                            ->where('id', $ad->id)
                            ->update([
                                'city_id' => (int) $shiraz->id,
                                'region_id' => $keepRegion ? (int) $ad->region_id : null,
                                'updated_at' => now(),
                            ]);
                    }
                });

            // 4) City-based admin access is also normalised to Shiraz. We keep the
            // existing admin rows/roles and only replace their city scope.
            if (DB::getSchemaBuilder()->hasTable('admin_city')) {
                $adminIds = DB::table('admin_city')->distinct()->pluck('admin_id');
                DB::table('admin_city')->delete();

                foreach ($adminIds as $adminId) {
                    DB::table('admin_city')->insert([
                        'admin_id' => $adminId,
                        'city_id' => (int) $shiraz->id,
                    ]);
                }
            }
        });
    }

    public function down()
    {
        // Deliberately irreversible: after SPAD becomes Shiraz-only there is no reliable
        // way to infer the former city of each migrated advertisement.
    }

    private function normalize($value)
    {
        $value = trim((string) $value);
        $value = strtr($value, [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
        ]);
        $value = str_replace(["\xE2\x80\x8C", "\xE2\x80\x8F"], '', $value); // ZWNJ / RTL mark
        $value = preg_replace('/^(خیابان|بلوار|محله|محدوده|شهرک)\s+/u', '', $value);
        $value = preg_replace('/[\s\-_\/،,.]+/u', '', $value);

        return $value;
    }
}
