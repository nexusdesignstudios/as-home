<?php
// Script to identify owner and properties for nexlancer.eg@gmail.com
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Property;

$email = 'nexlancer.eg@gmail.com';
$customer = Customer::where('email', $email)->first();

if ($customer) {
    echo "Customer Found: " . $customer->name . " (ID: " . $customer->id . ")\n";
    $properties = Property::where('added_by', $customer->id)->get();
    echo "Found " . $properties->count() . " properties.\n";
    foreach ($properties as $p) {
        echo "- ID: {$p->id} | Title: {$p->title} | Class: {$p->property_classification}\n";
    }
} else {
    echo "No customer found with email $email\n";
}
