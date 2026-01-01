<?php

// Debug script to investigate why reservations 893, 894 show N/A
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== Debugging Reservations 893, 894 ===\n\n";

$testIds = [893, 894, 895];

foreach ($testIds as $id) {
    echo "Reservation {$id}:\n";
    
    $reservation = Reservation::find($id);
    if (!$reservation) {
        echo "  Not found!\n\n";
        continue;
    }
    
    echo "  Raw database values:\n";
    echo "  - reservable_type: '{$reservation->reservable_type}'\n";
    echo "  - reservable_id: {$reservation->reservable_id}\n";
    echo "  - property_id: {$reservation->property_id}\n";
    echo "  - payment_method: " . ($reservation->payment_method ?? 'null') . "\n";
    
    // Check if the resolvable exists
    if ($reservation->resolvable_type === 'App\Models\HotelRoom' || $reservation->resolvable_type === 'hotel_room') {
        $room = \App\Models\HotelRoom::find($reservation->resolvable_id);
        if ($room) {
            echo "  HotelRoom found:\n";
            echo "  - Room ID: {$room->id}\n";
            echo "  - Room Number: {$room->room_number}\n";
            echo "  - Property ID: {$room->property_id}\n";
            
            // Check if property exists
            $property = \App\Models\Property::find($room->property_id);
            if ($property) {
                echo "  - Property Title: {$property->title}\n";
                echo "  - Property Classification: {$property->property_classification}\n";
            } else {
                echo "  - Property: NOT FOUND\n";
            }
            
            // Check room type
            if ($room->room_type_id) {
                $roomType = \App\Models\RoomType::find($room->room_type_id);
                if ($roomType) {
                    echo "  - Room Type: {$roomType->name}\n";
                }
            }
        } else {
            echo "  HotelRoom: NOT FOUND\n";
        }
    }
    
    echo "\n";
}

echo "=== Testing Relationship Loading ===\n\n";

// Test with different relationship loading approaches
foreach ($testIds as $id) {
    echo "Reservation {$id} relationship loading test:\n";
    
    // Test 1: Load with polymorphic relationship
    $reservation = Reservation::with(['reservable'])->find($id);
    if ($reservation && $reservation->resolvable) {
        echo "  resolvable loaded: YES\n";
        echo "  resolvable type: " . get_class($reservation->resolvable) . "\n";
        
        // Try to load property relationship
        if (method_exists($reservation->resolvable, 'property')) {
            $reservation->resolvable->load('property');
            if ($reservation->resolvable->property) {
                echo "  Property loaded: YES\n";
                echo "  Property title: {$reservation->resolvable->property->title}\n";
            } else {
                echo "  Property loaded: NO\n";
            }
        } else {
            echo "  No property relationship found\n";
        }
    } else {
        echo "  resolvable loaded: NO\n";
    }
    
    echo "\n";
}

echo "=== Checking for Data Inconsistencies ===\n\n";

// Check if there are any inconsistencies in the data
foreach ($testIds as $id) {
    echo "Reservation {$id} consistency check:\n";
    
    $reservation = Reservation::find($id);
    if (!$reservation) continue;
    
    // Check if the resolvable type matches what we expect
    if ($reservation->resolvable_type === 'hotel_room') {
        echo "  WARNING: resolvable_type is 'hotel_room' instead of 'App\\Models\\HotelRoom'\n";
        
        // This might be the issue - let's see if we can still load it
        $room = \App\Models\HotelRoom::find($reservation->resolvable_id);
        if ($room) {
            echo "  But HotelRoom model found with ID {$reservation->resolvable_id}\n";
            echo "  This suggests the polymorphic relationship might not work correctly\n";
        }
    }
    
    echo "\n";
}