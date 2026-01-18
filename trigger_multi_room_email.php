<?php

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Services\ReservationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

// 1. Get or Create a Customer
$customer = Customer::firstOrCreate(
    ['email' => 'nexlancer.eg@gmail.com'],
    [
        'name' => 'Nexlancer Test',
        'password' => bcrypt('password'),
        'mobile' => '01000000000',
        'logintype' => 'email', // Adding common fields
        'isActive' => 1
    ]
);

// 2. Get a Hotel Room (Ensure one exists)
$room = HotelRoom::first();
if (!$room) {
    echo "No Hotel Room found. Please create one first.\n";
    exit;
}

// 3. Generate a shared Transaction ID
$transactionId = 'TEST_TX_' . Str::random(10);
echo "Transaction ID: " . $transactionId . "\n";

// 4. Create 4 Linked Reservations
$reservations = collect();
$checkIn = now()->addDays(5);
$checkOut = now()->addDays(7);

echo "Creating 4 reservations...\n";

for ($i = 1; $i <= 4; $i++) {
    $res = Reservation::create([
        'customer_id' => $customer->id,
        'reservable_type' => 'App\Models\HotelRoom',
        'reservable_id' => $room->id,
        'property_id' => $room->property_id ?? 1, // Fallback if needed
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'transaction_id' => $transactionId,
        'total_price' => 1500.00,
        'number_of_guests' => 2,
        'number_of_rooms' => 1,
        'special_requests' => "Multi-room test request #{$i}",
        'booking_channel' => 'direct',
        'gateway_data' => json_encode(['manual_trigger' => true])
    ]);
    
    $reservations->push($res);
    echo "Created Reservation ID: " . $res->id . "\n";
}

// 5. Trigger the Aggregated Email
echo "Triggering Aggregated Email...\n";
try {
    $reservationService = app(ReservationService::class);
    $reservationService->sendAggregatedReservationConfirmationEmail($reservations);
    echo "Email sent successfully (simulated).\n";
} catch (\Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// 6. Verify Database Integrity
$savedReservations = Reservation::where('transaction_id', $transactionId)->get();
echo "\n--- Database Verification ---\n";
echo "Count Found: " . $savedReservations->count() . " (Expected: 4)\n";
foreach ($savedReservations as $saved) {
    echo "ID: {$saved->id} | Status: {$saved->status} | Paid: {$saved->payment_status} | TxID: {$saved->transaction_id}\n";
}
