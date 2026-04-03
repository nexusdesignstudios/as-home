<?php
// Exhaustive check for ahmed@cairo-trade.com reservations and Shellghada hotel data
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use Carbon\Carbon;

$email = 'ahmed@cairo-trade.com';
$customer = Customer::where('email', $email)->first();

if ($customer) {
    echo "--- CUSTOMER FOUND: {$customer->name} (ID: {$customer->id}) ---\n";
    $reservations = Reservation::where('customer_id', $customer->id)
        ->with(['reservable'])
        ->get();
    
    echo "Found " . $reservations->count() . " total reservations:\n";
    foreach ($reservations as $res) {
        $pTitle = 'Unknown';
        if ($res->reservable_type === 'hotel_room' || $res->reservable_type === 'App\Models\HotelRoom') {
            $room = HotelRoom::find($res->reservable_id);
            $pTitle = ($room->property->title ?? 'N/A') . " - " . ($room->title ?? 'N/A');
        } elseif ($res->reservable_type === 'property' || $res->reservable_type === 'App\Models\Property') {
            $prop = Property::find($res->reservable_id);
            $pTitle = $prop->title ?? 'N/A';
        }
        
        echo "- ID: {$res->id} | Status: {$res->status} | P-Status: {$res->payment_status} | Method: " . ($res->payment_method ?? 'N/A') . " | In: " . $res->check_in_date->toDateString() . " | Out: " . $res->check_out_date->toDateString() . " | Amount: {$res->total_price} | Property: $pTitle\n";
    }
} else {
    echo "Customer with email $email NOT FOUND.\n";
}

echo "\n--- SEARCHING FOR ALL MARCH 2026 RESERVATIONS (Status: Confirmed or Approved) ---\n";
$start = Carbon::parse('2026-03-01')->startOfMonth();
$end = Carbon::parse('2026-03-31')->endOfMonth();

$allMarch = Reservation::whereIn('status', ['confirmed', 'approved'])
    ->whereBetween('check_out_date', [$start, $end])
    ->with(['customer'])
    ->get();

echo "Found " . $allMarch->count() . " TOTAL confirmed/approved reservations in March.\n";
foreach ($allMarch as $res) {
    $pTitle = 'Unknown';
    if ($res->reservable_type === 'hotel_room' || $res->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($res->reservable_id);
        $pTitle = ($room->property->title ?? 'N/A') . " - " . ($room->title ?? 'N/A');
    } elseif ($res->reservable_type === 'property' || $res->reservable_type === 'App\Models\Property') {
        $prop = Property::find($res->reservable_id);
        $pTitle = $prop->title ?? 'N/A';
    }
    
    if (stripos($pTitle, 'Shellghada') !== false) {
         echo "[MATCH] ID: {$res->id} | Client: " . ($res->customer->email ?? 'N/A') . " | Status: {$res->status} | Out: " . $res->check_out_date->toDateString() . " | Amount: {$res->total_price} | Property: $pTitle\n";
    }
}
