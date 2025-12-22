<?php

/**
 * Test script to check vacation home reservations API response
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Reservation;
use App\Models\Property;

echo "========================================\n";
echo "Testing Vacation Home Reservations API\n";
echo "========================================\n\n";

// Get all reservations with reservable_type = App\Models\Property
$reservations = Reservation::with(['reservable', 'property'])
    ->where('reservable_type', 'App\\Models\\Property')
    ->get();

echo "Total reservations with reservable_type = App\\Models\\Property: " . $reservations->count() . "\n\n";

// Check which ones are vacation homes (property_classification = 4)
$vacationHomeReservations = [];
foreach ($reservations as $reservation) {
    $property = $reservation->reservable;
    if ($property && $property->property_classification == 4) {
        $vacationHomeReservations[] = $reservation;
    }
}

echo "Vacation home reservations (property_classification = 4): " . count($vacationHomeReservations) . "\n\n";

if (count($vacationHomeReservations) > 0) {
    echo "Sample vacation home reservations:\n";
    foreach (array_slice($vacationHomeReservations, 0, 5) as $reservation) {
        echo "  - ID: {$reservation->id}\n";
        echo "    Customer ID: {$reservation->customer_id}\n";
        echo "    Property ID: {$reservation->property_id}\n";
        echo "    Reservable ID: {$reservation->reservable_id}\n";
        echo "    Reservable Type: {$reservation->reservable_type}\n";
        echo "    Apartment ID: " . ($reservation->apartment_id ?? 'N/A') . "\n";
        if ($reservation->reservable) {
            echo "    Property Title: {$reservation->reservable->title}\n";
            echo "    Property Classification: {$reservation->reservable->property_classification}\n";
        } else {
            echo "    ⚠️  Reservable relationship NOT loaded!\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️  No vacation home reservations found!\n";
    echo "\nChecking all reservations:\n";
    foreach ($reservations->take(5) as $reservation) {
        echo "  - ID: {$reservation->id}, Reservable Type: {$reservation->reservable_type}\n";
        if ($reservation->reservable) {
            echo "    Property Classification: " . ($reservation->reservable->property_classification ?? 'N/A') . "\n";
        } else {
            echo "    ⚠️  Reservable NOT loaded\n";
        }
    }
}

echo "\n";

