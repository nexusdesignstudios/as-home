<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckHotelStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:hotel-structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check hotel_rooms table structure';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "=== CHECKING HOTEL_ROOMS TABLE STRUCTURE ===\n\n";

        $columns = Schema::getColumnListing('hotel_rooms');
        
        echo "Columns in hotel_rooms table:\n";
        foreach ($columns as $column) {
            echo "  - $column\n";
        }

        echo "\n=== CHECKING AVAILABLE_DATES_HOTEL_ROOMS TABLE ===\n\n";
        
        if (Schema::hasTable('available_dates_hotel_rooms')) {
            echo "available_dates_hotel_rooms table exists\n";
            
            $availColumns = Schema::getColumnListing('available_dates_hotel_rooms');
            echo "Columns in available_dates_hotel_rooms table:\n";
            foreach ($availColumns as $column) {
                echo "  - $column\n";
            }
            
            // Check sample data
            $sampleAvail = DB::table('available_dates_hotel_rooms')
                ->limit(5)
                ->get();
            
            echo "\nSample available_dates data:\n";
            foreach ($sampleAvail as $avail) {
                echo "  Room ID: {$avail->hotel_room_id}, From: {$avail->from_date}, To: {$avail->to_date}, Type: {$avail->type}\n";
            }
        } else {
            echo "available_dates_hotel_rooms table does NOT exist\n";
        }

        echo "\n=== CHECKING ROOM DATA ===\n\n";

        // Get sample room data
        $rooms = DB::table('hotel_rooms')
            ->select('id', 'room_number', 'room_type_id', 'price_per_night')
            ->limit(5)
            ->get();

        echo "Sample room data:\n";
        foreach ($rooms as $room) {
            echo "  Room ID: {$room->id}, Number: {$room->room_number}, Type ID: {$room->room_type_id}\n";
        }

        echo "\n=== CHECKING PROPERTIES TABLE ===\n\n";

        $propColumns = Schema::getColumnListing('propertys');
        echo "Columns in propertys table:\n";
        foreach ($propColumns as $column) {
            echo "  - $column\n";
        }

        echo "\n=== CHECKING FOR AMAZING 4 STAR HOTEL ===\n\n";

        // Get property ID - try different column names
        $property = DB::table('propertys')
            ->where('title', 'like', '%Amazing 4 Star Hotel%')
            ->orWhere('name', 'like', '%Amazing 4 Star Hotel%')
            ->orWhere('property_name', 'like', '%Amazing 4 Star Hotel%')
            ->first();

        if ($property) {
            echo "Found property: " . ($property->title ?? $property->name ?? $property->property_name) . " (ID: {$property->id})\n";
            
            $hotelRooms = DB::table('hotel_rooms')
                ->where('property_id', $property->id)
                ->count();
            
            echo "Hotel rooms count: $hotelRooms\n";
        } else {
            echo "Amazing 4 Star Hotel not found\n";
            
            // Show all properties for debugging
            $allProps = DB::table('propertys')
                ->select('id', 'title', 'name', 'property_name')
                ->limit(5)
                ->get();
            
            echo "Available properties:\n";
            foreach ($allProps as $prop) {
                echo "  ID: {$prop->id}, Title: " . ($prop->title ?? $prop->name ?? $prop->property_name) . "\n";
            }
        }

        return 0;
    }
}
