<?php

use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting PayPal IPN Test...\n";

// IDs from the successful reservation test
$transactionId = 'RES_1770639410_65_3005'; 
$orderId = 'TEST_ORDER_' . time();

// Simulate PayPal IPN data (PAYMENT.CAPTURE.COMPLETED event)
$data = [
    'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
    'resource' => [
        'id' => 'CAPTURE_ID_' . time(),
        'custom_id' => $transactionId,
        'amount' => [
            'value' => '100.00',
            'currency_code' => 'USD'
        ],
        'supplementary_data' => [
            'related_ids' => [
                'order_id' => $orderId
            ]
        ]
    ]
];

try {
    // Create a request with JSON content
    $request = Request::create(
        '/api/payments/paypal/ipn', 
        'POST', 
        [], 
        [], 
        [], 
        ['CONTENT_TYPE' => 'application/json'], 
        json_encode($data)
    );
    
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
