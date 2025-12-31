<?php

// Test the fix by creating a new reservation with the Green hotel rooms
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Customer;

echo "=== Testing Fix with Green Hotel Property ===\n\n";

// Get the Green hotel property
$greenHotel = Property::find(351);
if ($greenHotel) {
    echo "Green Hotel Property:\n";
    echo "ID: {$greenHotel->id}\n";
    echo "Title: {$greenHotel->title}\n";
    echo "Refund Policy: " . ($greenHotel->refund_policy ?? 'NULL') . "\n";
    echo "Classification: {$greenHotel->property_classification}\n\n";
    
    // Get rooms with flexible refund policy
    $flexibleRooms = HotelRoom::where('property_id', 351)
        ->where('refund_policy', 'flexible')
        ->get();
    
    echo "Rooms with flexible refund policy:\n";
    foreach ($flexibleRooms as $room) {
        echo "Room ID: {$room->id}, Number: {$room->room_number}, Refund Policy: {$room->refund_policy}\n";
    }
    echo "\n";
    
    // Simulate the new logic
    echo "=== Simulating New Reservation Logic ===\n";
    
    foreach ($flexibleRooms->take(2) as $room) {
        echo "Testing Room {$room->id}:\n";
        
        // Property level check
        $isFlexible = $greenHotel->refund_policy === 'flexible';
        echo "Property is flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
        
        // Room level override check
        $roomIsFlexible = $isFlexible;
        if ($room && $room->refund_policy) {
            $roomIsFlexible = $room->refund_policy === 'flexible';
        }
        echo "Room is flexible: " . ($roomIsFlexible ? 'YES' : 'NO') . "\n";
        
        // Expected reservation behavior
        $expectedStatus = $roomIsFlexible ? 'confirmed' : 'pending';
        $expectedPaymentMethod = $roomIsFlexible ? 'cash' : 'online';
        
        echo "Expected Status: {$expectedStatus}\n";
        echo "Expected Payment Method: {$expectedPaymentMethod}\n";
        echo "---\n";
    }
    
    echo "\n=== Conclusion ===\n";
    echo "With the new logic, rooms 755 and 756 should create CONFIRMED reservations\n";
    echo "because they have 'flexible' refund policy, even though the property doesn't.\n";
    echo "This is the correct behavior - room policy overrides property policy.\n";
    
} else {
    echo "❌ Green hotel property not found\n";
}

echo "\n=== Testing with Property 312 (Known Flexible) ===\n";
$flexibleProperty = Property::find(312);
if ($flexibleProperty) {
    echo "Flexible Property:\n";
    echo "ID: {$flexibleProperty->id}\n";
    echo "Title: {$flexibleProperty->title}\n";
    echo "Refund Policy: {$flexibleProperty->refund_policy}\n\n";
    
    // Test with property 312 rooms
    $propertyRooms = HotelRoom::where('property_id', 312)->get();
    echo "Property 312 rooms:\n";
    foreach ($propertyRooms as $room) {
        $isFlexible = $flexibleProperty->refund_policy === 'flexible';
        $roomIsFlexible = $isFlexible;
        if ($room && $room->refund_policy) {
            $roomIsFlexible = $room->refund_policy === 'flexible';
        }
        
        echo "Room {$room->id}: Expected Status = " . ($roomIsFlexible ? 'confirmed' : 'pending') . "\n";
    }
}