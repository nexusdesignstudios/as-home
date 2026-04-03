<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Models\Property;

$res = Reservation::find(2959);
if ($res) {
    if ($res->reservable_type === 'App\Models\Property' || $res->reservable_type === 'property') {
        $p = $res->reservable;
    } elseif ($res->reservable_type === 'App\Models\HotelRoom' || $res->reservable_type === 'hotel_room') {
        $hr = HotelRoom::find($res->reservable_id);
        $p = $hr ? Property::find($hr->property_id) : null;
    }
    
    if ($p) {
        echo "Prop ID: " . $p->id . " | Title: " . $p->title . "\n";
    } else {
        echo "No property found\n";
    }
} else {
    echo "Res not found\n";
}
