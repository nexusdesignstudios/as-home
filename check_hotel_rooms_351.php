<?php

// Check hotel rooms for property 351
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Hotel Rooms for Property 351 ===\n\n";

$hotelRooms = DB::table('hotel_rooms')
    ->where('property_id', 351)
    ->select(['id', 'room_number', 'property_id', 'room_type_id', 'refund_policy'])
    ->get();

echo "Hotel Rooms for Property 351:\n";
foreach ($hotelRooms as $room) {
    echo "  Room ID: {$room->id}\n";
    echo "  Room Number: {$room->room_number}\n";
    echo "  Property ID: {$room->property_id}\n";
    echo "  Room Type ID: {$room->room_type_id}\n";
    echo "  Refund Policy: {$room->refund_policy}\n";
    echo "\n";
}

// Check room types
echo "=== Room Types ===\n";
$roomTypes = DB::table('room_types')
    ->whereIn('id', $hotelRooms->pluck('room_type_id'))
    ->select(['id', 'name'])
    ->get();

foreach ($roomTypes as $type) {
    echo "  Room Type ID: {$type->id}\n";
    echo "  Name: {$type->name}\n";
    echo "\n";
}

echo "=== Analysis ===\n";
echo "Reservations 893 and 894 have resolvable_id = 351 (property ID), but it should be a hotel room ID.\n";
echo "We need to update their resolvable_id to point to actual hotel room IDs.\n";