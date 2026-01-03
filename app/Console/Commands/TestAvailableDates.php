<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HotelRoom;
use App\Models\Property;

class TestAvailableDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:available-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test available_dates loading for hotel rooms';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "=== TESTING AVAILABLE_DATES LOADING ===\n\n";

        // Get Amazing 4 Star Hotel property
        $property = Property::where('title', 'like', '%Amazing 4 Star Hotel%')->first();
        
        if (!$property) {
            echo "Amazing 4 Star Hotel not found\n";
            return 0;
        }

        echo "Found property: {$property->title} (ID: {$property->id})\n\n";

        // Load hotel rooms with available_dates
        $hotelRooms = HotelRoom::where('property_id', $property->id)
            ->with('roomType')
            ->get();

        echo "Found {$hotelRooms->count()} hotel rooms\n\n";

        foreach ($hotelRooms as $room) {
            echo "=== Room ID: {$room->id} (" . ($room->roomType ? $room->roomType->name : 'Unknown') . ") ===\n";
            
            // Test the available_dates accessor
            $availableDates = $room->available_dates;
            
            echo "Available dates count: " . count($availableDates) . "\n";
            
            if (!empty($availableDates)) {
                foreach ($availableDates as $i => $dateRange) {
                    echo "  Range " . ($i + 1) . ": {$dateRange['from']} to {$dateRange['to']} ({$dateRange['type']})\n";
                    if (isset($dateRange['price'])) {
                        echo "    Price: {$dateRange['price']}\n";
                    }
                }
            } else {
                echo "  No available dates configured\n";
            }
            
            // Test JSON serialization (what the API would return)
            $roomArray = $room->toArray();
            echo "JSON includes available_dates: " . (isset($roomArray['available_dates']) ? 'YES' : 'NO') . "\n";
            
            if (isset($roomArray['available_dates'])) {
                echo "JSON available_dates count: " . count($roomArray['available_dates']) . "\n";
            }
            
            echo "\n";
        }

        echo "=== TEST COMPLETE ===\n";
        echo "The available_dates should now be included in API responses!\n";

        return 0;
    }
}
