<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== HOTEL CLASS 5 - OPEN/CLOSED PERIODS ANALYSIS ===\n\n";

// Get hotels with class 5
$hotels = DB::table('propertys')
    ->where('class', 5)
    ->whereIn('id', [387, 388]) // Xiroses and Ismailia
    ->orderBy('id')
    ->get();

echo "🏨 CLASS 5 HOTELS FOUND:\n";
foreach ($hotels as $hotel) {
    echo "   ID: {$hotel->id}\n";
    echo "   Title: {$hotel->title}\n";
    echo "   Class: {$hotel->class}\n";
    echo "   Status: " . ($hotel->status == 1 ? 'Active' : 'Inactive') . "\n";
    echo "   Availability Type: {$hotel->availability_type}\n";
    echo "   ---\n";
}

echo "\n📅 XIROSES (ID: 387) - AVAILABILITY PERIODS:\n";

// Get Xiroses periods
$xirosesPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->orderBy('from_date')
    ->get();

echo "   Total periods: {$xirosesPeriods->count()}\n";
foreach ($xirosesPeriods as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status})\n";
}

echo "\n📅 ISMAILIA (ID: 388) - AVAILABILITY PERIODS:\n";

// Get Ismailia periods
$ismailiaPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

echo "   Total periods: {$ismailiaPeriods->count()}\n";
foreach ($ismailiaPeriods as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status})\n";
}

echo "\n🔍 FEBRUARY 2026 COMPARISON:\n";
echo "   Date        | Xiroses (387) | Ismailia (388)\n";
echo "   ------------|----------------|----------------\n";

$datesToCheck = ['2026-02-20', '2026-02-21', '2026-02-22', '2026-02-23'];

foreach ($datesToCheck as $date) {
    // Check Xiroses
    $xirosesInPeriod = false;
    $xirosesPeriodType = null;
    foreach ($xirosesPeriods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $xirosesInPeriod = true;
            $xirosesPeriodType = $period->type;
            break;
        }
    }
    
    $xirosesStatus = "GAP";
    if ($xirosesInPeriod) {
        $xirosesStatus = ($xirosesPeriodType == 'dead' || $xirosesPeriodType == 'closed') ? "CLOSED" : "OPEN";
    }
    
    // Check Ismailia
    $ismailiaInPeriod = false;
    $ismailiaPeriodType = null;
    foreach ($ismailiaPeriods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $ismailiaInPeriod = true;
            $ismailiaPeriodType = $period->type;
            break;
        }
    }
    
    $ismailiaStatus = "GAP";
    if ($ismailiaInPeriod) {
        $ismailiaStatus = ($ismailiaPeriodType == 'dead' || $ismailiaPeriodType == 'closed') ? "CLOSED" : "OPEN";
    }
    
    echo "   {$date} | {$xirosesStatus} (" . ($xirosesPeriodType ?? 'none') . ") | {$ismailiaStatus} (" . ($ismailiaPeriodType ?? 'none') . ")\n";
}

echo "\n🎯 EXPECTED FRONTEND BEHAVIOR:\n";
echo "   Xiroses (availability_type: 2 - CLOSED PERIOD):\n";
echo "     -> ALL dates should show as CLOSED/RESERVED\n";
echo "     -> Availability percentage: ~0%\n";
echo "   Ismailia (availability_type: 1 - OPEN PERIOD):\n";
echo "     -> Only 'open' type periods should show as AVAILABLE\n";
echo "     -> 'dead'/'closed' types and gaps should show as CLOSED\n";
echo "     -> Availability percentage: based on actual 'open' periods\n\n";

echo "📋 FRONTEND LOGIC CHECK:\n";
echo "   Current frontend logic should:\n";
echo "   1. Check room.availability_type first\n";
echo "   2. If availability_type = 2 -> ALL dates CLOSED\n";
echo "   3. If availability_type = 1 -> Check period types\n";
echo "   4. Only 'open' type periods = AVAILABLE\n";
echo "   5. Gaps = CLOSED\n\n";

echo "=== ANALYSIS COMPLETE ===\n";
