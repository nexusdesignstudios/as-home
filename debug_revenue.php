<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "Searching for reservations with price between 8370 and 8380...\n";
$resList = Reservation::whereBetween('total_price', [8370, 8380])->get();
echo "Found: " . $resList->count() . "\n";

foreach ($resList as $res) {
    echo "ID: " . $res->id . " | Price: " . $res->total_price . " | Status: " . $res->status . "\n";
    if ($res->property) {
        echo "  Owner: " . $res->property->added_by . "\n";
    } else {
        echo "  Property not found or ID null.\n";
    }
}
