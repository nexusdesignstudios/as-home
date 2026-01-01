<?php

/**
 * Test the API response for Green Hotel 2 reservations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Testing API Response for Green Hotel 2\n";
echo "========================================\n\n";

// Find Green Hotel 2
$property = Property::where('title', 'like', '%Green hotel 2 testing room only%')->first();

if (!$property) {
    echo "❌ Green Hotel 2 not found\n";
    exit;
}

echo "Property ID: {$property->id}\n";
echo "Title: {$property->title}\n";
echo "Owner ID: {$property->user_id}\n\n";

// Get ALL reservations for this property (including the specific IDs you mentioned)
$allReservations = Reservation::where('property_id', $property->id)
    ->orderBy('id', 'desc')
    ->get();

echo "=== ALL RESERVATIONS FOR GREEN HOTEL 2 ===\n";
echo "Total reservations: {$allReservations->count()}\n\n";

// Check specifically for the reservations you mentioned
$targetIds = [896, 897, 898];
foreach ($targetIds as $id) {
    $reservation = $allReservations->firstWhere('id', $id);
    echo "Reservation #{$id}:\n";
    
    if ($reservation) {
        echo "  ✅ FOUND\n";
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
        
        // Check if this should be mapped to confirmed
        if ($isFlexible) {
            echo "  Will be mapped to: confirmed (display_status: confirmed)\n";
        }
    } else {
        echo "  ❌ NOT FOUND\n";
    }
    echo "\n";
}

echo "=== ALL RESERVATIONS (Recent First) ===\n";
foreach ($allReservations->take(10) as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Status: {$reservation->status}\n";
    echo "  Payment method: " . ($reservation->payment_method ?: 'null') . "\n";
    
    // Check if flexible
    $paymentMethod = $reservation->payment_method ?: 'cash';
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
    echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
    
    if ($isFlexible) {
        echo "  Will show as: confirmed (display_status)\n";
    }
    echo "\n";
}

// Check reservations for January 1-4, 2026
echo "=== RESERVATIONS FOR JANUARY 1-4, 2026 ===\n";
$januaryReservations = Reservation::where('property_id', $property->id)
    ->where(function($query) {
        $query->whereBetween('check_in_date', ['2026-01-01', '2026-01-04'])
              ->orWhereBetween('check_out_date', ['2026-01-01', '2026-01-04'])
              ->orWhere(function($q) {
                  $q->where('check_in_date', '<=', '2026-01-01')
                    ->where('check_out_date', '>=', '2026-01-04');
              });
    })
    ->get();

echo "Found {$januaryReservations->count()} reservations for Jan 1-4, 2026:\n\n";

foreach ($januaryReservations as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Status: {$reservation->status}\n";
    echo "  Payment method: " . ($reservation->payment_method ?: 'null') . "\n";
    
    // Check if flexible
    $paymentMethod = $reservation->payment_method ?: 'cash';
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
    echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
    
    if ($isFlexible) {
        echo "  Will show as: confirmed (display_status)\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "Key Findings:\n";
echo "- Check if reservations 896, 897, 898 exist in database\n";
echo "- Verify their actual status vs displayed status\n";
echo "- Check if there are other reservations blocking January 1-4\n";
echo "========================================\n";