<?php
// Check all Superior rooms and fix availability
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING ALL SUPERIOR ROOMS ===\n\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// 1. Get all Superior rooms (room_type_id = 5)
echo "1. ALL SUPERIOR ROOMS\n";
echo "===================\n";

$superiorRooms = \App\Models\HotelRoom::where('room_type_id', 5)
    ->where('property_id', 357)
    ->where('status', 1)
    ->orderBy('id')
    ->get();

echo "Found {$superiorRooms->count()} Superior rooms:\n";
foreach ($superiorRooms as $room) {
    $roomTypeName = $room->roomType ? $room->roomType->name : 'Unknown';
    echo "  - Room {$room->id}: $roomTypeName (Price: " . ($room->price_per_night ?? 'N/A') . ")\n";
}

// 2. Check availability for each room
echo "\n2. AVAILABILITY STATUS\n";
echo "====================\n";

$reservationService = app(\App\Services\ReservationService::class);
$availableRooms = [];
$unavailableRooms = [];

foreach ($superiorRooms as $room) {
    $isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $room->id, $checkInDate, $checkOutDate);
    
    echo "Room {$room->id}: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    
    if ($isAvailable) {
        $availableRooms[] = $room;
    } else {
        $unavailableRooms[] = $room;
        
        // Check why it's not available
        $hasAvailability = \DB::table('available_dates_hotel_rooms')
            ->where('hotel_room_id', $room->id)
            ->where('from_date', '<=', $checkInDate)
            ->where('to_date', '>=', $checkOutDate)
            ->exists();
        
        $hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $room->id, 'App\\Models\\HotelRoom');
        
        echo "  - Has availability data: " . ($hasAvailability ? "YES" : "NO") . "\n";
        echo "  - Has blocking reservations: " . ($hasOverlap ? "YES" : "NO") . "\n";
        
        if (!$hasAvailability) {
            echo "  → NEEDS AVAILABILITY DATA\n";
        }
    }
}

// 3. Fix rooms without availability data
echo "\n3. FIXING ROOMS WITHOUT AVAILABILITY\n";
echo "===================================\n";

foreach ($unavailableRooms as $room) {
    $hasAvailability = \DB::table('available_dates_hotel_rooms')
        ->where('hotel_room_id', $room->id)
        ->where('from_date', '<=', $checkInDate)
        ->where('to_date', '>=', $checkOutDate)
        ->exists();
    
    if (!$hasAvailability) {
        // Add availability for this room
        $basePrice = $room->price_per_night ?? 1400;
        
        try {
            \DB::table('available_dates_hotel_rooms')->insert([
                'property_id' => $room->property_id,
                'hotel_room_id' => $room->id,
                'from_date' => $checkInDate,
                'to_date' => $checkOutDate,
                'price' => $basePrice,
                'type' => 'open',
                'nonrefundable_percentage' => $room->nonrefundable_percentage ?? 85,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            echo "✅ Added availability for Room {$room->id} (Price: $basePrice)\n";
        } catch (\Exception $e) {
            echo "❌ Error adding availability for Room {$room->id}: " . $e->getMessage() . "\n";
        }
    }
}

// 4. Final check
echo "\n4. FINAL AVAILABILITY CHECK\n";
echo "==========================\n";

$finalAvailable = 0;
foreach ($superiorRooms as $room) {
    $isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $room->id, $checkInDate, $checkOutDate);
    
    echo "Room {$room->id}: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    
    if ($isAvailable) {
        $finalAvailable++;
    }
}

// 5. Summary
echo "\n5. SUMMARY\n";
echo "=========\n";

echo "Total Superior rooms: {$superiorRooms->count()}\n";
echo "Available rooms: $finalAvailable\n";
echo "Unavailable rooms: " . ($superiorRooms->count() - $finalAvailable) . "\n";

if ($finalAvailable > 0) {
    echo "\n✅ SUCCESS: $finalAvailable Superior room(s) available\n";
    echo "   Booking system should work!\n";
} else {
    echo "\n❌ All Superior rooms are unavailable\n";
}

// 6. SQL for live server
echo "\n6. SQL FOR LIVE SERVER\n";
echo "====================\n";

echo "Run this SQL on your live server to fix all Superior rooms:\n\n";

foreach ($unavailableRooms as $room) {
    $hasAvailability = \DB::table('available_dates_hotel_rooms')
        ->where('hotel_room_id', $room->id)
        ->where('from_date', '<=', $checkInDate)
        ->where('to_date', '>=', $checkOutDate)
        ->exists();
    
    if (!$hasAvailability) {
        $basePrice = $room->price_per_night ?? 1400;
        echo "-- Add availability for Room {$room->id}\n";
        echo "INSERT INTO available_dates_hotel_rooms (\n";
        echo "    property_id, hotel_room_id, from_date, to_date, price, type,\n";
        echo "    nonrefundable_percentage, created_at, updated_at\n";
        echo ") VALUES (\n";
        echo "    {$room->property_id}, {$room->id}, '$checkInDate', '$checkOutDate',\n";
        echo "    $basePrice, 'open', " . ($room->nonrefundable_percentage ?? 85) . ", NOW(), NOW()\n";
        echo ");\n\n";
    }
}

echo "\n=== CHECK COMPLETE ===\n";
