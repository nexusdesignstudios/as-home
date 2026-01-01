<?php
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== ANALYZING RESERVATION DATA STRUCTURE ===\n\n";

// Get all reservations for property 351
$reservations = Reservation::where('property_id', 351)
    ->whereIn('status', ['pending', 'approved', 'confirmed'])
    ->get();

echo "Total reservations: " . $reservations->count() . "\n\n";

foreach ($reservations as $reservation) {
    echo "🔒 Reservation ID: {$reservation->id}\n";
    echo "📅 Check-in: {$reservation->check_in_date}\n";
    echo "📅 Check-out: {$reservation->check_out_date}\n";
    echo "🔖 Status: {$reservation->status}\n";
    echo "💳 Payment Method: {$reservation->payment_method}\n";
    echo "🛏️ Reservable ID: {$reservation->reservable_id}\n";
    echo "🏠 Reservable Type: {$reservation->reservable_type}\n";
    echo "🏨 Property ID: {$reservation->property_id}\n";
    echo "🏢 Apartment ID: {$reservation->apartment_id}\n";
    echo "🏢 Apartment Quantity: {$reservation->apartment_quantity}\n";
    
    // Check if reservable_data contains room information
    if ($reservation->reservable_data) {
        $reservableData = json_decode($reservation->reservable_data, true);
        echo "📊 Reservable Data: " . json_encode($reservableData, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "---\n";
}