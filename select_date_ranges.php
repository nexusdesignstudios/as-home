<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SELECT DATE RANGES FROM: TO: ===\n\n";

echo "🏨 XIROSES HOTEL (ID: 387) - CLOSED PERIOD\n";
echo "   Availability Type: 2 (CLOSED PERIOD)\n\n";

// Get Xiroses periods
$xirosesPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->orderBy('from_date')
    ->get();

echo "📅 SELECT DATE RANGES:\n";
foreach ($xirosesPeriods as $index => $period) {
    echo "   " . ($index + 1) . ". Select dates from: {$period->from_date} to: {$period->to_date}\n";
    echo "      Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    echo "      ---\n";
}

echo "\n🏨 ISMAILIA HOTEL (ID: 388) - OPEN PERIOD\n";
echo "   Availability Type: 1 (OPEN PERIOD)\n\n";

// Get Ismailia periods
$ismailiaPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

echo "📅 SELECT DATE RANGES:\n";
foreach ($ismailiaPeriods as $index => $period) {
    echo "   " . ($index + 1) . ". Select dates from: {$period->from_date} to: {$period->to_date}\n";
    echo "      Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    echo "      ---\n";
}

echo "\n🔍 FEBRUARY 2026 - SELECT DATES:\n";
echo "   For Xiroses (CLOSED PERIOD):\n";
echo "     Select dates from: 2026-02-01 to: 2026-02-28 -> ALL CLOSED\n";
echo "     Select dates from: 2026-02-20 to: 2026-02-23 -> ALL CLOSED\n\n";

echo "   For Ismailia (OPEN PERIOD):\n";
echo "     Select dates from: 2026-02-20 to: 2026-02-21 -> CLOSED (gap)\n";
echo "     Select dates from: 2026-02-22 to: 2026-02-23 -> AVAILABLE (open period)\n";

echo "\n📋 FRONTEND EXPECTED BEHAVIOR:\n";
echo "   Xiroses: Any date selection -> CLOSED/RESERVED\n";
echo "   Ismailia: Only dates in open periods -> AVAILABLE\n";
echo "   Ismailia: Dates in gaps -> CLOSED/RESERVED\n\n";

echo "=== SELECT DATE RANGES COMPLETE ===\n";
