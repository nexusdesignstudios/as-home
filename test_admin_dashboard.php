<?php

// Test admin dashboard property list issue - comprehensive
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Comprehensive Admin Dashboard Property List Test ===\n";

// Check if user is authenticated
echo "Auth check: " . (Auth::check() ? "✅ Authenticated" : "❌ Not authenticated") . "\n";

if (Auth::check()) {
    $user = Auth::user();
    echo "User ID: " . $user->id . "\n";
    echo "User email: " . $user->email . "\n";
    echo "User type: " . $user->type . "\n";
    echo "User status: " . $user->status . "\n";
    echo "User permissions: " . ($user->permissions ?? "null") . "\n";
    
    // Check CheckLogin middleware requirements
    echo "\n=== CheckLogin Middleware Check ===\n";
    if ($user->status != 0) {
        echo "✅ User status is not 0 - CheckLogin middleware would pass\n";
    } else {
        echo "❌ User status is 0 - CheckLogin middleware would fail\n";
    }
    
    // Test has_permissions function
    echo "\n=== Testing permissions ===\n";
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
        echo "This would redirect with error: You are not authorize to operate on the module \n";
    }
    
    // Check total property count in database
    echo "\n=== Database Property Count ===\n";
    $totalProperties = \App\Models\Property::count();
    echo "Total properties in database: " . $totalProperties . "\n";
    
    $activeProperties = \App\Models\Property::where('status', 1)->count();
    echo "Active properties: " . $activeProperties . "\n";
    
} else {
    echo "\n❌ User is not authenticated - this is why you're seeing issues!\n";
    echo "The admin dashboard requires authentication to access property data.\n";
    echo "\nTo fix this, you need to:\n";
    echo "1. Log in to the admin dashboard first\n";
    echo "2. Make sure your user has the proper permissions\n";
    echo "3. Ensure your user status is not 0 (CheckLogin middleware requirement)\n";
}