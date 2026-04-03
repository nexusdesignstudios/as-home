<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use Carbon\Carbon;

$today = Carbon::today();
echo "Looking for confirmed/completed reservations checking out on " . $today->toDateString() . "...\n";

$reservations = Reservation::whereDate('check_out_date', $today->toDateString())
    ->whereIn('status', ['confirmed', 'approved', 'completed'])
    ->get();

if ($reservations->count() === 0) {
    echo "No checkouts found for today. Checking last 7 days...\n";
    $reservations = Reservation::whereBetween('check_out_date', [$today->copy()->subDays(7), $today])
        ->whereIn('status', ['confirmed', 'approved', 'completed'])
        ->get();
}

foreach ($reservations as $res) {
    $propertyName = 'Unknown';
    if ($res->reservable_type === 'App\Models\Property') {
        $propertyName = $res->reservable->title ?? 'Property #' . $res->reservable_id;
    } elseif ($res->reservable_type === 'App\Models\HotelRoom') {
        $propertyName = $res->reservable->property->title ?? 'Hotel Room #' . $res->reservable_id;
    }
    
    echo "ID: {$res->id}, Customer: " . ($res->customer->name ?? 'N/A') . ", Property: {$propertyName}, Checkout: {$res->check_out_date->toDateString()}, Status: {$res->status}\n";
}
