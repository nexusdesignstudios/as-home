<?php

// Test both studio and 1 bedroom filters to verify the fix
require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

// Bootstrap Laravel
require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Create controller instance
$controller = new ApiController();

function testFilter($controller, $bedrooms, $description) {
    echo "\n=== Testing $description (bedrooms=$bedrooms) ===\n";
    
    $request = Request::create('/api/get_property', 'GET', [
        'bedrooms' => $bedrooms,
        'property_classification' => '4',
        'property_type' => '1',
        'category_id' => 'all categories (not filtered)',
        'bathrooms' => 'none',
        'facilities' => 'none',
        'location' => 'none',
        'priceRange' => 'none'
    ]);
    
    $app = app();
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
            return;
        }
        
        echo "Total Properties: " . count($data['data'] ?? []) . "\n";
        
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $property) {
                echo "  Property ID: {$property['id']} - {$property['title']}\n";
                echo "  Bedrooms From Params: {$property['bedroomsFromParams']}\n";
                echo "  ---\n";
            }
        }
        
        // Also show properties that should match but don't
        if (count($data['data'] ?? []) === 0) {
            echo "  No properties found for this filter\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Test both filters
testFilter($controller, '0', 'STUDIO FILTER');
testFilter($controller, '1', '1 BEDROOM FILTER');

// Let's also check what properties exist in the database for vacation homes
echo "\n=== Checking Database for Vacation Homes ===\n";
$vacationHomes = DB::table('propertys')
    ->where('property_classification', 4)
    ->where('status', 1)
    ->select('id', 'title', 'propery_type')
    ->get();

echo "Total Vacation Homes in Database: " . count($vacationHomes) . "\n";
foreach ($vacationHomes as $property) {
    echo "  Property ID: {$property->id} - {$property->title}\n";
    
    // Check assign_parameters
    $assignParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) use ($property) {
            $q->where('assign_parameters.property_id', $property->id)
              ->orWhere(function($q2) use ($property) {
                  $q2->where('assign_parameters.modal_id', $property->id)
                     ->where('assign_parameters.modal_type', 'like', '%Property%');
              });
        })
        ->where('parameters.name', 'bedrooms')
        ->select('assign_parameters.value')
        ->first();
    
    if ($assignParams) {
        echo "    Assign Parameters Bedrooms: {$assignParams->parameter_value}\n";
    } else {
        echo "    Assign Parameters Bedrooms: None\n";
    }
    
    // Check vacation_apartments
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', $property->id)
        ->where('status', 1)
        ->select('bedrooms')
        ->get();
    
    if (count($vacationApts) > 0) {
        $bedrooms = array_unique(array_map(function($apt) { return $apt->bedrooms; }, $vacationApts->toArray()));
        echo "    Vacation Apartments Bedrooms: " . implode(', ', $bedrooms) . "\n";
    } else {
        echo "    Vacation Apartments Bedrooms: None\n";
    }
    echo "    ---\n";
}