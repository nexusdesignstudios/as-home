<?php

// Test admin dashboard property list issue
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing Admin Dashboard Property List Issue ===\n";

// Check if user is authenticated
echo "Auth check: " . (Auth::check() ? "✅ Authenticated" : "❌ Not authenticated") . "\n";

if (Auth::check()) {
    $user = Auth::user();
    echo "User ID: " . $user->id . "\n";
    echo "User email: " . $user->email . "\n";
    echo "User type: " . $user->type . "\n";
    echo "User permissions: " . ($user->permissions ?? "null") . "\n";
    
    // Test has_permissions function
    echo "\nTesting permissions:\n";
    echo "has_permissions('read', 'property'): " . (has_permissions('read', 'property') ? "✅ YES" : "❌ NO") . "\n";
    
    if (has_permissions('read', 'property')) {
        echo "✅ User has permission to read properties\n";
        
        // Test the getPropertyList method directly
        echo "\n=== Testing getPropertyList Method ===\n";
        $controller = new \App\Http\Controllers\PropertController();
        
        // Create a mock request
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
        
        try {
            $response = $controller->getPropertyList($request);
            $data = $response->getData();
            
            echo "Total properties: " . ($data->total ?? 'unknown') . "\n";
            echo "Properties in response: " . (isset($data->rows) ? count($data->rows) : 0) . "\n";
            
            if (isset($data->rows) && count($data->rows) > 0) {
                echo "✅ Properties found in database\n";
                echo "First property ID: " . $data->rows[0]->id . "\n";
                echo "First property title: " . $data->rows[0]->title . "\n";
            } else {
                echo "❌ No properties found in database\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error calling getPropertyList: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ User does NOT have permission to read properties\n";
        echo "This would redirect to: " . PERMISSION_ERROR_MSG . "\n";
    }
} else {
    echo "\n❌ User is not authenticated - this is why you're seeing issues!\n";
    echo "The admin dashboard requires authentication to access property data.\n";
}

echo "\n=== PERMISSION_ERROR_MSG ===\n";
echo "You are not authorize to operate on the module " . "\n";