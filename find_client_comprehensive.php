<?php
// Comprehensive script to find client email by mobile number or ID 522
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Reservation;

echo "--- Searching by Mobile: 201007825652 ---\n";
$mobile1 = '201007825652';
$c1 = Customer::where('mobile', $mobile1)->first();
if ($c1) {
    echo "Found Customer: " . $c1->name . " | Email: " . $c1->email . " | Mobile: " . $c1->mobile . "\n";
} else {
    echo "No customer found for $mobile1\n";
}

echo "\n--- Searching by Mobile suffix: 1007825652 ---\n";
$mobile2 = '1007825652';
$c2 = Customer::where('mobile', 'LIKE', '%' . $mobile2)->first();
if ($c2) {
    echo "Found Customer: " . $c2->name . " | Email: " . $c2->email . " | Mobile: " . $c2->mobile . "\n";
} else {
    echo "No customer found for *$mobile2\n";
}

echo "\n--- Searching by Reservation ID: 522 ---\n";
$r = Reservation::find(522);
if ($r) {
    echo "Reservation 522 found. Customer ID: " . $r->customer_id . "\n";
    if ($r->customer) {
        echo "Customer Name: " . $r->customer->name . " | Email: " . $r->customer->email . " | Mobile: " . $r->customer->mobile . "\n";
    } else {
        echo "No customer linked to Reservation 522.\n";
    }
} else {
    echo "Reservation 522 not found.\n";
}
