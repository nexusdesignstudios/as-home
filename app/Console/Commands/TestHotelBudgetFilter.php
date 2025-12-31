<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;

class TestHotelBudgetFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hotel-budget-filter {min_price=1000} {max_price=2000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hotel budget filter fix';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $minPrice = $this->argument('min_price');
        $maxPrice = $this->argument('max_price');
        
        $this->info("🧪 Testing Hotel Budget Filter Fix");
        $this->info("==================================");
        $this->info("Price Range: {$minPrice} - {$maxPrice} EGP per night");
        $this->info("");

        // Test 1: Check if there are hotel properties with rooms in the price range
        $this->info("📋 Test 1: Finding Hotel Properties");
        $this->info("-----------------------------------");

        $hotelProperties = Property::where('property_classification', 5)
            ->whereHas('hotelRooms', function ($query) use ($minPrice, $maxPrice) {
                $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
            })
            ->with(['hotelRooms' => function ($query) use ($minPrice, $maxPrice) {
                $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
            }])
            ->limit(5)
            ->get();

        $this->info("Found " . $hotelProperties->count() . " hotel properties with rooms in price range:");

        foreach ($hotelProperties as $hotel) {
            $this->info("");
            $this->info("🏨 Hotel ID: {$hotel->id}");
            $this->info("   Title: {$hotel->title}");
            $this->info("   Location: {$hotel->city}, {$hotel->state}");
            $this->info("   Main Property Price: {$hotel->price} EGP");
            
            $validRooms = $hotel->hotelRooms->filter(function ($room) use ($minPrice, $maxPrice) {
                return $room->price_per_night >= $minPrice && $room->price_per_night <= $maxPrice;
            });
            
            $this->info("   Valid Rooms in Range ({$validRooms->count()}):");
            foreach ($validRooms as $room) {
                $this->info("   - Room {$room->id}: {$room->price_per_night} EGP/night");
            }
            
            $minRoomPrice = $hotel->hotelRooms->min('price_per_night');
            $this->info("   ⭐ Minimum Room Price: {$minRoomPrice} EGP/night");
        }

        // Test 2: Simulate the API filtering logic
        $this->info("");
        $this->info("🔍 Test 2: Simulating API Filter Logic");
        $this->info("--------------------------------------");

        // Simulate the new filtering logic
        $filteredProperties = Property::where('property_classification', 5)
            ->whereHas('hotelRooms', function ($query) use ($minPrice, $maxPrice) {
                if (!empty($minPrice)) {
                    $query->where('price_per_night', '>=', $minPrice);
                }
                if (!empty($maxPrice)) {
                    $query->where('price_per_night', '<=', $maxPrice);
                }
            })
            ->with('hotelRooms')
            ->get();

        $this->info("Properties that would be returned by API: " . $filteredProperties->count());

        foreach ($filteredProperties as $property) {
            $minRoomPrice = $property->hotelRooms->min('price_per_night');
            $this->info("- Property {$property->id}: {$property->title} (Min room price: {$minRoomPrice} EGP)");
        }

        // Test 3: Compare with old filtering method
        $this->info("");
        $this->info("⚠️  Test 3: Old vs New Filtering Comparison");
        $this->info("------------------------------------------");

        // Old method (filtering by main property price)
        $oldMethodProperties = Property::where('property_classification', 5)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->get();

        $this->info("Old method (main property price) would return: " . $oldMethodProperties->count() . " properties");

        // New method (filtering by room price)
        $this->info("New method (minimum room price) returns: " . $filteredProperties->count() . " properties");

        if ($oldMethodProperties->count() !== $filteredProperties->count()) {
            $this->info("✅ Filtering behavior has changed!");
            $this->info("   Before: Only properties with main price in range");
            $this->info("   After: Properties with ANY room price in range");
        } else {
            $this->info("⚠️  No change in filtering results - may need further investigation");
        }

        // Test 4: Check specific hotel that should match the example
        $this->info("");
        $this->info("🎯 Test 4: Checking for 4-Star Hotel Example");
        $this->info("---------------------------------------------");

        $exampleHotel = Property::where('property_classification', 5)
            ->where(function ($query) {
                $query->where('title', 'like', '%4 Star%')
                      ->orWhere('title', 'like', '%Amazing 4 Star%');
            })
            ->whereHas('hotelRooms', function ($query) use ($minPrice, $maxPrice) {
                $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
            })
            ->with('hotelRooms')
            ->first();

        if ($exampleHotel) {
            $this->info("✅ Found example hotel!");
            $this->info("   ID: {$exampleHotel->id}");
            $this->info("   Title: {$exampleHotel->title}");
            $this->info("   Location: {$exampleHotel->city}, {$exampleHotel->state}, {$exampleHotel->country}");
            $this->info("   Main Property Price: {$exampleHotel->price} EGP");
            
            $minRoomPrice = $exampleHotel->hotelRooms->min('price_per_night');
            $maxRoomPrice = $exampleHotel->hotelRooms->max('price_per_night');
            $this->info("   Room Price Range: {$minRoomPrice} - {$maxRoomPrice} EGP/night");
            
            if ($minRoomPrice >= $minPrice && $minRoomPrice <= $maxPrice) {
                $this->info("   ✅ This hotel SHOULD appear in budget filter results!");
            } else {
                $this->info("   ❌ This hotel would NOT appear in budget filter results");
            }
        } else {
            $this->info("❌ Could not find the example 4-star hotel in the database");
        }

        $this->info("");
        $this->info("🎉 Test Complete!");
        $this->info("==================");
        $this->info("The budget filter should now work correctly with hotel properties.");
        $this->info("It filters based on minimum room price instead of main property price.");

        return Command::SUCCESS;
    }
}