<?php
/**
 * Test Script for Hotel Room Availability
 * 
 * Usage: php test_hotel_availability.php
 * Or via browser: http://your-domain.com/test_hotel_availability.php?from_date=2026-01-01&to_date=2026-01-10
 */

// Load Laravel bootstrap
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\AvailableDatesHotelRoom;

// Get parameters
$fromDate = $_GET['from_date'] ?? '2026-01-01';
$toDate = $_GET['to_date'] ?? '2026-01-10';

echo "========================================\n";
echo "Hotel Room Availability Test Script\n";
echo "========================================\n\n";
echo "Search Period: {$fromDate} to {$toDate}\n\n";

// Step 1: Check available_dates_hotel_rooms table directly
echo "STEP 1: Checking available_dates_hotel_rooms table...\n";
echo "---------------------------------------------------\n";
$availableDates = DB::table('available_dates_hotel_rooms')
    ->where('from_date', '<=', $fromDate)
    ->where('to_date', '>=', $toDate)
    ->where(function ($query) {
        $query->where('type', '!=', 'reserved')
            ->orWhereNull('type');
    })
    ->get();

echo "Found " . $availableDates->count() . " available date records\n\n";

if ($availableDates->count() > 0) {
    echo "Available Date Records:\n";
    foreach ($availableDates->take(10) as $date) {
        echo "  - ID: {$date->id}, Property: {$date->property_id}, Room: {$date->hotel_room_id}, ";
        echo "From: {$date->from_date}, To: {$date->to_date}, Type: {$date->type}\n";
    }
    if ($availableDates->count() > 10) {
        echo "  ... and " . ($availableDates->count() - 10) . " more\n";
    }
    echo "\n";
}

// Step 2: Get unique room IDs
$roomIds = $availableDates->pluck('hotel_room_id')->unique();
echo "STEP 2: Found " . $roomIds->count() . " unique room IDs\n";
echo "Room IDs: " . $roomIds->implode(', ') . "\n\n";

// Step 3: Check hotel rooms
echo "STEP 3: Checking hotel_rooms table...\n";
echo "---------------------------------------------------\n";
$rooms = HotelRoom::whereIn('id', $roomIds)
    ->where('status', 1)
    ->get();

echo "Found " . $rooms->count() . " active rooms (status = 1)\n\n";

if ($rooms->count() > 0) {
    echo "Room Details:\n";
    foreach ($rooms as $room) {
        echo "  - Room ID: {$room->id}, Property ID: {$room->property_id}, ";
        echo "Status: {$room->status}, Room Number: {$room->room_number}\n";
    }
    echo "\n";
}

// Step 4: Check properties
echo "STEP 4: Checking properties table...\n";
echo "---------------------------------------------------\n";
$propertyIds = $rooms->pluck('property_id')->unique();
$properties = Property::whereIn('id', $propertyIds)->get();

echo "Found " . $properties->count() . " properties\n\n";

foreach ($properties as $property) {
    echo "Property ID: {$property->id}\n";
    echo "  - Title: {$property->title}\n";
    echo "  - Classification: {$property->property_classification} ";
    echo ($property->property_classification == 5 ? "✓ (Hotel)" : "✗ (Not Hotel)") . "\n";
    echo "  - Status: {$property->status} ";
    echo ($property->status == 1 ? "✓ (Active)" : "✗ (Inactive)") . "\n";
    echo "  - Request Status: {$property->request_status} ";
    echo ($property->request_status == 'approved' ? "✓ (Approved)" : "✗ (Not Approved)") . "\n";
    echo "\n";
}

// Step 5: Filter properties that meet search criteria
echo "STEP 5: Filtering properties by search criteria...\n";
echo "---------------------------------------------------\n";
$filteredProperties = $properties->filter(function($property) {
    return $property->property_classification == 5 &&
           $property->status == 1 &&
           $property->request_status == 'approved';
});

echo "Properties that meet criteria: " . $filteredProperties->count() . "\n\n";

if ($filteredProperties->count() > 0) {
    echo "Matching Properties:\n";
    foreach ($filteredProperties as $property) {
        echo "  - Property ID: {$property->id}, Title: {$property->title}\n";
        
        // Get rooms for this property
        $propertyRooms = $rooms->where('property_id', $property->id);
        echo "    Rooms: " . $propertyRooms->count() . "\n";
        
        foreach ($propertyRooms as $room) {
            echo "      - Room ID: {$room->id}, Number: {$room->room_number}\n";
            
            // Get matching available dates
            $matchingDates = $availableDates->where('hotel_room_id', $room->id);
            echo "        Available Dates: " . $matchingDates->count() . " periods\n";
            foreach ($matchingDates as $date) {
                echo "          * {$date->from_date} to {$date->to_date} (Type: {$date->type})\n";
            }
        }
    }
    echo "\n";
} else {
    echo "❌ NO PROPERTIES MATCH THE SEARCH CRITERIA!\n\n";
    echo "Reasons why properties might be filtered out:\n";
    foreach ($properties as $property) {
        $reasons = [];
        if ($property->property_classification != 5) {
            $reasons[] = "property_classification = {$property->property_classification} (needs 5)";
        }
        if ($property->status != 1) {
            $reasons[] = "status = {$property->status} (needs 1)";
        }
        if ($property->request_status != 'approved') {
            $reasons[] = "request_status = '{$property->request_status}' (needs 'approved')";
        }
        
        if (!empty($reasons)) {
            echo "  Property {$property->id}: " . implode(', ', $reasons) . "\n";
        }
    }
    echo "\n";
}

// Step 6: Test the actual query used in the endpoint (NEW APPROACH)
echo "STEP 6: Testing the actual searchHotelsWithDates query (NEW APPROACH)...\n";
echo "---------------------------------------------------\n";
try {
    $checkInDate = \Carbon\Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
    $checkOutDate = \Carbon\Carbon::createFromFormat('Y-m-d', $toDate)->startOfDay();
    $checkInStr = $checkInDate->format('Y-m-d');
    $checkOutStr = $checkOutDate->format('Y-m-d');
    
    echo "Using NEW approach: Query available_dates_hotel_rooms directly\n";
    echo "Date range: {$checkInStr} to {$checkOutStr}\n\n";
    
    // Step 6a: Get property_ids from available_dates_hotel_rooms table directly
    echo "Step 6a: Querying available_dates_hotel_rooms for property_ids...\n";
    $availablePropertyIds = DB::table('available_dates_hotel_rooms')
        ->where('from_date', '<=', $checkInStr)
        ->where('to_date', '>=', $checkOutStr)
        ->where(function ($query) {
            $query->where('type', '!=', 'reserved')
                ->orWhereNull('type');
        })
        ->distinct()
        ->pluck('property_id')
        ->toArray();
    
    echo "Found " . count($availablePropertyIds) . " unique property_ids\n";
    if (count($availablePropertyIds) > 0) {
        echo "Property IDs: " . implode(', ', array_slice($availablePropertyIds, 0, 20));
        if (count($availablePropertyIds) > 20) {
            echo " ... and " . (count($availablePropertyIds) - 20) . " more";
        }
        echo "\n\n";
    } else {
        echo "❌ No property_ids found in available_dates_hotel_rooms for this date range!\n\n";
        echo "This means no hotels have available dates covering the search period.\n";
        echo "Check if:\n";
        echo "  1. Dates in available_dates_hotel_rooms table cover the search period\n";
        echo "  2. Type is not 'reserved'\n";
        echo "  3. from_date <= search_from AND to_date >= search_to\n\n";
        return;
    }
    
    // Step 6b: Get properties with those IDs
    echo "Step 6b: Querying properties table with property_ids...\n";
    $propertyQuery = Property::whereIn('id', $availablePropertyIds)
        ->where('property_classification', 5)
        ->where('request_status', 'approved')
        ->where('status', 1);
    
    $baseCount = $propertyQuery->count();
    echo "Properties found (after filtering by classification/status): {$baseCount}\n\n";
    
    if ($baseCount == 0) {
        echo "❌ No properties match the criteria!\n";
        echo "Checking why properties are filtered out...\n\n";
        
        // Check each property
        $allProperties = Property::whereIn('id', $availablePropertyIds)->get();
        foreach ($allProperties as $prop) {
            $reasons = [];
            if ($prop->property_classification != 5) {
                $reasons[] = "property_classification = {$prop->property_classification} (needs 5)";
            }
            if ($prop->status != 1) {
                $reasons[] = "status = {$prop->status} (needs 1)";
            }
            if ($prop->request_status != 'approved') {
                $reasons[] = "request_status = '{$prop->request_status}' (needs 'approved')";
            }
            
            if (!empty($reasons)) {
                echo "  Property {$prop->id} ({$prop->title}): " . implode(', ', $reasons) . "\n";
            } else {
                echo "  Property {$prop->id} ({$prop->title}): ✓ Should match!\n";
            }
        }
        echo "\n";
    }
    
    // Step 6c: Apply additional filters (if any)
    echo "Step 6c: Final query with all filters...\n";
    $finalCount = $propertyQuery->count();
    echo "Final count (after all filters): {$finalCount}\n\n";
    
    if ($finalCount > 0) {
        $results = $propertyQuery->take(5)->get();
        echo "✅ SUCCESS! Found {$finalCount} properties\n\n";
        echo "Sample Results:\n";
        foreach ($results as $property) {
            echo "  - Property ID: {$property->id}, Title: {$property->title}\n";
            echo "    Classification: {$property->property_classification}, ";
            echo "Status: {$property->status}, Request Status: {$property->request_status}\n";
        }
        echo "\n";
        
        echo "SQL Query:\n";
        echo $propertyQuery->toSql() . "\n";
        echo "\nBindings:\n";
        print_r($propertyQuery->getBindings());
    } else {
        echo "❌ Query returns 0 results!\n";
        echo "\nSQL Query:\n";
        echo $propertyQuery->toSql() . "\n";
        echo "\nBindings:\n";
        print_r($propertyQuery->getBindings());
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";

