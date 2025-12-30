<?php

// Check actual vacation apartments data
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;

echo "=== Checking Actual Vacation Apartments Data ===\n\n";

// Check all vacation apartments for property 333
$vacationApts = VacationApartment::where('property_id', 333)->get();

echo "Property 333 Vacation Apartments (count: {$vacationApts->count()}):\n";
foreach ($vacationApts as $apt) {
    echo "- ID: {$apt->id}\n";
    echo "  Unit: {$apt->unit}\n";
    echo "  Title: {$apt->title}\n";
    echo "  Bedrooms: {$apt->bedrooms}\n";
    echo "  Bathrooms: {$apt->bathrooms}\n\n";
}

// Check property classification
echo "Property 333 Classification: " . Property::find(333)->property_classification . "\n\n";

// Test the actual relationship
$property = Property::find(333);
echo "Using relationship: " . $property->vacationApartments()->count() . " apartments\n";
echo "Using direct query: " . VacationApartment::where('property_id', 333)->count() . " apartments\n";

// Check if there's a relationship issue
echo "\n=== Checking Relationship Definition ===\n";
$reflection = new ReflectionMethod($property, 'vacationApartments');
echo "Relationship method exists: " . ($reflection ? 'YES' : 'NO') . "\n";

// Test with fresh query
$freshProperty = Property::with('vacationApartments')->find(333);
echo "Fresh query vacation apartments: " . $freshProperty->vacationApartments->count() . "\n";

// Check all properties with vacation apartments
echo "\n=== All Properties with Vacation Apartments ===\n";
$allProps = Property::has('vacationApartments')->get();
foreach ($allProps as $prop) {
    echo "Property {$prop->id}: {$prop->title} ({$prop->vacationApartments->count()} apartments)\n";
}