<?php
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== TESTING RESERVATION MATCHING LOGIC ===\n\n";

// Get all reservations for property 351
$reservations = Reservation::where('property_id', 351)
    ->whereIn('status', ['pending', 'approved', 'confirmed'])
    ->get();

echo "Total reservations: " . $reservations->count() . "\n\n";

// Test room IDs 755-762 (the 8 rooms for Green Hotel 2)
$testRoomIds = [755, 756, 757, 758, 759, 760, 761, 762];
$testDate = '2026-01-01';

foreach ($testRoomIds as $roomId) {
    echo "🏨 Room $roomId:\n";
    
    foreach ($reservations as $reservation) {
        $checkInDate = substr($reservation->check_in_date, 0, 10);
        $checkOutDate = substr($reservation->check_out_date, 0, 10);
        
        // Method 1: Direct room ID match
        $method1Match = $reservation->reservable_id === $roomId;
        
        // Method 2: Property ID with room data in reservable_data
        $method2Match = false;
        if ($reservation->reservable_id === 351 && $reservation->reservable_data) {
            $reservableData = json_decode($reservation->reservable_data, true);
            if (is_array($reservableData)) {
                foreach ($reservableData as $roomData) {
                    if (isset($roomData['id']) && $roomData['id'] === $roomId) {
                        $method2Match = true;
                        break;
                    }
                }
            }
        }
        
        $roomIdMatch = $method1Match || $method2Match;
        $dateInRange = $testDate >= $checkInDate && $testDate < $checkOutDate;
        $isWithinReservation = $roomIdMatch && $dateInRange;
        
        if ($isWithinReservation) {
            echo "  🔒 BLOCKED by Reservation {$reservation->id}:\n";
            echo "     Check-in: $checkInDate, Check-out: $checkOutDate\n";
            echo "     Status: {$reservation->status}, Payment: {$reservation->payment_method}\n";
            echo "     Method: " . ($method1Match ? "Direct ID" : "Property + Data") . "\n";
        }
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
$blockedRooms = [];
foreach ($testRoomIds as $roomId) {
    $isBlocked = false;
    foreach ($reservations as $reservation) {
        $checkInDate = substr($reservation->check_in_date, 0, 10);
        $checkOutDate = substr($reservation->check_out_date, 0, 10);
        
        // Method 1: Direct room ID match
        $method1Match = $reservation->reservable_id === $roomId;
        
        // Method 2: Property ID with room data in reservable_data
        $method2Match = false;
        if ($reservation->reservable_id === 351 && $reservation->reservable_data) {
            $reservableData = json_decode($reservation->reservable_data, true);
            if (is_array($reservableData)) {
                foreach ($reservableData as $roomData) {
                    if (isset($roomData['id']) && $roomData['id'] === $roomId) {
                        $method2Match = true;
                        break;
                    }
                }
            }
        }
        
        $roomIdMatch = $method1Match || $method2Match;
        $dateInRange = $testDate >= $checkInDate && $testDate < $checkOutDate;
        
        if ($roomIdMatch && $dateInRange) {
            $isBlocked = true;
            break;
        }
    }
    
    if ($isBlocked) {
        $blockedRooms[] = $roomId;
    }
}

echo "Rooms that should be BLOCKED on $testDate: " . implode(', ', $blockedRooms) . "\n";
echo "Rooms that should show 100% available: " . implode(', ', array_diff($testRoomIds, $blockedRooms)) . "\n";