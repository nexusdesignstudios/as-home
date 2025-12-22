<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking vacation home reservations in database...\n\n";

// Get all reservations with reservable_type = App\Models\Property
$reservations = DB::table('reservations')
    ->where('reservable_type', 'App\\Models\\Property')
    ->get();

echo "Total reservations with reservable_type = App\\Models\\Property: " . $reservations->count() . "\n\n";

// Check which properties have classification = 4
$vacationHomePropertyIds = DB::table('propertys')
    ->where('property_classification', 4)
    ->pluck('id')
    ->toArray();

echo "Vacation home property IDs (classification = 4): " . count($vacationHomePropertyIds) . "\n";
if (count($vacationHomePropertyIds) > 0) {
    echo "Property IDs: " . implode(', ', array_slice($vacationHomePropertyIds, 0, 10)) . "...\n\n";
}

// Check reservations for vacation home properties
$vacationReservations = DB::table('reservations')
    ->where('reservable_type', 'App\\Models\\Property')
    ->whereIn('reservable_id', $vacationHomePropertyIds)
    ->get();

echo "Reservations for vacation home properties: " . $vacationReservations->count() . "\n\n";

if ($vacationReservations->count() > 0) {
    echo "Sample vacation home reservations:\n";
    foreach ($vacationReservations->take(5) as $res) {
        $property = DB::table('propertys')->where('id', $res->reservable_id)->first();
        echo "  - Reservation ID: {$res->id}\n";
        echo "    Customer ID: {$res->customer_id}\n";
        echo "    Property ID: {$res->property_id}\n";
        echo "    Reservable ID: {$res->reservable_id}\n";
        echo "    Apartment ID: " . ($res->apartment_id ?? 'N/A') . "\n";
        if ($property) {
            echo "    Property Title: {$property->title}\n";
            echo "    Property Classification: {$property->property_classification}\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️  No reservations found for vacation home properties!\n";
    echo "\nChecking if there are any reservations with apartment_id:\n";
    $aptReservations = DB::table('reservations')
        ->where('reservable_type', 'App\\Models\\Property')
        ->whereNotNull('apartment_id')
        ->get();
    echo "Reservations with apartment_id: " . $aptReservations->count() . "\n";
    if ($aptReservations->count() > 0) {
        foreach ($aptReservations->take(5) as $res) {
            echo "  - Reservation ID: {$res->id}, Apartment ID: {$res->apartment_id}, Property ID: {$res->property_id}\n";
        }
    }
}

echo "\n";

