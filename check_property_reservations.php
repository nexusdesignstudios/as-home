<?php

/**
 * Check reservations for property "Amazing 01-Bedrroom in Dream Hotel 01" (ID: 334)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Property Reservations Check\n";
echo "========================================\n\n";

$propertyId = 334;
$property = Property::find($propertyId);

if (!$property) {
    echo "❌ Property not found\n";
    exit;
}

echo "Property ID: {$propertyId}\n";
echo "Title: {$property->title}\n";
echo "Classification: {$property->property_classification}\n";
echo "\n";

// Check all reservations for this property
echo "========================================\n";
echo "All Reservations for Property {$propertyId}\n";
echo "========================================\n\n";

$allReservations = Reservation::where('property_id', $propertyId)
    ->get(['id', 'customer_id', 'reservable_id', 'reservable_type', 'check_in_date', 'check_out_date', 'status', 'apartment_id', 'apartment_quantity', 'special_requests']);

echo "Total reservations: {$allReservations->count()}\n\n";

if ($allReservations->isEmpty()) {
    echo "❌ No reservations found in database\n";
} else {
    foreach ($allReservations as $res) {
        echo "Reservation ID: {$res->id}\n";
        echo "  Customer ID: {$res->customer_id}\n";
        echo "  Reservable ID: {$res->reservable_id}\n";
        echo "  Reservable Type: {$res->reservable_type}\n";
        echo "  Check-in: {$res->check_in_date}\n";
        echo "  Check-out: {$res->check_out_date}\n";
        echo "  Status: {$res->status}\n";
        echo "  Apartment ID: " . ($res->apartment_id ?? 'NULL') . "\n";
        echo "  Apartment Quantity: " . ($res->apartment_quantity ?? 'NULL') . "\n";
        if ($res->special_requests) {
            echo "  Special Requests: " . substr($res->special_requests, 0, 100) . "...\n";
        }
        echo "\n";
    }
}

// Check reservations by reservable_id (property ID)
echo "========================================\n";
echo "Reservations by Reservable ID (Property ID)\n";
echo "========================================\n\n";

$reservableReservations = Reservation::where('reservable_id', $propertyId)
    ->where('reservable_type', 'App\\Models\\Property')
    ->get(['id', 'customer_id', 'check_in_date', 'check_out_date', 'status', 'apartment_id']);

echo "Total: {$reservableReservations->count()}\n\n";
foreach ($reservableReservations as $res) {
    echo "Reservation ID: {$res->id}, Status: {$res->status}, Dates: {$res->check_in_date} to {$res->check_out_date}\n";
}

// Check vacation apartments
echo "\n========================================\n";
echo "Vacation Apartments\n";
echo "========================================\n\n";

$apartments = VacationApartment::where('property_id', $propertyId)->get();
echo "Total apartments: {$apartments->count()}\n";
foreach ($apartments as $apt) {
    echo "  Apartment ID: {$apt->id}, Number: {$apt->apartment_number}\n";
    
    // Check reservations for this apartment
    $aptReservations = Reservation::where('property_id', $propertyId)
        ->where(function($query) use ($apt) {
            $query->where('apartment_id', $apt->id)
                  ->orWhere('special_requests', 'LIKE', "%Apartment ID: {$apt->id}%");
        })
        ->get(['id', 'check_in_date', 'check_out_date', 'status']);
    
    echo "    Reservations: {$aptReservations->count()}\n";
    foreach ($aptReservations as $aptRes) {
        echo "      - Reservation {$aptRes->id}: {$aptRes->check_in_date} to {$aptRes->check_out_date} (Status: {$aptRes->status})\n";
    }
}

// Check what the API would return
echo "\n========================================\n";
echo "API Response Check\n";
echo "========================================\n\n";

// Simulate what getCustomerReservations or similar endpoint would return
$customerReservations = Reservation::where('reservable_type', 'App\\Models\\Property')
    ->where('reservable_id', $propertyId)
    ->with(['reservable', 'property'])
    ->get();

echo "Reservations with eager loading: {$customerReservations->count()}\n";
foreach ($customerReservations as $res) {
    echo "  Reservation {$res->id}:\n";
    echo "    Property ID: {$res->property_id}\n";
    echo "    Reservable ID: {$res->reservable_id}\n";
    echo "    Reservable Type: {$res->reservable_type}\n";
    echo "    Has Reservable: " . ($res->reservable ? 'Yes' : 'No') . "\n";
    echo "    Has Property: " . ($res->property ? 'Yes' : 'No') . "\n";
    if ($res->reservable) {
        echo "    Reservable Classification: " . ($res->reservable->property_classification ?? 'N/A') . "\n";
    }
    echo "\n";
}

