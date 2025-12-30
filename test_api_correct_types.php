<?php

// Test API with correct property_type values
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

echo "=== Testing API with Correct Property Types ===\n\n";

$apiController = new ApiController();

// Test 1: With property_type = 'rent' (actual database value)
echo "1. Testing with property_type = 'rent' (actual database value):\n";
$request1 = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_type' => 'rent',  // Use actual database value
    'property_classification' => 4
]);

$response1 = $apiController->get_property($request1);
$data1 = is_array($response1) ? $response1 : json_decode($response1->getContent(), true);

echo "   Response status: " . ($data1['status'] ?? 'unknown') . "\n";
echo "   Total properties: " . ($data1['total'] ?? 0) . "\n";
if (isset($data1['data']) && is_array($data1['data'])) {
    echo "   Properties found: " . count($data1['data']) . "\n";
    foreach ($data1['data'] as $i => $prop) {
        echo "   Property {$i}: ID {$prop['id']}, Title: {$prop['title']}\n";
    }
}

// Test 2: With property_type = 1 (what API might expect)
echo "\n2. Testing with property_type = 1 (integer):\n";
$request2 = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_type' => 1,  // Integer value
    'property_classification' => 4
]);

$response2 = $apiController->get_property($request2);
$data2 = is_array($response2) ? $response2 : json_decode($response2->getContent(), true);

echo "   Response status: " . ($data2['status'] ?? 'unknown') . "\n";
echo "   Total properties: " . ($data2['total'] ?? 0) . "\n";
if (isset($data2['data']) && is_array($data2['data'])) {
    echo "   Properties found: " . count($data2['data']) . "\n";
    foreach ($data2['data'] as $i => $prop) {
        echo "   Property {$i}: ID {$prop['id']}, Title: {$prop['title']}\n";
    }
}

// Test 3: Without property_type filter
echo "\n3. Testing without property_type filter (all vacation homes):\n";
$request3 = new Request([
    'offset' => 0,
    'limit' => 10,
    'property_classification' => 4  // Only vacation homes
]);

$response3 = $apiController->get_property($request3);
$data3 = is_array($response3) ? $response3 : json_decode($response3->getContent(), true);

echo "   Response status: " . ($data3['status'] ?? 'unknown') . "\n";
echo "   Total properties: " . ($data3['total'] ?? 0) . "\n";
if (isset($data3['data']) && is_array($data3['data'])) {
    echo "   Properties found: " . count($data3['data']) . "\n";
    foreach ($data3['data'] as $i => $prop) {
        echo "   Property {$i}: ID {$prop['id']}, Title: {$prop['title']}\n";
    }
}