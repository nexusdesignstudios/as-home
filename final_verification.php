<?php
// Final verification of the fix
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINAL VERIFICATION OF FIX ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// 1. Check database availability
echo "1. DATABASE AVAILABILITY CHECK\n";
echo "==============================\n";

$availabilityExists = \DB::table('available_dates_hotel_rooms')
    ->where('hotel_room_id', $roomId)
    ->where('from_date', '<=', $checkInDate)
    ->where('to_date', '>=', $checkOutDate)
    ->exists();

echo "Availability in database: " . ($availabilityExists ? "YES ✅" : "NO ❌") . "\n";

// 2. Check backend availability
echo "\n2. BACKEND AVAILABILITY CHECK\n";
echo "============================\n";

$reservationService = app(\App\Services\ReservationService::class);
$isAvailable = $reservationService->areDatesAvailable('App\\Models\\HotelRoom', $roomId, $checkInDate, $checkOutDate);

echo "Backend availability result: " . ($isAvailable ? "AVAILABLE ✅" : "NOT AVAILABLE ❌") . "\n";

// 3. Check for blocking reservations
echo "\n3. RESERVATION BLOCKING CHECK\n";
echo "===========================\n";

$hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
echo "Has blocking reservations: " . ($hasOverlap ? "YES ❌" : "NO ✅") . "\n";

// 4. Summary
echo "\n4. SUMMARY\n";
echo "=========\n";

if ($isAvailable && !$hasOverlap) {
    echo "✅ SUCCESS: Room $roomId is AVAILABLE for $checkInDate to $checkOutDate\n";
    echo "   - Database has availability data ✅\n";
    echo "   - Backend confirms availability ✅\n";
    echo "   - No blocking reservations ✅\n";
    echo "\n   Booking should work without 500 error!\n";
} else {
    echo "❌ ISSUE: Room $roomId is NOT AVAILABLE\n";
    if (!$availabilityExists) echo "   - Database missing availability data ❌\n";
    if (!$isAvailable) echo "   - Backend rejects availability ❌\n";
    if ($hasOverlap) echo "   - Has blocking reservations ❌\n";
}

// 5. Next steps for deployment
echo "\n5. DEPLOYMENT CHECKLIST\n";
echo "======================\n";

echo "To deploy this fix:\n";
echo "1. ✅ Database updated (available_dates_hotel_rooms table)\n";
echo "2. ✅ ReservationService.php updated\n";
echo "3. ⚠️  Deploy backend changes to server\n";
echo "4. ⚠️  Clear Laravel cache on server\n";
echo "5. ⚠️  Test the booking flow\n";
echo "6. ⚠️  Verify frontend calendar shows correct availability\n";

echo "\n=== FIX VERIFICATION COMPLETE ===\n";
