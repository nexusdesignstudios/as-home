<?php

// Test bedroom filtering with property 333 specifically
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

echo "=== Testing Bedroom Filter for Property 333 ===\n\n";

$apiController = new ApiController();

// First, let's check property 333 details
echo "1. Property 333 details:\n";
$prop333 = \App\Models\Property::with(['assignParameter.parameter', 'vacationApartments'])
    ->find(333);

echo "   - Title: {$prop333->title}\n";
echo "   - Property Type: {$prop333->propery_type}\n";
echo "   - Classification: {$prop333->property_classification}\n";

// Check assign_parameters
echo "   - Assign Parameters:\n";
foreach ($prop333->assignParameter as $param) {
    echo "     * {$param->parameter->name}: {$param->value}\n";
}

// Check vacation_apartments
echo "   - Vacation Apartments:\n";
foreach ($prop333->vacationApartments as $apt) {
    echo "     * Unit {$apt->unit}: {$apt->bedrooms} bedrooms, {$apt->bathrooms} bathrooms\n";
}

// Test filters
echo "\n2. Testing bedroom filters:\n";

// Test Studio filter (bedrooms = 0)
echo "   Studio filter (bedrooms = 0):\n";
$requestStudio = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '0'
]);

$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

echo "   Found " . count($dataStudio['data'] ?? []) . " properties\n";
$found333Studio = false;
foreach ($dataStudio['data'] ?? [] as $prop) {
    if ($prop['id'] == 333) {
        $found333Studio = true;
        echo "   ✓ Property 333 found in Studio filter\n";
    }
}
if (!$found333Studio) {
    echo "   ✗ Property 333 NOT found in Studio filter\n";
}

// Test 1 bedroom filter
echo "\n   1 bedroom filter (bedrooms = 1):\n";
$request1Bed = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '1'
]);

$response1Bed = $apiController->get_property($request1Bed);
$data1Bed = is_array($response1Bed) ? $response1Bed : json_decode($response1Bed->getContent(), true);

echo "   Found " . count($data1Bed['data'] ?? []) . " properties\n";
$found333_1Bed = false;
foreach ($data1Bed['data'] ?? [] as $prop) {
    if ($prop['id'] == 333) {
        $found333_1Bed = true;
        echo "   ✓ Property 333 found in 1 bedroom filter\n";
    }
}
if (!$found333_1Bed) {
    echo "   ✗ Property 333 NOT found in 1 bedroom filter\n";
}

echo "\n=== Summary ===\n";
if ($found333Studio && $found333_1Bed) {
    echo "❌ ISSUE: Property 333 appears in BOTH Studio and 1 bedroom filters\n";
    echo "   This confirms the filtering conflict reported by the user\n";
} elseif (!$found333Studio && !$found333_1Bed) {
    echo "❌ ISSUE: Property 333 appears in NEITHER filter\n";
} elseif ($found333Studio && !$found333_1Bed) {
    echo "✅ Property 333 appears only in Studio filter (correct)\n";
} elseif (!$found333Studio && $found333_1Bed) {
    echo "✅ Property 333 appears only in 1 bedroom filter (correct)\n";
}