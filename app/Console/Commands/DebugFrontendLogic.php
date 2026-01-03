<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HotelRoom;
use App\Models\Property;

class DebugFrontendLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:frontend-logic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug frontend availability logic';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "=== DEBUGGING FRONTEND AVAILABILITY LOGIC ===\n\n";

        // Get Amazing 4 Star Hotel property
        $property = Property::where('title', 'like', '%Amazing 4 Star Hotel%')->first();
        
        if (!$property) {
            echo "Amazing 4 Star Hotel not found\n";
            return 0;
        }

        echo "Property: {$property->title} (ID: {$property->id})\n\n";

        // Load hotel rooms with available_dates
        $hotelRooms = HotelRoom::where('property_id', $property->id)
            ->with('roomType')
            ->get();

        echo "Found {$hotelRooms->count()} hotel rooms\n\n";

        $checkIn = '2026-01-23';
        $checkOut = '2026-01-24';

        // Simulate frontend logic
        $uniqueRoomTypes = [];
        $availableRoomTypes = [];
        
        // Group rooms by room_type_id (like frontend)
        $roomTypeMap = [];
        foreach ($hotelRooms as $room) {
            $roomTypeId = $room->room_type_id;
            
            if (!isset($roomTypeMap[$roomTypeId])) {
                $roomTypeMap[$roomTypeId] = [
                    'room_type_id' => $roomTypeId,
                    'room_type' => $room->roomType,
                    'rooms' => [],
                    'representativeRoom' => $room,
                ];
            }
            
            $roomTypeMap[$roomTypeId]['rooms'][] = $room;
        }
        
        $uniqueRoomTypes = array_values($roomTypeMap);
        
        echo "=== UNIQUE ROOM TYPES ===\n";
        foreach ($uniqueRoomTypes as $roomType) {
            echo "Room Type ID: {$roomType['room_type_id']}, Name: " . ($roomType['room_type']->name ?? 'Unknown') . "\n";
            echo "  Total rooms: " . count($roomType['rooms']) . "\n";
            
            // Check if any room in this type is available (simulate frontend logic)
            $hasAvailableRoom = false;
            foreach ($roomType['rooms'] as $room) {
                $isAvailable = $this->simulateFrontendAvailabilityCheck($room, $checkIn, $checkOut);
                if ($isAvailable) {
                    $hasAvailableRoom = true;
                    echo "  Room {$room->id}: ✅ Available\n";
                } else {
                    echo "  Room {$room->id}: ❌ Not Available\n";
                }
                
                // Debug room availability data
                if ($room->available_dates) {
                    echo "    Available dates: " . count($room->available_dates) . " ranges\n";
                    foreach ($room->available_dates as $i => $dateRange) {
                        echo "      Range " . ($i + 1) . ": {$dateRange['from']} to {$dateRange['to']} ({$dateRange['type']})\n";
                    }
                } else {
                    echo "    Available dates: NONE\n";
                }
            }
            
            if ($hasAvailableRoom) {
                $availableRoomTypes[] = $roomType;
                echo "  ✅ Room type will be shown\n";
            } else {
                echo "  ❌ Room type will be hidden\n";
            }
            
            echo "\n";
        }
        
        echo "\n=== AVAILABLE ROOM TYPES COUNT ===\n";
        echo "Available room types: " . count($availableRoomTypes) . "\n";
        
        foreach ($availableRoomTypes as $roomType) {
            echo "- {$roomType['room_type']->name} (ID: {$roomType['room_type_id']})\n";
        }
        
        echo "\n=== FRONTEND ISSUE ANALYSIS ===\n";
        
        if (count($availableRoomTypes) === 1) {
            echo "❌ ISSUE: Only 1 room type available, but we expect more\n";
            
            // Check if there are actually more rooms available
            $totalAvailableRooms = 0;
            foreach ($uniqueRoomTypes as $roomType) {
                foreach ($roomType['rooms'] as $room) {
                    if ($this->simulateFrontendAvailabilityCheck($room, $checkIn, $checkOut)) {
                        $totalAvailableRooms++;
                    }
                }
            }
            
            echo "Total available rooms across all types: $totalAvailableRooms\n";
            
            if ($totalAvailableRooms > count($availableRoomTypes[0]['rooms'])) {
                echo "❌ PROBLEM: Multiple room types have available rooms but only 1 type is being shown\n";
                echo "   This suggests the frontend filtering logic is working correctly\n";
                echo "   But the UI might be hiding other room types\n";
            } else {
                echo "✅ CORRECT: Only 1 room type actually has available rooms\n";
            }
        } else {
            echo "✅ CORRECT: Multiple room types available\n";
        }

        echo "\n=== DEBUG COMPLETE ===\n";

        return 0;
    }
    
    /**
     * Simulate frontend availability check logic
     */
    private function simulateFrontendAvailabilityCheck($room, $checkIn, $checkOut)
    {
        // Simulate available_dates check (like frontend)
        $availableDates = $room->available_dates;
        
        if (empty($availableDates)) {
            return true; // No dates configured = available
        }
        
        // Check if checkIn date is available in any range (one-night booking logic)
        foreach ($availableDates as $dateRange) {
            if ($dateRange['type'] !== 'reserved' && 
                $checkIn >= $dateRange['from'] && 
                $checkIn <= $dateRange['to']) {
                return true;
            }
        }
        
        return false;
    }
}
