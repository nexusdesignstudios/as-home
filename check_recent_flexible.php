<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

echo "=== CHECKING RECENT FLEXIBLE RESERVATIONS ===\n\n";

// Check for recent flexible reservations (cash payment method, confirmed status)
$recentFlexible = Reservation::where('payment_method', 'cash')
    ->where('status', 'confirmed')
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->with(['property', 'reservable'])
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "Found " . $recentFlexible->count() . " recent flexible reservations:\n\n";

foreach ($recentFlexible as $reservation) {
    echo "Reservation ID: {$reservation->id}\n";
    echo "Property: " . ($reservation->property->title ?? 'N/A') . "\n";
    echo "Room ID: {$reservation->reservable_id}\n";
    echo "Check-in: {$reservation->check_in_date}\n";
    echo "Check-out: {$reservation->check_out_date}\n";
    echo "Customer: {$reservation->customer_name}\n";
    echo "Email: {$reservation->customer_email}\n";
    echo "Phone: {$reservation->customer_phone}\n";
    echo "Status: {$reservation->status}\n";
    echo "Payment Method: {$reservation->payment_method}\n";
    echo "Payment Status: {$reservation->payment_status}\n";
    echo "Total Price: {$reservation->total_price}\n";
    echo "Created: {$reservation->created_at}\n";
    
    // Get room details
    if ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($reservation->reservable_id);
        if ($room) {
            echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
            echo "Room Type: " . ($room->room_type->name ?? 'Unknown') . "\n";
        }
    }
    echo "   ---\n";
}

// Check for conflicts among recent flexible reservations
echo "\n=== CHECKING FOR CONFLICTS AMONG RECENT FLEXIBLE RESERVATIONS ===\n";

$conflicts = DB::table('reservations')
    ->select('reservable_id', 'check_in_date', 'check_out_date', 
           DB::raw('COUNT(*) as reservation_count'),
           DB::raw('GROUP_CONCAT(id) as reservation_ids'))
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->where('payment_method', 'cash')
    ->where('status', 'confirmed')
    ->where('created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 7 DAY)'))
    ->groupBy('reservable_id', 'check_in_date', 'check_out_date')
    ->havingRaw('COUNT(*) > 1')
    ->get();

echo "Found " . $conflicts->count() . " conflicts among recent flexible reservations:\n\n";

foreach ($conflicts as $conflict) {
    echo "=== CONFLICT ===\n";
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
    
    // Get individual reservations
    $individualReservations = DB::table('reservations')
        ->where('reservable_id', $conflict->reservable_id)
        ->where('check_in_date', $conflict->check_in_date)
        ->where('check_out_date', $conflict->check_out_date)
        ->where('payment_method', 'cash')
        ->where('status', 'confirmed')
        ->orderBy('created_at', 'asc')
        ->get(['id', 'customer_name', 'customer_email', 'created_at']);
    
    echo "Individual Reservations:\n";
    foreach ($individualReservations as $res) {
        echo "  - ID: " . $res->id . ", Customer: " . $res->customer_name . 
             ", Created: " . $res->created_at . "\n";
    }
    echo "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
