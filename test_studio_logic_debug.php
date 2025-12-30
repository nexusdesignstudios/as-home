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

// Test the exact query logic
$apiController = new ApiController();

echo "=== Testing Studio Filter Logic Step by Step ===\n";

// First, let's see what properties have property_classification != 4
$requestNoVacation = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => '1,2,3' // Exclude vacation homes
]);

$responseNoVacation = $apiController->get_property($requestNoVacation);
$dataNoVacation = is_array($responseNoVacation) ? $responseNoVacation : json_decode($responseNoVacation->getContent(), true);

echo "Properties with classification != 4 (regular properties):\n";
if (isset($dataNoVacation['data']) && is_array($dataNoVacation['data'])) {
    foreach ($dataNoVacation['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}, Classification: {$property['property_classification']}\n";
    }
}

echo "\n=== Now testing Studio filter (should only show regular properties) ===\n";

// Test Studio filter
$requestStudio = new Request([
    'offset' => 0,
    'limit' => 10,
    'bedrooms' => '0'
]);

$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

echo "Studio filter results:\n";
$found333 = false;
if (isset($dataStudio['data']) && is_array($dataStudio['data'])) {
    foreach ($dataStudio['data'] as $property) {
        echo "Property ID: {$property['id']}, Title: {$property['title']}, Classification: {$property['property_classification']}\n";
        if ($property['id'] == 333) {
            $found333 = true;
            echo "  ❌ Property 333 is a vacation home (classification 4) but appears in Studio filter!\n";
        }
    }
}

echo $found333 ? "\n❌ ISSUE CONFIRMED: Studio filter is including vacation homes\n" : "\n✅ Studio filter correctly excludes vacation homes\n";