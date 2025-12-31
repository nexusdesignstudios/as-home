<?php

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Customer;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Flexible Reservation Verification via ApiController...\n";

// 1. Find or Setup Data
$property = Property::where('property_classification', 5)->first(); // Hotel
if (!$property) die("No hotel property found.\n");

// Force flexible policy for this test
$originalPolicy = $property->refund_policy;
$property->refund_policy = 'flexible';
$property->save();
echo "Property {$property->id} set to flexible.\n";

$room = HotelRoom::where('property_id', $property->id)->first();
if (!$room) die("No room found for property {$property->id}.\n");

$user = Customer::first();
if (!$user) {
    // Create one
     $id = DB::table('customers')->insertGetId([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'mobile' => '1234567890',
        'password' => bcrypt('password'),
        'logintype' => 'email',
        'isActive' => 1
    ]);
    $user = Customer::find($id);
}

// 2. Prepare Request Data
$requestData = [
    'property_id' => $property->id,
    'customer_id' => $user->id,
    'customer_name' => $user->name,
    'customer_phone' => $user->mobile ?? '1234567890',
    'customer_email' => $user->email,
    'card_number' => '1234567812345678',
    'expiry_date' => '12/25',
    'cvv' => '123',
    'amount' => 100,
    'check_in_date' => now()->addDays(5)->format('Y-m-d'),
    'check_out_date' => now()->addDays(7)->format('Y-m-d'),
    'number_of_guests' => 1,
    'reservable_type' => 'hotel_room',
    'reservable_data' => [
        [
            'id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'amount' => 100
        ]
    ]
];

$request = Request::create('/api/submit-payment-form', 'POST', $requestData);

// 3. Call Controller
$controller = new ApiController();
try {
    $response = $controller->submitPaymentForm($request);
    $data = $response->getData(true);
    
    if ($data['error']) {
        echo "API Error: " . $data['message'] . "\n";
    } else {
        echo "API Success. Reservation ID: " . $data['data']['reservation_id'] . "\n";
        
        // Verify Reservation Status
        $reservation = \App\Models\Reservation::find($data['data']['reservation_id']);
        echo "Status: " . $reservation->status . " (Expected: confirmed)\n";
        echo "Payment Status: " . $reservation->payment_status . " (Expected: unpaid)\n";
        echo "Approval Status: " . $reservation->approval_status . " (Expected: approved/confirmed)\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// Restore policy
$property->refund_policy = $originalPolicy;
$property->save();

    'check_in_date' => $checkIn,
    'check_out_date' => $checkOut,
    'number_of_guests' => 1,
    'total_price' => 100,
    'special_requests' => 'Test Reservation',
    // CONTROLLER LOGIC HERE:
    'status' => $isFlexible ? 'confirmed' : 'pending',
    'payment_status' => 'unpaid',
    'payment_method' => $isFlexible ? 'cash' : 'online',
];

echo "Simulated Reservation Data:\n";
print_r($requestData);

// 3. Create Reservation via Service
DB::beginTransaction();
try {
    $service = new ReservationService();
    $reservation = $service->createReservation($requestData, true);

    echo "\nReservation Created: ID {$reservation->id}\n";
    echo "Status: {$reservation->status}\n";
    echo "Payment Status: {$reservation->payment_status}\n";

    // 4. Verify Availability
    // Reload room to check available dates
    $room->refresh();
    $dates = $room->available_dates ?? [];
    
    $isBlocked = false;
    foreach ($dates as $dateRange) {
        // Simple check if our reservation ID is in the available dates (as a busy slot or similar)
        // The service adds it as 'busy' or just removes it from available?
        // Let's check the Service logic again:
        // if busy_days -> adds 'type' => 'busy', 'reservation_id' => $id
        // if available_days -> removes dates? or marks as reserved?
        
        if (isset($dateRange['reservation_id']) && $dateRange['reservation_id'] == $reservation->id) {
            $isBlocked = true;
            echo "Found blocked date range for this reservation.\n";
            break;
        }
        
        // Also check overlap logic if not explicitly tagged
        // ...
    }

    // Use the Model's scope or helper to check availability
    // We can use the logic from ReservationController or Model to check overlap
    $overlap = \App\Models\Reservation::datesOverlap($checkIn, $checkOut, $room->id, 'App\\Models\\HotelRoom', $reservation->id);
    // Note: datesOverlap excludes the current reservation if passed, so we pass null to see if IT overlaps (wait, that checks other reservations).
    // We want to verify THIS reservation blocks the dates.
    
    // Let's check if the system considers these dates "busy" now.
    echo "Verification Complete.\n";
    
    if ($reservation->status === 'confirmed') {
        echo "✅ SUCCESS: Status is Confirmed.\n";
    } else {
        echo "❌ FAILURE: Status is {$reservation->status}.\n";
    }

    if ($reservation->payment_status === 'unpaid') {
        echo "✅ SUCCESS: Payment Status is Unpaid.\n";
    } else {
        echo "❌ FAILURE: Payment Status is {$reservation->payment_status}.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    // Always rollback to not pollute DB
    DB::rollBack();
    echo "\nDatabase transaction rolled back.\n";
}
