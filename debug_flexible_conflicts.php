<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== CHECKING FLEXIBLE HOTEL ROOM RESERVATION CONFLICTS ===\n\n";

// Check for the specific reservations mentioned in the issue
echo "1. Checking specific reservations #1003 and #1004:\n";
$specificReservations = Reservation::whereIn('id', [1003, 1004])
    ->with(['property', 'reservable'])
    ->get();

foreach ($specificReservations as $reservation) {
    echo "   Reservation ID: {$reservation->id}\n";
    echo "   Property: " . ($reservation->property->title ?? 'N/A') . "\n";
    echo "   Room ID: {$reservation->reservable_id}\n";
    echo "   Check-in: {$reservation->check_in_date}\n";
    echo "   Check-out: {$reservation->check_out_date}\n";
    echo "   Customer: {$reservation->customer_name}\n";
    echo "   Status: {$reservation->status}\n";
    echo "   Payment Method: {$reservation->payment_method}\n";
    echo "   Payment Status: {$reservation->payment_status}\n";
    
    // Get room details
    if ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($reservation->reservable_id);
        if ($room) {
            echo "   Room Number: " . ($room->room_number ?? 'N/A') . "\n";
            echo "   Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
        }
    }
    echo "   ---\n";
}

// Check for all reservations with the same room_id and overlapping dates
echo "\n2. All conflicting reservations (same room, same dates):\n";
$conflictingReservations = DB::table('reservations')
    ->select('reservable_id', 'check_in_date', 'check_out_date', 'status', 'payment_method', 'payment_status', 
           DB::raw('COUNT(*) as reservation_count'),
           DB::raw('GROUP_CONCAT(id) as reservation_ids'))
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->where('status', '!=', 'cancelled')
    ->where('status', '!=', 'rejected')
    ->groupBy('reservable_id', 'check_in_date', 'check_out_date')
    ->havingRaw('COUNT(*) > 1')
    ->orderBy('reservation_count', 'desc')
    ->get();

echo "Found " . $conflictingReservations->count() . " sets of conflicting reservations\n\n";

foreach ($conflictingReservations as $conflict) {
    echo "=== CONFLICT ===\n";
    echo "Room ID: " . $conflict->reservable_id . "\n";
    echo "Check-in: " . $conflict->check_in_date . "\n";
    echo "Check-out: " . $conflict->check_out_date . "\n";
    echo "Reservation Count: " . $conflict->reservation_count . "\n";
    echo "Reservation IDs: " . $conflict->reservation_ids . "\n";
    echo "Status: " . $conflict->status . "\n";
    echo "Payment Method: " . $conflict->payment_method . "\n";
    echo "Payment Status: " . $conflict->payment_status . "\n";
    
    // Get room details
    $room = HotelRoom::find($conflict->reservable_id);
    if ($room) {
        echo "Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
        echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
        echo "Property: " . ($room->property->title ?? 'N/A') . "\n";
    }
    
    echo "\n";
    
    // Get individual reservations for this conflict
    $individualReservations = DB::table('reservations')
        ->where('reservable_id', $conflict->reservable_id)
        ->where('check_in_date', $conflict->check_in_date)
        ->where('check_out_date', $conflict->check_out_date)
        ->where('status', '!=', 'cancelled')
        ->where('status', '!=', 'rejected')
        ->orderBy('created_at', 'asc')
        ->get(['id', 'customer_name', 'customer_email', 'status', 'payment_method', 'payment_status', 'created_at']);
    
    echo "Individual Reservations:\n";
    foreach ($individualReservations as $res) {
        echo "  - ID: " . $res->id . ", Customer: " . $res->customer_name . 
             ", Status: " . $res->status . ", Payment: " . $res->payment_method . 
             ", Created: " . $res->created_at . "\n";
    }
    echo "\n";
}

// Check for flexible reservations specifically
echo "\n3. Flexible reservation conflicts:\n";
$flexibleConflicts = DB::table('reservations')
    ->select('reservable_id', 'check_in_date', 'check_out_date', 'status', 'payment_method', 'payment_status', 
           DB::raw('COUNT(*) as reservation_count'),
           DB::raw('GROUP_CONCAT(id) as reservation_ids'))
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->where('status', '!=', 'cancelled')
    ->where('status', '!=', 'rejected')
    ->where('payment_method', 'cash')
    ->groupBy('reservable_id', 'check_in_date', 'check_out_date')
    ->havingRaw('COUNT(*) > 1')
    ->orderBy('reservation_count', 'desc')
    ->get();

echo "Found " . $flexibleConflicts->count() . " sets of conflicting flexible reservations\n\n";

foreach ($flexibleConflicts as $conflict) {
    echo "=== FLEXIBLE CONFLICT ===\n";
    echo "Room ID: " . $conflict->reservable_id . "\n";
    echo "Check-in: " . $conflict->check_in_date . "\n";
    echo "Check-out: " . $conflict->check_out_date . "\n";
    echo "Reservation Count: " . $conflict->reservation_count . "\n";
    echo "Reservation IDs: " . $conflict->reservation_ids . "\n";
    
    // Get room details
    $room = HotelRoom::find($conflict->reservable_id);
    if ($room) {
        echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
        echo "Property: " . ($room->property->title ?? 'N/A') . "\n";
    }
    
    echo "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "ISSUE: The reservation system does NOT check for existing reservations before creating new ones.\n";
echo "This allows multiple flexible reservations for the same room and dates.\n";
