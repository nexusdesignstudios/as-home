<?php

namespace App\Observers;

use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

class HotelRoomObserver
{
    /**
     * Handle the HotelRoom "created" event.
     */
    public function created(HotelRoom $hotelRoom): void
    {
        $this->syncAvailableDates($hotelRoom);
    }

    /**
     * Handle the HotelRoom "updated" event.
     */
    public function updated(HotelRoom $hotelRoom): void
    {
        $this->syncAvailableDates($hotelRoom);
    }

    /**
     * Sync available_dates JSON to available_dates_hotel_rooms table
     */
    protected function syncAvailableDates(HotelRoom $hotelRoom): void
    {
        // Sync for availability_type = 1 (available_days), 2 (busy_days), or NULL
        $availabilityTypeRaw = $hotelRoom->getRawOriginal('availability_type');
        if (isset($availabilityTypeRaw) && !in_array((int) $availabilityTypeRaw, [1, 2], true)) {
            return;
        }

        // Delete existing records for this room
        DB::table('available_dates_hotel_rooms')
            ->where('hotel_room_id', $hotelRoom->id)
            ->delete();

        // Process available_dates (already an array due to model casting)
        $availableDates = $hotelRoom->available_dates;
        
        if (is_array($availableDates) && !empty($availableDates)) {
            $insertData = [];
            
            foreach ($availableDates as $dateRange) {
                    // Skip if required fields are missing
                    if (!isset($dateRange['from']) || !isset($dateRange['to'])) {
                        continue;
                    }

                    // Skip reserved dates
                    if (isset($dateRange['type']) && $dateRange['type'] === 'reserved') {
                        continue;
                    }

                    // Determine period type based on hotel room availability_type
                    $periodType = 'open'; // Default
                    if ((int) $availabilityTypeRaw === 2) {
                        $periodType = 'dead'; // Busy Days should create dead periods
                    } elseif (isset($dateRange['type'])) {
                        $periodType = $dateRange['type']; // Use explicit type if provided
                    }

                    $insertData[] = [
                        'property_id' => $hotelRoom->property_id,
                        'hotel_room_id' => $hotelRoom->id,
                        'from_date' => $dateRange['from'],
                        'to_date' => $dateRange['to'],
                        'price' => isset($dateRange['price']) && $dateRange['price'] !== '' ? (float)$dateRange['price'] : null,
                        'type' => $periodType,
                        'nonrefundable_percentage' => isset($dateRange['nonrefundable_percentage']) ? (float)$dateRange['nonrefundable_percentage'] : (isset($hotelRoom->nonrefundable_percentage) ? (float)$hotelRoom->nonrefundable_percentage : null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

            // Insert in batches for better performance
            if (!empty($insertData)) {
                $chunks = array_chunk($insertData, 500);
                foreach ($chunks as $chunk) {
                    DB::table('available_dates_hotel_rooms')->insert($chunk);
                }
            }
        }
    }
}