<?php

// Test the actual API endpoint that's being called by the admin dashboard
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing Admin Dashboard API Endpoint ===\n";

// Test the getPropertyList API endpoint directly
echo "Testing getPropertyList API endpoint...\n";

// Create a mock request like the admin dashboard would make
$request = new \Illuminate\Http\Request();
$request->merge([
    'offset' => 0,
    'limit' => 10,
    'sort' => 'id',
    'order' => 'desc',
    'search' => '',
    'status' => '',
    'category' => '',
    'property_type' => '',
    'property_classification' => '',
    'property_added_by' => '',
    'property_accessibility' => '',
    'customerID' => ''
]);

$controller = new \App\Http\Controllers\PropertController();

try {
    $response = $controller->getPropertyList($request);
    $data = $response->getData();
    
    echo "API Response Status: ✅ SUCCESS\n";
    echo "Total properties: " . ($data->total ?? 'unknown') . "\n";
    echo "Properties in response: " . (isset($data->rows) ? count($data->rows) : 0) . "\n";
    
    if (isset($data->rows) && count($data->rows) > 0) {
        echo "✅ Properties found in database\n";
        echo "First property ID: " . $data->rows[0]->id . "\n";
        echo "First property title: " . $data->rows[0]->title . "\n";
        echo "First property status: " . $data->rows[0]->status . "\n";
        
        // Show all property IDs in the response
        echo "Property IDs in response: ";
        foreach ($data->rows as $property) {
            echo $property->id . " ";
        }
        echo "\n";
        
    } else {
        echo "❌ No properties found in database\n";
        
        // Let's check what the actual SQL query looks like
        echo "\n=== Debugging SQL Query ===\n";
        
        // Test a simple property count
        $totalCount = \App\Models\Property::count();
        echo "Total properties in database: " . $totalCount . "\n";
        
        // Test with no filters applied
        $query = \App\Models\Property::with('category')
            ->with('customer:id,name,mobile')
            ->with('assignParameter.parameter')
            ->with('interested_users.customer:id,name,email,mobile')
            ->with('documents')
            ->with('gallery')
            ->with('advertisement')
            ->orderBy('id', 'desc');
            
        $total = $query->count();
        $properties = $query->offset(0)->limit(10)->get();
        
        echo "Direct query total: " . $total . "\n";
        echo "Direct query count: " . count($properties) . "\n";
        
        if (count($properties) > 0) {
            echo "✅ Direct query found properties!\n";
            echo "First property ID: " . $properties[0]->id . "\n";
            echo "First property title: " . $properties[0]->title . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error calling getPropertyList: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}