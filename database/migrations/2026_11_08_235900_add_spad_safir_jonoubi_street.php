<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add the owner-requested Safir Jonoubi street to Shiraz listing locations.
 * Preserve existing region ids and coordinates if a matching street exists.
 */
class AddSpadSafirJonoubiStreet extends Migration
{
    public function up()
    {
        $city = DB::table('cities')->where('name', 'شیراز')->first();
        if (!$city || !is_numeric($city->lat) || !is_numeric($city->lng)) {
            throw new RuntimeException('SPAD_SHIRAZ_CITY_NOT_FOUND');
        }

        DB::transaction(function () use ($city) {
            $street = 'سفیر جنوبی';
            $matching = DB::table('regions')
                ->where('city_id', $city->id)
                ->select('id', 'name')
                ->lockForUpdate()
                ->get()
                ->filter(function ($row) use ($street) {
                    return $this->normalized($row->name) === $this->normalized($street);
                })
                ->values();

            if ($matching->count() > 1) {
                throw new RuntimeException('SPAD_SAFIR_JONOUBI_DUPLICATE_REGIONS_REVIEW_REQUIRED');
            }

            if ($matching->count() === 1) {
                $region = $matching->first();
                if ($region->name !== $street) {
                    DB::table('regions')->where('id', $region->id)
                        ->update(['name' => $street]);
                }
                echo 'SPAD_SAFIR_JONOUBI=EXISTING ID=' . $region->id . PHP_EOL;
                return;
            }

            // Existing schema requires lat/lng. Reuse the city-center
            // fallback convention; these are NOT verified street coordinates.
            $id = DB::table('regions')->insertGetId([
                'name' => $street,
                'city_id' => $city->id,
                'lat' => (float) $city->lat,
                'lng' => (float) $city->lng,
                'area' => null,
            ]);
            echo 'SPAD_SAFIR_JONOUBI=CREATED ID=' . $id . PHP_EOL;
        }, 3);
    }

    public function down()
    {
        // Non-destructive: newly registered ads may refer to this region.
    }

    private function normalized($text)
    {
        $text = trim((string) $text);
        $text = strtr($text, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه']);
        $text = preg_replace('/[\x{200C}\x{200D}\x{200E}\x{200F}]/u', '', $text);
        $text = preg_replace('/^(خیابان|بلوار|محله|محدوده)\s+/u', '', $text);
        return preg_replace('/[\s\-_\/،,.]+/u', '', $text);
    }
}
