<?php

// Test the API controller directly to debug the issue
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

echo "=== Testing API Controller Directly ===\n\n";

$apiController = new ApiController();

// Test 1: Basic request
echo "1. Testing basic request (no filters):\n";
$request1 = new Request([
    'offset' => 0,
    'limit' => 5
]);

try {
    $response1 = $apiController->get_property($request1);
    $data1 = is_array($response1) ? $response1 : json_decode($response1->getContent(), true);
    
    echo "Total properties: " . ($data1['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data1['returnedProperties'] ?? 0) . "\n";
    
    if (isset($data1['properties']) && count($data1['properties']) > 0) {
        echo "Sample properties:\n";
        foreach ($data1['properties'] as $prop) {
            echo "- ID: {$prop['id']}, Title: {$prop['title']}\n";
        }
    } else {
        echo "❌ No properties found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Studio filter for vacation homes
echo "\n2. Testing studio filter for vacation homes:\n";
$request2 = new Request([
    'bedrooms' => 'studio',
    'property_classification' => '4',
    'property_type' => '1',
    'offset' => 0,
    'limit' => 10
]);

try {
    $response2 = $apiController->get_property($request2);
    $data2 = is_array($response2) ? $response2 : json_decode($response2->getContent(), true);
    
    echo "Total properties: " . ($data2['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data2['returnedProperties'] ?? 0) . "\n";
    
    $studioFound = false;
    if (isset($data2['properties']) && count($data2['properties']) > 0) {
        echo "Properties found:\n";
        foreach ($data2['properties'] as $prop) {
            echo "- ID: {$prop['id']}, Title: {$prop['title']}, Bedrooms: {$prop['bedroomsFromParams']}\n";
            if ($prop['id'] == 333) {
                $studioFound = true;
            }
        }
    } else {
        echo "❌ No properties found\n";
    }
    
    echo "Property 333 found: " . ($studioFound ? "✅ YES" : "❌ NO") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: 1 bedroom filter for vacation homes
echo "\n3. Testing 1 bedroom filter for vacation homes:\n";
$request3 = new Request([
    'bedrooms' => '1',
    'property_classification' => '4',
    'property_type' => '1',
    'offset' => 0,
    'limit' => 10
]);

try {
    $response3 = $apiController->get_property($request3);
    $data3 = is_array($response3) ? $response3 : json_decode($response3->getContent(), true);
    
    echo "Total properties: " . ($data3['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data3['returnedProperties'] ?? 0) . "\n";
    
    $oneBedFound = false;
    if (isset($data3['properties']) && count($data3['properties']) > 0) {
        echo "Properties found:\n";
        foreach ($data3['properties'] as $prop) {
            echo "- ID: {$prop['id']}, Title: {$prop['title']}, Bedrooms: {$prop['bedroomsFromParams']}\n";
            if ($prop['id'] == 333) {
                $oneBedFound = true;
            }
        }
    } else {
        echo "❌ No properties found\n";
    }
    
    echo "Property 333 found: " . ($oneBedFound ? "✅ YES" : "❌ NO") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "Based on controller tests:\n";
echo "- Basic request returns data: " . ((isset($data1['totalProperties']) && $data1['totalProperties'] > 0) ? "✅ YES" : "❌ NO") . "\n";
echo "- Studio filter finds property 333: " . ($studioFound ? "✅ YES" : "❌ NO") . "\n";
echo "- 1 bedroom filter finds property 333: " . ($oneBedFound ? "✅ YES" : "❌ NO") . "\n";