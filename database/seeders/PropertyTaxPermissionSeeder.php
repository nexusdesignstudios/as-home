<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyTaxPermissionSeeder extends Seeder
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

        // Insert property_taxes permissions
        $permissions = [
            [
                'id' => $maxId + 1,
                'name' => 'property_taxes',
                'action' => 'create',
                'description' => 'Create Property Taxes'
            ],
            [
                'id' => $maxId + 2,
                'name' => 'property_taxes',
                'action' => 'read',
                'description' => 'View Property Taxes'
            ],
            [
                'id' => $maxId + 3,
                'name' => 'property_taxes',
                'action' => 'update',
                'description' => 'Update Property Taxes'
            ],
            [
                'id' => $maxId + 4,
                'name' => 'property_taxes',
                'action' => 'delete',
                'description' => 'Delete Property Taxes'
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
