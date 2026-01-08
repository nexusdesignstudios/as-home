<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\HotelRoom;

class SyncHotelRoomAvailableDates extends Command
{
    protected $signature = "sync:hotel-room-available-dates {--dry-run : Show what would be synced without making changes}";
    protected $description = "Manually sync hotel_rooms.available_dates JSON to available_dates_hotel_rooms table";

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info("🔧 Syncing hotel room available dates");
        
        if ($dryRun) {
            $this->info("🧪 DRY RUN MODE - No changes will be made");
        }

        // Get all hotel rooms with available_dates
        $hotelRooms = HotelRoom::whereNotNull('available_dates')
            ->where('available_dates', '!=', '')
            ->where('available_dates', '!=', '[]')
            ->where(function ($query) {
                $query->whereNull('availability_type')
                    ->orWhere('availability_type', 1)
                    ->orWhere('availability_type', 2);
            })
            ->get();

        $totalRooms = $hotelRooms->count();
        $syncedRooms = 0;
        $totalPeriods = 0;

        foreach ($hotelRooms as $hotelRoom) {
            $this->info("Processing room {$hotelRoom->room_number} (ID: {$hotelRoom->id})");
            
            // Delete existing records for this room
            if (!$dryRun) {
                DB::table('available_dates_hotel_rooms')
                    ->where('hotel_room_id', $hotelRoom->id)
                    ->delete();
            }

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
                    // Priority: Busy Days (availability_type = 2) overrides everything
                    if ($hotelRoom->availability_type == 2) {
                        $periodType = 'closed'; // Busy Days should create closed periods
                    } elseif (isset($dateRange['type'])) {
                        $periodType = $dateRange['type']; // Use explicit type if provided
                    } else {
                        $periodType = 'open'; // Default
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

                $periodsCount = count($insertData);
                $totalPeriods += $periodsCount;
                
                if (!$dryRun && !empty($insertData)) {
                    $chunks = array_chunk($insertData, 500);
                    foreach ($chunks as $chunk) {
                        DB::table('available_dates_hotel_rooms')->insert($chunk);
                    }
                }
                
                $this->info("   Synced {$periodsCount} periods");
                $syncedRooms++;
            } elseif ($hotelRoom->availability_type == 2) {
                // Handle rooms with closed periods (availability_type = 2) that have empty available_dates
                // These rooms should still be processed to maintain their closed status
                $this->info("   Room has closed periods but no available_dates - keeping closed status");
                $syncedRooms++;
            } else {
                $this->info("   No valid periods found");
            }
        }

        $this->info("\n✅ Sync complete!");
        $this->info("Processed {$totalRooms} rooms");
        $this->info("Synced {$syncedRooms} rooms");
        $this->info("Total periods synced: {$totalPeriods}");
        
        return 0;
    }
}