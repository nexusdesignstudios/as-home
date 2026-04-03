<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invoice_template = system_setting('hotel_booking_tax_invoice_flexible_mail_template');
file_put_contents('invoice_template_debug.html', $invoice_template);
echo "Length: " . strlen($invoice_template) . "\n";
echo "Saved to invoice_template_debug.html\n";
