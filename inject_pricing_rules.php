<?php

use App\Models\Property;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the property
echo "Searching for 'Malorca Hotel'...\n";
$property = Property::where('title', 'LIKE', '%Malorca%')->first();

if (!$property) {
    echo "Property not found.\n";
    exit(1);
}

echo "Found Property: " . $property->title . " (ID: " . $property->id . ")\n";

// Find rooms
$rooms = HotelRoom::where('property_id', $property->id)->get();

if ($rooms->isEmpty()) {
    echo "No rooms found for this property.\n";
    exit(1);
}

echo "Found " . $rooms->count() . " rooms.\n";

$targetRoom = $rooms->first();
echo "Updating Room ID: " . $targetRoom->id . "\n";

// JSON rules
$rules = [
    ["guest_count" => 3, "percentage_adjustment" => 10],
    ["guest_count" => 4, "percentage_adjustment" => 20],
    ["guest_count" => 5, "percentage_adjustment" => 30]
];

// Update
try {
    $targetRoom->guest_pricing_rules = $rules;
    $targetRoom->save();
    echo "Successfully updated guest_pricing_rules for Room " . $targetRoom->id . "\n";
    echo "New Rules: " . json_encode($targetRoom->guest_pricing_rules) . "\n";
} catch (\Exception $e) {
    echo "Error updating room: " . $e->getMessage() . "\n";
}
