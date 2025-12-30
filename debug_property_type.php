<?php

// Check property 333 details including property_type
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Property 333 Detailed Check ===\n\n";

$property = Property::find(333);
if (!$property) {
    echo "❌ Property 333 not found\n";
    exit;
}

echo "Property Details:\n";
echo "- ID: {$property->id}\n";
echo "- Title: {$property->title}\n";
echo "- Status: {$property->status}\n";
echo "- Request Status: {$property->request_status}\n";
echo "- Classification: {$property->property_classification}\n";
echo "- Property Type: {$property->propery_type}\n";
echo "- Category ID: {$property->category_id}\n\n";

// Check if property_type filter is the issue
echo "Property Type Analysis:\n";
echo "- Request property_type = 1 (rent): " . ($property->propery_type == 1 ? "✅ MATCH" : "❌ MISMATCH") . "\n";
echo "- Request property_type = 0 (sell): " . ($property->propery_type == 0 ? "✅ MATCH" : "❌ MISMATCH") . "\n\n";

// Test without property_type filter
echo "=== Testing API Without Property Type Filter ===\n\n";

$apiController = new \App\Http\Controllers\ApiController();

// Test studio filter without property_type
echo "1. Studio filter WITHOUT property_type:\n";
$request1 = new \Illuminate\Http\Request([
    'bedrooms' => 'studio',
    'property_classification' => '4',
    'offset' => 0,
    'limit' => 10
]);

try {
    $response1 = $apiController->get_property($request1);
    $data1 = is_array($response1) ? $response1 : json_decode($response1->getContent(), true);
    
    echo "Total properties: " . ($data1['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data1['returnedProperties'] ?? 0) . "\n";
    
    $studioFound = false;
    if (isset($data1['properties']) && count($data1['properties']) > 0) {
        echo "Properties found:\n";
        foreach ($data1['properties'] as $prop) {
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

// Test 1 bedroom filter without property_type
echo "\n2. 1 bedroom filter WITHOUT property_type:\n";
$request2 = new \Illuminate\Http\Request([
    'bedrooms' => '1',
    'property_classification' => '4',
    'offset' => 0,
    'limit' => 10
]);

try {
    $response2 = $apiController->get_property($request2);
    $data2 = is_array($response2) ? $response2 : json_decode($response2->getContent(), true);
    
    echo "Total properties: " . ($data2['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data2['returnedProperties'] ?? 0) . "\n";
    
    $oneBedFound = false;
    if (isset($data2['properties']) && count($data2['properties']) > 0) {
        echo "Properties found:\n";
        foreach ($data2['properties'] as $prop) {
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

echo "\n=== Conclusion ===\n";
echo "If the tests above show property 333, then the issue is with the property_type filter.\n";
echo "Property 333 has property_type = {$property->propery_type}, but the request expects 1.\n";