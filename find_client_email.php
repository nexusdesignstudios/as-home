<?php
// Simple script to find client email for reservation 522
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\User;

$resId = 522;
$reservation = Reservation::find($resId);

if ($reservation) {
    echo "Reservation found (ID: $resId)\n";
    $customer = $reservation->customer;
    if ($customer) {
        echo "Customer Name: " . $customer->name . "\n";
        echo "Customer Email: " . $customer->email . "\n";
        echo "Customer Mobile: " . $customer->mobile . "\n";
    } else {
        $user = $reservation->user;
        if ($user) {
            echo "User Name: " . $user->name . "\n";
            echo "User Email: " . $user->email . "\n";
        } else {
            echo "No associated customer or user found for this reservation.\n";
        }
    }
} else {
    echo "Reservation with ID $resId was not found.\n";
    
    // Maybe 522 is a property ID and the user wants the owner's email?
    $property = \App\Models\Property::find(522);
    if ($property) {
        echo "Property found (ID: 522)\n";
        $owner = $property->customer;
        if ($owner) {
            echo "Owner Name: " . $owner->name . "\n";
            echo "Owner Email: " . $owner->email . "\n";
        } else {
            echo "No owner found for this property.\n";
        }
    } else {
        echo "Property with ID 522 was also not found.\n";
    }
}
