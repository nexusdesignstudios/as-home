<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$settings = Setting::first();

if ($settings) {
    echo "Settings Found:\n";
    echo "paypal_gateway: " . $settings->paypal_gateway . "\n";
    echo "paypal_test_mode: " . $settings->paypal_test_mode . "\n";
    echo "currency: " . $settings->currency . "\n"; 
} else {
    echo "No settings found.\n";
}
