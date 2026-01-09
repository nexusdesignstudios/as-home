<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DURATION ANALYSIS FROM DATABASE ===\n\n";

echo "🏨 XIROSES HOTEL (ID: 387) - DURATION PERIODS:\n";
echo "   Availability Type: 2 (CLOSED PERIOD)\n\n";

// Get Xiroses periods
$xirosesPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 387)
    ->orderBy('from_date')
    ->get();

echo "📅 ALL PERIODS WITH DURATIONS:\n";
$totalDaysXiroses = 0;
foreach ($xirosesPeriods as $index => $period) {
    $from = new DateTime($period->from_date);
    $to = new DateTime($period->to_date);
    $duration = $from->diff($to)->days + 1; // Include both start and end days
    
    echo "   " . ($index + 1) . ". {$period->from_date} to {$period->to_date}\n";
    echo "      Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    echo "      Duration: {$duration} days\n";
    echo "      ---\n";
    
    $totalDaysXiroses += $duration;
}

echo "   📊 SUMMARY XIROSES:\n";
echo "   Total periods: {$xirosesPeriods->count()}\n";
echo "   Total days covered: {$totalDaysXiroses}\n";
echo "   All periods are CLOSED (type: dead)\n\n";

echo "🏨 ISMAILIA HOTEL (ID: 388) - DURATION PERIODS:\n";
echo "   Availability Type: 1 (OPEN PERIOD)\n\n";

// Get Ismailia periods
$ismailiaPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

echo "📅 ALL PERIODS WITH DURATIONS:\n";
$totalDaysIsmailia = 0;
$openDaysIsmailia = 0;
foreach ($ismailiaPeriods as $index => $period) {
    $from = new DateTime($period->from_date);
    $to = new DateTime($period->to_date);
    $duration = $from->diff($to)->days + 1; // Include both start and end days
    
    echo "   " . ($index + 1) . ". {$period->from_date} to {$period->to_date}\n";
    echo "      Type: {$period->type} (" . (($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN") . ")\n";
    echo "      Duration: {$duration} days\n";
    echo "      ---\n";
    
    $totalDaysIsmailia += $duration;
    if ($period->type == 'open') {
        $openDaysIsmailia += $duration;
    }
}

echo "   📊 SUMMARY ISMAILIA:\n";
echo "   Total periods: {$ismailiaPeriods->count()}\n";
echo "   Total days covered: {$totalDaysIsmailia}\n";
echo "   Open days: {$openDaysIsmailia}\n";
echo "   Closed days: " . ($totalDaysIsmailia - $openDaysIsmailia) . "\n";
echo "   Availability percentage: " . round(($openDaysIsmailia / $totalDaysIsmailia) * 100, 1) . "%\n\n";

echo "🔍 FEBRUARY 2026 SPECIFIC DURATIONS:\n";
echo "   Date Range: 2026-02-01 to 2026-02-28 (28 days)\n\n";

// Check February for Xiroses
echo "   XIROSES FEBRUARY:\n";
$xirosesFebDays = 0;
foreach ($xirosesPeriods as $period) {
    $periodStart = new DateTime($period->from_date);
    $periodEnd = new DateTime($period->to_date);
    $febStart = new DateTime('2026-02-01');
    $febEnd = new DateTime('2026-02-28');
    
    // Calculate overlap with February
    $overlapStart = max($periodStart, $febStart);
    $overlapEnd = min($periodEnd, $febEnd);
    
    if ($overlapStart <= $overlapEnd) {
        $overlapDays = $overlapStart->diff($overlapEnd)->days + 1;
        echo "      {$period->from_date} to {$period->to_date} - Overlap: {$overlapDays} days (type: {$period->type})\n";
        $xirosesFebDays += $overlapDays;
    }
}
echo "      Total February coverage: {$xirosesFebDays}/28 days\n";
echo "      Expected closed days: 28 (availability_type: 2)\n\n";

// Check February for Ismailia
echo "   ISMAILIA FEBRUARY:\n";
$ismailiaFebOpenDays = 0;
$ismailiaFebClosedDays = 0;
foreach ($ismailiaPeriods as $period) {
    $periodStart = new DateTime($period->from_date);
    $periodEnd = new DateTime($period->to_date);
    $febStart = new DateTime('2026-02-01');
    $febEnd = new DateTime('2026-02-28');
    
    // Calculate overlap with February
    $overlapStart = max($periodStart, $febStart);
    $overlapEnd = min($periodEnd, $febEnd);
    
    if ($overlapStart <= $overlapEnd) {
        $overlapDays = $overlapStart->diff($overlapEnd)->days + 1;
        echo "      {$period->from_date} to {$period->to_date} - Overlap: {$overlapDays} days (type: {$period->type})\n";
        
        if ($period->type == 'open') {
            $ismailiaFebOpenDays += $overlapDays;
        } else {
            $ismailiaFebClosedDays += $overlapDays;
        }
    }
}
$gapDays = 28 - $ismailiaFebOpenDays - $ismailiaFebClosedDays;
echo "      Total February coverage: " . ($ismailiaFebOpenDays + $ismailiaFebClosedDays) . "/28 days\n";
echo "      Open days: {$ismailiaFebOpenDays}\n";
echo "      Closed days: {$ismailiaFebClosedDays}\n";
echo "      Gap days: {$gapDays}\n";
echo "      Expected available days: {$ismailiaFebOpenDays} (only open periods)\n\n";

echo "📋 DURATION SUMMARY:\n";
echo "   Xiroses: All periods are CLOSED -> Frontend should show 0% available\n";
echo "   Ismailia: Only open periods count -> Frontend should show " . round(($ismailiaFebOpenDays / 28) * 100, 1) . "% available for February\n\n";

echo "=== DURATION ANALYSIS COMPLETE ===\n";
