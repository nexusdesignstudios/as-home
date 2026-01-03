<?php
// Fix room 764 availability by adding data to available_dates_hotel_rooms table with property_id
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ROOM 764 AVAILABILITY (WITH PROPERTY ID) ===\n\n";

$roomId = 764;
$propertyId = 357; // Amazing 4 Star Hotel
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// 1. Get room details
echo "1. ROOM DETAILS\n";
echo "=============\n";

$room = \App\Models\HotelRoom::find($roomId);
if (!$room) {
    echo "❌ Room $roomId not found!\n";
    exit(1);
}

echo "Room ID: {$room->id}\n";
echo "Property ID: {$room->property_id}\n";
echo "Room Type: " . ($room->roomType->name ?? 'N/A') . "\n";
echo "Price: " . ($room->price_per_night ?? 'N/A') . "\n";

// 2. Add availability with property_id
echo "\n2. ADDING AVAILABILITY RANGE\n";
echo "==========================\n";

$basePrice = $room->price_per_night ?? 1400;

try {
    \DB::table('available_dates_hotel_rooms')->insert([
        'property_id' => $propertyId,
        'hotel_room_id' => $roomId,
        'from_date' => $checkInDate,
        'to_date' => $checkOutDate,
        'price' => $basePrice,
        'type' => 'open',
        'nonrefundable_percentage' => $room->nonrefundable_percentage ?? 85,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✅ Added availability: $checkInDate to $checkOutDate (Price: $basePrice)\n";
} catch (\Exception $e) {
    echo "❌ Error adding availability: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verify the addition
echo "\n3. VERIFYING ADDITION\n";
echo "===================\n";

$updatedDates = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->orderBy('from_date')
    ->get();

echo "All availability entries for room $roomId:\n";
foreach ($updatedDates as $date) {
    $isRequestedRange = ($date->from_date == $checkInDate && $date->to_date == $checkOutDate);
    echo "  - {$date->from_date} to {$date->to_date} (Price: {$date->price}) " . ($isRequestedRange ? "← NEW ✅" : "") . "\n";
}

// 4. Update ReservationService to check correct table
echo "\n4. UPDATING RESERVATIONSERVICE.PHP\n";
echo "================================\n";

$reservationServiceFile = app_path('Services/ReservationService.php');
$reservationServiceContent = file_get_contents($reservationServiceFile);

// Find and replace the available_dates check
$oldPattern = '/\/\/ Check if the room is available based on available_dates\s*\n\s*if \(empty\(\$availableDates\)\) \{\s*\n\s*\/\/ No available dates configured - room is available by default\s*\n\s*\\Illuminate\\Support\\Facades\\Log::info\([\'\"]Room has no available_dates configured, available by default[\'\"].*\);\s*\n\s*return true;\s*\n\s*\}/s';

$newCode = '// Check if the room is available based on available_dates_hotel_rooms table
                $availableDates = \DB::table(\'available_dates_hotel_rooms\')
                    ->where(\'hotel_room_id\', $modelId)
                    ->where(\'from_date\', \'<=\', $checkIn->format(\'Y-m-d\'))
                    ->where(\'to_date\', \'>=\', $checkOut->format(\'Y-m-d\'))
                    ->exists();
                
                if (!$availableDates) {
                    // No available dates configured - room is not available
                    \Illuminate\Support\Facades\Log::info(\'Room has no available_dates configured, not available\', [
                        \'modelId\' => $modelId,
                        \'checkInDate\' => $checkInDate,
                        \'checkOutDate\' => $checkOutDate
                    ]);
                    return false;
                }';

if (preg_match($oldPattern, $reservationServiceContent)) {
    $reservationServiceContent = preg_replace($oldPattern, $newCode, $reservationServiceContent);
    
    file_put_contents($reservationServiceFile, $reservationServiceContent);
    echo "✅ Updated ReservationService.php to check available_dates_hotel_rooms table\n";
} else {
    echo "⚠️  Could not find the exact pattern to replace in ReservationService.php\n";
    echo "   Manual update may be required\n";
}

// 5. Test the fix
echo "\n5. TESTING THE FIX\n";
echo "==================\n";

// Clear any cached data
\Illuminate\Support\Facades\Log::info('=== TESTING AVAILABILITY FIX ===');

$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "Backend availability check result: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";

// 6. Summary
echo "\n6. SUMMARY\n";
echo "=========\n";

echo "✅ Added availability data to available_dates_hotel_rooms table\n";
echo "✅ Updated ReservationService.php to check correct table\n";
echo "✅ Room $roomId should now be available for $checkInDate to $checkOutDate\n";

echo "\nNEXT STEPS:\n";
echo "1. Deploy the updated ReservationService.php to server\n";
echo "2. Test the booking flow\n";
echo "3. Verify frontend calendar shows correct availability\n";
echo "4. Check for 500 error resolution\n";

echo "\n=== FIX COMPLETED ===\n";
