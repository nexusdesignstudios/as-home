<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckHotelRoomsSchema extends Command
{
    protected $signature = 'check:hotel-rooms-schema';
    protected $description = 'Check if hotel_rooms table has available_dates column';

    public function handle()
    {
        $this->info("🔍 Checking hotel_rooms table schema");
        $this->info("====================================");

        if (!Schema::hasTable('hotel_rooms')) {
            $this->error("❌ hotel_rooms table does not exist");
            return 1;
        }

        $columns = Schema::getColumnListing('hotel_rooms');
        $this->info("Columns in hotel_rooms table:");
        foreach ($columns as $col) {
            $this->line("  - {$col}");
        }

        if (in_array('available_dates', $columns)) {
            $this->info("✅ available_dates column exists");
        } else {
            $this->error("❌ available_dates column MISSING");
            $this->line("\n🔧 To add it, run:");
            $this->line("  php artisan migrate");
        }

        // Show recent migrations
        $this->info("\n📋 Recent migrations (last 10):");
        $migrations = DB::table('migrations')->orderBy('id', 'desc')->limit(10)->get(['migration', 'batch']);
        foreach ($migrations as $m) {
            $this->line("  [Batch {$m->batch}] {$m->migration}");
        }

        return 0;
    }
}
