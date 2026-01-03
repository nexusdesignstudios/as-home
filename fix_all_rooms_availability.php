<?php
// Fix availability for all rooms of the same type automatically
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ALL ROOMS AVAILABILITY AUTOMATICALLY ===\n\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// 1. Get all Superior rooms (room_type_id = 5)
echo "1. FINDING ALL SUPERIOR ROOMS\n";
echo "==========================\n";

$superiorRooms = \App\Models\HotelRoom::where('room_type_id', 5)
    ->where('property_id', 357)
    ->where('status', 1)
    ->orderBy('id')
    ->get();

echo "Found {$superiorRooms->count()} Superior rooms:\n";
foreach ($superiorRooms as $room) {
    echo "  - Room {$room->id}: {$room->room_type->name}\n";
}

// 2. Check availability for each room
echo "\n2. CHECKING AVAILABILITY FOR EACH ROOM\n";
echo "====================================\n";

$unavailableRooms = [];
$availableRooms = [];

foreach ($superiorRooms as $room) {
    // Check if room has availability data
    $hasAvailability = \DB::table('available_dates_hotel_rooms')
        ->where('hotel_room_id', $room->id)
        ->where('from_date', '<=', $checkInDate)
        ->where('to_date', '>=', $checkOutDate)
        ->exists();
    
    // Check if room has blocking reservations
    $hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $room->id, 'App\\Models\\HotelRoom');
    
    $isAvailable = $hasAvailability && !$hasOverlap;
    
    echo "Room {$room->id}: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    echo "  - Has availability data: " . ($hasAvailability ? "YES" : "NO") . "\n";
    echo "  - Has blocking reservations: " . ($hasOverlap ? "YES" : "NO") . "\n";
    
    if (!$isAvailable) {
        $unavailableRooms[] = $room;
        if (!$hasAvailability) {
            echo "  → Missing availability data\n";
        }
        if ($hasOverlap) {
            echo "  → Has blocking reservations\n";
        }
    } else {
        $availableRooms[] = $room;
    }
    echo "\n";
}

// 3. Fix missing availability data
echo "3. FIXING MISSING AVAILABILITY DATA\n";
echo "=================================\n";

foreach ($unavailableRooms as $room) {
    // Check if room has availability data
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

// 4. Re-check availability after fix
echo "\n4. RE-CHECKING AVAILABILITY AFTER FIX\n";
echo "====================================\n";

$reservationService = app(\App\Services\ReservationService::class);
$totalAvailable = 0;

foreach ($superiorRooms as $room) {
    $isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $room->id, $checkInDate, $checkOutDate);
    
    echo "Room {$room->id}: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";
    
    if ($isAvailable) {
        $totalAvailable++;
    }
}

// 5. Summary
echo "\n5. SUMMARY\n";
echo "=========\n";

echo "Total Superior rooms: {$superiorRooms->count()}\n";
echo "Available rooms: $totalAvailable\n";
echo "Unavailable rooms: " . ($superiorRooms->count() - $totalAvailable) . "\n";

if ($totalAvailable > 0) {
    echo "\n✅ SUCCESS: At least one Superior room is available\n";
    echo "   The booking system should now work!\n";
    echo "   Users can book any available Superior room\n";
} else {
    echo "\n❌ ISSUE: No Superior rooms are available\n";
    echo "   Check if all rooms have blocking reservations\n";
}

// 6. Create a function to automatically add availability for any room
echo "\n6. AUTOMATIC AVAILABILITY SYSTEM\n";
echo "===============================\n";

echo "To prevent this issue in the future:\n";
echo "1. When a new room is created, automatically add default availability\n";
echo "2. Create a cron job to ensure all rooms have availability data\n";
echo "3. Update the booking system to check room availability dynamically\n";

echo "\n=== FIX COMPLETE ===\n";
