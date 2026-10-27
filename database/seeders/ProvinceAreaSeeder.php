<?php

namespace Database\Seeders;

use App\Models\Province;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Database\Seeder;

class ProvinceAreaSeeder extends Seeder {
    public function run() {
        $data = json_decode(file_get_contents(storage_path('dataset_province.json')));
        foreach ($data->features as $province) {
            $p = Province::firstWhere('name', str_replace('استان ', '', $province->properties->province_name));
            $coordinates = collect($province->geometry->coordinates[0]);
            $points = $coordinates->map(function ($coord) {
                return new Point($coord[1], $coord[0]);
            })->toArray();
            $polygon = new Polygon([new LineString($points)]);

            $p->area = $polygon;
            $p->save();
        }
    }
}
