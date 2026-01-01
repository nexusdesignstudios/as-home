<?php

/**
 * Test the API response for Green Hotel 2 reservations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
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
echo "Title: {$property->title}\n\n";

// Find the customer/user who owns this property (for API call)
$customer = Customer::whereHas('properties', function($query) use ($property) {
    $query->where('properties.id', $property->id);
})->first();

if (!$customer) {
    echo "❌ No customer found for this property\n";
    exit;
}

echo "Customer ID: {$customer->id}\n";
echo "Customer Name: {$customer->name}\n\n";

// Get ALL reservations for this property (not just confirmed ones)
$allReservations = Reservation::where('property_id', $property->id)
    ->orderBy('id', 'desc')
    ->get();

echo "=== ALL RESERVATIONS FOR GREEN HOTEL 2 ===\n";
echo "Total reservations: {$allReservations->count()}\n\n";

foreach ($allReservations as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
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
    
    echo "\n";
}

// Now test the API response format like the backend does
echo "=== SIMULATING API RESPONSE FORMAT ===\n\n";

foreach ($allReservations as $reservation) {
    echo "Reservation #{$reservation->id} API format:\n";
    
    // Simulate the backend logic from ReservationController
    $paymentMethod = $reservation->payment_method ?: 'cash';
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
    
    echo "  Original status: {$reservation->status}\n";
    
    if ($isFlexible) {
        $mappedStatus = 'confirmed';
        $displayStatus = 'confirmed';
        echo "  Mapped status: {$mappedStatus}\n";
        echo "  Display status: {$displayStatus}\n";
        echo "  is_flexible_reservation: true\n";
    } else {
        echo "  Mapped status: {$reservation->status}\n";
        echo "  Display status: none\n";
        echo "  is_flexible_reservation: false\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Key Findings:\n";
echo "- Check if reservations 896, 897 exist and their actual status\n";
echo "- Verify the API mapping logic for flexible reservations\n";
echo "- Confirm if the backend is returning the expected data\n";
echo "========================================\n";