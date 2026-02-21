<?php

use App\Models\Property;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slug = 'malorca-hotel';
$property = Property::where('slug_id', $slug)->first();

if (!$property) {
    echo "Property not found with slug: $slug\n";
    echo "Searching for similar properties...\n";
    $similar = Property::where('title', 'LIKE', '%Mallorca%')
        ->orWhere('slug_id', 'LIKE', '%malorca%')
        ->orWhere('slug_id', 'LIKE', '%mallorca%')
        ->get();
    
    if ($similar->count() > 0) {
        echo "Found similar properties:\n";
        foreach ($similar as $p) {
            echo "- ID: {$p->id}, Title: {$p->title}, Slug: {$p->slug_id}\n";
        }
        $property = $similar->first();
        echo "Using first match: {$property->title}\n";
    } else {
        echo "No similar properties found. Listing first 5 properties:\n";
        $all = Property::take(5)->get();
        foreach ($all as $p) {
             echo "- ID: {$p->id}, Title: {$p->title}, Slug: {$p->slug_id}\n";
        }
        exit(1);
    }
}

echo "Property found: {$property->title}\n";
echo "Classification: {$property->property_classification}\n";

use App\Models\HotelRoom;

// ... existing setup ...

echo "Checking for ANY room with guest_pricing_rules...\n";
$roomWithRules = HotelRoom::whereNotNull('guest_pricing_rules')
    ->where('guest_pricing_rules', '!=', '[]')
    ->where('guest_pricing_rules', '!=', 'null')
    ->first();

if ($roomWithRules) {
    echo "Found a room with rules (ID: {$roomWithRules->id}):\n";
    echo "  Property ID: {$roomWithRules->property_id}\n";
    echo "  Guest Pricing Rules: " . json_encode($roomWithRules->guest_pricing_rules) . "\n";
    
    // Find the property for this room
    $p = Property::find($roomWithRules->property_id);
    if ($p) {
        echo "  Property Title: {$p->title}\n";
        echo "  Property Slug: {$p->slug_id}\n";
    }
} else {
    echo "No rooms found with guest_pricing_rules in the entire database!\n";
}

