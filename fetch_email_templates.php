<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$confirmation = system_setting('reservation_confirmation_mail_template');
echo "=== Confirmation Template ===\n" . $confirmation . "\n\n";

$invoice = system_setting('hotel_booking_tax_invoice_flexible_mail_template');
echo "=== Invoice Template ===\n" . $invoice . "\n\n";
