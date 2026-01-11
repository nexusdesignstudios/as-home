<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting debug script v3...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    echo "Vendor autoloaded.\n";

    $app = require_once __DIR__.'/bootstrap/app.php';
    echo "Bootstrap app loaded.\n";

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "Kernel made.\n";

    // Boot the app
    $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "Kernel handled request (bootstrapped).\n";

    // Creating mock request with ALL parameters explicitly merged
    echo "Creating mock request...\n";
    
    $payload = [
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
    ];

    $request = Illuminate\Http\Request::create('/api/submit-payment-form', 'POST', $payload);
    
    // IMPORTANT: Manually set the input source to ensure validation sees it
    $request->merge($payload);
    $request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($payload));
    
    // Force JSON headers
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');

    // Replace the global request instance in the container
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
