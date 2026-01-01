<?php

/**
 * Debug Green Hotel 2 confirmed flexible reservations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Green Hotel 2 Confirmed Flexible Reservations Debug\n";
echo "========================================\n\n";

// Find Green Hotel 2
$property = Property::where('title', 'like', '%Green hotel 2 testing room only%')->first();

if (!$property) {
    echo "❌ Green Hotel 2 not found\n";
    exit;
}

echo "Property ID: {$property->id}\n";
echo "Title: {$property->title}\n\n";

// Get confirmed flexible reservations for Green Hotel 2
$reservations = Reservation::where('property_id', $property->id)
    ->where('status', 'confirmed')
    ->where(function($query) {
        $query->where('payment_method', 'cash')
              ->orWhereNull('payment_method')
              ->orWhere('payment_method', '');
    })
    ->orderBy('check_in_date', 'asc')
    ->get();

echo "Confirmed Flexible Reservations: {$reservations->count()}\n\n";

foreach ($reservations as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Status: {$reservation->status}\n";
    echo "  Payment method: " . ($reservation->payment_method ?: 'cash') . "\n";
    echo "  Payment status: " . ($reservation->payment_status ?: 'unpaid') . "\n";
    echo "  Reservable type: {$reservation->reservable_type}\n";
    echo "  Reservable ID: {$reservation->reservable_id}\n";
    
    // Get room details if it's a room type reservation
    if ($reservation->reservable_type === 'room_type') {
        $roomType = HotelRoomType::find($reservation->reservable_id);
        echo "  Room type: " . ($roomType ? $roomType->name : 'N/A') . "\n";
    } elseif ($reservation->reservable_type === 'hotel_room') {
        $hotelRoom = HotelRoom::find($reservation->reservable_id);
        echo "  Hotel room: " . ($hotelRoom ? $hotelRoom->room_number : 'N/A') . "\n";
    }
    
    echo "\n";
}

// Analyze date blocking
echo "=== DATE BLOCKING ANALYSIS ===\n\n";

$allDates = [];
foreach ($reservations as $reservation) {
    $checkIn = new DateTime($reservation->check_in_date);
    $checkOut = new DateTime($reservation->check_out_date);
    
    echo "Reservation #{$reservation->id} blocks:\n";
    
    // Include check-in date, exclude check-out date
    $currentDate = clone $checkIn;
    while ($currentDate < $checkOut) {
        $dateStr = $currentDate->format('Y-m-d');
        echo "  - {$dateStr}\n";
        $allDates[$dateStr] = ($allDates[$dateStr] ?? 0) + 1;
        $currentDate->modify('+1 day');
    }
    echo "\n";
}

echo "=== DATE BLOCKING SUMMARY ===\n";
ksort($allDates);
foreach ($allDates as $date => $count) {
    echo "{$date}: {$count} reservation(s) blocking\n";
}

// Check total rooms available for this property
$totalRooms = HotelRoom::where('property_id', $property->id)->count();
echo "\n=== PROPERTY ROOMS ===\n";
echo "Total rooms in property: {$totalRooms}\n";

// Get rooms with their types
$rooms = HotelRoom::where('property_id', $property->id)
    ->with('room_type')
    ->get();

echo "Room details:\n";
foreach ($rooms as $room) {
    echo "  Room #{$room->id}: {$room->room_number} (Type: " . ($room->room_type ? $room->room_type->name : 'N/A') . ")\n";
}

echo "\n========================================\n";
echo "Expected behavior: These confirmed flexible reservations should block availability\n";
echo "If calendar shows '8 open', the blocking logic is not working correctly\n";
echo "========================================\n";