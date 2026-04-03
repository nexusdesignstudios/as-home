<?php
// Script to cancel reservations 1211 and 2894
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

$ids = [1211, 2894];
foreach ($ids as $id) {
    $res = Reservation::find($id);
    if ($res) {
        $res->status = 'cancelled';
        if ($res->save()) {
            echo "Reservation ID $id successfully updated to cancelled.\n";
        } else {
            echo "Failed to save Reservation ID $id.\n";
        }
    } else {
        echo "Reservation ID $id not found.\n";
    }
}
