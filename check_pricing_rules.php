<?php

use App\Models\Property;
use App\Models\HotelRoom;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slug = 'hurghada-hotel-malorca-5-stars';
$property = Property::where('slug_id', $slug)->first();

if (!$property) {
    echo "Property not found for slug: $slug\n";
    exit;
}

echo "Property ID: " . $property->id . "\n";
echo "Title: " . $property->title . "\n";
echo "Classification: " . $property->property_classification . "\n";

$rooms = HotelRoom::where('property_id', $property->id)->get();

echo "Status: " . $property->status . "\n";
echo "Request Status: " . $property->request_status . "\n";
echo "Found " . $rooms->count() . " rooms.\n";

foreach ($rooms as $room) {
    echo "Room ID: " . $room->id . "\n";
    echo "Room Name: " . ($room->roomType ? $room->roomType->name : 'Custom') . "\n";
    echo "Base Price: " . $room->price_per_night . "\n";
    echo "Base Guests: " . $room->base_guests . "\n";
    echo "Guest Pricing Rules: " . json_encode($room->guest_pricing_rules) . "\n";
    echo "----------------------------------------\n";
}
