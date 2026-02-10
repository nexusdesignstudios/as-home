<?php

use App\Http\Controllers\ReservationController;
use App\Models\Reservation;
use App\Models\Property;
use App\Models\Customer;
use App\Models\HotelRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Starting Test...\n";

// 1. Setup Data
$propertyId = 586; // Hurghada Hotel Malorca 5 Stars
$ownerId = 14;
$customerId = 26; // The booker

$property = Property::find($propertyId);
if (!$property) {
    die("Property not found\n");
}
echo "Property found: " . $property->title . "\n";

$owner = Customer::find($ownerId);
if (!$owner) {
    die("Owner not found\n");
}
echo "Owner found: " . $owner->name . "\n";

// Ensure owner has a token for notification (dummy one if needed)
if ($owner->usertokens->isEmpty()) {
    echo "Owner has no FCM tokens. Creating a dummy one for testing...\n";
    $owner->usertokens()->create([
        'fcm_id' => 'dummy_token_' . uniqid(),
        'token' => 'dummy_access_token_' . uniqid(), // Assuming 'token' column exists, check schema if error
        'device_type' => 'android' // Assuming column exists
    ]);
    // Refresh owner relation
    $owner->load('usertokens');
} else {
    echo "Owner has " . $owner->usertokens->count() . " FCM tokens.\n";
}

// 2. Prepare Request for storeFlexible
// We need to find a valid hotel room for this property
$hotelRoom = HotelRoom::where('property_id', $propertyId)->first();
if (!$hotelRoom) {
    die("No hotel room found for this property\n");
}

$requestData = [
    'customer_id' => $customerId,
    'property_id' => $propertyId,
    'check_in_date' => date('Y-m-d', strtotime('+1 day')),
    'check_out_date' => date('Y-m-d', strtotime('+2 days')),
    'adults' => 1,
    'children' => 0,
    'room_id' => $hotelRoom->id,
    'room_type_id' => $hotelRoom->room_type_id, // Assuming this exists
    'guest_details' => json_encode([
        ['name' => 'Test Guest', 'age' => 30]
    ]),
    'total_price' => 1000,
    'payment_method' => 'cash', // Flexible booking
    'booking_type' => 'flexible_booking'
];

$request = new Request($requestData);

// 3. Call Controller
// We need to mock Auth::user() if the controller uses it, but storeFlexible usually takes customer_id from request or assumes admin/owner context.
// Let's assume we are calling it as an API or internal call.

$controller = app(ReservationController::class);

echo "Calling storeFlexible...\n";
try {
    // We need to use reflection or just call it if public. 
    // storeFlexible is public? Let's check. 
    // It is likely public as it's a controller action.
    
    // However, looking at previous analysis, it might be an API endpoint.
    // Let's try calling it directly.
    $response = $controller->storeFlexible($request);
    
    echo "Response: " . json_encode($response) . "\n";
    
    if (isset($response->original['error']) && $response->original['error']) {
        echo "Error in response: " . $response->original['message'] . "\n";
    } else {
        echo "Reservation created successfully.\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "Check laravel.log for 'send_push_notification called'\n";
