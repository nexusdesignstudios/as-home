<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

// Test the date range logic for reservation 896
echo "=== DEBUGGING DATE RANGE LOGIC ===\n\n";

$reservation = Reservation::find(896);
if ($reservation) {
    echo "Reservation #896:\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Status: {$reservation->status}\n";
    
    $checkInDate = new DateTime($reservation->check_in_date);
    $checkOutDate = new DateTime($reservation->check_out_date);
    
    echo "\nDate range test (inclusive check-in, exclusive check-out):\n";
    echo "  Check-in: " . $checkInDate->format('Y-m-d') . " (inclusive)\n";
    echo "  Check-out: " . $checkOutDate->format('Y-m-d') . " (exclusive)\n";
    
    $testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];
    
    foreach ($testDates as $date) {
        $testDate = new DateTime($date);
        $isWithinRange = $date >= $reservation->check_in_date && $date < $reservation->check_out_date;
        
        echo "\nTesting: $date\n";
        echo "  Date comparison: '$date' >= '{$reservation->check_in_date}' && '$date' < '{$reservation->check_out_date}'\n";
        echo "  Result: " . ($isWithinRange ? 'WITHIN RANGE' : 'OUTSIDE RANGE') . "\n";
        
        if ($isWithinRange) {
            echo "  Should block: YES (confirmed flexible reservation)\n";
        }
    }
}

echo "\n=== CHECKING ALL RESERVATIONS AGAIN ===\n";

$reservations = Reservation::whereIn('id', [896, 897, 898])->get();

foreach ($reservations as $reservation) {
    echo "\nReservation #{$reservation->id}:\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    
    $testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];
    
    foreach ($testDates as $date) {
        $isWithinRange = $date >= $reservation->check_in_date && $date < $reservation->check_out_date;
        
        if ($isWithinRange) {
            echo "  Blocks $date: YES\n";
        }
    }
}