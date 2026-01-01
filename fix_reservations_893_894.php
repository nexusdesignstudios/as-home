<?php

// Fix the reservable_type and reservable_id for reservations 893 and 894
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing Reservations 893 and 894 ===\n\n";

// Check current state
$currentReservations = DB::table('reservations')
    ->whereIn('id', [893, 894])
    ->select(['id', 'reservable_type', 'reservable_id', 'property_id'])
    ->get();

echo "Current reservations:\n";
foreach ($currentReservations as $reservation) {
    echo "  Reservation {$reservation->id}:\n";
    echo "    reservable_type: " . ($reservation->reservable_type ?? 'NULL') . "\n";
    echo "    reservable_id: " . ($reservation->reservable_id ?? 'NULL') . "\n";
    echo "    property_id: {$reservation->property_id}\n";
    echo "\n";
}

// Get available hotel rooms for property 351
$hotelRooms = DB::table('hotel_rooms')
    ->where('property_id', 351)
    ->select(['id', 'room_number', 'property_id', 'room_type_id', 'refund_policy'])
    ->get();

echo "Available hotel rooms for property 351:\n";
foreach ($hotelRooms as $room) {
    echo "  Room ID: {$room->id} (refund_policy: {$room->refund_policy})\n";
}
echo "\n";

// Fix the reservations
$fixedRooms = [];
foreach ($currentReservations as $reservation) {
    // Find the next available hotel room
    $availableRoom = null;
    foreach ($hotelRooms as $room) {
        if (!in_array($room->id, $fixedRooms)) {
            $availableRoom = $room;
            break;
        }
    }
    
    if ($availableRoom) {
        echo "Fixing reservation {$reservation->id}:\n";
        echo "  Setting reservable_type to 'App\\\\Models\\\\HotelRoom'\n";
        echo "  Setting reservable_id to {$availableRoom->id}\n";
        echo "  Keeping property_id as {$reservation->property_id}\n";
        echo "\n";
        
        // Update the reservation
        DB::table('reservations')
            ->where('id', $reservation->id)
            ->update([
                'reservable_type' => 'App\\Models\\HotelRoom',
                'reservable_id' => $availableRoom->id
            ]);
        
        $fixedRooms[] = $availableRoom->id;
        echo "  ✅ Fixed!\n\n";
    } else {
        echo "  ❌ No available hotel rooms for reservation {$reservation->id}\n\n";
    }
}

// Verify the fix
$updatedReservations = DB::table('reservations')
    ->whereIn('id', [893, 894])
    ->select(['id', 'reservable_type', 'reservable_id', 'property_id'])
    ->get();

echo "=== Verification ===\n";
echo "Updated reservations:\n";
foreach ($updatedReservations as $reservation) {
    echo "  Reservation {$reservation->id}:\n";
    echo "    reservable_type: " . ($reservation->reservable_type ?? 'NULL') . "\n";
    echo "    reservable_id: " . ($reservation->reservable_id ?? 'NULL') . "\n";
    echo "    property_id: {$reservation->property_id}\n";
    echo "\n";
}

echo "=== Summary ===\n";
echo "Reservations 893 and 894 have been fixed to point to actual hotel rooms.\n";
echo "The admin dashboard should now show property data for these reservations.\n";