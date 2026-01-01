<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

// Test the exact date parsing logic from the frontend
echo "=== TESTING FRONTEND DATE PARSING LOGIC ===\n\n";

$reservations = Reservation::whereIn('id', [896, 897, 898])->get();

foreach ($reservations as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
    echo "  Raw check_in_date: '{$reservation->check_in_date}'\n";
    echo "  Raw check_out_date: '{$reservation->check_out_date}'\n";
    
    // Simulate frontend date parsing
    $checkInDateRaw = $reservation->check_in_date;
    $checkOutDateRaw = $reservation->check_out_date;
    
    // Frontend logic
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkInDateRaw)) {
        $checkInDate = $checkInDateRaw;
        echo "  ✓ Check-in already in YYYY-MM-DD format: '{$checkInDate}'\n";
    } else {
        $checkInParsed = new DateTime($checkInDateRaw);
        $checkInDate = $checkInParsed->format('Y-m-d');
        echo "  ✓ Parsed check-in date: '{$checkInDate}'\n";
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOutDateRaw)) {
        $checkOutDate = $checkOutDateRaw;
        echo "  ✓ Check-out already in YYYY-MM-DD format: '{$checkOutDate}'\n";
    } else {
        $checkOutParsed = new DateTime($checkOutDateRaw);
        $checkOutDate = $checkOutParsed->format('Y-m-d');
        echo "  ✓ Parsed check-out date: '{$checkOutDate}'\n";
    }
    
    echo "\n  Date range test:\n";
    $testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];
    
    foreach ($testDates as $dateStr) {
        $dateInRange = $dateStr >= $checkInDate && $dateStr < $checkOutDate;
        echo "    {$dateStr} >= {$checkInDate} && {$dateStr} < {$checkOutDate} = " . ($dateInRange ? '✓ IN RANGE' : '✗ OUT OF RANGE') . "\n";
    }
    
    echo "\n";
}

// Test the corrected blocking logic with proper date parsing
echo "=== FINAL TEST WITH CORRECTED LOGIC ===\n\n";

foreach ($reservations as $reservation) {
    echo "Reservation #{$reservation->id}:\n";
    
    // Parse dates like frontend
    $checkInDateRaw = $reservation->check_in_date;
    $checkOutDateRaw = $reservation->check_out_date;
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkInDateRaw)) {
        $checkInDate = $checkInDateRaw;
    } else {
        $checkInParsed = new DateTime($checkInDateRaw);
        $checkInDate = $checkInParsed->format('Y-m-d');
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOutDateRaw)) {
        $checkOutDate = $checkOutDateRaw;
    } else {
        $checkOutParsed = new DateTime($checkOutDateRaw);
        $checkOutDate = $checkOutParsed->format('Y-m-d');
    }
    
    // Apply corrected blocking logic
    $actualStatus = strtolower($reservation->status ?? '');
    $displayStatus = strtolower($reservation->display_status ?? '');
    $reservationStatus = $displayStatus ?: $actualStatus;
    
    $isFlexibleReservation = true; // These are cash payments
    $statusToCheck = $reservation->display_status && trim($reservation->display_status) !== '' 
        ? $reservation->display_status 
        : $reservationStatus;
    
    $shouldBlock = $isFlexibleReservation ? 
        $statusToCheck !== 'cancelled' && $statusToCheck !== 'rejected' :
        in_array($reservationStatus, ["confirmed", "approved", "pending", "active"]);
    
    echo "  Date range: {$checkInDate} to {$checkOutDate} (inclusive check-in, exclusive check-out)\n";
    echo "  Status: {$reservation->status} (display: '{$reservation->display_status}')\n";
    echo "  Should Block: " . ($shouldBlock ? 'YES' : 'NO') . "\n";
    
    $testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];
    
    foreach ($testDates as $dateStr) {
        $dateInRange = $dateStr >= $checkInDate && $dateStr < $checkOutDate;
        $blocksThisDate = $dateInRange && $shouldBlock;
        echo "    {$dateStr}: " . ($blocksThisDate ? '🔒 BLOCKED' : '✅ AVAILABLE') . "\n";
    }
    
    echo "\n";
}