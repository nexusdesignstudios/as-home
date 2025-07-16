<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\HotelAddonFieldPermissionSeeder;

class SeedHotelAddonFieldPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:hotel-addon-field-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the hotel addon field permissions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Seeding hotel addon field permissions...');

        // Run the seeder
        $seeder = new HotelAddonFieldPermissionSeeder();
        $seeder->run();

        $this->info('Hotel addon field permissions seeded successfully!');

        return Command::SUCCESS;
    }
}
