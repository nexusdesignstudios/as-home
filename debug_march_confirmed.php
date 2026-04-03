<?php
// Script to investigate why my search for Shellghada March confirmed reservations failed
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use Carbon\Carbon;

echo "--- Checking Reservation 2959 and 2960 ---\n";
foreach ([2959, 2960] as $id) {
    $res = Reservation::find($id);
    if ($res) {
        echo "Res ID: $id | Status: {$res->status} | Checkout: " . optional($res->check_out_date)->toDateString() . "\n";
        echo "Reservable: {$res->reservable_type} (ID: {$res->reservable_id})\n";
        if ($res->reservable) {
            if ($res->reservable_type === 'App\Models\Property') {
                 echo "Property Title: " . $res->reservable->title . "\n";
            } elseif ($res->reservable_type === 'App\Models\HotelRoom') {
                 echo "HotelRoom Title: " . $res->reservable->title . "\n";
                 echo "Property ID: " . $res->reservable->property_id . "\n";
                 if ($res->reservable->property) {
                     echo "Property Title: " . $res->reservable->property->title . "\n";
                 } else {
                     echo "ERROR: Property not found for this room!\n";
                 }
            }
        } else {
             echo "ERROR: Reservable not found!\n";
        }
    } else {
        echo "Res ID: $id NOT FOUND IN DB.\n";
    }
    echo "\n";
}

echo "--- Searching for all CONFIRMED reservations in March 2026 (Checkout in March) ---\n";
$start = Carbon::parse('2026-03-01')->startOfMonth();
$end = Carbon::parse('2026-03-31')->endOfMonth();

$allConfirmed = Reservation::where('status', 'confirmed')
    ->whereBetween('check_out_date', [$start, $end])
    ->with(['reservable', 'customer'])
    ->get();

echo "Found " . $allConfirmed->count() . " TOTAL confirmed reservations in March 2026.\n";
foreach ($allConfirmed as $res) {
    $pTitle = 'Unknown';
    if ($res->reservable_type === 'App\Models\Property') {
        $pTitle = $res->reservable->title ?? 'N/A';
    } else if ($res->reservable_type === 'App\Models\HotelRoom') {
        $pTitle = ($res->reservable->property->title ?? 'N/A') . " - " . ($res->reservable->title ?? 'N/A');
    }
    
    // Check if it matches "Shellghada"
    if (stripos($pTitle, 'Shellghada') !== false) {
        echo "[MATCH] ID: {$res->id} | Property: $pTitle | Status: {$res->status} | Checkout: " . $res->check_out_date->toDateString() . "\n";
    }
}
