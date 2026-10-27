<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $default_attributes=[
            [
                'code'=>'name',
                'name'=>'نام',
                'type' => 'text',
                'is_default' => true,
                'is_variable' => false,
            ],
            [
                'code' => 'description',
                'name' => 'توضیحات',
                'type' => 'textarea',
                'is_default' => true,
                'is_variable' => false,
            ],
            [
                'code' => 'main_image',
                'name' => 'تصویر شاخص',
                'type' => 'image',
                'is_default' => true,
                'is_variable' => false,
            ],
            [
                'code' => 'gallery',
                'name' => 'توضیحات',
                'type' => 'gallery',
                'is_default' => true,
                'is_variable' => false,
            ],
        ];
        foreach ($default_attributes as $attribute){
            Attribute::create($attribute);
        }
    }
}
