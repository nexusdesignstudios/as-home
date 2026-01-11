<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a mock request
$request = Illuminate\Http\Request::create('/api/submit-payment-form', 'POST', [
    'property_id' => 393,
    'customer_name' => 'Test User',
    'customer_phone' => '1234567890',
    'customer_email' => 'test@example.com',
    'payment_method' => 'pay_at_property',
    'amount' => 0,
    'currency' => 'EGP',
    'check_in_date' => '2026-01-28',
    'check_out_date' => '2026-01-29',
    'number_of_guests' => 1,
    'reservable_type' => 'hotel_room',
    'reservable_data' => [
        [
            'id' => 844,
            'room_type_id' => 1,
            'amount' => 2576
        ]
    ],
    'booking_type' => 'flexible_booking',
    'is_flexible_booking' => true,
    'approval_status' => 'approved',
    'requires_approval' => false
]);

// Bind the request to the container
$app->instance('request', $request);

echo "--- Simulating Payment Form Submission ---\n";

try {
    // Manually invoke the controller action
    $controller = $app->make(\App\Http\Controllers\ApiController::class);
    $response = $controller->submitPaymentForm($request);

    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "❌ Controller Error: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
