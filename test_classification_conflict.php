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

// Test Studio filter WITHOUT property_classification filter
$apiController = new ApiController();

echo "=== Testing Studio Filter WITHOUT property_classification ===\n";
$requestStudio = new Request([
    'offset' => 0,
    'limit' => 10,
    'bedrooms' => '0'
    // Note: NO property_classification filter
]);

$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

echo "Total properties found: " . ($dataStudio['total'] ?? 'unknown') . "\n";
echo "Properties returned: " . count($dataStudio['data'] ?? []) . "\n";

$found333 = false;
if (isset($dataStudio['data']) && is_array($dataStudio['data'])) {
    foreach ($dataStudio['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}, Classification: {$property['property_classification']}\n";
        if ($property['id'] == 333) {
            $found333 = true;
        }
    }
}

echo $found333 ? "❌ Property 333 found in Studio filter!\n" : "✅ Property 333 NOT found in Studio filter\n";

echo "\n=== Testing Studio Filter WITH property_classification = 4 ===\n";
$requestStudioWithClass = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4,
    'bedrooms' => '0'
]);

$responseStudioWithClass = $apiController->get_property($requestStudioWithClass);
$dataStudioWithClass = is_array($responseStudioWithClass) ? $responseStudioWithClass : json_decode($responseStudioWithClass->getContent(), true);

echo "Total properties found: " . ($dataStudioWithClass['total'] ?? 'unknown') . "\n";
echo "Properties returned: " . count($dataStudioWithClass['data'] ?? []) . "\n";

$found333WithClass = false;
if (isset($dataStudioWithClass['data']) && is_array($dataStudioWithClass['data'])) {
    foreach ($dataStudioWithClass['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}, Classification: {$property['property_classification']}\n";
        if ($property['id'] == 333) {
            $found333WithClass = true;
        }
    }
}

echo $found333WithClass ? "❌ Property 333 found in Studio filter with vacation homes!\n" : "✅ Property 333 NOT found in Studio filter with vacation homes\n";