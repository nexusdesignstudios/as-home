<?php

// Test script to verify flexible reservation behavior fix
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;

echo "=== Testing Flexible Reservation Behavior Fix ===\n\n";

// Test 1: Check current properties and their refund policies
echo "1. Current Properties and Refund Policies:\n";
$properties = Property::select('id', 'title', 'refund_policy', 'property_classification')
    ->whereIn('id', [312, 313, 314, 315])
    ->get();

foreach ($properties as $property) {
    echo "   Property ID: {$property->id}\n";
    echo "   Title: {$property->title}\n";
    echo "   Refund Policy: " . ($property->refund_policy ?? 'NULL') . "\n";
    echo "   Classification: {$property->property_classification}\n";
    echo "   ---\n";
}

// Test 2: Check recent reservations and their behavior
echo "\n2. Recent Reservations Behavior Analysis:\n";
$reservations = Reservation::with(['property', 'reservable'])
    ->whereIn('id', [892, 893, 894, 895])
    ->orderBy('id', 'desc')
    ->get();

foreach ($reservations as $reservation) {
    echo "   Reservation ID: {$reservation->id}\n";
    echo "   Status: {$reservation->status}\n";
    echo "   Payment Method: {$reservation->payment_method}\n";
    echo "   Payment Status: {$reservation->payment_status}\n";
    echo "   Property ID: {$reservation->property_id}\n";
    echo "   Property Title: " . ($reservation->property->title ?? 'N/A') . "\n";
    echo "   Property Refund Policy: " . ($reservation->property->refund_policy ?? 'NULL') . "\n";
    echo "   Reservable Type: {$reservation->reservable_type}\n";
    echo "   Reservable ID: {$reservation->reservable_id}\n";
    
    // Show room details if it's a hotel room
    if ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $room = HotelRoom::find($reservation->reservable_id);
        echo "   Room Number: " . ($room->room_number ?? 'N/A') . "\n";
        echo "   Room Refund Policy: " . ($room->refund_policy ?? 'NULL') . "\n";
    }
    
    echo "   ---\n";
}

// Test 3: Verify expected behavior vs actual behavior
echo "\n3. Expected vs Actual Behavior Analysis:\n";
foreach ($reservations as $reservation) {
    $expectedFlexible = false;
    
    // Determine if reservation should be flexible based on refund policy
    if ($reservation->property && $reservation->property->refund_policy === 'flexible') {
        if ($reservation->reservable_type === 'App\Models\Property') {
            $expectedFlexible = true;
        } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
            $room = HotelRoom::find($reservation->reservable_id);
            // Room policy can override property policy if set
            if ($room && $room->refund_policy) {
                $expectedFlexible = $room->refund_policy === 'flexible';
            } else {
                $expectedFlexible = true;
            }
        }
    }
    
    $actualFlexible = ($reservation->status === 'confirmed' && $reservation->payment_method === 'cash');
    
    echo "   Reservation ID: {$reservation->id}\n";
    echo "   Should be flexible: " . ($expectedFlexible ? 'YES' : 'NO') . "\n";
    echo "   Actually flexible: " . ($actualFlexible ? 'YES' : 'NO') . "\n";
    echo "   Status: {$reservation->status}\n";
    echo "   Payment Method: {$reservation->payment_method}\n";
    echo "   Result: " . ($expectedFlexible === $actualFlexible ? '✅ CORRECT' : '❌ INCORRECT') . "\n";
    echo "   ---\n";
}

// Test 4: Create a new test reservation to verify the fix
echo "\n4. Creating New Test Reservation (Property with Flexible Policy):\n";
try {
    // Find a property with flexible refund policy
    $flexibleProperty = Property::where('refund_policy', 'flexible')
        ->where('property_classification', 5) // Hotel
        ->first();
    
    if ($flexibleProperty) {
        echo "   Using Property ID: {$flexibleProperty->id} ({$flexibleProperty->title})\n";
        echo "   Refund Policy: {$flexibleProperty->refund_policy}\n";
        
        // Check if there are available rooms
        $availableRooms = HotelRoom::where('property_id', $flexibleProperty->id)
            ->where('status', true)
            ->limit(3)
            ->get();
        
        if ($availableRooms->isNotEmpty()) {
            echo "   Available Rooms: " . $availableRooms->count() . "\n";
            foreach ($availableRooms as $room) {
                echo "   - Room {$room->room_number} (ID: {$room->id})\n";
            }
            echo "   ✅ Ready for testing new flexible reservation behavior\n";
        } else {
            echo "   ❌ No available rooms found for this property\n";
        }
    } else {
        echo "   ❌ No property with flexible refund policy found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "The fix has been applied to ReservationController.php:\n";
echo "1. Property refund policy is now checked before setting reservation status\n";
echo "2. Hotel rooms check both property and individual room refund policies\n";
echo "3. Flexible reservations get 'confirmed' status and 'cash' payment method\n";
echo "4. Non-flexible reservations get 'pending' status and 'online' payment method\n";
echo "5. Email notifications are now conditional based on refund policy\n";
echo "\nTo test the fix, create a new reservation through the API with a property that has 'flexible' refund_policy.\n";

echo "\n=== Next Steps ===\n";
echo "1. Test creating a new flexible reservation via API\n";
echo "2. Verify it gets 'confirmed' status and 'cash' payment method\n";
echo "3. Test creating a reservation with non-flexible property\n";
echo "4. Verify it gets 'pending' status and 'online' payment method\n";
echo "5. Check that reservations 894/893/892 now behave consistently\n";