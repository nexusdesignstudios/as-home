<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SHARM EL-SHEIKH 5 STARS XIROSES - FEB 21-22 ANALYSIS ===\n\n";

// Get hotel data
$hotel = DB::table('propertys')->where('id', 387)->first();

echo "🏨 HOTEL INFO:\n";
echo "   ID: {$hotel->id}\n";
echo "   Title: {$hotel->title}\n";
echo "   Availability Type: {$hotel->availability_type}\n";
echo "   Status: " . ($hotel->status == 1 ? 'Active' : 'Inactive') . "\n\n";

// Get rooms
$rooms = DB::table('hotel_rooms')->where('property_id', 387)->get();

echo "🛏️ ROOMS:\n";
foreach ($rooms as $room) {
    echo "   Room {$room->id} - availability_type: {$room->availability_type}\n";
}

echo "\n📅 AVAILABILITY PERIODS FOR FEBRUARY 2026:\n";

// Get availability periods
$periods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->where('from_date', '<=', '2026-03-01')
    ->where('to_date', '>=', '2026-02-01')
    ->orderBy('from_date')
    ->get();

foreach ($periods as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status})\n";
}

echo "\n🔍 SPECIFIC FEBRUARY DATES ANALYSIS:\n";

$datesToCheck = ['2026-02-20', '2026-02-21', '2026-02-22', '2026-02-23'];

foreach ($datesToCheck as $date) {
    echo "   Date: {$date}\n";
    
    // Check if date falls within any availability period
    $inPeriod = false;
    $periodType = null;
    $periodRange = null;
    
    foreach ($periods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $inPeriod = true;
            $periodType = $period->type;
            $periodRange = $period->from_date . ' to ' . $period->to_date;
            break;
        }
    }
    
    if ($inPeriod) {
        $status = ($periodType == 'dead' || $periodType == 'closed') ? "CLOSED" : "OPEN";
        echo "     Status: {$status} (within period: {$periodRange})\n";
        echo "     Period Type: {$periodType}\n";
    } else {
        echo "     Status: GAP (not in any period)\n";
        echo "     Expected: Should be CLOSED for Closed Period hotels\n";
    }
    echo "\n";
}

echo "\n🎯 CURRENT FRONTEND LOGIC ISSUE:\n";
echo "   PROBLEM: Frontend treats 'open' type as AVAILABLE\n";
echo "   BUT for Xiroses: availability_type = 2 (CLOSED PERIOD)\n";
echo "   EXPECTED: ALL dates should be CLOSED regardless of period type\n";
echo "   ACTUAL: Feb 21-22 shows as OPEN because period type = 'open'\n\n";

echo "📋 CORRECT LOGIC SHOULD BE:\n";
echo "   If availability_type = 2 (CLOSED PERIOD):\n";
echo "     -> ALL dates = CLOSED (ignore period types)\n";
echo "   If availability_type = 1 (OPEN PERIOD):\n";
echo "     -> Only 'open' type periods = AVAILABLE\n";
echo "     -> 'dead'/'closed' type periods = CLOSED\n";
echo "     -> Gaps between periods = CLOSED\n\n";

echo "=== ANALYSIS COMPLETE ===\n";
