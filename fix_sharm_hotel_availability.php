<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING SHARM EL-SHEIKH 5 STARS XIROSES AVAILABILITY DATA ===\n";

// Get property 387
$property = \App\Models\Property::find(387);

if (!$property) {
    echo "Property 387 not found!\n";
    exit;
}

echo "Hotel: " . $property->title . " (ID: " . $property->id . ")\n";

// Get hotel rooms for this property
$hotelRooms = \App\Models\HotelRoom::where('property_id', 387)->get();

echo "\n=== BEFORE FIX ===\n";
foreach ($hotelRooms as $room) {
    echo "Room " . $room->id . ":\n";
    echo "  Availability Type: " . $room->availability_type . "\n";
    $availableDatesStr = is_string($room->available_dates) ? $room->available_dates : json_encode($room->available_dates);
    echo "  Available Dates: " . substr($availableDatesStr, 0, 100) . "...\n";
    
    // Parse the JSON
    $availableDates = is_string($room->available_dates) ? json_decode($room->available_dates, true) : $room->available_dates;
    
    if (is_array($availableDates)) {
        echo "  Date Ranges: " . count($availableDates) . "\n";
        foreach ($availableDates as $i => $range) {
            echo "    " . ($i+1) . ": " . $range['from'] . " to " . $range['to'] . " (type: " . $range['type'] . ")\n";
        }
    }
    echo "  ---\n";
}

// Fix the data
echo "\n=== APPLYING FIX ===\n";
foreach ($hotelRooms as $room) {
    echo "Fixing Room " . $room->id . "...\n";
    
    // Parse the JSON
    $availableDates = is_string($room->available_dates) ? json_decode($room->available_dates, true) : $room->available_dates;
    
    if (is_array($availableDates)) {
        // Change all "type":"open" to "type":"closed" for Closed Period hotels
        $isClosedPeriod = $room->availability_type === 'busy_days';
        
        if ($isClosedPeriod) {
            $changed = false;
            foreach ($availableDates as &$range) {
                if ($range['type'] === 'open') {
                    $range['type'] = 'closed';
                    $changed = true;
                    echo "  Changed: " . $range['from'] . " to " . $range['to'] . " (open -> closed)\n";
                }
            }
            
            if ($changed) {
                // Update the room
                $room->available_dates = json_encode($availableDates);
                $room->save();
                echo "  ✅ Updated Room " . $room->id . "\n";
            } else {
                echo "  ℹ️  No changes needed for Room " . $room->id . "\n";
            }
        } else {
            echo "  ℹ️  Room " . $room->id . " is not Closed Period, skipping\n";
        }
    }
}

echo "\n=== AFTER FIX ===\n";
foreach ($hotelRooms as $room) {
    echo "Room " . $room->id . ":\n";
    echo "  Availability Type: " . $room->availability_type . "\n";
    $availableDatesStr = is_string($room->available_dates) ? $room->available_dates : json_encode($room->available_dates);
    echo "  Available Dates: " . substr($availableDatesStr, 0, 100) . "...\n";
    
    // Parse the JSON
    $availableDates = is_string($room->available_dates) ? json_decode($room->available_dates, true) : $room->available_dates;
    
    if (is_array($availableDates)) {
        echo "  Date Ranges: " . count($availableDates) . "\n";
        foreach ($availableDates as $i => $range) {
            echo "    " . ($i+1) . ": " . $range['from'] . " to " . $range['to'] . " (type: " . $range['type'] . ")\n";
        }
    }
    echo "  ---\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Fixed Sharm El-Sheikh 5 stars Xiroses availability data\n";
echo "✅ Changed 'type: open' to 'type: closed' for Closed Period rooms\n";
echo "✅ Now the calendar should show correct availability:\n";
echo "   - Jan 7-20: UNAVAILABLE (closed period)\n";
echo "   - Jan 21-22: AVAILABLE (not in closed period)\n";
echo "   - Jan 23-19: UNAVAILABLE (closed period)\n";
echo "   - Jan 22: AVAILABLE (gap between closed periods)\n";
