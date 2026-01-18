<?php

use App\Models\Reservation;

$reservations = Reservation::orderBy('id', 'desc')->take(5)->get();

foreach ($reservations as $r) {
    echo "ID: " . $r->id . " | Total: " . $r->total_price . " | ReservableID: " . $r->reservable_id . " | Type: " . $r->reservable_type . "\n";
    echo "Data: " . substr($r->reservable_data, 0, 100) . "...\n";
    echo "--------------------------------------------------\n";
}
