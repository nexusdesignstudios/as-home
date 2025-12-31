<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugHotelRoomStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:hotel-room-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug hotel room status distribution';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("🔍 Debugging Hotel Room Status Distribution");
        $this->info("==========================================");

        // Get all hotel rooms regardless of status
        $allRooms = DB::table('hotel_rooms')->get();
        $this->info("Total hotel rooms (all status): " . count($allRooms));

        $activeRooms = DB::table('hotel_rooms')->where('status', 1)->get();
        $this->info("Active hotel rooms (status=1): " . count($activeRooms));

        $inactiveRooms = DB::table('hotel_rooms')->where('status', '!=', 1)->get();
        $this->info("Inactive hotel rooms (status!=1): " . count($inactiveRooms));

        $this->info("");

        // Show some sample rooms
        if (count($allRooms) > 0) {
            $this->info("Sample hotel rooms:");
            foreach ($allRooms->take(10) as $room) {
                $this->info("  Property {$room->property_id}: Room {$room->id} = {$room->price_per_night} EGP/night (status: {$room->status})");
            }
        }

        $this->info("");

        // Now test our original test command logic
        $this->info("Testing original test command logic (without status filter):");
        
        $hotelsWithoutStatus = Property::where('property_classification', 5)
            ->whereHas('hotelRooms', function ($query) {
                $query->whereBetween('price_per_night', [1000, 2000]);
                // Note: No status filter here!
            })
            ->with(['hotelRooms' => function ($query) {
                $query->whereBetween('price_per_night', [1000, 2000]);
            }])
            ->get();

        $this->info("Hotels found (without status filter): " . $hotelsWithoutStatus->count());

        foreach ($hotelsWithoutStatus as $hotel) {
            $validRooms = $hotel->hotelRooms->filter(function ($room) {
                return $room->price_per_night >= 1000 && $room->price_per_night <= 2000;
            });
            $this->info("  Property {$hotel->id}: {$hotel->title} ({$validRooms->count()} valid rooms)");
        }

        $this->info("");
        $this->info("✅ Debug complete!");

        return Command::SUCCESS;
    }
}