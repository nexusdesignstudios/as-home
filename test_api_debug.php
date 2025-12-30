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
    
    echo "Response type: " . gettype($response) . "\n";
    if (is_array($response)) {
        echo "Response is an array\n";
        echo "Keys: " . implode(', ', array_keys($response)) . "\n";
        
        if (isset($response['error']) && $response['error'] === false) {
            echo "1 Bedroom Filter Results:\n";
            echo "Total properties: " . $response['total'] . "\n";
            echo "Returned properties: " . count($response['data']) . "\n";
            
            $found333 = false;
            foreach ($response['data'] as $property) {
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
        } else {
            echo "Error: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "Response is not an array\n";
        var_dump($response);
    }
    
} catch (\Exception $e) {
    echo "Error testing 1BR filter: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}