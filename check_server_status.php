<?php
// Check server status and debug the issue
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SERVER STATUS CHECK ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Checking room availability on server:\n";
echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// 1. Check if availability data exists on server
echo "1. AVAILABILITY DATA CHECK\n";
echo "========================\n";

$availabilityExists = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->where('from_date', '<=', $checkInDate)
    ->where('to_date', '>=', $checkOutDate)
    ->exists();

echo "Availability exists in database: " . ($availabilityExists ? "YES ✅" : "NO ❌") . "\n";

if (!$availabilityExists) {
    echo "❌ The fix hasn't been deployed to the server yet!\n";
    echo "   The availability data was only added to local database.\n";
    echo "   You need to deploy the database changes to the server.\n";
}

// 2. Check ReservationService code
echo "\n2. RESERVATIONSERVICE CODE CHECK\n";
echo "===============================\n";

$reservationServiceFile = app_path('Services/ReservationService.php');
$reservationServiceContent = file_get_contents($reservationServiceFile);

if (strpos($reservationServiceContent, 'available_dates_hotel_rooms') !== false) {
    echo "✅ ReservationService.php has been updated\n";
} else {
    echo "❌ ReservationService.php hasn't been updated on server\n";
    echo "   The code changes were only made locally.\n";
    echo "   You need to deploy the code changes to the server.\n";
}

// 3. Check what the current server would return
echo "\n3. CURRENT SERVER RESPONSE\n";
echo "========================\n";

$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "Server would return: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";

// 4. Check the exact error
echo "\n4. ERROR ANALYSIS\n";
echo "==============\n";

echo "The error message says: 'No rooms available for the selected dates. All rooms of this type are fully booked.'\n";
echo "This means:\n";
echo "1. Room 764 is not available (backend thinks so)\n";
echo "2. Alternative rooms of same type are also not available\n";
echo "3. Backend returns 500 error with this message\n";

// 5. Check room type and alternative rooms
echo "\n5. ALTERNATIVE ROOMS CHECK\n";
echo "========================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "Room Type ID: {$room->room_type_id}\n";
    
    // Find other rooms of same type
    $sameTypeRooms = \App\Models\HotelRoom::where('room_type_id', $room->room_type_id)
        ->where('property_id', $room->property_id)
        ->where('status', 1)
        ->where('id', '!=', $roomId)
        ->get();
    
    echo "Other rooms of same type: {$sameTypeRooms->count()}\n";
    
    foreach ($sameTypeRooms as $otherRoom) {
        $otherAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $otherRoom->id, $checkInDate, $checkOutDate);
        echo "  - Room {$otherRoom->id}: " . ($otherAvailable ? "AVAILABLE" : "NOT AVAILABLE") . "\n";
    }
}

// 6. Deployment instructions
echo "\n6. DEPLOYMENT INSTRUCTIONS\n";
echo "==========================\n";

echo "To fix this issue, you need to deploy:\n\n";
echo "1. DATABASE CHANGES:\n";
echo "   - Run the SQL to add availability for room 764\n";
echo "   - Or use the fix script on the server\n\n";

echo "2. CODE CHANGES:\n";
echo "   - Update ReservationService.php on server\n";
echo "   - Pull the latest code from git\n\n";

echo "3. CLEAR CACHE:\n";
echo "   - Clear Laravel cache\n";
echo "   - Clear any application cache\n\n";

echo "4. TEST:\n";
echo "   - Try the booking again\n";
echo "   - Should work without 500 error\n";

echo "\n=== SERVER CHECK COMPLETE ===\n";
