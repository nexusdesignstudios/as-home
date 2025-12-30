<?php

// Test the corrected logic
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

echo "=== Corrected Logic Test ===\n\n";

$property = Property::with(['assignParameter.parameter'])->find(333);

echo "Property 333: {$property->title}\n";
echo "Classification: {$property->property_classification}\n";
echo "Has vacation apartments: NO (confirmed)\n";
echo "Assign Parameter 'Bedroom': " . $property->assignParameter->where('parameter.name', 'Bedroom')->first()->value . "\n\n";

echo "=== Expected Behavior ===\n";
echo "1. Studio filter (bedrooms = 0): Should NOT show property 333\n";
echo "   - Reason: Studio filter excludes vacation homes (property_classification != 4)\n\n";
echo "2. 1 bedroom filter (bedrooms = 1): Should show property 333\n";
echo "   - Reason: It's a vacation home with no vacation apartments, so falls back to assign_parameters\n";
echo "   - But assign_parameters has 'studio', not '1', so should NOT match\n\n";

// Test actual behavior
$apiController = new ApiController();

// Test Studio filter
echo "=== Testing Studio Filter ===\n";
$requestStudio = new Request([
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
        echo "❌ Property 333 found in Studio filter (WRONG!)\n";
    }
}
if (!$found333Studio) {
    echo "✅ Property 333 NOT found in Studio filter (CORRECT!)\n";
}

// Test 1 bedroom filter
echo "\n=== Testing 1 Bedroom Filter ===\n";
$request1Bed = new Request([
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
        echo "❌ Property 333 found in 1 bedroom filter (WRONG!)\n";
        echo "   Should not appear because assign_parameters has 'studio', not '1'\n";
    }
}
if (!$found333_1Bed) {
    echo "✅ Property 333 NOT found in 1 bedroom filter (CORRECT!)\n";
}

echo "\n=== Summary ===\n";
if (!$found333Studio && !$found333_1Bed) {
    echo "✅ PERFECT: Property 333 appears in NEITHER filter (as expected)\n";
    echo "   - Studio filter correctly excludes vacation homes\n";
    echo "   - 1 bedroom filter correctly excludes 'studio' value\n";
} else {
    echo "❌ ISSUE: Property 333 appears in wrong filters\n";
}