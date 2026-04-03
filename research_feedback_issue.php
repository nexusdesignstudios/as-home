<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;

$resId = 2971;
$res = Reservation::find($resId);

if ($res) {
    echo "Reservation ID: $resId\n";
    echo "Reservable Type: " . $res->reservable_type . "\n";
    echo "Reservable ID: " . $res->reservable_id . "\n";
    
    $p = null;
    if ($res->reservable_type === 'App\Models\Property') {
        $p = $res->reservable;
    } elseif ($res->reservable_type === 'App\Models\HotelRoom') {
        $hr = HotelRoom::find($res->reservable_id);
        if ($hr) {
            echo "Hotel Room found. Property ID: " . $hr->property_id . "\n";
            $p = Property::find($hr->property_id);
        } else {
            echo "Hotel Room NOT found.\n";
        }
    }
    
    if ($p) {
        echo "Property Title: [" . ($p->title ?? 'EMPTY') . "]\n";
    } else {
        echo "Property NOT found.\n";
    }
} else {
    echo "Reservation NOT found.\n";
}

echo "\nWeb URL Setting: " . (system_setting('web_url') ?: 'NOT SET') . "\n";
