<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RunPropertySeedersOnly extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Only run the property-related seeders
        $this->call([
            ParameterSeeder::class,
            CategorySeeder::class,
            PropertySeeder::class,
        ]);
    }
}
