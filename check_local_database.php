<?php
// Check local database for room 764 reservations
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LOCAL DATABASE CHECK FOR ROOM 764 ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// 1. Check if room exists
echo "1. ROOM EXISTENCE CHECK\n";
echo "====================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "✅ Room $roomId exists\n";
    echo "   Room Type ID: {$room->room_type_id}\n";
    echo "   Property ID: {$room->property_id}\n";
    echo "   Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
    echo "   Price: " . ($room->price_per_night ? $room->price_per_night : 'N/A') . "\n";
    
    if ($room->property) {
        echo "   Property Name: {$room->property->title}\n";
    }
} else {
    echo "❌ Room $roomId does not exist in database!\n";
    echo "   This could be the issue - trying to book non-existent room\n";
    exit(1);
}

// 2. Check all reservations for this room
echo "\n2. ALL RESERVATIONS FOR ROOM $roomId\n";
echo "===============================\n";

$allReservations = \App\Models\Reservation::where('reservable_id', $roomId)
    ->where(function($q) {
        $q->where('reservable_type', 'App\\Models\\HotelRoom')
          ->orWhere('reservable_type', 'hotel_room');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total reservations: {$allReservations->count()}\n\n";

if ($allReservations->count() === 0) {
    echo "   No reservations found for room $roomId\n";
    echo "   Room should be available for booking\n";
} else {
    foreach ($allReservations as $res) {
        $status = $res->status;
        $paymentStatus = $res->payment_status ? $res->payment_status : 'N/A';
        $paymentMethod = $res->payment_method ? $res->payment_method : 'N/A';
        $resCheckIn = $res->check_in_date->format('Y-m-d');
        $resCheckOut = $res->check_out_date->format('Y-m-d');
        
        // Check if active status
        $isActive = in_array($status, ['confirmed', 'approved', 'pending']);
        
        // Check if overlaps with requested dates
        $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
        
        echo "   Reservation ID: {$res->id}\n";
        echo "     Status: $status " . ($isActive ? "(ACTIVE)" : "(INACTIVE)") . "\n";
        echo "     Payment: $paymentStatus / $paymentMethod\n";
        echo "     Dates: $resCheckIn to $resCheckOut\n";
        echo "     Overlaps: " . ($overlaps ? "YES ❌" : "NO ✅") . "\n";
        echo "     Created: " . $res->created_at->format('Y-m-d H:i:s') . "\n";
        echo "\n";
    }
}

// 3. Check specifically for overlapping reservations
echo "\n3. OVERLAPPING RESERVATIONS CHECK\n";
echo "================================\n";

$overlappingReservations = \App\Models\Reservation::where('reservable_id', $roomId)
    ->where(function($q) {
        $q->where('reservable_type', 'App\\Models\\HotelRoom')
          ->orWhere('reservable_type', 'hotel_room');
    })
    ->where(function ($query) use ($checkInDate, $checkOutDate) {
        $query->where(function ($q) use ($checkInDate, $checkOutDate) {
            // Reservation starts during requested period
            $q->where('check_in_date', '>=', $checkInDate)
              ->where('check_in_date', '<', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            // Reservation ends during requested period
            $q->where('check_out_date', '>', $checkInDate)
              ->where('check_out_date', '<=', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            // Reservation completely contains requested period
            $q->where('check_in_date', '<=', $checkInDate)
              ->where('check_out_date', '>=', $checkOutDate);
        });
    })
    ->get();

echo "Overlapping reservations: {$overlappingReservations->count()}\n\n";

foreach ($overlappingReservations as $res) {
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    echo "   Overlapping Reservation ID: {$res->id}\n";
    echo "     Status: {$res->status} " . ($isActive ? "(BLOCKING)" : "(NOT BLOCKING)") . "\n";
    echo "     Dates: {$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')}\n";
    echo "\n";
}

// 4. Check using datesOverlap method
echo "\n4. datesOverlap METHOD CHECK\n";
echo "===========================\n";

$hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
echo "datesOverlap result: " . ($hasOverlap ? "TRUE - Room BLOCKED ❌" : "FALSE - Room AVAILABLE ✅") . "\n";

// 5. Check active overlapping reservations
echo "\n5. ACTIVE OVERLAPPING RESERVATIONS\n";
echo "================================\n";

$activeOverlapping = \App\Models\Reservation::where('reservable_id', $roomId)
    ->where(function($q) {
        $q->where('reservable_type', 'App\\Models\\HotelRoom')
          ->orWhere('reservable_type', 'hotel_room');
    })
    ->whereIn('status', ['confirmed', 'approved', 'pending'])
    ->where(function ($query) use ($checkInDate, $checkOutDate) {
        $query->where(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '>=', $checkInDate)
              ->where('check_in_date', '<', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_out_date', '>', $checkInDate)
              ->where('check_out_date', '<=', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '<=', $checkInDate)
              ->where('check_out_date', '>=', $checkOutDate);
        });
    })
    ->get();

echo "Active overlapping reservations: {$activeOverlapping->count()}\n\n";

foreach ($activeOverlapping as $res) {
    echo "   BLOCKING Reservation ID: {$res->id}\n";
    echo "     Status: {$res->status}\n";
    echo "     Payment: " . ($res->payment_method ? $res->payment_method : 'N/A') . "\n";
    echo "     Dates: {$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')}\n";
    echo "     Created: " . $res->created_at->format('Y-m-d H:i:s') . "\n";
    echo "\n";
}

// 6. Summary
echo "\n6. SUMMARY\n";
echo "=========\n";

if ($activeOverlapping->count() > 0) {
    echo "❌ Room $roomId is BLOCKED for $checkInDate to $checkOutDate\n";
    echo "   Found {$activeOverlapping->count()} active blocking reservation(s)\n";
    echo "   Backend should reject booking attempts\n";
    echo "   Frontend should show room as unavailable\n";
    echo "   500 error should NOT occur (backend correctly rejects)\n";
} else {
    echo "✅ Room $roomId IS AVAILABLE for $checkInDate to $checkOutDate\n";
    echo "   No active blocking reservations found\n";
    echo "   Backend should allow booking\n";
    echo "   If 500 error occurs, there's a backend bug\n";
    echo "   Frontend should show room as available\n";
}

echo "\nDatabase check completed.\n";
