<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Use Facades
use App\Models\Customer;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Chats;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

echo "--- STARTING NOTIFICATION TEST FLOW ---\n";

// 1. Setup User
$user = Customer::where('email', 'nexlancer.g@gmail.com')->first();
if (!$user) {
    echo "User nexlancer.g@gmail.com not found! Creating test user.\n";
    $user = Customer::create([
        'name' => 'Test User',
        'email' => 'nexlancer.g@gmail.com', // Use the email user mentioned
        'password' => bcrypt('password'),
        'mobile' => '1234567890'
    ]);
}



Auth::guard('sanctum')->setUser($user);
echo "Logged in as User ID: " . $user->id . "\n";

// 2. Setup Property (Vacation Home)
$property = Property::where('property_classification', '!=', 5)->first(); // Not a hotel
if (!$property) {
    echo "No Property found! Creating one.\n";
    $property = Property::create([
        'title' => 'Test Property',
        'property_classification' => 4, // Vacation Home
        'added_by' => $user->id,
        'instant_booking' => 0,
        'price' => 100
    ]);
}
echo "Using Property ID: " . $property->id . "\n";

// 3. Setup Hotel and Room
$hotel = Property::find(586); // Hurghada Hotel Malorca 5 Stars
if (!$hotel) {
    echo "Hotel 586 not found, falling back to any hotel.\n";
    $hotel = Property::where('property_classification', 5)->first();
}
$room = null;
if ($hotel) {
    $room = HotelRoom::where('property_id', $hotel->id)->first();
}

if (!$hotel || !$room) {
    echo "No Hotel or Room found. Creating mock Hotel/Room.\n";
} else {
    echo "Using Hotel ID: " . $hotel->id . " and Room ID: " . $room->id . "\n";
}

// 4. Test Chat Notification (Removed direct Controller call to avoid permission issues)
// This is now handled in section 8 via ApiController
echo "\n--- SKIPPING DIRECT CHAT CONTROLLER TEST (using API Controller instead) ---\n";
/*
$chatController = app(ChatController::class);
$chatRequest = new Request([
    'sender_by' => $user->id,
    'receiver_id' => $user->id, // Send to self for test
    'message' => 'Test Message Notification',
    'property_id' => $property->id,
    'type' => 'chat'
]);

try {
    $response = $chatController->store($chatRequest);
    echo "Chat Response: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Chat Error: " . $e->getMessage() . "\n";
}
*/

// 5. Test Property Reservation Notification (Normal Creation)
echo "\n--- TESTING PROPERTY RESERVATION NOTIFICATION ---\n";
$resController = app(ReservationController::class);
// Dynamic dates to avoid conflicts - Using far future to avoid collisions
$checkInDate = date('Y-m-d', strtotime('+' . rand(1000, 1100) . ' days'));
$checkOutDate = date('Y-m-d', strtotime($checkInDate . ' + 2 days'));

echo "Using dates: $checkInDate to $checkOutDate\n";

$resRequest = new Request([
    'reservable_id' => $property->id,
    'reservable_type' => 'property',
    'property_id' => $property->id,
    'check_in_date' => $checkInDate,
    'check_out_date' => $checkOutDate,
    'number_of_guests' => 2,
    'status' => 'pending', // Property reservation
    'payment_status' => 0,
    'total_price' => 1000
]);

try {
    ob_start();
    $resController->createReservation($resRequest);
    $output = ob_get_clean();
    echo "Property Reservation Request Sent\n";
    echo "Output: " . $output . "\n"; // Show output for debugging
    // Check if output contains error
    if (strpos($output, '"error":true') !== false) {
        echo "Error Output: " . substr($output, 0, 200) . "...\n";
    }
} catch (\Exception $e) {
    ob_end_clean();
    echo "Property Reservation Exception: " . $e->getMessage() . "\n";
}

// 6. Test Hotel Reservation (Flexible - should auto-confirm and notify)
if ($hotel && $room) {
    echo "\n--- TESTING HOTEL RESERVATION (FLEXIBLE) ---\n";
    $offset = rand(1101, 1200);
    $hotelRequest = new Request([
        'reservable_type' => 'hotel_room',
        'property_id' => $hotel->id,
        'reservable_id' => [
            ['id' => $room->id, 'amount' => 100]
        ],
        'check_in_date' => date('Y-m-d', strtotime('+' . $offset . ' days')),
        'check_out_date' => date('Y-m-d', strtotime('+' . ($offset + 2) . ' days')),
        'number_of_guests' => 1,
        'booking_type' => 'flexible', // Force flexible
        'status' => 'confirmed' // Simulate what frontend/logic might do or rely on controller logic
    ]);

    try {
        ob_start();
        $resController->createReservation($hotelRequest);
        $output = ob_get_clean();
        echo "Hotel Reservation Request Sent\n";
         if (strpos($output, '"error":true') !== false) {
            echo "Error Output: " . substr($output, 0, 200) . "...\n";
        }
    } catch (\Exception $e) {
        ob_end_clean();
        echo "Hotel Reservation Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nSkipping Hotel Test (No Hotel/Room found)\n";
}

// 7. Test Hotel Reservation Notification (Non-Flexible)
if ($hotel && $room) {
    echo "\n--- TESTING HOTEL RESERVATION NOTIFICATION (Non-Flexible) ---\n";
    $offset = rand(1201, 1300);
    $hotelRequest = new Request([
        'reservable_type' => 'hotel_room',
        'property_id' => $hotel->id,
        'reservable_id' => [['id' => $room->id, 'amount' => 100]],
        'check_in_date' => date('Y-m-d', strtotime('+' . $offset . ' days')),
        'check_out_date' => date('Y-m-d', strtotime('+' . ($offset + 2) . ' days')),
        'number_of_guests' => 1,
        'booking_type' => 'non_refundable'
    ]);

    try {
        ob_start();
        $resController->createReservation($hotelRequest);
        $output = ob_get_clean();
        echo "Hotel Reservation Request Sent\n";
         if (strpos($output, '"error":true') !== false) {
            echo "Error Output: " . substr($output, 0, 200) . "...\n";
        }
    } catch (\Exception $e) {
        ob_end_clean();
        echo "Hotel Reservation Error: " . $e->getMessage() . "\n";
    }
}

// 8. Test Chat Notification
echo "\n--- TESTING CHAT NOTIFICATION ---\n";

$apiResponseService = $app->make(\App\Services\ApiResponseService::class);
$apiController = new ApiController($apiResponseService);

$chatRequest = new Request([
    'sender_id' => $user->id,
    'receiver_id' => $property ? $property->added_by : 1, // Use added_by as receiver (owner)
    'message' => 'Test message for notification',
    'property_id' => $property ? $property->id : 1, // Ensure property_id is set
    'message_type' => 'text'
]);

try {
    $response = $apiController->send_message($chatRequest);
    $content = json_decode($response->getContent(), true);
    if (isset($content['error']) && $content['error']) {
         echo "Chat Error: " . ($content['message'] ?? 'Unknown') . "\n";
    } else {
         echo "Chat Message Sent: " . ($content['message'] ?? 'OK') . "\n";
    }
} catch (\Exception $e) {
    echo "Chat Exception: " . $e->getMessage() . "\n";
}
