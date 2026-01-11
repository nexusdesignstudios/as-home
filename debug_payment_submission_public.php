<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting PUBLIC debug script...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    // 1. Prepare payload - exactly as seen in your logs
    echo "Creating mock request...\n";
    $payload = [
        'property_id' => 394, // Updated property ID
        'property_owner_id' => 92,
        'customer_id' => 14,
        'property_title' => 'test',
        'property_address' => 'Apartment 6, Building no. 80, Mubarak 2',
        'property_city' => 'Hurghada',
        'property_country' => 'Egypt',
        'property_classification' => 5,
        'property_type' => 'rent',
        'customer_name' => 'ibrahim ahmed',
        'customer_phone' => '201061874267',
        'customer_email' => 'nexlancer.eg@gmail.com',
        'payment_method' => 'pay_at_property',
        'amount' => 0,
        'discount_percentage' => 0,
        'original_amount' => 1417,
        'currency' => 'EGP',
        'check_in_date' => '2026-01-19',
        'check_out_date' => '2026-01-20',
        'number_of_guests' => 1,
        'number_of_rooms' => 1,
        'special_requests' => '',
        'reservable_type' => 'hotel_room',
        'reservable_data' => [
            [
                'id' => 847, // Updated room ID
                'room_type_id' => 1,
                'room_type_name' => 'Standard Room',
                'guest_count' => 1,
                'package_id' => 266,
                'package_name' => 'breakfast',
                'is_refundable' => true,
                'base_price' => 1000,
                'total_base_price' => 0,
                'package_price' => '100.00',
                'nights' => 1,
                'nonrefundable_percentage' => 100,
                'room_description' => 'Standard Room - breakfast',
                'max_guests' => 3,
                'daily_prices' => [],
                'service_charge' => 132,
                'sales_tax' => 172.48,
                'city_tax' => 12.32,
                'service_tax' => 0,
                'amount' => 1417,
                'status' => 1
            ]
        ],
        'review_url' => 'http://localhost:3000/review/?property_id=394&property_classification=5',
        'approval_status' => 'approved',
        'requires_approval' => false,
        'booking_type' => 'flexible_booking',
        'property_details' => [
            'title' => 'test',
            'address' => 'Apartment 6, Building no. 80, Mubarak 2',
            'city' => 'Hurghada',
            'country' => 'Egypt',
            'classification' => 5,
            'type' => 'rent'
        ],
        'is_flexible_booking' => true,
        'flexible_booking_discount' => 0
    ];

    // Create request with parameters
    $request = Illuminate\Http\Request::create(
        '/api/submit-payment-form', 
        'POST', 
        $payload
    );

    // Populate all input bags to bypass any validation weirdness
    $request->merge($payload); 
    $request->request->add($payload);
    $request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($payload));

    // Headers
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
