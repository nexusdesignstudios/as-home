<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Hotel Rooms for Property 387 (Sharm El-Sheikh 5 stars Xiroses) ===\n";

// Get property 387
$property = \App\Models\Property::find(387);

if (!$property) {
    echo "Property 387 not found!\n";
    exit;
}

echo "Hotel Found: " . $property->title . " (ID: " . $property->id . ")\n";
echo "Property Classification: " . $property->property_classification . "\n";

// Get hotel rooms for this property
$hotelRooms = \App\Models\HotelRoom::where('property_id', 387)->get();

echo "\nHotel Rooms for Property 387:\n";
foreach ($hotelRooms as $room) {
    echo "  Room ID: " . $room->id . "\n";
    echo "  Room Number: " . ($room->room_number ?? 'N/A') . "\n";
    echo "  Property ID: " . $room->property_id . "\n";
    echo "  Room Type ID: " . $room->room_type_id . "\n";
    echo "  Availability Type: " . ($room->availability_type ?? 'N/A') . "\n";
    $availableDates = $room->available_dates;
    if (is_array($availableDates)) {
        echo "  Available Dates: " . json_encode($availableDates) . "\n";
    } else {
        echo "  Available Dates: " . ($availableDates ?? 'NULL') . "\n";
    }
    echo "  Price per Night: " . $room->price_per_night . "\n";
    echo "  Refund Policy: " . ($room->refund_policy ?? 'N/A') . "\n";
    echo "  Created At: " . $room->created_at . "\n";
    echo "  Updated At: " . $room->updated_at . "\n";
    echo "  ---\n";
}

// Check available_dates table for these rooms
echo "\n=== Checking Available Dates Table ===\n";
foreach ($hotelRooms as $room) {
    $availableDates = \DB::table('available_dates')
        ->where('room_id', $room->id)
        ->get();
    
    echo "Room " . $room->id . " Available Dates:\n";
    if ($availableDates->count() > 0) {
        foreach ($availableDates as $date) {
            echo "  - From: " . $date->from . " To: " . $date->to . " Type: " . $date->type . "\n";
        }
    } else {
        echo "  - No available dates found\n";
    }
    echo "  ---\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "Total Rooms: " . $hotelRooms->count() . "\n";

$roomsWithAvailabilityType = $hotelRooms->whereNotNull('availability_type')->count();
$roomsWithAvailableDates = $hotelRooms->whereNotNull('available_dates')->count();

echo "Rooms with Availability Type: " . $roomsWithAvailabilityType . "\n";
echo "Rooms with Available Dates: " . $roomsWithAvailableDates . "\n";

// Check availability types
$closedPeriodRooms = $hotelRooms->whereIn('availability_type', ['2', 'busy_days']);
echo "Closed Period Rooms: " . $closedPeriodRooms->count() . "\n";

foreach ($closedPeriodRooms as $room) {
    echo "  - Room " . $room->id . ": " . $room->availability_type . "\n";
}

echo "\n=== ANALYSIS ===\n";
if ($closedPeriodRooms->count() > 0) {
    $roomsWithEmptyDates = $closedPeriodRooms->filter(function($room) {
        return empty($room->available_dates) || $room->available_dates === '[]';
    });
    
    echo "Closed Period Rooms with Empty Available Dates: " . $roomsWithEmptyDates->count() . "\n";
    
    if ($roomsWithEmptyDates->count() > 0) {
        echo "⚠️  ISSUE: " . $roomsWithEmptyDates->count() . " rooms have Closed Period but no available_dates\n";
        echo "   This means ALL dates should be AVAILABLE for these rooms\n";
        echo "   If calendar shows unavailable dates, the issue is in frontend logic\n";
    }
}
