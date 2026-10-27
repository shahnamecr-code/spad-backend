<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
    //    $this->call(PermissionSeeder::class);
      // $this->call(AttributeSeeder::class);
/*        $admin = Admin::create([
            'first_name' => 'spad',
            'last_name' => 'spad',
            'status' => 1,
            'password' => '12345678',
            'username' => 'spad',
            'email' => "info@spad.estate"
        ]);
        $admin->assignRole('Super Admin');*/
        // $this->call(ProvinceSeeder::class);
        // $this->call(CitySeeder::class);
        $this->call(ProvinceAreaSeeder::class);
        $this->call(CityAreaSeeder::class);
    }
}
