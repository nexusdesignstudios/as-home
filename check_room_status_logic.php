<?php
// Check if backend rejects inactive rooms
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECK ROOM STATUS LOGIC ===\n\n";

$roomId = 764;

// 1. Check room status
echo "1. ROOM STATUS CHECK\n";
echo "==================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "Room ID: {$room->id}\n";
    echo "Status: " . ($room->status ? 'Active (1)' : 'Inactive (0)') . "\n";
    echo "Raw Status: " . $room->getRawOriginal('status') . "\n";
    echo "Status Type: " . gettype($room->status) . "\n";
}

// 2. Check ReservationController for room status checks
echo "\n2. ROOM STATUS CHECKS IN RESERVATIONCONTROLLER\n";
echo "============================================\n";

$controllerFile = app_path('Http/Controllers/ReservationController.php');
$controllerContent = file_get_contents($controllerFile);

// Look for room status checks
if (strpos($controllerContent, 'room->status') !== false) {
    echo "✅ Found room status checks in ReservationController\n";
    
    // Extract relevant lines
    $lines = explode("\n", $controllerContent);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'room->status') !== false) {
            echo "   Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ No room status checks found in ReservationController\n";
}

// 3. Check createReservationWithPayment method
echo "\n3. ROOM STATUS IN createReservationWithPayment\n";
echo "==========================================\n";

// Look for the specific method
$pattern = '/public function createReservationWithPayment.*?^}/ms';
if (preg_match($pattern, $controllerContent, $matches)) {
    $methodContent = $matches[0];
    
    if (strpos($methodContent, 'status') !== false) {
        echo "Found status checks in createReservationWithPayment:\n";
        $lines = explode("\n", $methodContent);
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, 'status') !== false && strpos($line, 'room->status') !== false) {
                echo "   Line: " . trim($line) . "\n";
            }
        }
    }
}

// 4. Test availability check with inactive room
echo "\n4. AVAILABILITY CHECK WITH INACTIVE ROOM\n";
echo "======================================\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// Check if ReservationService considers room status
$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "ReservationService::areDatesAvailable result: " . ($isAvailable ? "AVAILABLE" : "NOT AVAILABLE") . "\n";

// 5. Check if there's a specific check for inactive rooms
echo "\n5. INACTIVE ROOM HANDLING\n";
echo "========================\n";

// Look for inactive room checks in the controller
if (strpos($controllerContent, 'status === false') !== false) {
    echo "Found checks for status === false:\n";
    $lines = explode("\n", $controllerContent);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'status === false') !== false) {
            echo "   Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
        }
    }
}

// 6. Check the exact error that would be returned
echo "\n6. SIMULATE BOOKING REQUEST\n";
echo "==========================\n";

// Create a mock request similar to what frontend sends
$mockRequest = new \stdClass();
$mockRequest->property_id = 357;
$mockRequest->reservable_type = 'hotel_room';
$mockRequest->reservable_id = [['id' => 764, 'amount' => 1694]];
$mockRequest->check_in_date = '2026-01-13';
$mockRequest->check_out_date = '2026-01-14';
$mockRequest->number_of_guests = 1;
$mockRequest->special_requests = '';
$mockRequest->payment = [
    'amount' => 1694,
    'email' => 'test@example.com',
    'first_name' => 'Test',
    'last_name' => 'User',
    'phone' => '1234567890'
];

echo "Mock request created for room $roomId (status: " . ($room->status ? 'Active' : 'Inactive') . ")\n";
echo "This simulates what the frontend sends\n";

// 7. Check if room status is validated before availability
echo "\n7. ROOM STATUS VALIDATION ORDER\n";
echo "==============================\n";

echo "Typical validation order:\n";
echo "1. Check if room exists\n";
echo "2. Check if room is active (status = 1)\n";
echo "3. Check availability (reservations)\n";
echo "4. Process booking\n\n";

echo "If step 2 fails (room inactive), booking should be rejected\n";

// 8. Quick fix suggestion
echo "\n8. QUICK FIX\n";
echo "==========\n";

if ($room && !$room->status) {
    echo "❌ Room $roomId is INACTIVE\n";
    echo "   This is likely causing the 500 error\n";
    echo "   Fix: UPDATE hotel_rooms SET status = 1 WHERE id = 764;\n";
    echo "   Or check why the room was marked inactive\n";
} else {
    echo "✅ Room $roomId is ACTIVE\n";
    echo "   The issue is elsewhere\n";
}

echo "\nCheck completed.\n";
