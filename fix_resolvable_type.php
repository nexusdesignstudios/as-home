<?php

// Fix script to correct the resolvable_type for reservations 893, 894
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== Fixing Resolvable Type for Reservations 893, 894 ===\n\n";

$testIds = [893, 894];

foreach ($testIds as $id) {
    echo "Reservation {$id}:\n";
    
    $reservation = Reservation::find($id);
    if (!$reservation) {
        echo "  Not found!\n\n";
        continue;
    }
    
    echo "  Current resolvable_type: '{$reservation->resolvable_type}'\n";
    echo "  Current resolvable_id: {$reservation->resolvable_id}\n";
    
    // Check if the hotel room actually exists
    $room = \App\Models\HotelRoom::find($reservation->resolvable_id);
    if ($room) {
        echo "  HotelRoom exists: YES\n";
        echo "  Room property_id: {$room->property_id}\n";
        
        // Fix the resolvable_type
        if ($reservation->resolvable_type !== 'App\Models\HotelRoom') {
            echo "  Fixing resolvable_type to 'App\Models\HotelRoom'...\n";
            $reservation->resolvable_type = 'App\Models\HotelRoom';
            $reservation->save();
            echo "  ✅ Fixed!\n";
        } else {
            echo "  Already correct!\n";
        }
    } else {
        echo "  HotelRoom exists: NO\n";
        echo "  Cannot fix - room not found!\n";
    }
    
    echo "\n";
}

echo "=== Testing the Fix ===\n\n";

// Test the fix by loading the reservations again
foreach ($testIds as $id) {
    echo "Reservation {$id} after fix:\n";
    
    $reservation = Reservation::with([
        'customer:id,name,email,mobile',
        'reservable',
        'reservable.property:id,title,property_classification',
        'reservable.roomType:id,name'
    ])->find($id);
    
    if (!$reservation) {
        echo "  Not found!\n\n";
        continue;
    }
    
    echo "  resolvable_type: '{$reservation->resolvable_type}'\n";
    
    if ($reservation->resolvable) {
        echo "  resolvable loaded: YES\n";
        echo "  resolvable class: " . get_class($reservation->resolvable) . "\n";
        
        if ($reservation->resolvable->relationLoaded('property') && $reservation->resolvable->property) {
            echo "  Property loaded: YES\n";
            echo "  Property name: {$reservation->resolvable->property->title}\n";
        } else {
            echo "  Property loaded: NO\n";
        }
        
        if ($reservation->resolvable->relationLoaded('roomType') && $reservation->resolvable->roomType) {
            echo "  RoomType loaded: YES\n";
            echo "  Room type name: {$reservation->resolvable->roomType->name}\n";
        } else {
            echo "  RoomType loaded: NO\n";
        }
    } else {
        echo "  resolvable loaded: NO\n";
    }
    
    echo "\n";
}

echo "=== Summary ===\n";
echo "The fix should now allow the admin dashboard to show property data for reservations 893 and 894.\n";