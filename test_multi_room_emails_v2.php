<?php

use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Models\User; // Or Customer model
use App\Services\ReservationService;
use App\Models\Customer;

// Ensure we have a customer
$customer = Customer::first();
if (!$customer) {
    echo "No Customer found in database.\n";
    exit;
}
echo "Using Customer ID: {$customer->id} ({$customer->email})\n";

// Ensure we have a hotel room
$hotelRoom = HotelRoom::first();
if (!$hotelRoom) {
    echo "No HotelRoom found in database.\n";
    exit;
}

$reservationService = app(ReservationService::class);
$transactionId = 'TEST_TRANS_' . time();

echo "Testing Multi-Room Email Aggregation...\n";
echo "Transaction ID: $transactionId\n";

// --- Scenario 1: Standard Multi-Room Reservation (Non-Refundable) ---
echo "\n--- Scenario 1: Standard (Non-Refundable) ---\n";

$res1 = new Reservation();
$res1->customer_id = $customer->id;
$res1->reservable_type = 'hotel_room'; // Shorthand
$res1->reservable_id = $hotelRoom->id;
$res1->status = 'pending';
$res1->payment_status = 'paid';
$res1->transaction_id = $transactionId;
$res1->total_price = 1000;
$res1->number_of_guests = 2;
$res1->check_in_date = now()->addDays(1);
$res1->check_out_date = now()->addDays(3);
$res1->save();

$res2 = new Reservation();
$res2->customer_id = $customer->id;
$res2->reservable_type = 'App\Models\HotelRoom'; // Full class
$res2->reservable_id = $hotelRoom->id;
$res2->status = 'pending';
$res2->payment_status = 'paid';
$res2->transaction_id = $transactionId;
$res2->total_price = 1500;
$res2->number_of_guests = 1;
$res2->check_in_date = now()->addDays(1);
$res2->check_out_date = now()->addDays(3);
$res2->save();

$reservations = collect([$res1, $res2]);
echo "Created 2 reservations with ID: {$res1->id}, {$res2->id}\n";

try {
    $reservationService->sendAggregatedReservationConfirmationEmail($reservations);
    echo "Standard Aggregated Email Sent Successfully.\n";
} catch (\Exception $e) {
    echo "Error sending Standard email: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// --- Scenario 2: Flexible Multi-Room Reservation ---
echo "\n--- Scenario 2: Flexible Booking ---\n";
$flexTransactionId = 'TEST_FLEX_' . time();
$res3 = new Reservation();
$res3->customer_id = $customer->id;
$res3->reservable_type = 'hotel_room';
$res3->reservable_id = $hotelRoom->id;
$res3->status = 'pending';
$res3->payment_status = 'paid';
$res3->transaction_id = $flexTransactionId;
$res3->total_price = 2000;
$res3->booking_type = 'flexible_booking'; // Key difference
$res3->number_of_guests = 3;
$res3->check_in_date = now()->addDays(5);
$res3->check_out_date = now()->addDays(7);
$res3->save();

$res4 = new Reservation();
$res4->customer_id = $customer->id;
$res4->reservable_type = 'hotel_room';
$res4->reservable_id = $hotelRoom->id;
$res4->status = 'pending';
$res4->payment_status = 'paid';
$res4->transaction_id = $flexTransactionId;
$res4->total_price = 2500;
$res4->booking_type = 'flexible_booking'; // Key difference
$res4->number_of_guests = 2;
$res4->check_in_date = now()->addDays(5);
$res4->check_out_date = now()->addDays(7);
$res4->save();

$flexReservations = collect([$res3, $res4]);
echo "Created 2 FLEXIBLE reservations with ID: {$res3->id}, {$res4->id}\n";

try {
    $reservationService->sendAggregatedReservationConfirmationEmail($flexReservations);
    echo "Flexible Aggregated Email Sent Successfully.\n";
} catch (\Exception $e) {
    echo "Error sending Flexible email: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Clean up (optional, but good practice if not needed persistent)
// $res1->delete(); $res2->delete(); $res3->delete(); $res4->delete();
