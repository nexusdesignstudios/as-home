<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedPropertyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:property-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed parameters, categories, and properties with classification data for testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Seeding parameters...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ParameterSeeder']);
        $this->info('Parameters seeded successfully!');

        $this->info('Seeding categories...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CategorySeeder']);
        $this->info('Categories seeded successfully!');

        $this->info('Seeding properties with classifications...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PropertySeeder']);
        $this->info('Properties seeded successfully!');

        $this->info('All property data has been seeded successfully!');

        return 0;
    }
}
