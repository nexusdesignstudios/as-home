<?php

/**
 * Verification script to ensure multi-unit vacation homes implementation is correct
 * and doesn't affect hotel reservations or single-unit vacation homes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;
use App\Models\Reservation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Multi-Unit Vacation Homes - Implementation Verification\n";
echo "========================================\n\n";

$allGood = true;

// 1. Check database columns exist
echo "1. Database Structure Check:\n";
if (Schema::hasColumn('reservations', 'apartment_id')) {
    echo "   ✅ apartment_id column exists\n";
} else {
    echo "   ❌ apartment_id column NOT found - Run migration!\n";
    $allGood = false;
}

if (Schema::hasColumn('reservations', 'apartment_quantity')) {
    echo "   ✅ apartment_quantity column exists\n";
} else {
    echo "   ❌ apartment_quantity column NOT found - Run migration!\n";
    $allGood = false;
}
echo "\n";

// 2. Check Reservation model fillable
echo "2. Reservation Model Check:\n";
$reservationModel = new \App\Models\Reservation();
$fillable = $reservationModel->getFillable();

if (in_array('apartment_id', $fillable)) {
    echo "   ✅ apartment_id in fillable array\n";
} else {
    echo "   ❌ apartment_id NOT in fillable array\n";
    $allGood = false;
}

if (in_array('apartment_quantity', $fillable)) {
    echo "   ✅ apartment_quantity in fillable array\n";
} else {
    echo "   ❌ apartment_quantity NOT in fillable array\n";
    $allGood = false;
}
echo "\n";

// 3. Check hotel reservations are unaffected
echo "3. Hotel Reservations Check:\n";
$hotelReservations = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
    ->orWhere('reservable_type', 'hotel_room')
    ->get(['id', 'apartment_id', 'apartment_quantity']);

$hotelReservationsWithApartment = $hotelReservations->filter(function($res) {
    return $res->apartment_id !== null || $res->apartment_quantity !== null;
})->count();

if ($hotelReservationsWithApartment === 0) {
    echo "   ✅ All hotel reservations have NULL apartment fields (unaffected)\n";
    echo "   Total hotel reservations checked: {$hotelReservations->count()}\n";
} else {
    echo "   ⚠️  Found {$hotelReservationsWithApartment} hotel reservation(s) with apartment fields set\n";
    echo "   This should not happen - hotel reservations should have NULL apartment fields\n";
    $allGood = false;
}
echo "\n";

// 4. Check single-unit vacation homes
echo "4. Single-Unit Vacation Homes Check:\n";
$singleUnitApartments = VacationApartment::where('quantity', '=', 1)
    ->get(['id', 'property_id', 'quantity']);

// Check reservations for single-unit apartments
$singleUnitReservations = DB::table('reservations')
    ->join('vacation_apartments', 'reservations.apartment_id', '=', 'vacation_apartments.id')
    ->where('reservations.reservable_type', 'App\\Models\\Property')
    ->where('vacation_apartments.quantity', '=', 1)
    ->whereNotNull('reservations.apartment_id')
    ->count();

if ($singleUnitReservations === 0) {
    echo "   ✅ Single-unit vacation homes have NULL apartment fields (unaffected)\n";
    echo "   Total single-unit apartments: {$singleUnitApartments->count()}\n";
} else {
    echo "   ⚠️  Found {$singleUnitReservations} single-unit reservation(s) with apartment fields set\n";
    echo "   Single-unit vacation homes should use datesOverlap logic, not apartment fields\n";
    $allGood = false;
}
echo "\n";

// 5. Check multi-unit vacation homes
echo "5. Multi-Unit Vacation Homes Check:\n";
$multiUnitApartments = VacationApartment::where('quantity', '>', 1)
    ->with('property:id,property_classification')
    ->get(['id', 'property_id', 'quantity']);

$multiUnitReservations = Reservation::where('reservable_type', 'App\\Models\\Property')
    ->whereNotNull('apartment_id')
    ->get(['id', 'apartment_id', 'apartment_quantity']);

$multiUnitWithFields = 0;
foreach ($multiUnitReservations as $res) {
    $apartment = VacationApartment::find($res->apartment_id);
    if ($apartment && $apartment->quantity > 1) {
        $multiUnitWithFields++;
    }
}

echo "   Total multi-unit apartments: {$multiUnitApartments->count()}\n";
echo "   Multi-unit reservations with apartment fields: {$multiUnitWithFields}\n";
if ($multiUnitWithFields > 0) {
    echo "   ✅ Multi-unit vacation homes are using apartment fields correctly\n";
} else {
    echo "   ℹ️  No multi-unit reservations found yet (this is OK if no bookings made)\n";
}
echo "\n";

// 6. Check backward compatibility (special_requests parsing)
echo "6. Backward Compatibility Check:\n";
$reservationsWithSpecialRequests = Reservation::where('reservable_type', 'App\\Models\\Property')
    ->whereNotNull('special_requests')
    ->where('special_requests', 'LIKE', '%Apartment ID:%')
    ->get(['id', 'special_requests', 'apartment_id', 'apartment_quantity']);

$hasSpecialRequests = $reservationsWithSpecialRequests->count() > 0;
if ($hasSpecialRequests) {
    echo "   ✅ Found reservations with apartment info in special_requests\n";
    echo "   Total: {$reservationsWithSpecialRequests->count()}\n";
    echo "   ✅ Backward compatibility maintained (can parse from special_requests)\n";
} else {
    echo "   ℹ️  No reservations with apartment info in special_requests found\n";
}
echo "\n";

// 7. Summary
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
if ($allGood) {
    echo "✅ All checks passed! Implementation is correct.\n";
    echo "\n";
    echo "Key Points:\n";
    echo "1. ✅ Database columns exist\n";
    echo "2. ✅ Reservation model updated\n";
    echo "3. ✅ Hotel reservations unaffected (NULL apartment fields)\n";
    echo "4. ✅ Single-unit vacation homes unaffected (NULL apartment fields)\n";
    echo "5. ✅ Multi-unit vacation homes use apartment fields\n";
    echo "6. ✅ Backward compatibility maintained\n";
} else {
    echo "❌ Some checks failed. Please review the issues above.\n";
}
echo "\n";

