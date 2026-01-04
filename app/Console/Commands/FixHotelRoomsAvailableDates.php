<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixHotelRoomsAvailableDates extends Command
{
    protected $signature = 'fix:hotel-rooms-available-dates';
    protected $description = 'Manually add available_dates column to hotel_rooms and mark migration as run';

    public function handle()
    {
        $this->info("🔧 Fixing hotel_rooms.available_dates column");

        // 1) Add the column if it doesn't exist
        if (!Schema::hasColumn('hotel_rooms', 'available_dates')) {
            DB::statement("ALTER TABLE hotel_rooms ADD COLUMN available_dates JSON NULL AFTER availability_type COMMENT 'Array of date ranges [{from: \"YYYY-MM-DD\", to: \"YYYY-MM-DD\"}]'");
            $this->info("✅ Added available_dates column");
        } else {
            $this->info("ℹ️  available_dates column already exists");
        }

        // 2) Mark the migration as run (if not already)
        $migration = '2025_07_12_155350_add_availability_fields_to_hotel_rooms_table';
        $exists = DB::table('migrations')->where('migration', $migration)->exists();
        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);
            $this->info("✅ Marked migration as run");
        } else {
            $this->info("ℹ️  Migration already marked as run");
        }

        $this->info("🎉 Done. Try creating a hotel property again.");
        return 0;
    }
}
