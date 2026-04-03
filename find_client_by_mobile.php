<?php
// Script to find client email by mobile number
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\User;
use App\Models\Reservation;

$mobile = '201007825652';
echo "Searching for client with mobile: $mobile\n";

$customer = Customer::where('mobile', $mobile)->first();
if ($customer) {
    echo "Customer found:\n";
    echo "ID: " . $customer->id . "\n";
    echo "Name: " . $customer->name . "\n";
    echo "Email: " . $customer->email . "\n";
    echo "Mobile: " . $customer->mobile . "\n";
    
    $res = Reservation::where('customer_id', $customer->id)->latest()->first();
    if ($res) {
        echo "Latest reservation ID: " . $res->id . "\n";
    }
} else {
    echo "No customer found with mobile $mobile. Searching users...\n";
    $user = User::where('mobile', $mobile)->first();
    if ($user) {
        echo "User found:\n";
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
    } else {
        echo "No client found with this mobile number.\n";
    }
}
