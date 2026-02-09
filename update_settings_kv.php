<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$updates = [
    'paypal_gateway' => '1',
    'paypal_test_mode' => '1',
    'paypal_business_id' => 'sb-qznyx49181595@business.example.com',
    'paypal_webhook_url' => 'https://maroon-fox-767665.hostingersite.com/api/payments/paypal/ipn',
    'paypal_currency_code' => 'USD'
];

foreach ($updates as $key => $value) {
    Setting::updateOrCreate(
        ['type' => $key],
        ['data' => $value]
    );
    echo "Updated $key to $value\n";
}
