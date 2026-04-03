<?php
// Throrough script to check for ANY reservations in March 2026 that could trigger a tax invoice
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;
use Carbon\Carbon;

$startDate = Carbon::parse('2026-03-01')->startOfMonth();
$endDate = Carbon::parse('2026-03-01')->endOfMonth();

echo "Checking ALL reservations between " . $startDate->toDateString() . " and " . $endDate->toDateString() . "\n";

// 1. Check Hotels (Classification 5)
$hotelPropertyIds = Property::where('property_classification', 5)->pluck('id');
$hotelRoomIds = HotelRoom::whereIn('property_id', $hotelPropertyIds)->pluck('id');

$hotelReservations = Reservation::where(function($query) use ($hotelPropertyIds, $hotelRoomIds) {
    $query->where(function($q) use ($hotelPropertyIds) {
        $q->where('reservable_type', 'App\Models\Property')
          ->whereIn('reservable_id', $hotelPropertyIds);
    })->orWhere(function($q) use ($hotelRoomIds) {
        $q->where('reservable_type', 'App\Models\HotelRoom')
          ->whereIn('reservable_id', $hotelRoomIds);
    });
})
->where('status', 'confirmed')
->whereIn('payment_status', ['paid', 'cash'])
->whereBetween('check_out_date', [$startDate, $endDate])
->get();

echo "Found " . $hotelReservations->count() . " confirmed Hotel reservations in March 2026.\n";

// 2. Check Vacation Homes (Classification 4)
$vacationHomePropertyIds = Property::where('property_classification', 4)->pluck('id');
$vacationHomeReservations = Reservation::where('reservable_type', 'App\Models\Property')
->whereIn('reservable_id', $vacationHomePropertyIds)
->where('status', 'confirmed')
->whereIn('payment_status', ['paid', 'cash'])
->whereBetween('check_out_date', [$startDate, $endDate])
->get();

echo "Found " . $vacationHomeReservations->count() . " confirmed Vacation Home reservations in March 2026.\n";

// 3. Check for ANY confirmed reservation in March 2026 regardless of classification
$allConfirmed = Reservation::where('status', 'confirmed')
->whereBetween('check_out_date', [$startDate, $endDate])
->get();

echo "Found " . $allConfirmed->count() . " TOTAL confirmed reservations in March 2026.\n";

if ($allConfirmed->count() > 0) {
    echo "Reservations found:\n";
    foreach ($allConfirmed as $res) {
        $p = null;
        if ($res->reservable_type === 'App\Models\Property') {
            $p = Property::find($res->reservable_id);
        } elseif ($res->reservable_type === 'App\Models\HotelRoom') {
            $hr = HotelRoom::find($res->reservable_id);
            if ($hr) $p = Property::find($hr->property_id);
        }
        $class = $p ? $p->getRawOriginal('property_classification') : 'N/A';
        echo "- ID: {$res->id}, Class: {$class}, Method: {$res->payment_method}, Status: {$res->status}, Payment: {$res->payment_status}, Checkout: {$res->check_out_date->toDateString()}\n";
    }
}
