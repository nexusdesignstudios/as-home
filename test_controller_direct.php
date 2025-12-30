<?php

// Direct test of the ApiController method without HTTP
require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Request::create('/api/get_property', 'GET', [
    'bedrooms' => '1',
    'property_classification' => '4',
    'property_type' => '1',
    'category_id' => 'all categories (not filtered)',
    'bathrooms' => 'none',
    'facilities' => 'none',
    'location' => 'none',
    'priceRange' => 'none'
]);

// Set up the application to handle the request
$app->instance('request', $request);
$kernel->handle($request);

// Create controller instance
$controller = new ApiController();

// Call the method directly
echo "=== Testing ApiController::get_property directly ===\n";
echo "Parameters: bedrooms=1, property_classification=4\n\n";

try {
    $response = $controller->get_property($request);
    
    // Handle different response types
    if (is_array($response)) {
        $data = $response;
    } elseif (method_exists($response, 'getData')) {
        $data = $response->getData(true);
    } else {
        $data = (array) $response;
    }
    
    echo "Response Type: " . gettype($response) . "\n";
    echo "Response Keys: " . json_encode(array_keys($data)) . "\n";
    
    if (isset($data['status'])) {
        echo "Response Status: " . $data['status'] . "\n";
    }
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Total Properties: " . count($data['data']) . "\n\n";
        
        foreach ($data['data'] as $property) {
            echo "Property ID: {$property['id']}\n";
            echo "Title: {$property['title']}\n";
            echo "Property Classification: {$property['property_classification']}\n";
            echo "Bedrooms From Params: {$property['bedroomsFromParams']}\n";
            echo "---\n";
        }
    } else {
        echo "No properties found or invalid response format\n";
        echo "Full response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}