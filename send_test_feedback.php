<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

$reservationId = 2971; // Using a valid hotel reservation ID found via script
$email = 'nexlancer.eg@gmail.com';

echo "Sending test feedback email for reservation $reservationId to $email...\n";

Artisan::call('test:feedback-request-email', [
    'reservation' => $reservationId,
    '--email' => $email,
    '--no-interaction' => true
]);

echo Artisan::output();
