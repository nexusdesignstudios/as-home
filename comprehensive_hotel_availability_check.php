<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE HOTEL AVAILABILITY SYSTEM CHECKUP ===\n";

// Check multiple hotels to ensure the fix works across all properties
echo "\n🔍 CHECKING MULTIPLE HOTELS FOR CONSISTENCY\n";

// Get all 5-star hotels (property_classification = 5)
$hotels = \App\Models\Property::where('property_classification', 5)
    ->where('status', 'active')
    ->limit(5)
    ->get();

echo "Found " . $hotels->count() . " 5-star hotels\n";

foreach ($hotels as $hotel) {
    echo "\n=== HOTEL: " . $hotel->title . " (ID: " . $hotel->id . ") ===\n";
    
    // Get hotel rooms
    $hotelRooms = \App\Models\HotelRoom::where('property_id', $hotel->id)->get();
    echo "Total Rooms: " . $hotelRooms->count() . "\n";
    
    // Analyze availability types and data
    $availabilityTypes = [];
    $dataIssues = [];
    
    foreach ($hotelRooms as $room) {
        $availabilityType = $room->availability_type ?? 'null';
        $availabilityTypes[$availabilityType] = ($availabilityTypes[$availabilityType] ?? 0) + 1;
        
        // Check available_dates data
        $availableDates = is_string($room->available_dates) ? 
            json_decode($room->available_dates, true) : 
            $room->available_dates;
        
        echo "Room " . $room->id . ":\n";
        echo "  Availability Type: " . $availabilityType . "\n";
        
        if (is_array($availableDates) && count($availableDates) > 0) {
            echo "  Available Dates: " . count($availableDates) . " ranges\n";
            
            $typeIssues = [];
            foreach ($availableDates as $i => $range) {
                $rangeType = $range['type'] ?? 'unknown';
                echo "    " . ($i+1) . ": " . $range['from'] . " to " . $range['to'] . " (type: " . $rangeType . ")\n";
                
                // Check for data consistency
                if ($availabilityType === 'busy_days' || $availabilityType === '2') {
                    // Closed Period should have 'closed' type
                    if ($rangeType !== 'closed' && $rangeType !== 'dead') {
                        $typeIssues[] = "Range " . ($i+1) . " has type '$rangeType' but should be 'closed' for Closed Period";
                    }
                } else if ($availabilityType === '1' || $availabilityType === 'available_days') {
                    // Open Period should have 'open' type
                    if ($rangeType !== 'open') {
                        $typeIssues[] = "Range " . ($i+1) . " has type '$rangeType' but should be 'open' for Open Period";
                    }
                }
            }
            
            if (!empty($typeIssues)) {
                $dataIssues[$room->id] = $typeIssues;
                echo "  ⚠️  DATA ISSUES:\n";
                foreach ($typeIssues as $issue) {
                    echo "    - " . $issue . "\n";
                }
            } else {
                echo "  ✅ Data consistency: GOOD\n";
            }
        } else {
            echo "  Available Dates: NULL or empty\n";
            
            // Check if this is expected
            if ($availabilityType === 'busy_days' || $availabilityType === '2') {
                echo "  ⚠️  Closed Period with no dates - ALL dates should be available\n";
            } else if ($availabilityType === '1' || $availabilityType === 'available_days') {
                echo "  ⚠️  Open Period with no dates - NO dates should be available\n";
            } else {
                echo "  ❓ Unknown availability type - behavior unpredictable\n";
            }
        }
        
        echo "  ---\n";
    }
    
    // Summary for this hotel
    echo "📊 Availability Type Summary:\n";
    foreach ($availabilityTypes as $type => $count) {
        echo "  " . $type . ": " . $count . " rooms\n";
    }
    
    if (!empty($dataIssues)) {
        echo "❌ Data Issues Found: " . count($dataIssues) . " rooms\n";
    } else {
        echo "✅ All rooms have consistent data\n";
    }
    
    echo "\n";
}

// Check reservations for these hotels
echo "\n🔍 CHECKING RESERVATIONS FOR THESE HOTELS\n";

foreach ($hotels as $hotel) {
    echo "\n=== RESERVATIONS FOR: " . $hotel->title . " ===\n";
    
    $reservations = \DB::table('reservations')
        ->where('reservable_id', $hotel->id)
        ->orWhere(function($query) use ($hotel) {
            $query->where('reservable_id', 'like', '%property_id:' . $hotel->id . '%');
        })
        ->get();
    
    echo "Total Reservations: " . $reservations->count() . "\n";
    
    if ($reservations->count() > 0) {
        echo "Recent Reservations:\n";
        foreach ($reservations->take(3) as $reservation) {
            echo "  - ID: " . $reservation->id . "\n";
            echo "    Check-in: " . $reservation->check_in_date . "\n";
            echo "    Check-out: " . $reservation->check_out_date . "\n";
            echo "    Status: " . ($reservation->status ?? 'N/A') . "\n";
            echo "    Payment: " . ($reservation->payment_method ?? 'N/A') . "\n";
            echo "    ---\n";
        }
    } else {
        echo "No reservations found\n";
    }
}

// Check database structure
echo "\n🔍 DATABASE STRUCTURE VERIFICATION\n";

$tables = ['propertys', 'hotel_rooms', 'reservations'];
foreach ($tables as $table) {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
    echo "Table '$table': " . implode(', ', $columns) . "\n";
}

echo "\n=== FRONTEND COMPONENTS TO CHECK ===\n";
echo "1. HotelBooking.jsx - Main booking logic\n";
echo "2. HotelBookingCalndar.jsx - Calendar display\n";
echo "3. HotelAvailabilityCalendar.jsx - Room availability cards\n";
echo "4. NewHotelAvailability.jsx - New hotel availability\n";
echo "5. SimpleDropdownCardHotel.jsx - Room type dropdown\n";
echo "6. hotelAvailabilityUtils.js - Shared utility functions\n";

echo "\n=== EXPECTED BEHAVIOR VERIFICATION ===\n";
echo "✅ Closed Period (availability_type = 'busy_days' or '2'):\n";
echo "   - available_dates with 'type': 'closed' = UNAVAILABLE dates\n";
echo "   - available_dates with 'type': 'open' = AVAILABLE dates (WRONG!)\n";
echo "   - empty available_dates = ALL dates AVAILABLE\n";
echo "\n✅ Open Period (availability_type = 'available_days' or '1'):\n";
echo "   - available_dates with 'type': 'open' = AVAILABLE dates\n";
echo "   - available_dates with 'type': 'closed' = UNAVAILABLE dates (WRONG!)\n";
echo "   - empty available_dates = NO dates AVAILABLE\n";

echo "\n🎯 NEXT STEPS FOR VERIFICATION:\n";
echo "1. Test each hotel in the frontend\n";
echo "2. Check calendar displays for closed periods\n";
echo "3. Verify room availability percentages\n";
echo "4. Test booking functionality\n";
echo "5. Ensure all components use consistent logic\n";

echo "\n✅ COMPREHENSIVE CHECKUP COMPLETE ===\n";
