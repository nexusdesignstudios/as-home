<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Check Xiroses property (ID 387) availability data
echo "=== Xiroses Property (ID: 387) Availability Data ===\n";

// Get hotel rooms for this property
$rooms = DB::table('hotel_rooms')
    ->where('property_id', 387)
    ->select('id', 'availability_type', 'available_dates')
    ->get();

echo "\nHotel Rooms:\n";
foreach ($rooms as $room) {
    echo "Room {$room->id} - availability_type: {$room->availability_type}\n";
    
    // Check available_dates_hotel_rooms for this room
    $dates = DB::table('available_dates_hotel_rooms')
        ->where('hotel_room_id', $room->id)
        ->orderBy('from_date')
        ->get();
    
    echo "  Available dates from available_dates_hotel_rooms:\n";
    foreach ($dates as $date) {
        echo "    {$date->from_date} to {$date->to_date} - type: {$date->type} - price: {$date->price}\n";
    }
    
    // Also check legacy available_dates JSON if it exists
    if ($room->available_dates) {
        echo "  Legacy available_dates JSON:\n";
        $jsonDates = json_decode($room->available_dates, true);
        if (is_array($jsonDates)) {
            foreach ($jsonDates as $date) {
                echo "    " . ($date['from'] ?? 'N/A') . " to " . ($date['to'] ?? 'N/A') . " - type: " . ($date['type'] ?? 'N/A') . " - price: " . ($date['price'] ?? 'N/A') . "\n";
            }
        }
    }
    echo "\n";
}

echo "\n=== Summary ===\n";
echo "Expected closed periods based on data:\n";

// Get all unique date ranges for this property
$allDates = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->orderBy('from_date')
    ->get();

foreach ($allDates as $date) {
    echo "{$date->from_date} to {$date->to_date} (type: {$date->type})\n";
}
