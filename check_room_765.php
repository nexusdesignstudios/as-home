<?php
// Check room 765 details
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING ROOM 765 ===\n\n";

$roomId = 765;

// 1. Check if room 765 exists
echo "1. ROOM 765 EXISTENCE\n";
echo "==================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "✅ Room $roomId exists\n";
    echo "  - Room Type ID: {$room->room_type_id}\n";
    echo "  - Property ID: {$room->property_id}\n";
    echo "  - Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
    echo "  - Price: " . ($room->price_per_night ?? 'N/A') . "\n";
    
    if ($room->roomType) {
        echo "  - Room Type Name: {$room->roomType->name}\n";
    }
    
    if ($room->property) {
        echo "  - Property Name: {$room->property->title}\n";
    }
} else {
    echo "❌ Room $roomId does not exist!\n";
    echo "   This explains why it's not available\n";
}

// 2. Check all rooms in the property
echo "\n2. ALL ROOMS IN PROPERTY 357\n";
echo "==========================\n";

$allRooms = \App\Models\HotelRoom::where('property_id', 357)
    ->where('status', 1)
    ->orderBy('room_type_id')
    ->orderBy('id')
    ->get();

echo "Total rooms: {$allRooms->count()}\n\n";

$roomTypes = [];
foreach ($allRooms as $r) {
    $typeName = $r->roomType ? $r->roomType->name : 'Unknown';
    if (!isset($roomTypes[$typeName])) {
        $roomTypes[$typeName] = [];
    }
    $roomTypes[$typeName][] = $r;
}

foreach ($roomTypes as $typeName => $rooms) {
    echo "$typeName rooms:\n";
    foreach ($rooms as $r) {
        echo "  - Room {$r->id}: " . ($r->price_per_night ?? 'N/A') . "\n";
    }
    echo "\n";
}

// 3. Check availability for room 765 if it exists
if ($room) {
    echo "3. ROOM 765 AVAILABILITY\n";
    echo "======================\n";
    
    $checkInDate = '2026-01-13';
    $checkOutDate = '2026-01-14';
    
    $reservationService = app(\App\Services\ReservationService::class);
    $isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);
    
    echo "Room $roomId availability for $checkInDate to $checkOutDate: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    
    // Check why it's not available
    if (!$isAvailable) {
        $hasAvailability = \DB::table('available_dates_hotel_rooms')
            ->where('hotel_room_id', $roomId)
            ->where('from_date', '<=', $checkInDate)
            ->where('to_date', '>=', $checkOutDate)
            ->exists();
        
        $hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
        
        echo "  - Has availability data: " . ($hasAvailability ? "YES" : "NO") . "\n";
        echo "  - Has blocking reservations: " . ($hasOverlap ? "YES" : "NO") . "\n";
        
        if (!$hasAvailability) {
            echo "  → Needs availability data added\n";
            
            // Generate SQL to fix it
            $basePrice = $room->price_per_night ?? 1400;
            echo "\nSQL to fix Room $roomId:\n";
            echo "INSERT INTO available_dates_hotel_rooms (\n";
            echo "    property_id, hotel_room_id, from_date, to_date, price, type,\n";
            echo "    nonrefundable_percentage, created_at, updated_at\n";
            echo ") VALUES (\n";
            echo "    {$room->property_id}, {$roomId}, '$checkInDate', '$checkOutDate',\n";
            echo "    $basePrice, 'open', " . ($room->nonrefundable_percentage ?? 85) . ", NOW(), NOW()\n";
            echo ");\n";
        }
    }
}

echo "\n=== CHECK COMPLETE ===\n";
