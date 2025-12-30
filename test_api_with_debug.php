<?php

// Test the actual API with detailed debugging
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

$controller = new ApiController();

// Test studio filter (bedrooms=0)
echo "=== Testing STUDIO FILTER (bedrooms=0) ===\n";
$request = Request::create('/api/get_property', 'GET', [
    'bedrooms' => '0',
    'property_classification' => '4',
    'property_type' => '1',
    'category_id' => 'all categories (not filtered)',
    'bathrooms' => 'none',
    'facilities' => 'none',
    'location' => 'none',
    'priceRange' => 'none'
]);

$app->instance('request', $request);

try {
    $response = $controller->get_property($request);
    
    if (is_array($response)) {
        $data = $response;
    } elseif (method_exists($response, 'getData')) {
        $data = $response->getData(true);
    } else {
        $data = (array) $response;
    }
    
    if (isset($data['error'])) {
        echo "Error: {$data['message']}\n";
    } else {
        echo "Total Properties: " . count($data['data'] ?? []) . "\n";
        
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $property) {
                echo "  Property ID: {$property['id']} - {$property['title']}\n";
                echo "  Bedrooms From Params: {$property['bedroomsFromParams']}\n";
                
                // Double-check the actual data
                $actualAssignParams = DB::table('assign_parameters')
                    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                    ->where(function($q) use ($property) {
                        $q->where('assign_parameters.property_id', $property['id'])
                          ->orWhere(function($q2) use ($property) {
                              $q2->where('assign_parameters.modal_id', $property['id'])
                                 ->where('assign_parameters.modal_type', 'like', '%Property%');
                          });
                    })
                    ->where('parameters.name', 'bedrooms')
                    ->select('assign_parameters.value')
                    ->first();
                
                echo "  Actual assign_parameters bedroom: " . ($actualAssignParams ? $actualAssignParams->value : 'None') . "\n";
                echo "  ---\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 1 bedroom filter (bedrooms=1)
echo "\n=== Testing 1 BEDROOM FILTER (bedrooms=1) ===\n";
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

$app->instance('request', $request);

try {
    $response = $controller->get_property($request);
    
    if (is_array($response)) {
        $data = $response;
    } elseif (method_exists($response, 'getData')) {
        $data = $response->getData(true);
    } else {
        $data = (array) $response;
    }
    
    if (isset($data['error'])) {
        echo "Error: {$data['message']}\n";
    } else {
        echo "Total Properties: " . count($data['data'] ?? []) . "\n";
        
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $property) {
                echo "  Property ID: {$property['id']} - {$property['title']}\n";
                echo "  Bedrooms From Params: {$property['bedroomsFromParams']}\n";
                
                // Double-check the actual data
                $actualAssignParams = DB::table('assign_parameters')
                    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                    ->where(function($q) use ($property) {
                        $q->where('assign_parameters.property_id', $property['id'])
                          ->orWhere(function($q2) use ($property) {
                              $q2->where('assign_parameters.modal_id', $property['id'])
                                 ->where('assign_parameters.modal_type', 'like', '%Property%');
                          });
                    })
                    ->where('parameters.name', 'bedrooms')
                    ->select('assign_parameters.value')
                    ->first();
                
                echo "  Actual assign_parameters bedroom: " . ($actualAssignParams ? $actualAssignParams->value : 'None') . "\n";
                echo "  ---\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}