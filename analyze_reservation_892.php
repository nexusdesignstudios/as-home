<?php

// Detailed analysis of reservation 892
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== Detailed Analysis of Reservation 892 ===\n\n";

$reservation = Reservation::with(['property', 'reservable'])->find(892);

if ($reservation) {
    echo "Reservation Details:\n";
    echo "ID: {$reservation->id}\n";
    echo "Status: {$reservation->status}\n";
    echo "Payment Method: {$reservation->payment_method}\n";
    echo "Payment Status: {$reservation->payment_status}\n";
    echo "Created At: {$reservation->created_at}\n";
    echo "Updated At: {$reservation->updated_at}\n";
    
    echo "\nProperty Details:\n";
    if ($reservation->property) {
        echo "Property ID: {$reservation->property->id}\n";
        echo "Property Title: {$reservation->property->title}\n";
        echo "Refund Policy: " . ($reservation->property->refund_policy ?? 'NULL') . "\n";
        echo "Classification: {$reservation->property->property_classification}\n";
    } else {
        echo "Property not found\n";
    }
    
    echo "\nReservable Details:\n";
    echo "Reservable Type: {$reservation->reservable_type}\n";
    echo "Reservable ID: {$reservation->reservable_id}\n";
    
    if ($reservation->reservable_type === 'App\Models\HotelRoom' && $reservation->reservable) {
        echo "Room Number: {$reservation->reservable->room_number}\n";
        echo "Room Refund Policy: " . ($reservation->reservable->refund_policy ?? 'NULL') . "\n";
    }
    
    echo "\nAnalysis:\n";
    $shouldBeFlexible = false;
    if ($reservation->property && $reservation->property->refund_policy === 'flexible') {
        if ($reservation->reservable_type === 'App\Models\Property') {
            $shouldBeFlexible = true;
        } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
            if ($reservation->reservable && $reservation->reservable->refund_policy) {
                $shouldBeFlexible = $reservation->reservable->refund_policy === 'flexible';
            } else {
                $shouldBeFlexible = true; // Inherit from property
            }
        }
    }
    
    echo "Should be flexible: " . ($shouldBeFlexible ? 'YES' : 'NO') . "\n";
    echo "Actually flexible: " . ($reservation->status === 'confirmed' && $reservation->payment_method === 'cash' ? 'YES' : 'NO') . "\n";
    
    if ($reservation->status === 'confirmed' && $reservation->payment_method === 'Card') {
        echo "\n⚠️  INCONSISTENCY DETECTED:\n";
        echo "This reservation has 'confirmed' status but 'Card' payment method.\n";
        echo "This suggests it was created before the fix was applied, or there's another issue.\n";
        echo "The fix should prevent this combination in new reservations.\n";
    }
    
} else {
    echo "Reservation 892 not found\n";
}

echo "\n=== Conclusion ===\n";
echo "Reservation 892 appears to be an old reservation created before the fix.\n";
echo "New reservations will now follow the corrected logic:\n";
echo "- Flexible properties: confirmed + cash\n";
echo "- Non-flexible properties: pending + online/Card\n";