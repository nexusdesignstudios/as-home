<?php

// Check specific unit 303 details
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;

echo "=== Checking Unit 303 Details ===\n\n";

// Find property 333 and check unit 303
$property = Property::with(['assignParameter.parameter', 'vacationApartments'])
    ->find(333);

echo "Property 333: {$property->title}\n";
echo "Classification: {$property->property_classification}\n\n";

// Check all units
echo "All Vacation Apartments:\n";
foreach ($property->vacationApartments as $apt) {
    echo "- Unit: {$apt->unit}\n";
    echo "  Bedrooms: {$apt->bedrooms}\n";
    echo "  Bathrooms: {$apt->bathrooms}\n";
    echo "  Title: {$apt->title}\n\n";
}

// Check if there's a unit 303
$unit303 = $property->vacationApartments->where('unit', '303')->first();
if ($unit303) {
    echo "✓ Found Unit 303:\n";
    echo "  Bedrooms: {$unit303->bedrooms}\n";
    echo "  Bathrooms: {$unit303->bathrooms}\n";
    echo "  Title: {$unit303->title}\n";
} else {
    echo "✗ Unit 303 not found in vacation_apartments table\n";
}

// Check assign_parameters for studio
echo "\nAssign Parameters for 'Bedroom':\n";
$bedroomParam = $property->assignParameter->where('parameter.name', 'Bedroom')->first();
if ($bedroomParam) {
    echo "  Value: {$bedroomParam->value}\n";
}

echo "\n=== Understanding the Issue ===\n";
echo "The user sees 'Studio' for unit 303, but this comes from assign_parameters, not vacation_apartments.\n";
echo "For vacation homes, we should prioritize vacation_apartments data over assign_parameters.\n";

// Test current API behavior
echo "\n=== Testing Current API Behavior ===\n";

$apiController = new \App\Http\Controllers\ApiController();

// Test Studio filter
echo "Studio filter (bedrooms = 0):\n";
$requestStudio = new \Illuminate\Http\Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '0'
]);

$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

$found333Studio = false;
foreach ($dataStudio['data'] ?? [] as $prop) {
    if ($prop['id'] == 333) {
        $found333Studio = true;
        echo "  ✓ Property 333 found in Studio filter\n";
        echo "    Reason: assign_parameters has 'studio' value\n";
    }
}

// Test 1 bedroom filter  
echo "\n1 bedroom filter (bedrooms = 1):\n";
$request1Bed = new \Illuminate\Http\Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '1'
]);

$response1Bed = $apiController->get_property($request1Bed);
$data1Bed = is_array($response1Bed) ? $response1Bed : json_decode($response1Bed->getContent(), true);

$found333_1Bed = false;
foreach ($data1Bed['data'] ?? [] as $prop) {
    if ($prop['id'] == 333) {
        $found333_1Bed = true;
        echo "  ✓ Property 333 found in 1 bedroom filter\n";
        echo "    Reason: vacation_apartments has units with 1 bedroom\n";
    }
}

echo "\n=== The Problem ===\n";
if ($found333Studio && $found333_1Bed) {
    echo "❌ Property 333 appears in BOTH filters because:\n";
    echo "   1. Studio filter: matches assign_parameters 'studio' value\n";
    echo "   2. 1 bedroom filter: matches vacation_apartments 1-bedroom units\n";
    echo "\n✅ Solution: For vacation homes, prioritize vacation_apartments over assign_parameters\n";
    echo "   - If property has vacation_apartments, use those for bedroom filtering\n";
    echo "   - Only use assign_parameters if no vacation_apartments exist\n";
}