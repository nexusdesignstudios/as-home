<?php

require_once 'vendor/autoload.php';

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== CHECKING FLEXIBLE HOTEL ROOM RESERVATION CONFLICTS ===\n\n";

// Check for reservations with the same room_id and overlapping dates
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

echo "Found " . $conflictingReservations->count() . " sets of conflicting reservations (same room, same dates)\n\n";

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

echo "\n=== FLEXIBLE RESERVATION CONFLICTS ===\n\n";
echo "Found " . $flexibleConflicts->count() . " sets of conflicting flexible reservations\n\n";

foreach ($flexibleConflicts as $conflict) {
    echo "=== FLEXIBLE CONFLICT ===\n";
    echo "Room ID: " . $conflict->reservable_id . "\n";
    echo "Check-in: " . $conflict->check_in_date . "\n";
    echo "Check-out: " . $conflict->check_out_date . "\n";
    echo "Reservation Count: " . $conflict->reservation_count . "\n";
    echo "Reservation IDs: " . $conflict->reservation_ids . "\n";
    echo "Status: " . $conflict->status . "\n";
    echo "Payment Status: " . $conflict->payment_status . "\n";
    
    // Get room details
    $room = HotelRoom::find($conflict->reservable_id);
    if ($room) {
        echo "Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
        echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
    }
    
    echo "\n";
    
    // Get individual reservations for this conflict
    $individualReservations = DB::table('reservations')
        ->where('reservable_id', $conflict->reservable_id)
        ->where('check_in_date', $conflict->check_in_date)
        ->where('check_out_date', $conflict->check_out_date)
        ->where('payment_method', 'cash')
        ->where('status', '!=', 'cancelled')
        ->where('status', '!=', 'rejected')
        ->orderBy('created_at', 'asc')
        ->get(['id', 'customer_name', 'customer_email', 'status', 'payment_status', 'created_at']);
    
    echo "Individual Flexible Reservations:\n";
    foreach ($individualReservations as $res) {
        echo "  - ID: " . $res->id . ", Customer: " . $res->customer_name . 
             ", Status: " . $res->status . ", Created: " . $res->created_at . "\n";
    }
    echo "\n";
}

// Check recent flexible reservations in the last 30 days
echo "=== RECENT FLEXIBLE RESERVATIONS (Last 30 Days) ===\n\n";

$recentFlexible = DB::table('reservations')
    ->select('reservable_id', 'check_in_date', 'check_out_date', 'customer_name', 'customer_email', 
           'status', 'payment_method', 'payment_status', 'created_at')
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->where('payment_method', 'cash')
    ->where('created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'))
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "Found " . $recentFlexible->count() . " recent flexible reservations (last 30 days)\n\n";

foreach ($recentFlexible as $res) {
    echo "ID: " . $res->reservable_id . ", Customer: " . $res->customer_name . 
         ", Dates: " . $res->check_in_date . " to " . $res->check_out_date . 
         ", Status: " . $res->status . ", Created: " . $res->created_at . "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "ISSUE FOUND: The reservation system does NOT check for existing reservations before creating new ones.\n";
echo "This allows multiple flexible reservations for the same room and dates.\n";
echo "The createReservation method in ReservationService directly creates reservations without availability checking.\n";
