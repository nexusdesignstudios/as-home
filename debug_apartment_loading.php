<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;

echo "Testing Apartment Loading:\n\n";

$property = Property::where('property_classification', 4)
    ->with([
        'vacationApartments' => function($query) {
            $query->where('status', 1);
        }
    ])
    ->first();

if ($property) {
    echo "Property ID: {$property->id}\n";
    echo "Property Classification: {$property->property_classification}\n";
    echo "Raw Original Classification: " . ($property->getRawOriginal('property_classification') ?? 'NULL') . "\n";
    echo "Relation Loaded: " . ($property->relationLoaded('vacationApartments') ? 'YES' : 'NO') . "\n";
    
    if ($property->relationLoaded('vacationApartments')) {
        $loaded = $property->getRelation('vacationApartments');
        echo "Loaded Apartments Count: " . ($loaded ? $loaded->count() : 'NULL') . "\n";
    }
    
    echo "Accessing via property->vacationApartments:\n";
    $apts = $property->vacationApartments;
    echo "Type: " . gettype($apts) . "\n";
    if (is_object($apts) && method_exists($apts, 'count')) {
        echo "Count: " . $apts->count() . "\n";
    } else {
        echo "Value: " . ($apts ?? 'NULL') . "\n";
    }
    
    // Try direct relationship access
    echo "\nDirect relationship access:\n";
    $directApts = $property->vacationApartments()->where('status', 1)->get();
    echo "Direct query count: " . $directApts->count() . "\n";
}

