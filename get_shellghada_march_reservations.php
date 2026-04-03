<?php
// Final corrected script to find ALL confirmed/approved reservations for Shellghada in March 2026
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use Carbon\Carbon;

$start = Carbon::parse('2026-03-01')->startOfMonth();
$end = Carbon::parse('2026-03-31')->endOfMonth();

echo "Searching for Shellghada reservations (Confirmed/Approved) with Checkout in March 2026...\n\n";

$reservations = Reservation::whereIn('status', ['confirmed', 'approved'])
    ->whereBetween('check_out_date', [$start, $end])
    ->with(['customer'])
    ->get();

$matches = [];
foreach ($reservations as $res) {
    $pTitle = 'Unknown';
    $reservable = null;
    
    // Manual lookup since reservable polymorphic link might use short names
    if ($res->reservable_type === 'hotel_room' || $res->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($res->reservable_id);
        if ($room) {
            $pTitle = ($room->property->title ?? 'N/A') . " - " . ($room->title ?? 'N/A');
        }
    } elseif ($res->reservable_type === 'property' || $res->reservable_type === 'App\Models\Property') {
        $prop = Property::find($res->reservable_id);
        if ($prop) {
            $pTitle = $prop->title ?? 'N/A';
        }
    }
    
    if (stripos($pTitle, 'Shellghada') !== false) {
        $matches[] = [
            'id' => $res->id,
            'client' => $res->customer->email ?? 'N/A',
            'property' => $pTitle,
            'check_in' => $res->check_in_date->toDateString(),
            'check_out' => $res->check_out_date->toDateString(),
            'status' => $res->status,
            'payment' => $res->payment_status,
            'method' => $res->payment_method ?? 'N/A',
            'amount' => $res->total_price,
            'transaction' => $res->transaction_id ?? 'N/A'
        ];
    }
}

if (count($matches) > 0) {
    printf("%-5s | %-10s | %-30s | %-30s | %-12s | %-12s | %-10s | %-10s\n", 
           "ID", "Status", "Property", "Client", "Check-in", "Check-out", "Payment", "Amount");
    echo str_repeat("-", 150) . "\n";
    foreach ($matches as $m) {
        printf("%-5d | %-10s | %-30s | %-30s | %-12s | %-12s | %-10s | %-10s\n",
               $m['id'],
               $m['status'],
               substr($m['property'], 0, 30),
               substr($m['client'], 0, 30),
               $m['check_in'],
               $m['check_out'],
               $m['payment'],
               $m['amount']);
    }
} else {
    echo "No matching reservations found.\n";
}
