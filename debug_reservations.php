<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;

echo "=== Debugging Property Owner Reservations API ===\n\n";

try {
    // Create a mock request
    $request = new Request();
    $request->merge([
        'per_page' => 20,
        'page' => 1
    ]);
    
    // Set the customer_id (14 as mentioned in the error)
    $customer_id = 14;
    
    echo "Testing with customer_id: {$customer_id}\n";
    echo "Request parameters: " . json_encode($request->all()) . "\n\n";
    
    // Create controller instance
    $controller = new ReservationController();
    
    // Call the method directly
    $response = $controller->getPropertyOwnerReservations($request, $customer_id);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
