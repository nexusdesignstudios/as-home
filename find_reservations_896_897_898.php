<?php

/**
 * Find all reservations for Green Hotel 2 with IDs 896, 897, 898
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Reservation;

echo "========================================\n";
echo "Finding Reservations 896, 897, 898\n";
echo "========================================\n\n";

$ids = [896, 897, 898];

foreach ($ids as $id) {
    $reservation = Reservation::find($id);
    
    echo "Reservation #{$id}:\n";
    
    if ($reservation) {
        echo "  ✅ FOUND\n";
        echo "  Property ID: {$reservation->property_id}\n";
        echo "  Check-in: {$reservation->check_in_date}\n";
        echo "  Check-out: {$reservation->check_out_date}\n";
        echo "  Status: {$reservation->status}\n";
        echo "  Payment method: " . ($reservation->payment_method ?: 'null') . "\n";
        echo "  Payment status: " . ($reservation->payment_status ?: 'null') . "\n";
        echo "  Reservable type: {$reservation->reservable_type}\n";
        echo "  Reservable ID: {$reservation->reservable_id}\n";
        echo "  Created at: {$reservation->created_at}\n";
        echo "  Updated at: {$reservation->updated_at}\n";
        
        // Check if this is flexible
        $paymentMethod = $reservation->payment_method ?: 'cash';
        $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
        echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
        
    } else {
        echo "  ❌ NOT FOUND in database\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "This explains why you see '8 open' - these reservations\n";
echo "might not exist in the database or might have different status\n";
echo "========================================\n";