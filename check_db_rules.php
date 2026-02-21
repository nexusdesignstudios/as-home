<?php

use App\Models\Property;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = 'hurghada-hotel-malorca-5-stars';
$property = Property::where('slug_id', $slug)->first();

if (!$property) {
    echo "Property not found for slug: $slug\n";
    exit;
}

echo "Property Found: {$property->title} (ID: {$property->id})\n";

$rooms = HotelRoom::where('property_id', $property->id)->get();

if ($rooms->isEmpty()) {
    echo "No rooms found for this property.\n";
    exit;
}

echo "Found " . $rooms->count() . " rooms.\n";

foreach ($rooms as $room) {
    echo "Room ID: {$room->id}\n";
    echo "  Name: " . ($room->custom_room_type ?? 'N/A') . "\n";
    echo "  Base Price: {$room->price_per_night}\n";
    echo "  Base Guests: {$room->base_guests}\n";
    
    // Check raw attribute first
    $rawRules = $room->getAttributes()['guest_pricing_rules'] ?? 'NULL';
    echo "  Raw guest_pricing_rules in DB: " . (is_string($rawRules) ? $rawRules : json_encode($rawRules)) . "\n";
    
    // Check casted attribute
    $castedRules = $room->guest_pricing_rules;
    echo "  Casted guest_pricing_rules: " . json_encode($castedRules) . "\n";
    echo "---------------------------------------------------\n";
}
