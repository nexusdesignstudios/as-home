<?php
// Fix room 764 availability by adding Jan 13-14, 2026 to available_dates
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ROOM 764 AVAILABILITY ===\n\n";

$roomId = 764;

// 1. Get current room data
echo "1. CURRENT ROOM CONFIGURATION\n";
echo "============================\n";

$room = \App\Models\HotelRoom::find($roomId);
if (!$room) {
    echo "❌ Room $roomId not found!\n";
    exit(1);
}

echo "Room ID: {$room->id}\n";
echo "Room Type: " . ($room->roomType->name ?? 'N/A') . "\n";
echo "Current Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";

echo "\nCurrent Available Dates:\n";
if ($room->available_dates && count($room->available_dates) > 0) {
    foreach ($room->available_dates as $range) {
        echo "  - {$range['from']} to {$range['to']} (Price: " . ($range['price'] ?? 'N/A') . ")\n";
    }
} else {
    echo "  (empty - room available by default)\n";
}

// 2. Prepare new available_dates
echo "\n2. UPDATING AVAILABLE_DATES\n";
echo "==========================\n";

$newAvailableDates = [];
$basePrice = $room->price_per_night ?? 1400;

// Keep existing ranges
if ($room->available_dates && count($room->available_dates) > 0) {
    foreach ($room->available_dates as $range) {
        $newAvailableDates[] = [
            'from' => $range['from'],
            'to' => $range['to'],
            'price' => $range['price'] ?? $basePrice
        ];
    }
}

// Add Jan 13-14, 2026
$newAvailableDates[] = [
    'from' => '2026-01-13',
    'to' => '2026-01-14',
    'price' => $basePrice
];

echo "Adding new range: 2026-01-13 to 2026-01-14 (Price: $basePrice)\n";

// 3. Update the room
echo "\n3. APPLYING FIX\n";
echo "==============\n";

try {
    $room->available_dates = $newAvailableDates;
    $room->save();
    
    echo "✅ Room $roomId updated successfully!\n";
    
    echo "\nNew Available Dates:\n";
    foreach ($room->available_dates as $range) {
        echo "  - {$range['from']} to {$range['to']} (Price: " . ($range['price'] ?? 'N/A') . ")\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error updating room: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Verify the fix
echo "\n4. VERIFYING FIX\n";
echo "===============\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// Check if dates are now in available range
$dateInAvailableRange = false;
foreach ($room->available_dates as $range) {
    if ($checkInDate >= $range['from'] && $checkOutDate <= $range['to']) {
        $dateInAvailableRange = true;
        break;
    }
}

echo "Jan 13-14, 2026 in available range: " . ($dateInAvailableRange ? "YES ✅" : "NO ❌") . "\n";

// Check backend availability
$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "Backend availability check: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";

// 5. Test reservation simulation
echo "\n5. RESERVATION SIMULATION\n";
echo "========================\n";

echo "Simulating booking request for room $roomId:\n";
echo "  - Check-in: $checkInDate\n";
echo "  - Check-out: $checkOutDate\n";
echo "  - Room ID: $roomId\n";
echo "  - Amount: $basePrice\n";

// Check for blocking reservations
$hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
echo "  - Has overlapping reservations: " . ($hasOverlap ? "YES ❌" : "NO ✅") . "\n";

echo "\nExpected result: " . ($isAvailable && !$hasOverlap ? "✅ BOOKING SHOULD SUCCEED" : "❌ BOOKING SHOULD FAIL");

// 6. Frontend calendar update
echo "\n6. FRONTEND CALENDAR UPDATE\n";
echo "========================\n";

echo "The frontend calendar should now:\n";
echo "1. Show Jan 13-14, 2026 as AVAILABLE (green background)\n";
echo "2. Allow users to select these dates\n";
echo "3. Allow booking to proceed to checkout\n";
echo "4. Not show 500 error\n";

echo "\nNOTE: You may need to:\n";
echo "- Clear browser cache\n";
echo "- Refresh the calendar page\n";
echo "- Wait for data to reload\n";

echo "\n=== FIX COMPLETED ===\n";
echo "Room $roomId is now available for Jan 13-14, 2026\n";
echo "Test the booking flow to verify the fix works!\n";
