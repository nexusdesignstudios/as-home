<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CORRECT LOGIC ANALYSIS - GAP vs PERIOD DAYS ===\n\n";

echo "🎯 BUSINESS RULES:\n";
echo "1. CLOSED PERIOD hotels (availability_type: 2):\n";
echo "   -> ALL dates should be CLOSED (ignore period types)\n";
echo "2. OPEN PERIOD hotels (availability_type: 1):\n";
echo "   -> 'open' type periods = AVAILABLE\n";
echo "   -> 'dead'/'closed' type periods = CLOSED\n";
echo "   -> GAP days (not in any period) = CLOSED\n\n";

echo "🔍 XIROSES ANALYSIS (availability_type: 2 - CLOSED PERIOD):\n";

// Get Xiroses periods
$periods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->where('from_date', '<=', '2026-03-01')
    ->where('to_date', '>=', '2026-02-01')
    ->orderBy('from_date')
    ->get();

echo "   Periods found: {$periods->count()}\n";
foreach ($periods as $period) {
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type}\n";
}

echo "\n   FEBRUARY ANALYSIS:\n";
$datesToCheck = ['2026-02-20', '2026-02-21', '2026-02-22', '2026-02-23'];

foreach ($datesToCheck as $date) {
    echo "   Date: {$date}\n";
    
    // Check if date falls within any availability period
    $inPeriod = false;
    $periodType = null;
    
    foreach ($periods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $inPeriod = true;
            $periodType = $period->type;
            break;
        }
    }
    
    if ($inPeriod) {
        $status = ($periodType == 'dead' || $periodType == 'closed') ? "CLOSED" : "OPEN";
        echo "     Status: {$status} (within period type: {$periodType})\n";
    } else {
        echo "     Status: GAP (not in any period)\n";
    }
}

echo "\n🔍 ISMAILIA ANALYSIS (availability_type: 1 - OPEN PERIOD):\n";

// Get Ismailia periods
$ismailiaPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->where('from_date', '<=', '2026-03-01')
    ->where('to_date', '>=', '2026-02-01')
    ->orderBy('from_date')
    ->get();

echo "   Periods found: {$ismailiaPeriods->count()}\n";
foreach ($ismailiaPeriods as $period) {
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type}\n";
}

echo "\n   FEBRUARY ANALYSIS:\n";
foreach ($datesToCheck as $date) {
    echo "   Date: {$date}\n";
    
    // Check if date falls within any availability period
    $inPeriod = false;
    $periodType = null;
    
    foreach ($ismailiaPeriods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $inPeriod = true;
            $periodType = $period->type;
            break;
        }
    }
    
    if ($inPeriod) {
        $status = ($periodType == 'dead' || $periodType == 'closed') ? "CLOSED" : "OPEN";
        echo "     Status: {$status} (within period type: {$periodType})\n";
    } else {
        echo "     Status: GAP (not in any period)\n";
    }
}

echo "\n📋 CORRECT FRONTEND LOGIC:\n";
echo "   For CLOSED PERIOD (availability_type: 2):\n";
echo "     -> ALL dates = CLOSED (ignore period types)\n";
echo "   For OPEN PERIOD (availability_type: 1):\n";
echo "     -> date in 'open' period = AVAILABLE\n";
echo "     -> date in 'dead'/'closed' period = CLOSED\n";
echo "     -> date in GAP (no period) = CLOSED\n\n";

echo "=== ANALYSIS COMPLETE ===\n";
