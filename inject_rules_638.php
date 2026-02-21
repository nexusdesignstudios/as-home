<?php

use App\Models\Property;
use App\Models\HotelRoom;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Injecting rules for Property ID: 638 (Mallorca Hotel)\n";
$property = Property::find(638);

if (!$property) {
    echo "Property 638 not found!\n";
    exit(1);
}

$rooms = HotelRoom::where('property_id', $property->id)->get();
echo "Found " . $rooms->count() . " rooms.\n";

$rules = [
    3 => 10,
    4 => 20,
    5 => 30
];

// Format as array of objects for the model cast
$rulesArray = [];
foreach ($rules as $count => $percent) {
    $rulesArray[] = [
        'guest_count' => $count,
        'percentage_adjustment' => $percent
    ];
}

foreach ($rooms as $room) {
    echo "Updating Room ID: {$room->id}...\n";
    
    $room->base_guests = 2;
    $room->min_guests = 1;
    $room->max_guests = 5;
    $room->guest_pricing_rules = $rulesArray;
    
    $room->save();
    echo "  Updated base_guests=2, max=5, and injected rules.\n";
}

echo "Done.\n";
