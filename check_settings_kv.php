<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$keys = ['paypal_gateway', 'paypal_test_mode', 'currency_symbol', 'paypal_business_id', 'paypal_webhook_url'];

foreach ($keys as $key) {
    $setting = Setting::where('type', $key)->first();
    echo "$key: " . ($setting ? $setting->data : 'NOT FOUND') . "\n";
}
