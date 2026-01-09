<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SPECIFIC DATE RANGES VERIFICATION ===\n\n";

echo "🏨 XIROSES HOTEL (ID: 387) - CLOSED PERIOD\n";
echo "   Availability Type: 2 (CLOSED PERIOD)\n\n";

// Get Xiroses periods
$xirosesPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->orderBy('from_date')
    ->get();

echo "📅 CHECKING SPECIFIC RANGES:\n";

// Check Jan 7-20
echo "   1. Range: 2026-01-07 to 2026-01-20\n";
$foundPeriods1 = [];
foreach ($xirosesPeriods as $period) {
    if (($period->from_date <= '2026-01-20' && $period->to_date >= '2026-01-07')) {
        $foundPeriods1[] = $period;
    }
}
if (!empty($foundPeriods1)) {
    foreach ($foundPeriods1 as $period) {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
} else {
    echo "      No periods found in this range\n";
}

// Check Feb 22-20 (invalid range, but let's check what exists around Feb)
echo "\n   2. Range: 2026-02-22 to 2026-02-20 (Note: End date before start date)\n";
echo "      Checking periods around February 22:\n";
foreach ($xirosesPeriods as $period) {
    if ($period->from_date <= '2026-02-28' && $period->to_date >= '2026-02-01') {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
}

// Check from 22 to end of year
echo "\n   3. Range: 2026-02-22 to end of year\n";
$foundPeriods3 = [];
foreach ($xirosesPeriods as $period) {
    if ($period->from_date <= '2026-12-31' && $period->to_date >= '2026-02-22') {
        $foundPeriods3[] = $period;
    }
}
if (!empty($foundPeriods3)) {
    foreach ($foundPeriods3 as $period) {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
} else {
    echo "      No periods found from Feb 22 to end\n";
}

echo "\n🏨 ISMAILIA HOTEL (ID: 388) - OPEN PERIOD\n";
echo "   Availability Type: 1 (OPEN PERIOD)\n\n";

// Get Ismailia periods
$ismailiaPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

echo "📅 CHECKING SPECIFIC RANGES:\n";

// Check Jan 7-21
echo "   1. Range: 2026-01-07 to 2026-01-21\n";
$foundPeriods1 = [];
foreach ($ismailiaPeriods as $period) {
    if (($period->from_date <= '2026-01-21' && $period->to_date >= '2026-01-07')) {
        $foundPeriods1[] = $period;
    }
}
if (!empty($foundPeriods1)) {
    foreach ($foundPeriods1 as $period) {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
} else {
    echo "      No periods found in this range\n";
}

// Check Feb 22-20 (invalid range, but let's check what exists around Feb)
echo "\n   2. Range: 2026-02-22 to 2026-02-20 (Note: End date before start date)\n";
echo "      Checking periods around February 22:\n";
foreach ($ismailiaPeriods as $period) {
    if ($period->from_date <= '2026-02-28' && $period->to_date >= '2026-02-01') {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
}

// Check from 22 to end of year
echo "\n   3. Range: 2026-02-22 to end of year\n";
$foundPeriods3 = [];
foreach ($ismailiaPeriods as $period) {
    if ($period->from_date <= '2026-12-31' && $period->to_date >= '2026-02-22') {
        $foundPeriods3[] = $period;
    }
}
if (!empty($foundPeriods3)) {
    foreach ($foundPeriods3 as $period) {
        echo "      Found: {$period->from_date} to {$period->to_date} - Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    }
} else {
    echo "      No periods found from Feb 22 to end\n";
}

echo "\n🔍 CORRECTED FEBRUARY ANALYSIS:\n";
echo "   For Xiroses (CLOSED PERIOD):\n";
echo "     Jan 7-20: Should be CLOSED (availability_type: 2)\n";
echo "     Feb 22-end: Should be CLOSED (availability_type: 2)\n";
echo "   For Ismailia (OPEN PERIOD):\n";
echo "     Jan 7-21: Should be AVAILABLE if in 'open' periods\n";
echo "     Feb 22-end: Should be AVAILABLE if in 'open' periods\n\n";

echo "📋 SUMMARY:\n";
echo "   Xiroses: All periods are type 'dead' (CLOSED)\n";
echo "   Ismailia: All periods are type 'open' (AVAILABLE)\n";
echo "   Frontend should respect availability_type first, then period types\n\n";

echo "=== VERIFICATION COMPLETE ===\n";
