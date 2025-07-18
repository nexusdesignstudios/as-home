<?php

namespace Database\Seeders;

use App\Models\HotelApartmentType;
use Illuminate\Database\Seeder;

class HotelApartmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apartmentTypes = [
            [
                'name' => 'Studio',
                'description' => 'A compact apartment with combined living and sleeping area.'
            ],
            [
                'name' => 'One Bedroom',
                'description' => 'An apartment with a separate bedroom and living area.'
            ],
            [
                'name' => 'Two Bedroom',
                'description' => 'An apartment with two separate bedrooms and a living area.'
            ],
            [
                'name' => 'Penthouse',
                'description' => 'A luxury apartment located on the top floor with premium amenities.'
            ],
            [
                'name' => 'Duplex',
                'description' => 'A two-story apartment with internal stairs.'
            ],
        ];

        foreach ($apartmentTypes as $apartmentType) {
            HotelApartmentType::create($apartmentType);
        }
    }
}
