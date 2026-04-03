<?php
// Simple script to find owner ID for reservationshellghada@gmail.com
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$email = 'reservationshellghada@gmail.com';
$customer = Customer::where('email', $email)->first();
if ($customer) {
    echo $customer->id;
} else {
    echo "NOT FOUND";
}
