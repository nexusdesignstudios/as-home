<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Property;
use Illuminate\Support\Facades\DB;

// Test a simple query to see if it works
echo "🧪 Testing Hotel Price Filter Query...\n\n";

try {
    $hotels = Property::where('property_classification', 5)
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('hotel_rooms')
                ->whereColumn('hotel_rooms.property_id', 'propertys.id')
                ->where('hotel_rooms.price_per_night', '>=', 1000)
                ->where('hotel_rooms.price_per_night', '<=', 2000)
                ->where('hotel_rooms.status', 1);
        })
        ->limit(5)
        ->get();

    echo '✅ Found ' . $hotels->count() . ' hotels with rooms in price range 1000-2000' . PHP_EOL;
    foreach ($hotels as $hotel) {
        echo 'Hotel: ' . $hotel->title . ' (ID: ' . $hotel->id . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . $e->getTraceAsString() . PHP_EOL;
}