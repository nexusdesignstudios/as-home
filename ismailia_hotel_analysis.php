<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ISMAILIA 5 STARS HOTEL - DETAILED ANALYSIS ===\n\n";

// Hotel ID 386 - "Ismailia 5 starts Hotel - Closed Period"
echo "🏨 HOTEL 1: Ismailia 5 starts Hotel - Closed Period (ID: 386)\n";
echo str_repeat("=", 80) . "\n";

$hotel386 = DB::table('propertys')->where('id', 386)->first();
echo "Title: {$hotel386->title}\n";
echo "Status: " . ($hotel386->status == 1 ? 'Active' : 'Inactive') . "\n";
echo "Availability Type: 2 (CLOSED PERIOD)\n\n";

$rooms386 = DB::table('hotel_rooms')->where('property_id', 386)->get();
echo "Rooms (All CLOSED):\n";
foreach ($rooms386 as $room) {
    echo "   Room {$room->id} - availability_type: {$room->availability_type} (CLOSED)\n";
}

echo "\nAvailability Periods:\n";
$periods386 = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 386)
    ->orderBy('from_date')
    ->get();

foreach ($periods386 as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status})\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

// Hotel ID 388 - "Ismailia 5 Stars Hotel" 
echo "🏨 HOTEL 2: Ismailia 5 Stars Hotel (ID: 388)\n";
echo str_repeat("=", 80) . "\n";

$hotel388 = DB::table('propertys')->where('id', 388)->first();
echo "Title: {$hotel388->title}\n";
echo "Status: " . ($hotel388->status == 1 ? 'Active' : 'Inactive') . "\n";
echo "Availability Type: 1 (OPEN PERIOD)\n\n";

$rooms388 = DB::table('hotel_rooms')->where('property_id', 388)->get();
echo "Rooms (All OPEN):\n";
foreach ($rooms388 as $room) {
    echo "   Room {$room->id} - availability_type: {$room->availability_type} (OPEN)\n";
}

echo "\nAvailability Periods:\n";
$periods388 = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

foreach ($periods388 as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status})\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔍 ISSUE ANALYSIS:\n\n";

echo "PROBLEM: User says Jan 21-22 and Feb 21-22 should be CLOSED/RESERVED\n";
echo "BUT Frontend shows ALL dates as RESERVED\n\n";

echo "HOTEL 386 (Closed Period):\n";
echo "   - availability_type: 2 (should close ALL dates)\n";
echo "   - All periods are type: 'open' (but should be 'dead'/'closed')\n";
echo "   - Frontend should show: All dates CLOSED\n\n";

echo "HOTEL 388 (Open Period):\n";
echo "   - availability_type: 1 (should check available_dates)\n";
echo "   - All periods are type: 'open' (should be available)\n";
echo "   - Frontend should show: Available dates based on periods\n\n";

echo "EXPECTED BEHAVIOR:\n";
echo "   - Hotel 386: All dates should show as CLOSED\n";
echo "   - Hotel 388: Only dates within 'open' periods should be available\n";
echo "   - Jan 21-22: Should be CLOSED for Hotel 386, AVAILABLE for Hotel 388\n";
echo "   - Feb 21-22: Should be CLOSED for Hotel 386, AVAILABLE for Hotel 388\n\n";

echo "CURRENT FRONTEND ISSUE:\n";
echo "   - Component is NOT checking availability_type properly\n";
echo "   - Component is treating type: 'open' as available\n";
echo "   - Component should respect availability_type: 2 = CLOSED\n\n";

echo "SOLUTION NEEDED:\n";
echo "   1. Fix HotelBookingCalendar to check availability_type first\n";
echo "   2. For availability_type: 2, show ALL dates as CLOSED\n";
echo "   3. For availability_type: 1, check available_dates ranges\n";
echo "   4. Handle 'open' vs 'dead'/'closed' types correctly\n";

echo "\n=== ANALYSIS COMPLETE ===\n";
