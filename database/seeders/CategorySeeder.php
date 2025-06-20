<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample category data
        $categories = [
            [
                'category' => 'Residential',
                'parameter_types' => '1,2,3,4,5', // Assumes these parameter IDs exist
                'image' => 'residential.jpg',
                'status' => 1,
                'slug_id' => 'residential'
            ],
            [
                'category' => 'Commercial',
                'parameter_types' => '1,2,3,4,5', // Assumes these parameter IDs exist
                'image' => 'commercial.jpg',
                'status' => 1,
                'slug_id' => 'commercial'
            ],
            [
                'category' => 'Land',
                'parameter_types' => '1,2,3', // Assumes these parameter IDs exist
                'image' => 'land.jpg',
                'status' => 1,
                'slug_id' => 'land'
            ],
            [
                'category' => 'Vacation Property',
                'parameter_types' => '1,2,3,4,5,6', // Assumes these parameter IDs exist
                'image' => 'vacation.jpg',
                'status' => 1,
                'slug_id' => 'vacation-property'
            ],
        ];

        // Insert categories
        foreach ($categories as $category) {
            // Check if category already exists
            $exists = DB::table('categories')
                ->where('category', $category['category'])
                ->orWhere('slug_id', $category['slug_id'])
                ->exists();

            if (!$exists) {
                // Add timestamps
                $category['created_at'] = now();
                $category['updated_at'] = now();

                DB::table('categories')->insert($category);
            }
        }
    }
}
