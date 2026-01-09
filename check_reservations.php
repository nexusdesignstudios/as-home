<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== Checking Reservations for Xiroses Property ===\n";

// Check for reservations during the problematic periods
$datesToCheck = [
    '2026-01-07', '2026-01-08', '2026-01-09', '2026-01-10', '2026-01-11', '2026-01-12', '2026-01-13', '2026-01-14', '2026-01-15', '2026-01-16', '2026-01-17', '2026-01-18', '2026-01-19',
    '2026-01-22', '2026-01-23',
    '2026-02-18', '2026-02-19'
];

foreach ($datesToCheck as $date) {
    echo "\n=== Checking reservations for date: $date ===\n";
    
    $reservations = DB::table('reservations')
        ->where('reservable_id', 387) // Xiroses property ID
        ->where(function($query) use ($date) {
            $query->where('check_in_date', '<=', $date)
                  ->where('check_out_date', '>', $date);
        })
        ->select('check_in_date', 'check_out_date', 'status', 'payment_method', 'reservable_data')
        ->get();
    
    if ($reservations->count() > 0) {
        echo "Found {$reservations->count()} reservation(s):\n";
        foreach ($reservations as $reservation) {
            echo "  {$reservation->check_in_date} to {$reservation->check_out_date} - Status: {$reservation->status} - Payment: {$reservation->payment_method}\n";
            
            // Check reservable_data for room-specific info
            if ($reservation->reservable_data) {
                $reservableData = json_decode($reservation->reservable_data, true);
                if (is_array($reservableData)) {
                    echo "  Reservable Data: " . json_encode($reservableData) . "\n";
                }
            }
        }
    } else {
        echo "No reservations found\n";
    }
}