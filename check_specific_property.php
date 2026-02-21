<?php

use App\Models\Property;
use App\Models\HotelRoom;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Property ID: 638\n";
$property = Property::find(638);

if (!$property) {
    echo "Property 638 not found via ID. Searching by slug 'malorca-hotel'...\n";
    $property = Property::where('slug_id', 'like', '%malorca-hotel%')->first();
}

if ($property) {
    echo "Property Found: {$property->title} (ID: {$property->id})\n";
    echo "Slug: {$property->slug_id}\n";
    
    $rooms = HotelRoom::where('property_id', $property->id)->get();
    echo "Found " . $rooms->count() . " rooms.\n";

    foreach ($rooms as $room) {
        echo "---------------------------------------------------\n";
        echo "Room ID: {$room->id}\n";
        echo "  Room Type ID: {$room->room_type_id}\n";
        echo "  Price: {$room->price_per_night}\n";
        echo "  Base Guests: {$room->base_guests}\n";
        
        $rawRules = $room->getAttributes()['guest_pricing_rules'] ?? 'NULL';
        echo "  Raw guest_pricing_rules in DB: " . (is_string($rawRules) ? $rawRules : json_encode($rawRules)) . "\n";
        
        $castedRules = $room->guest_pricing_rules;
        echo "  Casted guest_pricing_rules: " . json_encode($castedRules) . "\n";
    }
} else {
    echo "Property not found.\n";
}
