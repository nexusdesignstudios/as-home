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

// Check for reservations 896, 897, 898 specifically
echo "=== CHECKING RESERVATIONS 896, 897, 898 ===\n";

$reservationIds = [896, 897, 898];

foreach ($reservationIds as $reservationId) {
    echo "\n--- Reservation #{$reservationId} ---\n";
    
    $reservation = Reservation::with(['property'])
        ->where('id', $reservationId)
        ->first();
        
    if ($reservation) {
        // Get room info
        $room = HotelRoom::find($reservation->reservable_id);
        
        echo "  Property: {$reservation->property->name} (ID: {$reservation->property_id})\n";
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
    } else {
        echo "  ❌ Reservation not found in database\n";
    }
}

// Check ALL reservations for Green Hotel 2
echo "\n\n=== ALL GREEN HOTEL 2 RESERVATIONS ===\n";

$allReservations = Reservation::with(['property'])
    ->where('property_id', 351)
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

echo "Total reservations for Green Hotel 2: " . $allReservations->count() . "\n\n";

foreach ($allReservations as $reservation) {
    $room = HotelRoom::find($reservation->reservable_id);
    
    echo "Reservation #{$reservation->id}:\n";
    echo "  Room: " . ($room ? $room->room_number : 'Unknown') . " (ID: {$reservation->reservable_id})\n";
    echo "  Guest: {$reservation->first_name} {$reservation->last_name}\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Status: {$reservation->status} / {$reservation->display_status}\n";
    echo "  Payment: {$reservation->payment_method} / {$reservation->payment_status}\n";
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