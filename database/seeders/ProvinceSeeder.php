<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data=json_decode(file_get_contents(storage_path('marakez_ostan_geojson.json')));
        foreach($data->features as $item){
            Province::create([
                'id'=>$item->properties->province_coding,
                'name'=>str_replace('استان ','',$item->properties->name),
                'lng'=>$item->geometry->coordinates[0],
                'lat'=>$item->geometry->coordinates[1],
            ]);
        }
    }
}
