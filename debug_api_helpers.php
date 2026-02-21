<?php

// Load Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Property;
use App\Models\HotelRoom;

// 1. Find Malorca Hotel
echo "Searching for 'Malorca Hotel'...\n";
$property = Property::where('title', 'LIKE', '%Malorca%')->first();

if (!$property) {
    echo "Property not found.\n";
    exit(1);
}

echo "Found Property: " . $property->title . " (ID: " . $property->id . ")\n";

// 2. Check direct access to hotel_rooms
$rooms = $property->hotel_rooms; // This uses the accessor
if ($rooms) {
    echo "Accessor returned " . $rooms->count() . " rooms.\n";
    foreach ($rooms as $room) {
        echo "Room ID: " . $room->id . "\n";
        echo "Guest Pricing Rules (Direct): " . json_encode($room->guest_pricing_rules) . "\n";
    }
} else {
    echo "Accessor returned null or empty.\n";
}

// 3. Simulate get_property_details
// The helper expects a collection or array of properties
$properties = collect([$property]);

// We need to make sure the helper is loaded. It's usually autoloaded by composer or in AppServiceProvider.
// If it's a file in app/Helpers, we might need to require it if not autoloaded.
if (!function_exists('get_property_details')) {
    require_once __DIR__ . '/app/Helpers/custom_helper.php';
}

echo "\n--- Calling get_property_details ---\n";
$details = get_property_details($properties);

if (!empty($details) && isset($details[0])) {
    $p = $details[0];
    if (isset($p['hotel_rooms'])) {
        echo "API Result - Hotel Rooms Count: " . count($p['hotel_rooms']) . "\n";
        foreach ($p['hotel_rooms'] as $room) {
            echo "Room ID: " . $room['id'] . "\n";
            echo "Guest Pricing Rules (API): " . json_encode($room['guest_pricing_rules'] ?? 'MISSING') . "\n";
            echo "Keys present: " . implode(', ', array_keys($room)) . "\n";
        }
    } else {
        echo "API Result - No hotel_rooms key found.\n";
    }
} else {
    echo "API Result - Empty or invalid.\n";
}
