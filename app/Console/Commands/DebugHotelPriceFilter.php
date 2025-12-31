<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugHotelPriceFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:hotel-price-filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug hotel price filtering logic';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("🔍 Debugging Hotel Price Filter Query");
        $this->info("=====================================");

        // Test the exact same logic as the API
        $minPrice = 1000;
        $maxPrice = 2000;

        $this->info("Testing with: min_price=$minPrice, max_price=$maxPrice");
        $this->info("");

        // Start with the base query (similar to API)
        $query = Property::whereIn('propery_type', [0, 1])
            ->where(function ($q) {
                return $q->where(['status' => 1, 'request_status' => 'approved']);
            })
            ->where('property_classification', 5);

        $this->info("Base query count: " . $query->count() . " hotels");
        $this->info("");

        // Apply price filter using whereHas (our new approach)
        $filteredQuery = $query->whereHas('hotelRooms', function ($q) use ($minPrice, $maxPrice) {
            $q->whereBetween('price_per_night', [$minPrice, $maxPrice])
              ->where('status', 1);
        });

        $this->info("After price filter: " . $filteredQuery->count() . " hotels");
        $this->info("");

        // Get the actual SQL query
        $sql = $filteredQuery->toSql();
        $bindings = $filteredQuery->getBindings();

        $this->info("Generated SQL Query:");
        $this->info($sql);
        $this->info("");

        $this->info("Bindings:");
        print_r($bindings);
        $this->info("");

        // Let's also check the raw hotel rooms data
        $this->info("📊 Raw Hotel Rooms Data:");
        $this->info("========================");

        $hotelRooms = DB::table('hotel_rooms')
            ->whereBetween('price_per_night', [$minPrice, $maxPrice])
            ->where('status', 1)
            ->limit(10)
            ->get();

        $this->info("Hotel rooms in price range: " . count($hotelRooms));
        foreach ($hotelRooms as $room) {
            $this->info("- Property {$room->property_id}: Room {$room->id} = {$room->price_per_night} EGP/night");
        }

        $this->info("");
        $this->info("✅ Debug complete!");

        return Command::SUCCESS;
    }
}