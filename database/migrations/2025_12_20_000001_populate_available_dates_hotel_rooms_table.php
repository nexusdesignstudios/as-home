<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only migrate rooms with availability_type = 1 (available_days) or NULL
        // Skip busy_days (availability_type = 2) as per requirements
        // Use DB::table to get raw JSON values (bypassing Laravel's JSON casting)
        $hotelRooms = DB::table('hotel_rooms')
            ->where(function ($query) {
                $query->whereNull('availability_type')
                    ->orWhere('availability_type', 1);
            })
            ->whereNotNull('available_dates')
            ->where('available_dates', '!=', '')
            ->where('available_dates', '!=', '[]')
            ->whereRaw("JSON_VALID(available_dates) = 1")
            ->get();

        $insertData = [];

        foreach ($hotelRooms as $room) {
            // Get raw JSON string from database
            $availableDatesJson = $room->available_dates;
            
            // Decode JSON string to array
            $availableDates = json_decode($availableDatesJson, true);
            
            // If decoding failed or result is not an array or is empty, skip
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($availableDates) || empty($availableDates)) {
                continue;
            }

            foreach ($availableDates as $dateRange) {
                // Skip if required fields are missing
                if (!isset($dateRange['from']) || !isset($dateRange['to'])) {
                    continue;
                }

                // Skip reserved dates
                if (isset($dateRange['type']) && $dateRange['type'] === 'reserved') {
                    continue;
                }

                $insertData[] = [
                    'property_id' => $room->property_id,
                    'hotel_room_id' => $room->id,
                    'from_date' => $dateRange['from'],
                    'to_date' => $dateRange['to'],
                    'price' => isset($dateRange['price']) && $dateRange['price'] !== '' ? (float)$dateRange['price'] : null,
                    'type' => $dateRange['type'] ?? 'open',
                    'nonrefundable_percentage' => isset($dateRange['nonrefundable_percentage']) ? (float)$dateRange['nonrefundable_percentage'] : (isset($room->nonrefundable_percentage) ? (float)$room->nonrefundable_percentage : null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in batches for better performance
        if (!empty($insertData)) {
            $chunks = array_chunk($insertData, 500);
            foreach ($chunks as $chunk) {
                DB::table('available_dates_hotel_rooms')->insert($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the table (optional - you may want to keep the data)
        DB::table('available_dates_hotel_rooms')->truncate();
    }
};

