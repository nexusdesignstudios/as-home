<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;

// Get reservations for Green Hotel 2
$propertyId = 351;
$roomId = 755;

// Query reservations for this specific room
$reservations = Reservation::with(['property'])
    ->where('property_id', $propertyId)
    ->where('reservable_id', $roomId)
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->orderBy('check_in_date', 'asc')
    ->get();

echo "=== GREEN HOTEL 2 ROOM 755 RESERVATIONS ===\n";
echo "Property ID: $propertyId, Room ID: $roomId\n";
echo "Total reservations found: " . $reservations->count() . "\n\n";

foreach ($reservations as $reservation) {
    // Get room info separately
    $room = HotelRoom::find($reservation->reservable_id);
    
    echo "Reservation #{$reservation->id}:\n";
    echo "  Property: {$reservation->property->name}\n";
    echo "  Room: " . ($room ? $room->room_number : 'Unknown') . " (ID: {$reservation->reservable_id})\n";
    echo "  Guest: {$reservation->first_name} {$reservation->last_name}\n";
    echo "  Email: {$reservation->email}\n";
    echo "  Phone: {$reservation->phone}\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Price: {$reservation->total_price} {$reservation->currency}\n";
    echo "  Payment Method: {$reservation->payment_method}\n";
    echo "  Payment Gateway: {$reservation->payment_gateway}\n";
    echo "  Transaction Method: {$reservation->transaction_method}\n";
    echo "  Payment Status: {$reservation->payment_status}\n";
    echo "  Status: {$reservation->status}\n";
    echo "  Display Status: {$reservation->display_status}\n";
    echo "  Is Flexible: " . (isFlexibleReservation($reservation) ? 'YES' : 'NO') . "\n";
    echo "  Should Block: " . (shouldBlockRoom($reservation) ? 'YES' : 'NO') . "\n";
    echo "  Created At: {$reservation->created_at}\n";
    echo "  Updated At: {$reservation->updated_at}\n";
    echo "\n";
}

function isFlexibleReservation($reservation) {
    $paymentMethod = $reservation->payment_method ?? 'cash';
    return !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($reservation->payment));
}

function shouldBlockRoom($reservation) {
    $isFlexible = isFlexibleReservation($reservation);
    $actualStatus = strtolower($reservation->status ?? '');
    $displayStatus = strtolower($reservation->display_status ?? '');
    $statusToUse = $displayStatus ?: $actualStatus;
    
    if ($isFlexible) {
        // For flexible reservations, block unless cancelled or rejected
        return $statusToUse !== 'cancelled' && $statusToUse !== 'rejected';
    } else {
        // For non-flexible reservations, use standard blocking logic
        $blockingStatuses = ["confirmed", "approved", "pending", "active"];
        return in_array($statusToUse, $blockingStatuses);
    }
}

// Test specific dates
echo "=== DATE-BY-DATE BLOCKING TEST ===\n";
$testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];

foreach ($testDates as $date) {
    echo "\nTesting date: $date\n";
    $blocked = false;
    $blockingReservations = [];
    
    foreach ($reservations as $reservation) {
        $checkIn = $reservation->check_in_date;
        $checkOut = $reservation->check_out_date;
        
        // Check if date is within reservation period (inclusive check-in, exclusive check-out)
        if ($date >= $checkIn && $date < $checkOut) {
            if (shouldBlockRoom($reservation)) {
                $blocked = true;
                $blockingReservations[] = $reservation->id;
            }
        }
    }
    
    echo "  Room status: " . ($blocked ? "BLOCKED" : "AVAILABLE") . "\n";
    if ($blocked) {
        echo "  Blocking reservations: " . implode(', ', $blockingReservations) . "\n";
    }
}