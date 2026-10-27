<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data=json_decode(file_get_contents(storage_path('marakez_shar_geojson.json')));
        foreach($data->features as $item){
            City::create([
                'id'=>$item->properties->city_coding,
                'name'=>$item->properties->name,
                'lng'=>$item->geometry->coordinates[0],
                'lat'=>$item->geometry->coordinates[1],
                'province_id'=>$item->properties->province_coding,
            ]);
        }
    }
}
