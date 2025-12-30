<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture(),
    Illuminate\Http\Request::capture()
);

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

// Test Studio filter directly via API
$apiController = new ApiController();

// Test Studio filter
$requestStudio = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '0'
]);

echo "=== Testing Studio Filter (bedrooms = 0) ===\n";
$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

echo "Total properties found: " . ($dataStudio['total'] ?? 'unknown') . "\n";
echo "Properties returned: " . count($dataStudio['data'] ?? []) . "\n";

if (isset($dataStudio['data']) && is_array($dataStudio['data'])) {
    foreach ($dataStudio['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}\n";
        if ($property['id'] == 333) {
            echo "❌ Property 333 found in Studio filter!\n";
            echo "Property classification: {$property['property_classification']}\n";
        }
    }
}

echo "\n=== Testing 1 Bedroom Filter (bedrooms = 1) ===\n";
$request1Bed = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '1'
]);

$response1Bed = $apiController->get_property($request1Bed);
$data1Bed = is_array($response1Bed) ? $response1Bed : json_decode($response1Bed->getContent(), true);

echo "Total properties found: " . ($data1Bed['total'] ?? 'unknown') . "\n";
echo "Properties returned: " . count($data1Bed['data'] ?? []) . "\n";

if (isset($data1Bed['data']) && is_array($data1Bed['data'])) {
    foreach ($data1Bed['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}\n";
        if ($property['id'] == 333) {
            echo "✅ Property 333 found in 1-bedroom filter!\n";
        }
    }
}