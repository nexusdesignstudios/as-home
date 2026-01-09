<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== Checking Reservations for Xiroses Hotel Rooms ===\n";

// Check for reservations for rooms 812-816
$roomIds = [812, 813, 814, 815, 816];

foreach ($roomIds as $roomId) {
    echo "\n--- Room $roomId ---\n";
    
    $reservations = DB::table('reservations')
        ->where('reservable_id', $roomId)
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->select('check_in_date', 'check_out_date', 'status', 'payment_method', 'customer_id', 'total_price')
        ->orderBy('check_in_date', 'desc')
        ->limit(5)
        ->get();
    
    if ($reservations->count() > 0) {
        echo "Found {$reservations->count()} reservation(s):\n";
        foreach ($reservations as $reservation) {
            echo "  {$reservation->check_in_date} to {$reservation->check_out_date} - Status: {$reservation->status} - Payment: {$reservation->payment_method} - Price: {$reservation->total_price}\n";
        }
    } else {
        echo "No reservations found\n";
    }
}

echo "\n=== Summary ===\n";
echo "Hotel: Sharm El-Sheikh 5 stars Xiroses (ID: 387)\n";
echo "Rooms: 812, 813, 814, 815, 816\n";
echo "Availability Type: 2 (Closed Period)\n";
echo "Closed Periods:\n";
echo "  - Jan 7-19, 2026 (type: dead)\n";
echo "  - Jan 22-Feb 19, 2026 (type: dead)\n";
echo "  - Feb 22, 2026 - Jan 7, 2027 (type: dead)\n";
echo "Status: All dates are CLOSED/UNAVAILABLE\n";
