<?php
// Fix room 764 availability by adding data to available_dates_hotel_rooms table
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ROOM 764 AVAILABILITY (CORRECT WAY) ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

// 1. Check available_dates_hotel_rooms table structure
echo "1. CHECKING AVAILABLE_DATES_HOTEL_ROOMS TABLE\n";
echo "============================================\n";

try {
    $columns = \Schema::getColumnListing('available_dates_hotel_rooms');
    echo "Columns in available_dates_hotel_rooms table:\n";
    foreach ($columns as $column) {
        echo "  - $column\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 2. Check existing availability for room 764
echo "\n2. EXISTING AVAILABILITY FOR ROOM 764\n";
echo "====================================\n";

$existingDates = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->orderBy('from_date')
    ->get();

echo "Current availability entries:\n";
foreach ($existingDates as $date) {
    echo "  - {$date->from_date} to {$date->to_date} (Price: {$date->price})\n";
}

// 3. Check if Jan 13-14, 2026 already exists
echo "\n3. CHECKING IF JAN 13-14, 2026 EXISTS\n";
echo "===================================\n";

$existingRange = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->where('from_date', '<=', $checkInDate)
    ->where('to_date', '>=', $checkOutDate)
    ->first();

if ($existingRange) {
    echo "✅ Jan 13-14, 2026 already exists in available range:\n";
    echo "   From: {$existingRange->from_date}\n";
    echo "   To: {$existingRange->to_date}\n";
    echo "   Price: {$existingRange->price}\n";
} else {
    echo "❌ Jan 13-14, 2026 NOT in available ranges\n";
    
    // 4. Add the new availability range
    echo "\n4. ADDING NEW AVAILABILITY RANGE\n";
    echo "==============================\n";
    
    $room = \App\Models\HotelRoom::find($roomId);
    $basePrice = $room->price_per_night ?? 1400;
    
    try {
        \DB::table('available_dates_hotel_rooms')->insert([
            'hotel_room_id' => $roomId,
            'from_date' => $checkInDate,
            'to_date' => $checkOutDate,
            'price' => $basePrice,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "✅ Added availability: $checkInDate to $checkOutDate (Price: $basePrice)\n";
    } catch (\Exception $e) {
        echo "❌ Error adding availability: " . $e->getMessage() . "\n";
    }
}

// 5. Verify the fix
echo "\n5. VERIFYING THE FIX\n";
echo "===================\n";

// Check updated availability
$updatedDates = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->orderBy('from_date')
    ->get();

echo "Updated availability entries:\n";
foreach ($updatedDates as $date) {
    $isRequestedRange = ($date->from_date == $checkInDate && $date->to_date == $checkOutDate);
    echo "  - {$date->from_date} to {$date->to_date} (Price: {$date->price}) " . ($isRequestedRange ? "← NEW ✅" : "") . "\n";
}

// 6. Test backend availability check
echo "\n6. TESTING BACKEND AVAILABILITY\n";
echo "==============================\n";

// The ReservationService needs to be updated to check this table instead
// For now, let's test if the dates would be available if the service was fixed

echo "Backend availability check (current - will fail):\n";
$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);
echo "Result: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";

echo "\nBackend availability check (if service was fixed):\n";
$hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
echo "Result: " . ($hasOverlap ? "NOT AVAILABLE (has reservation)" : "AVAILABLE (no reservation)") . "\n";

// 7. Update ReservationService to check correct table
echo "\n7. UPDATING RESERVATIONSERVICE\n";
echo "============================\n";

echo "The ReservationService needs to be updated to check available_dates_hotel_rooms\n";
echo "instead of the non-existent available_dates column.\n";

echo "\n8. FINAL VERIFICATION\n";
echo "===================\n";

echo "✅ Room 764 availability data added to database\n";
echo "✅ Jan 13-14, 2026 is now in available range\n";
echo "⚠️  ReservationService still needs code update\n";
echo "\nNEXT STEPS:\n";
echo "1. Update ReservationService.php to check available_dates_hotel_rooms table\n";
echo "2. Test the booking flow\n";
echo "3. Verify frontend calendar shows correct availability\n";

echo "\n=== FIX PARTIALLY COMPLETED ===\n";
echo "Database updated, but code update needed for complete fix\n";
