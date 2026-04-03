<?php
// Fixed exhaustive script to find reservations related to shellghada@gmail.com
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;

$email = 'shellghada@gmail.com';
$customer = Customer::where('email', $email)->first();

if (!$customer) {
    echo "Customer with email $email was not found.\n";
    exit;
}

echo "Shellghada (ID: " . $customer->id . ") Email: " . $customer->email . "\n\n";

// 1. AS OWNER: Reservations for her hotels
echo "--- AS OWNER (Properties 519, 520) ---\n";
$ownerPropertyIds = Property::where('added_by', $customer->id)->pluck('id')->toArray();
$ownerReservations = Reservation::where(function($q) use ($ownerPropertyIds) {
    $q->where('reservable_type', 'App\Models\Property')->whereIn('reservable_id', $ownerPropertyIds);
})->orWhere(function($q) use ($ownerPropertyIds) {
    $hrIds = HotelRoom::whereIn('property_id', $ownerPropertyIds)->pluck('id')->toArray();
    if (!empty($hrIds)) $q->where('reservable_type', 'App\Models\HotelRoom')->whereIn('reservable_id', $hrIds);
    else $q->whereRaw('0=1');
})->with(['reservable', 'customer'])->get();

echo "Found " . $ownerReservations->count() . " reservations for her properties.\n";
foreach ($ownerReservations as $res) {
    $pName = 'Unknown';
    if ($res->reservable) {
        if ($res->reservable_type === 'App\Models\Property') {
            $pName = $res->reservable->title ?? 'N/A';
        } else if ($res->reservable_type === 'App\Models\HotelRoom') {
            $pName = ($res->reservable->property->title ?? 'Deleted Hotel') . " - " . ($res->reservable->title ?? 'Room');
        }
    }
    echo "- Res ID: {$res->id} | Property: $pName | Client: " . ($res->customer->name ?? 'N/A') . " | Status: {$res->status} | Checkout: " . $res->check_out_date->toDateString() . "\n";
}

// 2. AS CUSTOMER: Reservations she made
echo "\n--- AS CUSTOMER (Reservations she made) ---\n";
$customerReservations = Reservation::where('customer_id', $customer->id)->with(['reservable'])->get();
echo "Found " . $customerReservations->count() . " reservations she made.\n";
foreach ($customerReservations as $res) {
    $pName = 'Unknown';
    if ($res->reservable) {
        if ($res->reservable_type === 'App\Models\Property') {
            $pName = $res->reservable->title ?? 'N/A';
        } else if ($res->reservable_type === 'App\Models\HotelRoom') {
             $pName = ($res->reservable->property->title ?? 'Deleted Hotel') . " - " . ($res->reservable->title ?? 'Room');
        }
    }
    echo "- Res ID: {$res->id} | Property: $pName | Status: {$res->status} | Checkout: " . $res->check_out_date->toDateString() . "\n";
}

// 3. SEARCH Property 522 Reservations
echo "\n--- FOR PROPERTY 522 (Ref 522) ---\n";
$p522 = Property::find(522);
if ($p522) {
    echo "Property 522: " . $p522->title . " | Owner: " . ($p522->customer->name ?? 'N/A') . " (" . ($p522->customer->email ?? 'N/A') . ")\n";
    $p522Reservations = Reservation::where(function($q) use ($p522) {
        $q->where('reservable_type', 'App\Models\Property')->where('reservable_id', 522);
    })->orWhere(function($q) use ($p522) {
        $hrIds = HotelRoom::where('property_id', 522)->pluck('id')->toArray();
        if (!empty($hrIds)) $q->where('reservable_type', 'App\Models\HotelRoom')->whereIn('reservable_id', $hrIds);
        else $q->whereRaw('0=1');
    })->with(['customer'])->get();
    echo "Found " . $p522Reservations->count() . " reservations for Property 522.\n";
    foreach ($p522Reservations as $res) {
        echo "- Res ID: {$res->id} | Client: " . ($res->customer->name ?? 'N/A') . " (" . ($res->customer->email ?? 'N/A') . ") | Status: {$res->status} | Checkout: " . $res->check_out_date->toDateString() . "\n";
    }
} else {
    echo "Property 522 not found.\n";
}
