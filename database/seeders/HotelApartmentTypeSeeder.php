<?php

namespace Database\Seeders;

use App\Models\HotelApartmentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HotelApartmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $apartmentTypes = [
            [
                'name' => 'Studio',
                'description' => 'A compact apartment with combined living and sleeping area',
            ],
            [
                'name' => 'One Bedroom',
                'description' => 'An apartment with one separate bedroom',
            ],
            [
                'name' => 'Two Bedroom',
                'description' => 'An apartment with two separate bedrooms',
            ],
            [
                'name' => 'Penthouse',
                'description' => 'A luxury apartment located on the top floor with premium amenities',
            ],
            [
                'name' => 'Duplex',
                'description' => 'A two-story apartment with internal staircase',
            ],
        ];

        // Add permissions for hotel apartment types
        $permissions = [
            [
                'name' => 'hotel_apartment_types',
                'description' => 'Hotel Apartment Types Module'
            ]
        ];

        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($permissions as $permission) {
            foreach ($actions as $action) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'action' => $action,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Insert apartment types
        foreach ($apartmentTypes as $type) {
            HotelApartmentType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }
}
