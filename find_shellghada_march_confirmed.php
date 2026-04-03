<?php
// Script to find ALL confirmed reservations for Shellghada Hotel in March 2026
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;
use Carbon\Carbon;

$title = 'Shellghada Hotel and Beach';
echo "Searching for properties titled like: $title\n";

$properties = Property::where('title', 'LIKE', "%$title%")->get();
echo "Found " . $properties->count() . " properties.\n";

$propertyIds = $properties->pluck('id')->toArray();
foreach ($properties as $p) {
    echo "- ID: {$p->id} | Title: {$p->title} | Owner: " . ($p->customer->email ?? 'N/A') . "\n";
}

if (empty($propertyIds)) {
    echo "No properties found.\n";
    exit;
}

$startDate = Carbon::parse('2026-03-01')->startOfMonth();
$endDate = Carbon::parse('2026-03-31')->endOfMonth();

echo "\nSearching for CONFIRMED reservations in March 2026 (Checkout in March)...\n";

$reservations = Reservation::where(function($query) use ($propertyIds) {
    $query->where('reservable_type', 'App\Models\Property')
          ->whereIn('reservable_id', $propertyIds);
})->orWhere(function($query) use ($propertyIds) {
    $hrIds = HotelRoom::whereIn('property_id', $propertyIds)->pluck('id')->toArray();
    if (!empty($hrIds)) {
        $query->where('reservable_type', 'App\Models\HotelRoom')
              ->whereIn('reservable_id', $hrIds);
    } else {
        $query->whereRaw('0 = 1');
    }
})
->where('status', 'confirmed')
// ->whereIn('payment_status', ['paid', 'cash']) // Including all for now
->whereBetween('check_out_date', [$startDate, $endDate])
->with(['reservable', 'customer'])
->get();

echo "Found " . $reservations->count() . " confirmed reservations:\n\n";

if ($reservations->count() > 0) {
    printf("%-5s | %-12s | %-25s | %-12s | %-12s | %-10s | %-10s | %-15s\n", 
           "ID", "Method", "Client", "Check-in", "Check-out", "Payment", "Amount", "Reference");
    echo str_repeat("-", 120) . "\n";

    foreach ($reservations as $res) {
        $clientEmail = $res->customer->email ?? 'N/A';
        printf("%-5d | %-12s | %-25s | %-12s | %-12s | %-10s | %-10s | %-15s\n",
               $res->id,
               $res->payment_method ?? 'cash',
               substr($clientEmail, 0, 25),
               $res->check_in_date->toDateString(),
               $res->check_out_date->toDateString(),
               $res->payment_status,
               $res->total_price,
               $res->transaction_id ?? 'N/A');
    }
} else {
    echo "No confirmed reservations found for March 2026.\n";
}
