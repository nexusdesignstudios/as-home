<?php
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

echo "=== DEBUG ROOM AVAILABILITY DISPLAY ===\n\n";

// Get Green Hotel 2 (Property ID 351)
$property = Property::find(351);
if (!$property) {
    echo "❌ Property ID 351 (Green Hotel 2) not found\n";
    exit;
}

echo "🏨 Property: {$property->title} (ID: {$property->id})\n";
echo "🏨 Total Rooms: " . $property->hotelRooms()->count() . "\n\n";

// Get all rooms for this property
$rooms = $property->hotelRooms()->with('roomType')->get();

echo "=== ROOM DETAILS ===\n";
foreach ($rooms as $room) {
    echo "🛏️ Room ID: {$room->id}\n";
    echo "🛏️ Room Number: {$room->room_number}\n";
    echo "🛏️ Room Type: " . ($room->roomType->name ?? 'Unknown') . "\n";
    echo "💰 Price: {$room->price_per_night} EGP\n";
    echo "📅 Available Dates: " . json_encode($room->available_dates) . "\n";
    
    // Count reservations for this room - check correct column name
    $reservationCount = Reservation::where(function($query) use ($room) {
        $query->where('room_ids', 'LIKE', '%"' . $room->id . '"%')
              ->orWhere('room_ids', 'LIKE', '%' . $room->id . '%');
    })
    ->where('property_id', 351)
    ->whereIn('status', ['pending', 'approved', 'confirmed'])
    ->count();
    
    echo "🔒 Active Reservations: {$reservationCount}\n";
    echo "---\n";
}

// Check reservations for January 1-3, 2026 (the dates mentioned by user)
echo "=== RESERVATIONS FOR JAN 1-3, 2026 ===\n";
$reservations = Reservation::where('property_id', 351)
    ->where(function($query) {
        $query->whereBetween('check_in_date', ['2026-01-01', '2026-01-03'])
              ->orWhereBetween('check_out_date', ['2026-01-01', '2026-01-03'])
              ->orWhere(function($q) {
                  $q->where('check_in_date', '<=', '2026-01-01')
                    ->where('check_out_date', '>=', '2026-01-03');
              });
    })
    ->whereIn('status', ['pending', 'approved', 'confirmed'])
    ->get();

foreach ($reservations as $reservation) {
    echo "🔒 Reservation ID: {$reservation->id}\n";
    echo "🛏️ Room IDs: {$reservation->room_ids}\n";
    echo "📅 Check-in: {$reservation->check_in_date}\n";
    echo "📅 Check-out: {$reservation->check_out_date}\n";
    echo "🔖 Status: {$reservation->status}\n";
    echo "💳 Payment Method: {$reservation->payment_method}\n";
    echo "---\n";
}

echo "=== AVAILABILITY CALCULATION ===\n";
// Simulate frontend availability calculation
$targetDate = '2026-01-01';
foreach ($rooms as $room) {
    $isAvailable = true;
    
    // Check if room has available_dates restrictions
    if ($room->available_dates && is_array($room->available_dates)) {
        $dateInRange = false;
        foreach ($room->available_dates as $range) {
            if ($targetDate >= $range['from'] && $targetDate <= $range['to']) {
                $dateInRange = true;
                break;
            }
        }
        $isAvailable = $dateInRange;
    }
    
    // Check for conflicting reservations
    if ($isAvailable) {
        $conflictingReservations = Reservation::where(function($query) use ($room) {
            $query->where('room_ids', 'LIKE', '%"' . $room->id . '"%')
                  ->orWhere('room_ids', 'LIKE', '%' . $room->id . '%');
        })
        ->where('property_id', 351)
        ->where(function($query) use ($targetDate) {
            $query->where(function($q) use ($targetDate) {
                $q->where('check_in_date', '<=', $targetDate)
                  ->where('check_out_date', '>', $targetDate);
            });
        })
        ->whereIn('status', ['pending', 'approved', 'confirmed'])
        ->exists();
        
        if ($conflictingReservations) {
            $isAvailable = false;
        }
    }
    
    $availabilityPercentage = $isAvailable ? 100 : 0;
    echo "🛏️ Room {$room->id} ({$room->room_number}): {$availabilityPercentage}% available\n";
    echo "   - Available: " . ($isAvailable ? '✅' : '❌') . "\n";
    echo "   - Conflicting reservations: " . ($conflictingReservations ? 'Yes' : 'No') . "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";