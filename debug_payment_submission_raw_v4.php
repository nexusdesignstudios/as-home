<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting debug script v4...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle($request = Illuminate\Http\Request::capture());

    // 1. Find a valid user to authenticate as
    echo "Finding a user to authenticate...\n";
    $user = \App\Models\Customer::first(); // Or User::first() depending on your auth guard
    if (!$user) {
        die("❌ No customers found in database. Cannot simulate auth.\n");
    }
    echo "Authenticating as Customer ID: " . $user->id . "\n";
    
    // Simulate authentication
    $app->make('auth')->guard('sanctum')->setUser($user);
    // Also try standard web/api guard just in case
    // \Illuminate\Support\Facades\Auth::login($user);

    // 2. Prepare payload with customer_id
    echo "Creating mock request...\n";
    $payload = [
        'property_id' => 393,
        'customer_id' => $user->id, // Added required field
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
    ];

    $request = Illuminate\Http\Request::create('/api/submit-payment-form', 'POST', $payload);
    $request->merge($payload);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');

    $app->instance('request', $request);
    
    echo "Instantiating Controller...\n";
    $controller = $app->make(\App\Http\Controllers\ApiController::class);
    
    echo "Calling submitPaymentForm...\n";
    $response = $controller->submitPaymentForm($request);

    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";

} catch (\Throwable $e) {
    echo "\n❌ CRITICAL ERROR:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
