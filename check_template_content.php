<?php
// Script to get the content of hotel_booking_tax_invoice_flexible template
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HelperService;

$templateKey = 'hotel_booking_tax_invoice_flexible';
$type = HelperService::getEmailTemplatesTypes($templateKey);
if ($type) {
    echo "TYPE KEY: " . $type['type'] . "\n";
    echo "CONTENT: " . system_setting($type['type']) . "\n";
} else {
    echo "TEMPLATE TYPE NOT FOUND\n";
}
