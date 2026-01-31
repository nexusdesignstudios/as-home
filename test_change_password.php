<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

// Simulate authenticated user
$user = Customer::first(); 
if (!$user) {
    die("No user found");
}
echo "User found: " . $user->id . "\n";
Auth::login($user);

// Create request
// Note: We need to use valid old password to test success, but we don't know it.
// We can test validation failure or incorrect old password.
// This will at least test if the controller method crashes (500).

$request = Request::create('/api/change_password', 'POST', [
    'old_password' => 'wrongpassword', 
    'new_password' => 'NewPass123',
    'new_password_confirmation' => 'NewPass123'
]);

// Call controller
$controller = new ApiController();
try {
    $response = $controller->change_password($request);
    
    // Check if response is JSON
    if ($response->headers->get('Content-Type') === 'application/json') {
        echo "Response JSON: " . $response->getContent() . "\n";
    } else {
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (\Throwable $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
