<?php
// Script to show reservations for hotels owned by shellghada@gmail.com
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;

$email = 'shellghada@gmail.com';
$customer = Customer::where('email', $email)->first();

if (!$customer) {
    echo "Customer with email $email was not found.\n";
    exit;
}

echo "Owner: " . $customer->name . " (ID: " . $customer->id . ")\n";

// Get property IDs owned by this customer
$propertyIds = Property::where('added_by', $customer->id)->pluck('id')->toArray();
echo "Found " . count($propertyIds) . " property IDs: " . implode(', ', $propertyIds) . "\n";

if (empty($propertyIds)) {
    echo "This owner does not have any hotel properties.\n";
    exit;
}

// Get reservations for these properties (direct and via rooms)
$reservations = Reservation::where(function($query) use ($propertyIds) {
    // Property reservations
    $query->where('reservable_type', 'App\Models\Property')
          ->whereIn('reservable_id', $propertyIds);
})->orWhere(function($query) use ($propertyIds) {
    // HotelRoom reservations
    $hotelRoomIds = HotelRoom::whereIn('property_id', $propertyIds)->pluck('id')->toArray();
    if (!empty($hotelRoomIds)) {
        $query->where('reservable_type', 'App\Models\HotelRoom')
              ->whereIn('reservable_id', $hotelRoomIds);
    } else {
        // Force no results if no rooms exist to avoid selecting all reservations
        $query->whereRaw('0 = 1');
    }
})
->with(['reservable', 'customer'])
->orderBy('check_out_date', 'desc')
->get();

echo "Found " . $reservations->count() . " total reservations:\n\n";

if ($reservations->count() > 0) {
    // Display in a table-like format
    printf("%-5s | %-30s | %-20s | %-12s | %-12s | %-10s | %-10s\n", 
           "ID", "Property/Room", "Client", "Check-in", "Check-out", "Status", "Amount");
    echo str_repeat("-", 120) . "\n";

    foreach ($reservations as $res) {
        $pName = ($res->reservable_type === 'App\Models\Property') 
                 ? ($res->reservable->title ?? 'N/A')
                 : ($res->reservable->property->title . " - " . $res->reservable->title);
        
        printf("%-5d | %-30s | %-20s | %-12s | %-12s | %-10s | %-10s\n",
               $res->id,
               substr($pName, 0, 30),
               substr($res->customer->name ?? 'N/A', 0, 20),
               $res->check_in_date->toDateString(),
               $res->check_out_date->toDateString(),
               $res->status,
               $res->total_price);
    }
} else {
    echo "No reservations found for this owner's properties.\n";
}
