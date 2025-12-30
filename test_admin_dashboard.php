<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\PropertController;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request for testing
$request = Request::create('/getPropertyList', 'GET', [
    'sort' => 'id',
    'order' => 'desc',
    'offset' => 0,
    'limit' => 10,
    'search' => '',
    'status' => '',
    'category' => '',
    'property_type' => '',
    'property_classification' => '',
    'property_added_by' => '',
    'property_accessibility' => '',
    'customerID' => ''
]);

// Handle the request through the kernel
$response = $kernel->handle($request);

// Create PropertController instance
$controller = new PropertController();

// Test the getPropertyList method directly
echo "=== Testing Admin Dashboard getPropertyList ===\n";
echo "Request parameters:\n";
print_r($request->all());

try {
    $result = $controller->getPropertyList($request);
    
    echo "\n=== Response ===\n";
    
    // Check if it's a JSON response
    if (method_exists($result, 'getData')) {
        $data = $result->getData();
        echo "Total records: " . ($data->total ?? 'unknown') . "\n";
        echo "Rows count: " . (isset($data->rows) ? count($data->rows) : '0') . "\n";
        
        if (isset($data->rows) && count($data->rows) > 0) {
            echo "\nFirst few properties:\n";
            foreach (array_slice($data->rows, 0, 3) as $property) {
                echo "- ID: {$property->id}, Title: {$property->title}, Classification: " . ($property->property_classification ?? 'null') . "\n";
            }
        } else {
            echo "No properties found!\n";
        }
        
        echo "\nFull response:\n";
        print_r($data);
    } else {
        echo "Unexpected response type:\n";
        var_dump($result);
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}