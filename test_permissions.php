<?php

// Test permissions issue
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing Admin Dashboard Permissions Issue ===\n";

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
    echo "has_permissions('create', 'property'): " . (has_permissions('create', 'property') ? "✅ YES" : "❌ NO") . "\n";
    echo "has_permissions('update', 'property'): " . (has_permissions('update', 'property') ? "✅ YES" : "❌ NO") . "\n";
    echo "has_permissions('delete', 'property'): " . (has_permissions('delete', 'property') ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "\n❌ User is not authenticated - this is why you're seeing the login page!\n";
    echo "The admin dashboard requires authentication to access property data.\n";
}

echo "\n=== PERMISSION_ERROR_MSG ===\n";
echo PERMISSION_ERROR_MSG . "\n";