<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesAndPermissions = [
            [
                'name' => "Super Admin",
                'title' => "مدیریت سیستم",
                'permissions' => []
            ],
            [
                'name' => "manager",
                'title' => "مدیر",
                'permissions' => [
                    ['name' => 'reject.advertisement', 'title' => "رد و تایید آگهی"],
                    ['name' => 'transfer.advertisement', 'title' => "انتقال آگهی"],
                ]
            ],
            [
                'name' => "supervisor",
                'title' => "سرپرست",
                'permissions' => [
                    ['name' => 'reject.advertisement', 'title' => "رد و تایید آگهی"],
                ]
            ],
            [
                'name' => "expert",
                'title' => "کارشناس",
                'permissions' => []
            ],
        ];


        foreach ($rolesAndPermissions as $rolesAndPermission) {
            $role = Role::firstOrCreate(['name' => $rolesAndPermission['name'], 'guard_name' => 'admin', 'title' => $rolesAndPermission['title']]);

            if (isset($rolesAndPermission['permissions']) && count($rolesAndPermission['permissions']) > 0) {
                $permissions = [];
                foreach ($rolesAndPermission['permissions'] as $permission) {
                    $permissions[] = Permission::firstOrCreate(['name' => $permission['name'], 'guard_name' => 'admin', 'title' => $permission['title']]);
                }

                $role->syncPermissions($permissions);
            }

        }

        /*foreach ($roles as $role) {
            Role::create(['name' => $role['name'], 'guard_name' => 'admin', 'title' => $role['title']]);
        }*/

    }
}
