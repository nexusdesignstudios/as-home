<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TESTING ROOM AVAILABILITY LOGIC ===\n\n";

// Test the room availability logic that should be used for flexible reservations
function findAvailableRoom($propertyId, $checkInDate, $checkOutDate, $excludeReservationIds = []) {
    echo "Searching for available room for Property ID: $propertyId, Dates: $checkInDate to $checkOutDate\n";
    
    $availableRooms = HotelRoom::where('property_id', $propertyId)
        ->where('status', 1)
        ->whereDoesntHave('reservations', function ($query) use ($checkInDate, $checkOutDate, $excludeReservationIds) {
            $query->where('status', 'confirmed')
                ->whereNotIn('id', $excludeReservationIds)
                ->where(function ($dateQuery) use ($checkInDate, $checkOutDate) {
                    $dateQuery->where(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '<=', $checkInDate)
                            ->where('check_out_date', '>', $checkInDate);
                    })
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '<', $checkOutDate)
                            ->where('check_out_date', '>=', $checkOutDate);
                    })
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '>=', $checkInDate)
                            ->where('check_out_date', '<=', $checkOutDate);
                    });
                });
        })
        ->first();
    
    if ($availableRooms) {
        echo "Found available room: ID {$availableRooms->id}, Number " . ($availableRooms->room_number ?? 'N/A') . "\n";
        return $availableRooms;
    } else {
        echo "No available rooms found\n";
        return null;
    }
}

// Test with a sample property and dates
echo "1. Testing room availability logic:\n";

// Find a property with hotel rooms
$property = \App\Models\Property::where('property_classification', 5)
    ->where('status', 1)
    ->has('hotelRooms')
    ->first();

if ($property) {
    echo "Using Property: {$property->title} (ID: {$property->id})\n";
    
    // Show all rooms for this property
    $allRooms = HotelRoom::where('property_id', $property->id)
        ->where('status', 1)
        ->get();
    
    echo "Total rooms available: " . $allRooms->count() . "\n";
    foreach ($allRooms as $room) {
        echo "  - Room ID: {$room->id}, Number: " . ($room->room_number ?? 'N/A') . "\n";
    }
    
    // Test availability for some dates
    $checkIn = '2026-01-09';
    $checkOut = '2026-01-10';
    
    echo "\nTesting availability for dates: $checkIn to $checkOut\n";
    
    $availableRoom = findAvailableRoom($property->id, $checkIn, $checkOut);
    
    if ($availableRoom) {
        echo "✅ SUCCESS: Found available room\n";
    } else {
        echo "❌ NO ROOMS AVAILABLE\n";
    }
    
    // Test what happens with existing reservations
    echo "\n2. Testing with existing reservations:\n";
    
    // Find existing reservations for these dates
    $existingReservations = Reservation::where('property_id', $property->id)
        ->where('check_in_date', $checkIn)
        ->where('check_out_date', $checkOut)
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->where('status', 'confirmed')
        ->get();
    
    echo "Found " . $existingReservations->count() . " existing reservations for these dates:\n";
    foreach ($existingReservations as $res) {
        echo "  - Reservation ID: {$res->id}, Room ID: {$res->reservable_id}\n";
    }
    
    // Test finding another available room
    if ($existingReservations->isNotEmpty()) {
        $excludeIds = $existingReservations->pluck('id')->toArray();
        echo "\nTesting availability while excluding existing reservations:\n";
        $anotherAvailableRoom = findAvailableRoom($property->id, $checkIn, $checkOut, $excludeIds);
        
        if ($anotherAvailableRoom) {
            echo "✅ SUCCESS: Found another available room\n";
        } else {
            echo "❌ NO OTHER ROOMS AVAILABLE\n";
        }
    }
    
} else {
    echo "❌ No property found for testing\n";
}

echo "\n=== RECOMMENDATION ===\n";
echo "The room assignment logic in submitPaymentForm should be updated to:\n";
echo "1. For flexible bookings, find an available room instead of using the first room\n";
echo "2. Check room availability before creating the reservation\n";
echo "3. If no rooms are available, return an error response\n";
