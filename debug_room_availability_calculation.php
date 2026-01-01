<?php
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;

echo "=== DEBUGGING ROOM AVAILABILITY CALCULATION (CORRECTED) ===\n\n";

// Get Green Hotel 2 (Property ID 351)
$property = Property::find(351);
if (!$property) {
    echo "❌ Property ID 351 (Green Hotel 2) not found\n";
    exit;
}

echo "🏨 Property: {$property->title} (ID: {$property->id})\n\n";

// Get all rooms for this property
$rooms = $property->hotelRooms()->with('roomType')->get();

// Get reservations for this property
$reservations = Reservation::where('property_id', 351)
    ->whereIn('status', ['pending', 'approved', 'confirmed'])
    ->get();

echo "=== ROOM AVAILABILITY CHECK FOR JAN 1, 2026 (USING FRONTEND LOGIC) ===\n";
$targetDate = '2026-01-01';

foreach ($rooms as $room) {
    echo "\n🛏️ Room ID: {$room->id} ({$room->room_number})\n";
    
    // Check if room is available on Jan 1, 2026 using frontend logic
    $isAvailable = true;
    $blockingReason = null;
    
    // Check if there's an active reservation for this room on this date
    // Using the exact logic from the frontend
    $hasActiveReservation = false;
    
    foreach ($reservations as $reservation) {
        // Convert dates to match frontend format (YYYY-MM-DD)
        $checkInDate = substr($reservation->check_in_date, 0, 10);
        $checkOutDate = substr($reservation->check_out_date, 0, 10);
        $dateStr = $targetDate;
        
        echo "   🔍 Checking reservation {$reservation->id}:\n";
        echo "   🔍   Check-in: {$checkInDate}, Check-out: {$checkOutDate}\n";
        echo "   🔍   Target date: {$dateStr}\n";
        echo "   🔍   Reservable ID: {$reservation->reservable_id}, Room ID: {$room->id}\n";
        
        // Check if this reservation is for this room
        if ($reservation->reservable_id == $room->id) {
            echo "   ✅ Reservation matches room ID\n";
            
            // Check if date is within reservation period
            // Frontend logic: dateStr >= checkInDate && dateStr < checkOutDate
            $isWithinDateRange = ($dateStr >= $checkInDate && $dateStr < $checkOutDate);
            echo "   🔍   Date range check: {$dateStr} >= {$checkInDate} && {$dateStr} < {$checkOutDate} = {$isWithinDateRange}\n";
            
            if ($isWithinDateRange) {
                // Check if this reservation should block availability
                $paymentMethod = $reservation->payment_method ?: 'cash';
                $isFlexible = ($paymentMethod === 'cash' || $paymentMethod === 'offline');
                $blockingStatuses = ["confirmed", "approved", "pending", "active"];
                
                $isBlockingStatus = ($isFlexible && $reservation->status !== 'cancelled' && $reservation->status !== 'rejected') || 
                                   in_array($reservation->status, $blockingStatuses) || 
                                   !$reservation->status;
                
                echo "   💳 Payment method: {$paymentMethod} (Flexible: " . ($isFlexible ? 'Yes' : 'No') . ")\n";
                echo "   🔖 Status: {$reservation->status} (Should Block: " . ($isBlockingStatus ? 'Yes' : 'No') . ")\n";
                
                if ($isBlockingStatus) {
                    $hasActiveReservation = true;
                    $blockingReason = "Blocked by reservation {$reservation->id}";
                    echo "   ❌ Room is blocked by this reservation\n";
                    break;
                }
            }
        } else {
            echo "   ❌ Reservation does not match room ID\n";
        }
    }
    
    if ($hasActiveReservation) {
        $isAvailable = false;
    }
    
    if ($isAvailable) {
        echo "   ✅ Room is available on {$targetDate}\n";
    } else {
        echo "   ❌ Room is NOT available on {$targetDate}: {$blockingReason}\n";
    }
    
    $availabilityPercentage = $isAvailable ? 100 : 0;
    echo "   📊 Availability: {$availabilityPercentage}%\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";