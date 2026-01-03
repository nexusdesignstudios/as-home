<?php
// Check if calendar is handling available_dates correctly
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CALENDAR AVAILABLE_DATES CHECK ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// 1. Get room details with available_dates
echo "1. ROOM AVAILABLE_DATES CONFIGURATION\n";
echo "===================================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "Room ID: {$room->id}\n";
    echo "Room Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
    echo "Room Type ID: {$room->room_type_id}\n";
    echo "Property ID: {$room->property_id}\n";
    
    if ($room->available_dates && count($room->available_dates) > 0) {
        echo "\nAvailable Dates Configuration:\n";
        foreach ($room->available_dates as $range) {
            $from = $range['from'];
            $to = $range['to'];
            $price = $range['price'] ?? 'N/A';
            
            // Check if requested dates fall in this range
            $inRange = ($checkInDate >= $from && $checkOutDate <= $to);
            echo "  Range: $from to $to (Price: $price)\n";
            echo "    Requested dates ($checkInDate to $checkOutDate) in range: " . ($inRange ? "YES ✅" : "NO ❌") . "\n";
            
            if ($inRange) {
                echo "    → Room should be AVAILABLE\n";
            }
        }
        
        // Check if any range includes the requested dates
        $dateInAvailableRange = false;
        foreach ($room->available_dates as $range) {
            if ($checkInDate >= $range['from'] && $checkOutDate <= $range['to']) {
                $dateInAvailableRange = true;
                break;
            }
        }
        
        echo "\nFinal Available Dates Check:\n";
        echo "  Requested dates in available range: " . ($dateInAvailableRange ? "YES ✅" : "NO ❌") . "\n";
        
        if (!$dateInAvailableRange) {
            echo "  → Room should be UNAVAILABLE due to available_dates configuration\n";
        }
    } else {
        echo "Available Dates: (empty - room available by default)\n";
        echo "→ Room should be AVAILABLE\n";
    }
}

// 2. Check what the frontend calendar should receive
echo "\n2. FRONTEND CALENDAR DATA EXPECTATION\n";
echo "===================================\n";

// Simulate what the frontend calendar would receive
$calendarData = [];
$startDate = new DateTime('2026-01-01');
$endDate = new DateTime('2026-01-31');

while ($startDate <= $endDate) {
    $dateStr = $startDate->format('Y-m-d');
    
    // Check if date is in available_dates
    $isAvailableByDates = true;
    if ($room && $room->available_dates && count($room->available_dates) > 0) {
        $isAvailableByDates = false;
        foreach ($room->available_dates as $range) {
            if ($dateStr >= $range['from'] && $dateStr <= $range['to']) {
                $isAvailableByDates = true;
                break;
            }
        }
    }
    
    // Check for reservations
    $hasReservation = \App\Models\Reservation::where('reservable_id', $roomId)
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->whereIn('status', ['confirmed', 'approved', 'pending'])
        ->where('check_in_date', '<=', $dateStr)
        ->where('check_out_date', '>', $dateStr)
        ->exists();
    
    $finalAvailability = $isAvailableByDates && !$hasReservation;
    
    if ($dateStr >= '2026-01-13' && $dateStr <= '2026-01-14') {
        echo "Date: $dateStr\n";
        echo "  Available by available_dates: " . ($isAvailableByDates ? "YES" : "NO") . "\n";
        echo "  Has reservation: " . ($hasReservation ? "YES" : "NO") . "\n";
        echo "  Final availability: " . ($finalAvailability ? "AVAILABLE ✅" : "UNAVAILABLE ❌") . "\n";
        echo "\n";
    }
    
    $startDate->add(new DateInterval('P1D'));
}

// 3. Check if frontend is receiving this data
echo "\n3. FRONTEND INTEGRATION CHECK\n";
echo "===========================\n";

echo "Frontend Calendar Should:\n";
echo "1. Load room data with available_dates\n";
echo "2. Check each date against available_dates\n";
echo "3. Mark dates outside available_ranges as unavailable\n";
echo "4. Show unavailable dates with red indicators\n\n";

echo "If frontend shows available when it shouldn't:\n";
echo "- Frontend is not checking available_dates\n";
echo "- Frontend is using old cached data\n";
echo "- Frontend has a bug in date comparison\n\n";

echo "RECOMMENDATION:\n";
echo "1. Check browser console for calendar logs\n";
echo "2. Verify frontend receives available_dates from API\n";
echo "3. Check if frontend compares dates correctly\n";
echo "4. Clear browser cache and reload\n";

echo "\nCheck completed.\n";
