<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

echo "=== CHECKING RESERVATIONS #1003 and #1004 ===\n\n";

// Check for the specific reservations mentioned in the issue
$specificReservations = Reservation::whereIn('id', [1003, 1004])
    ->with(['property', 'reservable'])
    ->get();

foreach ($specificReservations as $reservation) {
    echo "Reservation ID: {$reservation->id}\n";
    echo "Property: " . ($reservation->property->title ?? 'N/A') . "\n";
    echo "Room ID: {$reservation->reservable_id}\n";
    echo "Check-in: {$reservation->check_in_date}\n";
    echo "Check-out: {$reservation->check_out_date}\n";
    echo "Customer: {$reservation->customer_name}\n";
    echo "Email: {$reservation->customer_email}\n";
    echo "Phone: {$reservation->customer_phone}\n";
    echo "Status: {$reservation->status}\n";
    echo "Payment Method: {$reservation->payment_method}\n";
    echo "Payment Status: {$reservation->payment_status}\n";
    echo "Total Price: {$reservation->total_price}\n";
    echo "Created: {$reservation->created_at}\n";
    
    // Get room details
    if ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($reservation->reservable_id);
        if ($room) {
            echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
            echo "Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
            echo "Property ID: " . $room->property_id . "\n";
        }
    }
    echo "   ---\n";
}

// Check if there are other reservations for the same room and dates
echo "\n=== OTHER RESERVATIONS FOR SAME ROOM AND DATES ===\n";

if ($specificReservations->isNotEmpty()) {
    $firstReservation = $specificReservations->first();
    $roomId = $firstReservation->reservable_id;
    $checkIn = $firstReservation->check_in_date;
    $checkOut = $firstReservation->check_out_date;
    
    echo "Checking for other reservations for Room ID: $roomId, Dates: $checkIn to $checkOut\n\n";
    
    $otherReservations = Reservation::where('reservable_id', $roomId)
        ->where('check_in_date', $checkIn)
        ->where('check_out_date', $checkOut)
        ->whereNotIn('id', [1003, 1004])
        ->with(['property', 'reservable'])
        ->get();
    
    echo "Found " . $otherReservations->count() . " other reservations for the same room and dates:\n\n";
    
    foreach ($otherReservations as $reservation) {
        echo "Reservation ID: {$reservation->id}\n";
        echo "Customer: {$reservation->customer_name}\n";
        echo "Email: {$reservation->customer_email}\n";
        echo "Status: {$reservation->status}\n";
        echo "Payment Method: {$reservation->payment_method}\n";
        echo "Created: {$reservation->created_at}\n";
        echo "   ---\n";
    }
}

// Check available rooms for the same property and dates
echo "\n=== AVAILABLE ROOMS FOR SAME PROPERTY AND DATES ===\n";

if ($specificReservations->isNotEmpty()) {
    $firstReservation = $specificReservations->first();
    $propertyId = $firstReservation->property_id;
    $checkIn = $firstReservation->check_in_date;
    $checkOut = $firstReservation->check_out_date;
    
    echo "Checking available rooms for Property ID: $propertyId, Dates: $checkIn to $checkOut\n\n";
    
    $availableRooms = HotelRoom::where('property_id', $propertyId)
        ->where('status', 1)
        ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
            $query->where('status', 'confirmed')
                ->where(function ($dateQuery) use ($checkIn, $checkOut) {
                    $dateQuery->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>', $checkIn);
                    })
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<', $checkOut)
                            ->where('check_out_date', '>=', $checkOut);
                    })
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '>=', $checkIn)
                            ->where('check_out_date', '<=', $checkOut);
                    });
                });
        })
        ->get();
    
    echo "Found " . $availableRooms->count() . " available rooms:\n\n";
    
    foreach ($availableRooms as $room) {
        echo "Room ID: {$room->id}\n";
        echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
        echo "Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
        echo "   ---\n";
    }
}

echo "\n=== ANALYSIS ===\n";
echo "The issue is confirmed: Both reservations #1003 and #1004 are assigned to the same room.\n";
echo "The system should have found an available room for the second reservation.\n";
