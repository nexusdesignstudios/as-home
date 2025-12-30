<?php

// Debug the current filtering logic
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;

echo "=== Debugging Current Logic ===\n\n";

$property = Property::with(['assignParameter.parameter', 'vacationApartments'])
    ->find(333);

echo "Property 333: {$property->title}\n";
echo "Classification: {$property->property_classification}\n";
echo "Has vacation apartments: " . ($property->vacationApartments->count() > 0 ? 'YES' : 'NO') . "\n";
echo "Vacation apartments count: " . $property->vacationApartments->count() . "\n\n";

// Test the logic conditions from my fix
echo "=== Testing Logic Conditions ===\n";

// Condition 1: Is it a vacation home with matching vacation apartments?
echo "1. Vacation home with 1-bedroom apartments:\n";
$has1BedApts = $property->vacationApartments->where('bedrooms', 1)->count() > 0;
echo "   - Is vacation home: " . ($property->property_classification == 4 ? 'YES' : 'NO') . "\n";
echo "   - Has 1-bedroom apartments: " . ($has1BedApts ? 'YES' : 'NO') . "\n";

if ($property->property_classification == 4 && $has1BedApts) {
    echo "   ✓ Would match vacation_apartments condition\n";
} else {
    echo "   ✗ Would NOT match vacation_apartments condition\n";
}

// Condition 2: Fallback to assign_parameters
echo "\n2. Fallback to assign_parameters:\n";
$bedroomParam = $property->assignParameter->where('parameter.name', 'Bedroom')->first();
$hasStudioInParams = $bedroomParam && strtolower(trim($bedroomParam->value)) === 'studio';

if ($property->property_classification != 4 || $property->vacationApartments->count() == 0) {
    echo "   - Would check assign_parameters (not vacation home OR no vacation apartments)\n";
    echo "   - Has 'studio' in assign_parameters: " . ($hasStudioInParams ? 'YES' : 'NO') . "\n";
} else {
    echo "   - Would NOT check assign_parameters (vacation home WITH apartments)\n";
}

echo "\n=== The Issue ===\n";
echo "Property 333 has BOTH:\n";
echo "- vacation_apartments with 1+ bedrooms\n";
echo "- assign_parameters with 'studio'\n";
echo "\nWith my fix, it should:\n";
echo "1. Match vacation_apartments for 1-bedroom filter\n";
echo "2. NOT match assign_parameters for studio filter (because it has vacation_apartments)\n";

echo "\n=== Testing Current API After Fix ===\n";

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
    }
}
if (!$found333Studio) {
    echo "  ✗ Property 333 NOT found in Studio filter (GOOD!)\n";
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
    }
}
if (!$found333_1Bed) {
    echo "  ✗ Property 333 NOT found in 1 bedroom filter\n";
}

echo "\n=== Result ===\n";
if (!$found333Studio && $found333_1Bed) {
    echo "✅ SUCCESS: Fix is working! Property 333 only appears in 1-bedroom filter\n";
} else {
    echo "❌ ISSUE: Fix is not working correctly\n";
}