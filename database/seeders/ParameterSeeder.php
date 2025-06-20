<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the columns exist
        $hasStatusColumn = Schema::hasColumn('parameters', 'status');
        $hasRequiredColumn = Schema::hasColumn('parameters', 'is_required');

        // Sample parameters data
        $parameters = [
            [
                'name' => 'Bedrooms',
                'type_of_parameter' => 'number',
                'type_values' => NULL,
            ],
            [
                'name' => 'Bathrooms',
                'type_of_parameter' => 'number',
                'type_values' => NULL,
            ],
            [
                'name' => 'Square Feet',
                'type_of_parameter' => 'number',
                'type_values' => NULL,
            ],
            [
                'name' => 'Garage',
                'type_of_parameter' => 'dropdown',
                'type_values' => json_encode(['Yes', 'No']),
            ],
            [
                'name' => 'Year Built',
                'type_of_parameter' => 'number',
                'type_values' => NULL,
            ],
            [
                'name' => 'Property Features',
                'type_of_parameter' => 'checkbox',
                'type_values' => json_encode(['Swimming Pool', 'Garden', 'Gym', 'Balcony', 'Elevator', 'Parking']),
            ],
        ];

        // Insert parameters
        foreach ($parameters as $parameter) {
            // Add timestamps
            $parameter['created_at'] = now();
            $parameter['updated_at'] = now();

            // Add status and is_required if columns exist
            if ($hasStatusColumn) {
                $parameter['status'] = 1;
            }

            if ($hasRequiredColumn) {
                $parameter['is_required'] = $parameter['name'] === 'Bedrooms' ||
                    $parameter['name'] === 'Bathrooms' ||
                    $parameter['name'] === 'Square Feet' ? 1 : 0;
            }

            DB::table('parameters')->insert($parameter);
        }
    }
}
