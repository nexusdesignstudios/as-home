<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Analyzing flexible reservation inconsistencies...\n\n";

// Check the problematic reservations
$reservationIds = [894, 893, 892];

foreach ($reservationIds as $reservationId) {
    echo "=== RESERVATION ID: {$reservationId} ===\n";
    
    $reservation = DB::table('reservations')
        ->where('id', $reservationId)
        ->first();
    
    if (!$reservation) {
        echo "Reservation not found!\n\n";
        continue;
    }
    
    echo "Basic Info:\n";
    echo "- Status: {$reservation->status}\n";
    echo "- Payment Status: {$reservation->payment_status}\n";
    echo "- Payment Method: {$reservation->payment_method}\n";
    echo "- Property ID: {$reservation->property_id}\n";
    echo "- Reservable Type: {$reservation->reservable_type}\n";
    echo "- Reservable ID: {$reservation->reservable_id}\n";
    
    // Check property details
    if ($reservation->property_id) {
        $property = DB::table('propertys')
            ->where('id', $reservation->property_id)
            ->first();
        
        if ($property) {
            echo "\nProperty Details:\n";
            echo "- Title: {$property->title}\n";
            echo "- Classification: {$property->property_classification}\n";
            echo "- Refund Policy: " . ($property->refund_policy ?? 'NULL') . "\n";
            echo "- Status: {$property->status}\n";
        } else {
            echo "\n❌ Property not found!\n";
        }
    } else {
        echo "\n❌ No property ID!\n";
    }
    
    // Check reservable details (room)
    if ($reservation->reservable_type === 'App\Models\HotelRoom' && $reservation->reservable_id) {
        $room = DB::table('hotel_rooms')
            ->where('id', $reservation->reservable_id)
            ->first();
        
        if ($room) {
            echo "\nRoom Details:\n";
            echo "- Room Number: {$room->room_number}\n";
            echo "- Property ID: {$room->property_id}\n";
            echo "- Refund Policy: " . ($room->refund_policy ?? 'NULL') . "\n";
            echo "- Status: {$room->status}\n";
        } else {
            echo "\n❌ Room not found!\n";
        }
    } else {
        echo "\n❌ Not a hotel room reservation!\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

// Compare with working reservation 895
echo "=== COMPARISON WITH WORKING RESERVATION 895 ===\n";

$workingReservation = DB::table('reservations')
    ->where('id', 895)
    ->first();

echo "Working Reservation 895:\n";
echo "- Status: {$workingReservation->status}\n";
echo "- Payment Status: {$workingReservation->payment_status}\n";
echo "- Payment Method: {$workingReservation->payment_method}\n";
echo "- Property ID: {$workingReservation->property_id}\n";
echo "- Reservable Type: {$workingReservation->reservable_type}\n";
echo "- Reservable ID: {$workingReservation->reservable_id}\n";

// Check its property
$workingProperty = DB::table('propertys')
    ->where('id', $workingReservation->property_id)
    ->first();

echo "\nWorking Property 312:\n";
echo "- Title: {$workingProperty->title}\n";
echo "- Classification: {$workingProperty->property_classification}\n";
echo "- Refund Policy: {$workingProperty->refund_policy}\n";

// Check its room
$workingRoom = DB::table('hotel_rooms')
    ->where('id', $workingReservation->reservable_id)
    ->first();

echo "\nWorking Room 647:\n";
echo "- Room Number: {$workingRoom->room_number}\n";
echo "- Property ID: {$workingRoom->property_id}\n";
echo "- Refund Policy: {$workingRoom->refund_policy}\n";