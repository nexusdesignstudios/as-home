<?php

use App\Models\Setting;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function update_setting($key, $value) {
    $setting = Setting::where('type', $key)->first();
    if ($setting) {
        $setting->data = $value;
        $setting->save();
        echo "Updated $key.\n";
    } else {
        Setting::create([
            'type' => $key,
            'data' => $value
        ]);
        echo "Created $key.\n";
    }
}

$clientId = 'ASq2qzxzzPMM3uRoN_Res9K84KTeRpOjH34SFqFEs0NJ78VQ4NsQ0htkpStdcWVujti2D6szBRQk8Axe';
$secret = 'EApqSoBOhArcqxkcio-flwz2Po1niOvwO3__IW1UNo41KIOOrYbsvcOfAQYe1kYuHFjrFgqooO1BZZyq';

update_setting('paypal_client_id', $clientId);
update_setting('paypal_secret', $secret);
update_setting('paypal_test_mode', '1'); // Ensure sandbox is on
update_setting('paypal_currency_code', 'USD');
update_setting('paypal_gateway', '1'); // Enable PayPal

echo "PayPal credentials updated successfully.\n";
