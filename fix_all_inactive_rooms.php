<?php
// Fix all inactive rooms and add availability data
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ALL INACTIVE ROOMS ===\n\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// 1. Find all rooms (including inactive)
echo "1. ALL ROOMS (ACTIVE & INACTIVE)\n";
echo "===============================\n";

$allRooms = \App\Models\HotelRoom::where('property_id', 357)
    ->orderBy('room_type_id')
    ->orderBy('status', 'desc')
    ->orderBy('id')
    ->get();

echo "Total rooms: {$allRooms->count()}\n\n";

$activeRooms = [];
$inactiveRooms = [];

foreach ($allRooms as $room) {
    $status = $room->status ? 'Active' : 'Inactive';
    $typeName = $room->roomType ? $room->roomType->name : 'Unknown';
    
    echo "Room {$room->id}: $typeName - $status\n";
    
    if ($room->status) {
        $activeRooms[] = $room;
    } else {
        $inactiveRooms[] = $room;
    }
}

echo "\nActive rooms: " . count($activeRooms) . "\n";
echo "Inactive rooms: " . count($inactiveRooms) . "\n";

// 2. Check availability for all rooms
echo "\n2. AVAILABILITY CHECK FOR ALL ROOMS\n";
echo "=================================\n";

$reservationService = app(\App\Services\ReservationService::class);
$availableRooms = [];

foreach ($allRooms as $room) {
    $isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $room->id, $checkInDate, $checkOutDate);
    
    $status = $room->status ? 'Active' : 'Inactive';
    echo "Room {$room->id} ($status): " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    
    if ($isAvailable) {
        $availableRooms[] = $room;
    }
}

// 3. Generate SQL fixes
echo "\n3. SQL FIXES FOR LIVE SERVER\n";
echo "==========================\n";

echo "-- SQL to activate inactive rooms and add availability\n";
echo "-- Run this on your live server database\n\n";

foreach ($inactiveRooms as $room) {
    echo "-- Activate Room {$room->id}\n";
    echo "UPDATE hotel_rooms SET status = 1 WHERE id = {$room->id};\n\n";
    
    echo "-- Add availability for Room {$room->id}\n";
    $basePrice = $room->price_per_night ?? 1400;
    echo "INSERT INTO available_dates_hotel_rooms (\n";
    echo "    property_id, hotel_room_id, from_date, to_date, price, type,\n";
    echo "    nonrefundable_percentage, created_at, updated_at\n";
    echo ") VALUES (\n";
    echo "    {$room->property_id}, {$room->id}, '$checkInDate', '$checkOutDate',\n";
    echo "    $basePrice, 'open', " . ($room->nonrefundable_percentage ?? 85) . ", NOW(), NOW()\n";
    echo ");\n\n";
}

// 4. Summary
echo "\n4. SUMMARY\n";
echo "=========\n";

echo "Total rooms: {$allRooms->count()}\n";
echo "Active rooms: " . count($activeRooms) . "\n";
echo "Inactive rooms: " . count($inactiveRooms) . "\n";
echo "Available rooms: " . count($availableRooms) . "\n";

if (count($inactiveRooms) > 0) {
    echo "\n⚠️  INACTIVE ROOMS FOUND:\n";
    foreach ($inactiveRooms as $room) {
        echo "  - Room {$room->id}: " . ($room->roomType ? $room->roomType->name : 'Unknown') . "\n";
    }
    
    echo "\n🔧 TO FIX:\n";
    echo "1. Activate inactive rooms (UPDATE hotel_rooms SET status = 1 WHERE id = [room_id])\n";
    echo "2. Add availability data for all rooms\n";
    echo "3. Clear cache\n";
    echo "4. Test booking\n";
} else {
    echo "\n✅ All rooms are active\n";
}

echo "\n=== FIX COMPLETE ===\n";
