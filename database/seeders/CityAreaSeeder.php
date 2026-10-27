<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CityAreaSeeder extends Seeder {
    public function run() {
//        ini_set('memory_limit',0);
        $data = json_decode(file_get_contents(storage_path('dataset_cities.json')));
        foreach ($data->features as $city) {
            $name = str_replace(['شهرستان ', ' '], '', $city->properties->county_name);
            $c = City::where(DB::raw("replace(name,' ','')"), 'like', "%$name%")->first();
            if ($c) {
                $c->area = \Grimzy\LaravelMysqlSpatial\Types\Geometry::fromJson(json_encode($city->geometry));
                $c->save();
            }
        }
    }
}
