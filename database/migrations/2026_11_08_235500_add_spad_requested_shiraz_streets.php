<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSpadRequestedShirazStreets extends Migration
{
    /**
     * Add the owner's requested searchable Shiraz streets to the existing
     * regions catalog. Existing region IDs, map coordinates and advertisement
     * references are retained. Safe to re-run.
     */
    public function up()
    {
        $city = DB::table('cities')->where('name', 'شیراز')->first();
        if (!$city || !is_numeric($city->lat) || !is_numeric($city->lng)) {
            throw new RuntimeException('SPAD_SHIRAZ_CITY_NOT_READY');
        }

        $names = [
            ['name' => 'سراج', 'aliases' => []],
            ['name' => 'پاییز', 'aliases' => []],
            ['name' => 'پاسارگاد غربی', 'aliases' => []],
            ['name' => 'پاسارگاد شرقی', 'aliases' => []],
            ['name' => 'باهنر جنوبی', 'aliases' => []],
            ['name' => 'اخلاق', 'aliases' => []],
            ['name' => 'باهنر شمالی', 'aliases' => []],
            ['name' => 'فرگاز', 'aliases' => []],
            ['name' => 'بلوار صلح', 'aliases' => ['صلح']],
            ['name' => 'بیست متری امام خمینی', 'aliases' => ['۲۰ متری امام خمینی', '20 متری امام خمینی']],
            ['name' => 'زاگرس شرقی', 'aliases' => []],
            ['name' => 'دستغیب', 'aliases' => []],
        ];

        DB::transaction(function () use ($city, $names) {
            $existing = DB::table('regions')
                ->where('city_id', $city->id)
                ->select('id', 'name')
                ->lockForUpdate()
                ->get();

            $inserted = 0;
            $matched = 0;
            foreach ($names as $entry) {
                $keys = array_unique(array_map(
                    [$this, 'normalized'],
                    array_merge([$entry['name']], $entry['aliases'])
                ));
                $found = $existing->first(function ($region) use ($keys) {
                    return in_array($this->normalized($region->name), $keys, true);
                });

                if ($found) {
                    $matched++;
                    // Use the exact requested UI label without changing the
                    // record's primary key, assigned properties or coordinates.
                    if ($found->name !== $entry['name']) {
                        DB::table('regions')->where('id', $found->id)
                            ->where('city_id', $city->id)
                            ->update(['name' => $entry['name']]);
                        $found->name = $entry['name'];
                    }
                } else {
                    // Region coordinates are required in legacy schema. Until a
                    // verified street centroid is supplied, match the existing
                    // Shiraz seeding convention of defaulting to city center.
                    $id = DB::table('regions')->insertGetId([
                        'name' => $entry['name'],
                        'city_id' => $city->id,
                        'lat' => (float) $city->lat,
                        'lng' => (float) $city->lng,
                        'area' => null,
                    ]);
                    $existing->push((object) ['id' => $id, 'name' => $entry['name']]);
                    $inserted++;
                }
            }
            echo 'SPAD_SHIRAZ_STREETS_MATCHED=' . $matched . PHP_EOL;
            echo 'SPAD_SHIRAZ_STREETS_INSERTED=' . $inserted . PHP_EOL;
            echo 'SPAD_SHIRAZ_STREETS_TOTAL=' . count($names) . PHP_EOL;
        }, 3);
    }

    public function down()
    {
        // Deliberately non-destructive: deleting these regions would break
        // any advertisements or CRM references created after deployment.
    }

    private function normalized($value)
    {
        $value = trim((string) $value);
        $value = strtr($value, [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $value = str_replace(["\xE2\x80\x8C", "\xE2\x80\x8F", "\xE2\x80\x8E"], '', $value);
        $value = preg_replace('/^(خیابان|بلوار|محله|محدوده|شهرک)\s+/u', '', $value);
        $value = preg_replace('/[\s\-_\/،,.]+/u', '', $value);
        $value = preg_replace('/^20(?=متری)/u', 'بیست', $value);
        return $value;
    }
}
