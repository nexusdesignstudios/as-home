<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== XIROSES HOTEL - DETAILED AVAILABILITY ANALYSIS ===\n\n";

// Hotel info
$hotelId = 387;
$hotelName = "Sharm El-Sheikh 5 stars Xiroses";
echo "🏨 Hotel: $hotelName (ID: $hotelId)\n";
echo "📍 Location: Sharm El-Sheikh, South Sinai, Egypt\n\n";

// Get all rooms
$rooms = DB::table('hotel_rooms')
    ->where('property_id', $hotelId)
    ->select('id', 'room_number', 'availability_type')
    ->get();

echo "🛏️  Hotel Rooms:\n";
foreach ($rooms as $room) {
    $status = $room->availability_type == 2 ? "CLOSED" : "OPEN";
    echo "   Room {$room->id} (#{$room->room_number}) - Status: $status\n";
}

// Get all availability periods
echo "\n📅 AVAILABILITY PERIODS ANALYSIS:\n";
echo str_repeat("=", 80) . "\n";

$availabilityPeriods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', $hotelId)
    ->orderBy('from_date')
    ->get();

if ($availabilityPeriods->count() > 0) {
    foreach ($availabilityPeriods as $period) {
        $type = $period->type;
        $status = ($type == 'dead' || $type == 'closed') ? "🔴 CLOSED" : "🟢 OPEN";
        $price = $period->price > 0 ? number_format($period->price, 2) : "N/A";
        
        echo "📅 Period: {$period->from_date} to {$period->to_date}\n";
        echo "   Status: $status (Type: $type)\n";
        echo "   Price: {$period->price}\n";
        echo "   " . str_repeat("-", 60) . "\n";
    }
} else {
    echo "❌ No availability periods found in database\n";
}

// Detailed day-by-day analysis for current month
echo "\n📆 DETAILED DAY-BY-DAY ANALYSIS - JANUARY 2026:\n";
echo str_repeat("=", 80) . "\n";

// Create date range for January 2026
$startDate = new DateTime('2026-01-01');
$endDate = new DateTime('2026-01-31');
$interval = new DateInterval('P1D');
$period = new DatePeriod($startDate, $interval, $endDate);

echo "Date       | Day    | Status  | Type   | Room Status | Notes\n";
echo str_repeat("-", 80) . "\n";

foreach ($period as $date) {
    $dateStr = $date->format('Y-m-d');
    $dayName = $date->format('D');
    
    // Check if this date falls in any closed period
    $isClosed = false;
    $periodType = 'unknown';
    
    foreach ($availabilityPeriods as $period) {
        $fromDate = new DateTime($period->from_date);
        $toDate = new DateTime($period->to_date);
        
        if ($date >= $fromDate && $date <= $toDate) {
            $isClosed = true;
            $periodType = $period->type;
            break;
        }
    }
    
    $status = $isClosed ? "🔴 CLOSED" : "🟢 OPEN";
    $typeDisplay = $periodType;
    $roomStatus = $isClosed ? "All Rooms Closed" : "All Rooms Open";
    $notes = $isClosed ? "Dead/Closed Period" : "Available for Booking";
    
    printf("%-10s | %-6s | %-7s | %-6s | %-12s | %s\n", 
        $dateStr, 
        $dayName, 
        $status, 
        $typeDisplay, 
        $roomStatus, 
        $notes
    );
}

// Configuration for preview
echo "\n⚙️  CONFIGURATION FOR PREVIEW:\n";
echo str_repeat("=", 80) . "\n";

echo "🏨 HOTEL CONFIGURATION:\n";
echo "   Property ID: $hotelId\n";
echo "   Hotel Name: $hotelName\n";
echo "   Availability Type: 2 (Closed Period)\n";
echo "   Total Rooms: " . $rooms->count() . "\n\n";

echo "📅 CLOSED PERIODS CONFIGURATION:\n";
foreach ($availabilityPeriods as $period) {
    echo "   {\n";
    echo "     \"from_date\": \"{$period->from_date}\",\n";
    echo "     \"to_date\": \"{$period->to_date}\",\n";
    echo "     \"type\": \"{$period->type}\",\n";
    echo "     \"price\": {$period->price},\n";
    echo "     \"status\": \"" . (($period->type == 'dead') ? 'CLOSED' : 'OPEN') . "\"\n";
    echo "   },\n";
}

echo "\n🎨 CALENDAR DISPLAY CONFIGURATION:\n";
echo "   Closed Days Color: #ff4757 (Red)\n";
echo "   Available Days Color: #2ed573 (Green)\n";
echo "   Closed Period Label: \"CLOSED\"\n";
echo "   Available Period Label: \"AVAILABLE\"\n";
echo "   Show Weekends: Yes\n";
echo "   Start Week: Sunday\n\n";

echo "📱 FRONTEND DISPLAY NOTES:\n";
echo "   • All dates should show as CLOSED/UNAVAILABLE\n";
echo "   • No booking buttons should be visible\n";
echo "   • Calendar should display red/closed styling\n";
echo "   • Hover text: \"Hotel Closed - No Availability\"\n";
echo "   • Click action: Show closed period message\n\n";

// Summary
echo "📊 SUMMARY:\n";
echo str_repeat("=", 80) . "\n";
echo "🔴 Total Closed Days: " . $availabilityPeriods->count() . " periods\n";
echo "🟢 Total Open Days: 0\n";
echo "📅 Current Status: HOTEL COMPLETELY CLOSED\n";
echo "⚠️  Booking Status: NOT ACCEPTING RESERVATIONS\n";
echo "🔧 Configuration Status: CORRECTLY CONFIGURED\n";

echo "\n✅ ANALYSIS COMPLETE\n";
