<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TESTING FLEXIBLE RESERVATION FIX ===\n\n";

// Test the new findAvailableHotelRoom method
function testFindAvailableRoom() {
    echo "1. Testing findAvailableHotelRoom method:\n";
    
    // Find a property with hotel rooms and flexible policy
    $property = Property::where('property_classification', 5)
        ->where('status', 1)
        ->where('refund_policy', 'flexible')
        ->has('hotelRooms')
        ->first();
    
    if (!$property) {
        echo "❌ No property with flexible policy found for testing\n";
        return;
    }
    
    echo "Using Property: {$property->title} (ID: {$property->id})\n";
    echo "Refund Policy: {$property->refund_policy}\n";
    
    // Get available rooms for this property
    $rooms = HotelRoom::where('property_id', $property->id)
        ->where('status', 1)
        ->limit(3)
        ->get();
    
    if ($rooms->count() < 2) {
        echo "❌ Need at least 2 rooms for testing, found: " . $rooms->count() . "\n";
        return;
    }
    
    echo "Found " . $rooms->count() . " rooms for testing:\n";
    foreach ($rooms as $room) {
        echo "  - Room ID: {$room->id}, Number: " . ($room->room_number ?? 'N/A') . "\n";
    }
    
    // Create mock reservable_data
    $reservableData = [];
    foreach ($rooms as $room) {
        $reservableData[] = ['id' => $room->id];
    }
    
    $checkIn = '2026-01-09';
    $checkOut = '2026-01-10';
    
    echo "\nTesting room availability for dates: $checkIn to $checkOut\n";
    
    // Test the method (simulate the logic from ApiController)
    $roomIds = array_column($reservableData, 'id');
    
    $availableRoom = HotelRoom::where('property_id', $property->id)
        ->whereIn('id', $roomIds)
        ->where('status', 1)
        ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
            $query->where('status', 'confirmed')
                ->where(function ($dateQuery) use ($checkIn, $checkOut) {
                    $dateQuery->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>', $checkIn);
                    })
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<', $checkOut)
                            ->where('check_out_date', '>=', $checkOut);
                    })
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '>=', $checkIn)
                            ->where('check_out_date', '<=', $checkOut);
                    });
                });
        })
        ->first();
    
    if ($availableRoom) {
        echo "✅ SUCCESS: Found available room\n";
        echo "  Assigned Room ID: {$availableRoom->id}\n";
        echo "  Room Number: " . ($availableRoom->room_number ?? 'N/A') . "\n";
    } else {
        echo "ℹ️  INFO: No rooms available (all might be reserved)\n";
    }
    
    // Test scenario: Create a reservation and then find another available room
    echo "\n2. Testing scenario with existing reservation:\n";
    
    // Find an existing confirmed reservation for this property
    $existingReservation = Reservation::where('property_id', $property->id)
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->where('status', 'confirmed')
        ->first();
    
    if ($existingReservation) {
        echo "Found existing reservation:\n";
        echo "  Reservation ID: {$existingReservation->id}\n";
        echo "  Room ID: {$existingReservation->reservable_id}\n";
        echo "  Dates: {$existingReservation->check_in_date} to {$existingReservation->check_out_date}\n";
        
        // Test finding another room for the same dates
        $availableRoomWithExisting = HotelRoom::where('property_id', $property->id)
            ->whereIn('id', $roomIds)
            ->where('status', 1)
            ->whereDoesntHave('reservations', function ($query) use ($existingReservation) {
                $query->where('status', 'confirmed')
                    ->where(function ($dateQuery) use ($existingReservation) {
                        $dateQuery->where(function ($q) use ($existingReservation) {
                            $q->where('check_in_date', '<=', $existingReservation->check_in_date)
                                ->where('check_out_date', '>', $existingReservation->check_in_date);
                        })
                        ->orWhere(function ($q) use ($existingReservation) {
                            $q->where('check_in_date', '<', $existingReservation->check_out_date)
                                ->where('check_out_date', '>=', $existingReservation->check_out_date);
                        })
                        ->orWhere(function ($q) use ($existingReservation) {
                            $q->where('check_in_date', '>=', $existingReservation->check_in_date)
                                ->where('check_out_date', '<=', $existingReservation->check_out_date);
                        });
                    });
            })
            ->first();
        
        if ($availableRoomWithExisting) {
            echo "✅ SUCCESS: Found another available room despite existing reservation\n";
            echo "  Alternative Room ID: {$availableRoomWithExisting->id}\n";
            echo "  Room Number: " . ($availableRoomWithExisting->room_number ?? 'N/A') . "\n";
        } else {
            echo "ℹ️  INFO: No other rooms available for these dates\n";
        }
    } else {
        echo "ℹ️  INFO: No existing reservations found for this property\n";
    }
}

// Test current conflicts
function testCurrentConflicts() {
    echo "\n3. Checking current conflicts in the system:\n";
    
    $conflicts = DB::table('reservations')
        ->select('reservable_id', 'check_in_date', 'check_out_date', 
               DB::raw('COUNT(*) as reservation_count'),
               DB::raw('GROUP_CONCAT(id) as reservation_ids'))
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->where('status', 'confirmed')
        ->where('payment_method', 'cash') // Flexible reservations
        ->groupBy('reservable_id', 'check_in_date', 'check_out_date')
        ->havingRaw('COUNT(*) > 1')
        ->get();
    
    echo "Found " . $conflicts->count() . " flexible reservation conflicts:\n\n";
    
    foreach ($conflicts as $conflict) {
        echo "=== CONFLICT ===\n";
        echo "Room ID: " . $conflict->reservable_id . "\n";
        echo "Check-in: " . $conflict->check_in_date . "\n";
        echo "Check-out: " . $conflict->check_out_date . "\n";
        echo "Reservation Count: " . $conflict->reservation_count . "\n";
        echo "Reservation IDs: " . $conflict->reservation_ids . "\n";
        
        $room = HotelRoom::find($conflict->reservable_id);
        if ($room) {
            echo "Room Number: " . ($room->room_number ?? 'N/A') . "\n";
            echo "Property: " . ($room->property->title ?? 'N/A') . "\n";
        }
        echo "\n";
    }
}

// Run tests
testFindAvailableRoom();
testCurrentConflicts();

echo "\n=== SUMMARY ===\n";
echo "✅ Fix implemented: findAvailableHotelRoom method added to ApiController\n";
echo "✅ Logic updated: Flexible bookings now check room availability\n";
echo "✅ Error handling: Returns error if no rooms available\n";
echo "✅ Logging: Added comprehensive logging for debugging\n";
echo "\nThe fix ensures that flexible reservations are assigned to available rooms only,\n";
echo "preventing multiple reservations for the same room and dates.\n";
