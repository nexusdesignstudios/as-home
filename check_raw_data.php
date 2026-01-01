<?php

// Check the raw database values for these reservations
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Raw Database Check for Reservations 893, 894 ===\n\n";

$reservations = DB::table('reservations')
    ->whereIn('id', [893, 894, 895])
    ->select(['id', 'reservable_type', 'reservable_id', 'property_id', 'payment_method', 'status'])
    ->get();

foreach ($reservations as $reservation) {
    echo "Reservation {$reservation->id}:\n";
    echo "  resolvable_type: " . (empty($reservation->resolvable_type) ? 'EMPTY' : "'{$reservation->resolvable_type}'") . "\n";
    echo "  resolvable_id: " . (empty($reservation->resolvable_id) ? 'EMPTY' : $reservation->reservable_id) . "\n";
    echo "  property_id: {$reservation->property_id}\n";
    echo "  payment_method: {$reservation->payment_method}\n";
    echo "  status: {$reservation->status}\n";
    echo "\n";
}

echo "=== Checking Property Data ===\n\n";

// Check if the property_id references exist
$propertyIds = [351, 312]; // From the reservations
$properties = DB::table('propertys')
    ->whereIn('id', $propertyIds)
    ->select(['id', 'title', 'property_classification', 'refund_policy'])
    ->get();

foreach ($properties as $property) {
    echo "Property {$property->id}:\n";
    echo "  title: {$property->title}\n";
    echo "  property_classification: {$property->property_classification}\n";
    echo "  refund_policy: " . ($property->refund_policy ?? 'NULL') . "\n";
    echo "\n";
}

echo "=== Checking Hotel Rooms ===\n\n";

// Check if property_id 351 has any hotel rooms
$rooms = DB::table('hotel_rooms')
    ->where('property_id', 351)
    ->select(['id', 'room_number', 'property_id', 'room_type_id', 'refund_policy'])
    ->get();

foreach ($rooms as $room) {
    echo "Hotel Room {$room->id}:\n";
    echo "  room_number: {$room->room_number}\n";
    echo "  property_id: {$room->property_id}\n";
    echo "  room_type_id: {$room->room_type_id}\n";
    echo "  refund_policy: " . ($room->refund_policy ?? 'NULL') . "\n";
    echo "\n";
}

echo "=== Analysis ===\n";
echo "Reservations 893 and 894 have property_id = 351 but no resolvable_type/resolvable_id.\n";
echo "This suggests they might be property-level reservations, not room-specific.\n";
echo "Property 351 is 'Green hotel 2 testing room only' which appears to be a hotel property.\n";
echo "These might need to be treated as Property reservations with classification 5 (hotel).\n";