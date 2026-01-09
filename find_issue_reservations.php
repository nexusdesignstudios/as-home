<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use Illuminate\Support\Facades\DB;

echo "=== SEARCHING FOR SPECIFIC RESERVATIONS FROM ISSUE ===\n\n";

// Search for reservations with the customer details from the issue
$customerEmail = 'nexlancer.eg@gmail.com';
$customerPhone = '201061874267';
$customerName = 'ibrahim ahmed';

$reservations = Reservation::where(function($query) use ($customerEmail, $customerPhone, $customerName) {
        $query->where('customer_email', $customerEmail)
              ->orWhere('customer_phone', $customerPhone)
              ->orWhere('customer_name', 'like', '%' . $customerName . '%');
    })
    ->where('reservable_type', 'App\\Models\\HotelRoom')
    ->with(['property', 'reservable'])
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found " . $reservations->count() . " reservations for this customer:\n\n";

foreach ($reservations as $reservation) {
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

// Check for conflicts among these reservations
if ($reservations->count() >= 2) {
    echo "\n=== CHECKING FOR CONFLICTS ===\n";
    
    $conflicts = DB::table('reservations')
        ->select('reservable_id', 'check_in_date', 'check_out_date', 
               DB::raw('COUNT(*) as reservation_count'),
               DB::raw('GROUP_CONCAT(id) as reservation_ids'))
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->where(function($query) use ($customerEmail, $customerPhone, $customerName) {
            $query->where('customer_email', $customerEmail)
                  ->orWhere('customer_phone', $customerPhone)
                  ->orWhere('customer_name', 'like', '%' . $customerName . '%');
        })
        ->where('status', '!=', 'cancelled')
        ->where('status', '!=', 'rejected')
        ->groupBy('reservable_id', 'check_in_date', 'check_out_date')
        ->havingRaw('COUNT(*) > 1')
        ->get();
    
    echo "Found " . $conflicts->count() . " conflicts:\n\n";
    
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
    }
}

echo "\n=== ANALYSIS COMPLETE ===\n";
