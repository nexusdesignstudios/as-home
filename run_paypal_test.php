<?php

use Illuminate\Http\Request;
use App\Http\Controllers\TestPayPalController;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting PayPal Test...\n";

try {
    $request = Request::create('/api/test-paypal-reservation', 'GET');
    
    // Resolve controller via app to handle dependency injection if any
    $controller = app(TestPayPalController::class);
    
    $response = $controller->test($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    print_r(json_decode($response->getContent(), true));
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\nTest Completed.\n";
