<?php
use App\Models\HotelRoom;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reservation = \App\Models\Reservation::find(2906);
if ($reservation) {
    echo "Reservation 2906: Room ID " . $reservation->reservable_id . ", Refund Policy: " . $reservation->refund_policy . PHP_EOL;
} else {
    echo "Reservation 2906 not found" . PHP_EOL;
}
