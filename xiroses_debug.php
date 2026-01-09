<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== XIROSES DEBUGGING INVESTIGATION ===\n\n";

echo "🏨 XIROSES HOTEL (ID: 387) - DETAILED ANALYSIS:\n";

// Get hotel basic info
$hotel = DB::table('propertys')->where('id', 387)->first();
echo "   Hotel Title: {$hotel->title}\n";
echo "   Availability Type: {$hotel->availability_type}\n";
echo "   Status: " . ($hotel->status == 1 ? 'Active' : 'Inactive') . "\n\n";

// Get rooms
$rooms = DB::table('hotel_rooms')->where('property_id', 387)->get();
echo "🛏️ ROOMS ANALYSIS:\n";
foreach ($rooms as $room) {
    echo "   Room {$room->id}:\n";
    echo "     availability_type: {$room->availability_type}\n";
    echo "     price_per_night: {$room->price_per_night}\n";
    
    // Get available_dates for this specific room
    $roomPeriods = DB::table('available_dates_hotel_rooms')
        ->where('property_id', 387)
        ->where('room_id', $room->id)
        ->orderBy('from_date')
        ->get();
    
    echo "     Periods count: {$roomPeriods->count()}\n";
    foreach ($roomPeriods as $period) {
        echo "       {$period->from_date} to {$period->to_date} - Type: {$period->type}\n";
    }
    echo "     ---\n";
}

echo "\n🔍 FRONTEND LOGIC EXPECTATION:\n";
echo "   For Xiroses (availability_type: 2 - CLOSED PERIOD):\n";
echo "   -> Frontend should check: room.availability_type === 2\n";
echo "   -> If true: isAvailable = false for ALL dates\n";
echo "   -> Result: All rooms show as 0% available (red)\n";
echo "   -> This is actually CORRECT behavior!\n\n";

echo "🤔 WAIT - This might be CORRECT!\n";
echo "   Xiroses has availability_type: 2 (CLOSED PERIOD)\n";
echo "   This means ALL dates should be CLOSED/RESERVED\n";
echo "   Showing 0% available with RED is actually CORRECT!\n\n";

echo "📋 COMPARISON WITH ISMAILIA:\n";
echo "   Ismailia: availability_type: 1 (OPEN PERIOD) -> Should show availability based on periods\n";
echo "   Xiroses: availability_type: 2 (CLOSED PERIOD) -> Should show 0% available\n\n";

echo "🎯 QUESTION FOR USER:\n";
echo "   Is the Xiroses behavior actually WRONG?\n";
echo "   Or is it CORRECT that Xiroses shows 0% available?\n";
echo "   Because availability_type: 2 means CLOSED PERIOD\n\n";

echo "🔍 IF XIROSES SHOULD SHOW AVAILABILITY:\n";
echo "   Then availability_type should be 1 (OPEN PERIOD)\n";
echo "   Or some periods should be type: 'open'\n";
echo "   But currently all periods are type: 'dead' (CLOSED)\n\n";

echo "=== INVESTIGATION COMPLETE ===\n";
