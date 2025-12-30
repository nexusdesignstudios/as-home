<?php

// Test the actual API with the fixed logic
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing API with Fixed Logic ===\n";

// Create a request to the API controller
$controller = new \App\Http\Controllers\ApiController();

// Test 1 bedroom filter
$request = new \Illuminate\Http\Request();
$request->merge([
    'property_classification' => '4', // Vacation homes
    'bedrooms' => '1',
    'property_type' => '1'
]);

try {
    $response = $controller->get_property($request);
    $data = $response->getData(true); // Get as array
    
    echo "1 Bedroom Filter Results:\n";
    echo "Total properties: " . $data['total'] . "\n";
    echo "Returned properties: " . count($data['data']) . "\n";
    
    $found333 = false;
    foreach ($data['data'] as $property) {
        if ($property['id'] == 333) {
            $found333 = true;
            echo "✅ Property 333 found! Title: " . $property['title'] . "\n";
            echo "   Bedrooms from params: " . $property['bedroomsFromParams'] . "\n";
            break;
        }
    }
    
    if (!$found333) {
        echo "❌ Property 333 not found in 1BR filter\n";
    }
    
} catch (\Exception $e) {
    echo "Error testing 1BR filter: " . $e->getMessage() . "\n";
}

echo "\n";

// Test studio filter
$request = new \Illuminate\Http\Request();
$request->merge([
    'property_classification' => '4', // Vacation homes
    'bedrooms' => '0',
    'property_type' => '1'
]);

try {
    $response = $controller->get_property($request);
    $data = $response->getData(true); // Get as array
    
    echo "Studio Filter Results:\n";
    echo "Total properties: " . $data['total'] . "\n";
    echo "Returned properties: " . count($data['data']) . "\n";
    
    $found333 = false;
    foreach ($data['data'] as $property) {
        if ($property['id'] == 333) {
            $found333 = true;
            echo "✅ Property 333 found! Title: " . $property['title'] . "\n";
            echo "   Bedrooms from params: " . $property['bedroomsFromParams'] . "\n";
            break;
        }
    }
    
    if (!$found333) {
        echo "❌ Property 333 not found in studio filter\n";
    }
    
} catch (\Exception $e) {
    echo "Error testing studio filter: " . $e->getMessage() . "\n";
}