<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayoutPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the admin role ID
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if (!$adminRoleId) {
            $this->command->error('Admin role not found!');
            return;
        }

        // Define permissions
        $permissions = [
            [
                'name' => 'property_payouts',
                'description' => 'Property Payouts Management'
            ]
        ];

        // Add permissions
        foreach ($permissions as $permission) {
            // Check if permission already exists
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();

            if (!$exists) {
                // Insert permission
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Assign permission to admin role
                DB::table('role_permissions')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                    'create' => 1,
                    'read' => 1,
                    'update' => 1,
                    'delete' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $this->command->info("Permission '{$permission['name']}' added successfully.");
            } else {
                $this->command->info("Permission '{$permission['name']}' already exists.");
            }
        }
    }
}
