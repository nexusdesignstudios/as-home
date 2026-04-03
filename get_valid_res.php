<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;

$res = Reservation::whereHas('reservable', function($q) {
    if (str_contains(get_class($q->getModel()), 'Property')) {
        $q->where('property_classification', 5);
    }
})->whereIn('status', ['confirmed', 'approved', 'completed'])->orderBy('id', 'desc')->first();

if (!$res) {
    // Try HotelRoom instead
    $res = Reservation::where('reservable_type', 'App\Models\HotelRoom')->whereIn('status', ['confirmed', 'approved', 'completed'])->orderBy('id', 'desc')->first();
}

if ($res) {
    echo $res->id;
} else {
    echo "NoneFound";
}
