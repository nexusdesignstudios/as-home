<?php

use App\Models\Property;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$properties = Property::where('title', 'LIKE', '%alorca%')
    ->orWhere('slug_id', 'LIKE', '%alorca%')
    ->get();

if ($properties->isEmpty()) {
    echo "No properties found matching 'alorca'. Listing first 5 properties:\n";
    $properties = Property::take(5)->get();
}

echo "Found " . $properties->count() . " properties.\n";

foreach ($properties as $p) {
    echo "ID: " . $p->id . "\n";
    echo "Title: " . $p->title . "\n";
    echo "Slug: " . $p->slug_id . "\n";
    echo "Status: " . $p->status . "\n";
    echo "Request Status: " . $p->request_status . "\n";
    echo "Property Classification: " . $p->property_classification . "\n";
    
    echo "Hotel Rooms Count: " . $p->hotelRooms()->count() . "\n";
    foreach($p->hotelRooms as $room) {
        echo "  Room ID: " . $room->id . "\n";
        echo "  Room Title: " . $room->room_title . "\n";
        echo "  Base Guests: " . $room->base_guests . "\n";
        echo "  Guest Pricing Rules: " . json_encode($room->guest_pricing_rules) . "\n";
    }
    echo "--------------------------------\n";
}
