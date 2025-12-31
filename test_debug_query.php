<?php
// Debug script to trace the exact query
require_once 'vendor/autoload.php';

use App\Models\Property;
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

echo "🔍 Debugging Hotel Price Filter Query\n";
echo "=====================================\n\n";

// Test the exact same logic as the API
$minPrice = 1000;
$maxPrice = 2000;

echo "Testing with: min_price=$minPrice, max_price=$maxPrice\n\n";

// Start with the base query (similar to API)
$query = Property::whereIn('propery_type', [0, 1])
    ->where(function ($q) {
        return $q->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->where('property_classification', 5);

echo "Base query count: " . $query->count() . " hotels\n\n";

// Apply price filter using whereHas (our new approach)
$filteredQuery = $query->whereHas('hotelRooms', function ($q) use ($minPrice, $maxPrice) {
    $q->whereBetween('price_per_night', [$minPrice, $maxPrice])
      ->where('status', 1);
});

echo "After price filter: " . $filteredQuery->count() . " hotels\n\n";

// Get the actual SQL query
$sql = $filteredQuery->toSql();
$bindings = $filteredQuery->getBindings();

echo "Generated SQL Query:\n";
echo $sql . "\n\n";

echo "Bindings:\n";
print_r($bindings);

echo "\n";

// Let's also check the raw hotel rooms data
echo "📊 Raw Hotel Rooms Data:\n";
echo "========================\n";

$hotelRooms = DB::table('hotel_rooms')
    ->whereBetween('price_per_night', [$minPrice, $maxPrice])
    ->where('status', 1)
    ->limit(10)
    ->get();

echo "Hotel rooms in price range: " . count($hotelRooms) . "\n";
foreach ($hotelRooms as $room) {
    echo "- Property {$room->property_id}: Room {$room->id} = {$room->price_per_night} EGP/night\n";
}

echo "\n✅ Debug complete!\n";