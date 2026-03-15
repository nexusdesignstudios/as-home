<?php

use App\Models\Property;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slugs = [
    'hurghada-hotel-malorca-5-stars',
    'malorca-hotel',
    'hotel-sharm-el-seikh'
];

foreach ($slugs as $slug) {
    echo "Checking slug: '$slug'\n";
    $property = Property::where('slug_id', $slug)->first();
    
    if ($property) {
        echo "  Found! ID: {$property->id}\n";
        echo "  Title: {$property->title}\n";
        echo "  Status: {$property->status}\n";
        echo "  Request Status: {$property->request_status}\n";
        echo "  Classification: {$property->property_classification}\n";
        echo "  Cancellation Period: " . ($property->cancellation_period ?? 'NULL') . "\n";
        
        // Check rooms for this property
        $rooms = \App\Models\HotelRoom::where('property_id', $property->id)->get();
        echo "  Room Count: " . $rooms->count() . "\n";
        foreach($rooms as $room) {
             echo "    Room ID: {$room->id}, Base Guests: {$room->base_guests}, Rules: " . json_encode($room->guest_pricing_rules) . "\n";
        }
    } else {
        echo "  Not found.\n";
    }
    echo "---------------------------------------------------\n";
}
