<?php

use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting PayPal IPN Test...\n";

// Transaction ID from the previous test run
$transactionId = 'RES_1770626758_65_5476';  

// Simulate PayPal IPN data
$data = [
    'custom' => $transactionId,
    'payment_status' => 'Completed',
    'txn_id' => 'PAYPAL_TEST_TXN_' . time(),
    'payer_email' => 'sb-test-payer@personal.example.com',
    'receiver_email' => 'sb-qznyx49181595@business.example.com',
    'mc_gross' => '100.00',
    'mc_currency' => 'USD',
];

try {
    $request = Request::create('/api/payments/paypal/ipn', 'POST', $data);
    
    $controller = app(WebhookController::class);
    
    $response = $controller->paypalReservationIpn($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    print_r(json_decode($response->getContent(), true));
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\nTest Completed.\n";
