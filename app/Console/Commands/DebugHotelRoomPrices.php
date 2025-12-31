<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugHotelRoomPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:hotel-room-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug hotel room price ranges';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("🔍 Debugging Hotel Room Price Ranges");
        $this->info("====================================");

        // Get all hotel rooms with their prices
        $hotelRooms = DB::table('hotel_rooms')
            ->where('status', 1)
            ->orderBy('price_per_night', 'asc')
            ->get();

        $this->info("Total active hotel rooms: " . count($hotelRooms));
        $this->info("");

        // Group by price ranges
        $priceRanges = [
            '0-500' => 0,
            '500-1000' => 0,
            '1000-1500' => 0,
            '1500-2000' => 0,
            '2000-2500' => 0,
            '2500-3000' => 0,
            '3000+' => 0
        ];

        foreach ($hotelRooms as $room) {
            $price = $room->price_per_night;
            if ($price < 500) {
                $priceRanges['0-500']++;
            } elseif ($price < 1000) {
                $priceRanges['500-1000']++;
            } elseif ($price < 1500) {
                $priceRanges['1000-1500']++;
            } elseif ($price < 2000) {
                $priceRanges['1500-2000']++;
            } elseif ($price < 2500) {
                $priceRanges['2000-2500']++;
            } elseif ($price < 3000) {
                $priceRanges['2500-3000']++;
            } else {
                $priceRanges['3000+']++;
            }
        }

        $this->info("Price Range Distribution:");
        foreach ($priceRanges as $range => $count) {
            $this->info("  $range EGP: $count rooms");
        }

        $this->info("");

        // Show some sample rooms in the 1000-2000 range
        $this->info("Sample rooms in 1000-2000 EGP range:");
        $sampleRooms = DB::table('hotel_rooms')
            ->where('status', 1)
            ->whereBetween('price_per_night', [1000, 2000])
            ->limit(10)
            ->get();

        foreach ($sampleRooms as $room) {
            $this->info("  Property {$room->property_id}: Room {$room->id} = {$room->price_per_night} EGP/night");
        }

        $this->info("");

        // Check what our original test command found
        $this->info("Checking original test command logic...");
        $hotels = Property::where('property_classification', 5)
            ->where('status', 1)
            ->where('request_status', 'approved')
            ->whereIn('propery_type', [0, 1])
            ->with('hotelRooms')
            ->get();

        $this->info("Total hotels: " . $hotels->count());

        $validHotels = [];
        foreach ($hotels as $hotel) {
            $hasValidRoom = false;
            foreach ($hotel->hotelRooms as $room) {
                if ($room->price_per_night >= 1000 && $room->price_per_night <= 2000 && $room->status == 1) {
                    $hasValidRoom = true;
                    break;
                }
            }
            if ($hasValidRoom) {
                $validHotels[] = $hotel;
            }
        }

        $this->info("Hotels with rooms in 1000-2000 range: " . count($validHotels));

        foreach ($validHotels as $hotel) {
            $minPrice = $hotel->hotelRooms->where('status', 1)->min('price_per_night');
            $this->info("  Property {$hotel->id}: {$hotel->title} (Min room: {$minPrice} EGP)");
        }

        $this->info("");
        $this->info("✅ Debug complete!");

        return Command::SUCCESS;
    }
}