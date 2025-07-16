<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HotelAddonFieldPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the maximum permission ID
        $maxId = DB::table('permissions')->max('id');

        // Insert hotel_addon_field permissions
        $permissions = [
            [
                'id' => $maxId + 1,
                'name' => 'hotel_addon_field',
                'action' => 'create',
                'description' => 'Create Hotel Addon Field'
            ],
            [
                'id' => $maxId + 2,
                'name' => 'hotel_addon_field',
                'action' => 'read',
                'description' => 'View Hotel Addon Field'
            ],
            [
                'id' => $maxId + 3,
                'name' => 'hotel_addon_field',
                'action' => 'update',
                'description' => 'Update Hotel Addon Field'
            ],
            [
                'id' => $maxId + 4,
                'name' => 'hotel_addon_field',
                'action' => 'delete',
                'description' => 'Delete Hotel Addon Field'
            ],
        ];

        // Insert permissions
        DB::table('permissions')->insert($permissions);

        // Get all roles with ID 1 (admin)
        $adminRoles = DB::table('roles')->where('id', 1)->get();

        // Assign permissions to admin roles
        foreach ($adminRoles as $role) {
            $rolePermissions = [];

            foreach ($permissions as $permission) {
                $rolePermissions[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permission['id']
                ];
            }

            DB::table('role_permissions')->insert($rolePermissions);
        }
    }
}
