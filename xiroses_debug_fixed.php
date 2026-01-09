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

// Check table structure first
echo "🔍 CHECKING AVAILABLE_DATES_HOTEL_ROOMS TABLE STRUCTURE:\n";
$columns = DB::select("DESCRIBE available_dates_hotel_rooms");
foreach ($columns as $column) {
    echo "   Column: {$column->Field} (Type: {$column->Type})\n";
}

echo "\n🛏️ ROOMS ANALYSIS:\n";
$rooms = DB::table('hotel_rooms')->where('property_id', 387)->get();
foreach ($rooms as $room) {
    echo "   Room {$room->id}:\n";
    echo "     availability_type: {$room->availability_type}\n";
    echo "     price_per_night: {$room->price_per_night}\n";
    
    // Get available_dates for this hotel (property-level)
    $roomPeriods = DB::table('available_dates_hotel_rooms')
        ->where('property_id', 387)
        ->orderBy('from_date')
        ->limit(5) // Just show first 5 for debugging
        ->get();
    
    echo "     Sample periods (first 5): {$roomPeriods->count()}\n";
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

echo "🤔 IMPORTANT REALIZATION:\n";
echo "   Xiroses showing 0% available with RED is CORRECT!\n";
echo "   Because availability_type: 2 means CLOSED PERIOD\n";
echo "   ALL dates should be CLOSED/RESERVED\n\n";

echo "📋 THE QUESTION IS:\n";
echo "   Do you WANT Xiroses to behave differently?\n";
echo "   If so, what should the correct behavior be?\n";
echo "   - Change availability_type to 1 (OPEN PERIOD)?\n";
echo "   - Change some periods to type: 'open'?\n";
echo "   - Or something else?\n\n";

echo "=== INVESTIGATION COMPLETE ===\n";
