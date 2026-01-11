<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Models\Reservation;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n--- STARTING FULL END-TO-END TEST ---\n";

// 1. Authenticate User
echo "\n1. Authenticating User...\n";
$loginData = [
    'type' => 3,
    'email' => 'nexlancer.eg@gmail.com',
    'password' => 'Talat1985#'
];

$request = Request::create('/api/user_signup', 'POST', $loginData);
$controller = new ApiController();
$response = $controller->user_signup($request);

$loginContent = $response->getContent();
$loginJson = json_decode($loginContent, true);

if (!isset($loginJson['token'])) {
    echo "❌ Login Failed!\n";
    echo "Response: " . $loginContent . "\n";
    exit(1);
}

$token = $loginJson['token'];
$userId = $loginJson['data']['id'];
echo "✅ Login Successful! User ID: $userId\n";
// echo "Token: " . substr($token, 0, 10) . "...\n";

// 2. Prepare Reservation Data (Dates: 2026-01-20 to 2026-01-21)
echo "\n2. Preparing Reservation Payload...\n";

$checkIn = '2026-01-20';
$checkOut = '2026-01-21';

// Ensure dates are clear (delete any conflicting tests)
Reservation::where('property_id', 393)
    ->where('check_in_date', $checkIn)
    ->where('check_out_date', $checkOut)
    ->delete();

$reservationData = [
    "property_id" => 393,
    "property_owner_id" => 14, // This should be ignored by the backend now
    "customer_id" => $userId,
    "property_title" => "Sharm elshiekh 5 stars test",
    "property_classification" => 5,
    "property_type" => "rent",
    "customer_name" => $loginJson['data']['name'],
    "customer_phone" => $loginJson['data']['mobile'] ?? "201061874267",
    "customer_email" => $loginJson['data']['email'],
    "payment_method" => "pay_at_property",
    "amount" => 0,
    "discount_percentage" => 0,
    "original_amount" => 2576,
    "currency" => "EGP",
    "check_in_date" => $checkIn,
    "check_out_date" => $checkOut,
    "number_of_guests" => 1,
    "number_of_rooms" => 1,
    "special_requests" => "Testing via Script",
    "reservable_type" => "hotel_room",
    "reservable_data" => [
        [
            "id" => 844,
            "room_type_id" => 1,
            "room_type_name" => "Standard Room",
            "guest_count" => 1,
            "package_id" => 265,
            "package_name" => "Basic",
            "is_refundable" => true,
            "base_price" => 2000,
            "total_base_price" => 0,
            "package_price" => 0,
            "nights" => 1,
            "nonrefundable_percentage" => 100,
            "room_description" => "Standard Room - Basic",
            "max_guests" => 3,
            "daily_prices" => [],
            "service_charge" => 240,
            "sales_tax" => 313.6,
            "city_tax" => 22.4,
            "service_tax" => 0,
            "amount" => 2576,
            "status" => 1
        ]
    ],
    "review_url" => "http://localhost:3000/review/?property_id=393&property_classification=5",
    "approval_status" => "approved",
    "requires_approval" => false,
    "booking_type" => "flexible_booking",
    "property_details" => [
        "title" => "Sharm elshiekh 5 stars test",
        "address" => "214 El-Salam, Sharm El Sheikh 1, South Sinai Governorate 8761432, Egypt",
        "city" => "Sharm El-Sheikh",
        "country" => "Egypt",
        "classification" => 5,
        "type" => "rent"
    ],
    "is_flexible_booking" => true,
    "flexible_booking_discount" => 0
];

// 3. Submit Reservation
echo "\n3. Submitting Reservation...\n";

// We need to mock the authenticated user context for Sanctum if middleware was checking it
// But here we are calling the controller method directly. 
// However, the controller doesn't seem to rely on Auth::user() for the core logic of submitPaymentForm
// It relies on the passed customer_id.
// But just in case, let's login the user in Laravel's Auth guard
Auth::loginUsingId($userId);

$request = Request::create('/api/submit-payment-form', 'POST', $reservationData);
$response = $controller->submitPaymentForm($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
$content = $response->getContent();
echo "Response Content: " . $content . "\n";

if ($response->getStatusCode() == 200) {
    $json = json_decode($content, true);
    $reservationId = $json['data']['reservation_id'] ?? null;
    
    if ($reservationId) {
        echo "\n✅ Reservation Created! ID: $reservationId\n";
        
        // 4. Verify Database
        echo "\n4. Verifying Database...\n";
        $reservation = Reservation::find($reservationId);
        
        echo "Status: " . $reservation->status . " (Expected: confirmed)\n";
        echo "Payment Method: " . $reservation->payment_method . " (Expected: cash)\n";
        echo "Payment Status: " . $reservation->payment_status . " (Expected: unpaid)\n";
        
        if ($reservation->status === 'confirmed' && $reservation->payment_method === 'cash') {
            echo "\n🎉 FULL TEST PASSED!\n";
        } else {
            echo "\n❌ DATA MISMATCH!\n";
        }
        
    } else {
        echo "\n❌ Reservation ID missing in response!\n";
    }
} else {
    echo "\n❌ Submission Failed!\n";
}
