<?php

/**
 * Fix existing reservations - set apartment_id and apartment_quantity to NULL
 * for hotel reservations and single-unit vacation homes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fixing Existing Reservations\n";
echo "========================================\n\n";

// 1. Set apartment fields to NULL for all hotel reservations
echo "1. Fixing hotel reservations:\n";
$hotelReservations = DB::table('reservations')
    ->whereIn('reservable_type', ['App\\Models\\HotelRoom', 'hotel_room'])
    ->where(function($query) {
        $query->whereNotNull('apartment_id')
              ->orWhereNotNull('apartment_quantity');
    })
    ->update([
        'apartment_id' => null,
        'apartment_quantity' => null
    ]);

echo "   Updated {$hotelReservations} hotel reservations\n\n";

// 2. Set apartment fields to NULL for single-unit vacation homes
echo "2. Fixing single-unit vacation homes:\n";
$singleUnitReservations = DB::table('reservations')
    ->join('vacation_apartments', 'reservations.apartment_id', '=', 'vacation_apartments.id')
    ->where('reservations.reservable_type', 'App\\Models\\Property')
    ->where('vacation_apartments.quantity', '=', 1)
    ->whereNotNull('reservations.apartment_id')
    ->update([
        'reservations.apartment_id' => null,
        'reservations.apartment_quantity' => null
    ]);

echo "   Updated {$singleUnitReservations} single-unit vacation home reservations\n\n";

// 3. Set apartment_quantity to NULL where it's 1 but shouldn't be
echo "3. Fixing reservations with default apartment_quantity = 1:\n";
$defaultQuantity = DB::table('reservations')
    ->where('apartment_quantity', 1)
    ->whereNull('apartment_id')
    ->update([
        'apartment_quantity' => null
    ]);

echo "   Updated {$defaultQuantity} reservations with default quantity\n\n";

echo "========================================\n";
echo "Done!\n";
echo "========================================\n";

