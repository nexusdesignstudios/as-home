<?php
// Test the fix
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING THE FIX ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// Test the fixed areDatesAvailable method
$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "areDatesAvailable result: " . ($isAvailable ? "✅ AVAILABLE" : "❌ NOT AVAILABLE") . "\n\n";

// Check room's available_dates
$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "Room available_dates:\n";
    if ($room->available_dates && count($room->available_dates) > 0) {
        foreach ($room->available_dates as $range) {
            echo "  - {$range['from']} to {$range['to']}\n";
        }
    } else {
        echo "  (empty - room should be available by default)\n";
    }
}

echo "\nFix verification: " . ($isAvailable ? "✅ SUCCESS" : "❌ FAILED") . "\n";
