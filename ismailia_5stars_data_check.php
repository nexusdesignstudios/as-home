<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ISMAILIA 5 STARS HOTEL (ID: 388) - DATA ANALYSIS ===\n\n";

// Get hotel basic info
$hotel = DB::table('propertys')->where('id', 388)->first();

echo "🏨 HOTEL INFORMATION:\n";
echo "   ID: {$hotel->id}\n";
echo "   Title: {$hotel->title}\n";
echo "   Status: " . ($hotel->status == 1 ? 'Active' : 'Inactive') . "\n";
echo "   City: {$hotel->city}\n";
echo "   State: {$hotel->state}\n";
echo "   Created: {$hotel->created_at}\n";
echo "   Updated: {$hotel->updated_at}\n\n";

// Get rooms
$rooms = DB::table('hotel_rooms')
    ->where('property_id', 388)
    ->select('id', 'room_number', 'availability_type', 'price_per_night', 'room_type_id', 'available_dates')
    ->get();

echo "🛏️  ROOMS DATA:\n";
foreach ($rooms as $room) {
    echo "   Room {$room->id} (#{$room->room_number})\n";
    echo "     Availability Type: {$room->availability_type} ";
    echo "(" . ($room->availability_type == 1 ? 'OPEN' : 'CLOSED') . ")\n";
    echo "     Price: EGP {$room->price_per_night}\n";
    echo "     Room Type ID: {$room->room_type_id}\n";
    
    // Check available_dates in room table
    if ($room->available_dates) {
        $roomDates = json_decode($room->available_dates, true);
        if (is_array($roomDates)) {
            echo "     Legacy Available Dates (JSON):\n";
            foreach ($roomDates as $date) {
                $price = isset($date['price']) ? $date['price'] : 'N/A';
                echo "       {$date['from']} to {$date['to']} - Type: {$date['type']} - Price: {$price}\n";
            }
        } else {
            echo "     Legacy Available Dates: Invalid JSON or empty\n";
        }
    } else {
        echo "     Legacy Available Dates: None\n";
    }
    echo "\n";
}

// Get availability periods from main table
echo "📅 AVAILABILITY PERIODS (available_dates_hotel_rooms):\n";
$periods = DB::table('available_dates_hotel_rooms')
    ->where('property_id', 388)
    ->orderBy('from_date')
    ->get();

foreach ($periods as $period) {
    $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
    echo "   {$period->from_date} to {$period->to_date}\n";
    echo "     Type: {$period->type}\n";
    echo "     Status: {$status}\n";
    echo "     Price: EGP {$period->price}\n";
    echo "     Hotel Room ID: {$period->hotel_room_id}\n";
    echo "     Created: {$period->created_at}\n\n";
}

// Check specific dates mentioned by user
echo "🔍 SPECIFIC DATES ANALYSIS (Jan 21-22, Feb 21-22):\n";
$datesToCheck = ['2026-01-21', '2026-01-22', '2026-02-21', '2026-02-22'];

foreach ($datesToCheck as $date) {
    echo "   Date: {$date}\n";
    
    // Check if date falls within any availability period
    $inPeriod = false;
    $periodType = null;
    $periodPrice = null;
    
    foreach ($periods as $period) {
        if ($date >= $period->from_date && $date <= $period->to_date) {
            $inPeriod = true;
            $periodType = $period->type;
            $periodPrice = $period->price;
            break;
        }
    }
    
    if ($inPeriod) {
        $status = ($periodType == 'dead' || $periodType == 'closed') ? "CLOSED" : "OPEN";
        echo "     Status: {$status} (within period: {$period->from_date} to {$period->to_date})\n";
        echo "     Period Type: {$periodType}\n";
        echo "     Expected Frontend: " . (($periodType == 'dead' || $periodType == 'closed') ? "CLOSED/UNAVAILABLE" : "OPEN/AVAILABLE") . "\n";
    } else {
        echo "     Status: NOT IN ANY PERIOD\n";
        echo "     Expected Frontend: OPEN/AVAILABLE (gap between periods)\n";
    }
    echo "\n";
}

// Check reservations for these dates
echo "📋 RESERVATIONS CHECK:\n";
foreach ($datesToCheck as $date) {
    $reservations = DB::table('reservations')
        ->where('property_id', 388)
        ->where(function($query) use ($date) {
            $query->where('check_in_date', '<=', $date)
                  ->where('check_out_date', '>', $date);
        })
        ->select('check_in_date', 'check_out_date', 'status', 'reservable_id', 'payment_method')
        ->get();
    
    echo "   {$date}: {$reservations->count()} reservation(s)\n";
    foreach ($reservations as $reservation) {
        echo "     {$reservation->check_in_date} to {$reservation->check_out_date}\n";
        echo "     Room: {$reservation->reservable_id}\n";
        echo "     Status: {$reservation->status}\n";
        echo "     Payment: {$reservation->payment_method}\n";
    }
}

echo "\n📊 SUMMARY:\n";
echo "   Hotel: Ismailia 5 Stars Hotel (ID: 388)\n";
echo "   Availability Type: 1 (OPEN PERIOD)\n";
echo "   Total Rooms: " . $rooms->count() . "\n";
echo "   Total Periods: " . $periods->count() . "\n";
echo "   All Period Types: 'open' (should be available)\n";
echo "   Expected Frontend: Show availability based on period ranges\n";
echo "   User Issue: Frontend shows ALL dates as reserved\n\n";

echo "🎯 FRONTEND BEHAVIOR EXPECTATION:\n";
echo "   - Jan 21-22: Should be AVAILABLE (gap between periods)\n";
echo "   - Feb 21-22: Should be AVAILABLE (within open period)\n";
echo "   - Only dates outside 'open' periods should be CLOSED\n";

echo "\n=== ANALYSIS COMPLETE ===\n";
