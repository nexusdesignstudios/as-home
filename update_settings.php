<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$settings = Setting::first();

if ($settings) {
    $settings->paypal_gateway = 1;
    $settings->paypal_test_mode = 1;
    // We don't necessarily want to change the global currency if it affects other things, 
    // but the user said "Paypal Currency Symbol USD". 
    // If 'currency' in settings is global, changing it might affect other gateways.
    // However, Paypal.php uses env('PAYPAL_CURRENCY'), so this settings column might be for display or other gateways.
    // Let's safe update it if it's empty, or leave it if it's set.
    // Given the user input, let's set it to USD if it's currently empty.
    if (empty($settings->currency)) {
        $settings->currency = 'USD';
    }
    
    $settings->save();
    
    echo "Settings Updated:\n";
    echo "paypal_gateway: " . $settings->paypal_gateway . "\n";
    echo "paypal_test_mode: " . $settings->paypal_test_mode . "\n";
    echo "currency: " . $settings->currency . "\n"; 
} else {
    echo "No settings found to update.\n";
}
