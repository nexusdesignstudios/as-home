<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SEARCHING FOR ISMAILIA 5 STARS HOTEL ===\n\n";

// Search for hotels with "Ismailia" in the name
$hotels = DB::table('propertys')
    ->where('title', 'LIKE', '%Ismailia%')
    ->orWhere('title', 'LIKE', '%Ismailia%')
    ->orWhere('title', 'LIKE', '%Ismailia%')
    ->select('id', 'title', 'status', 'city', 'state')
    ->get();

echo "Found {$hotels->count()} hotel(s) with Ismailia in name:\n";

foreach ($hotels as $hotel) {
    echo "\n🏨 Hotel ID: {$hotel->id}\n";
    echo "   Title: {$hotel->title}\n";
    echo "   Status: " . ($hotel->status == 1 ? 'Active' : 'Inactive') . "\n";
    echo "   City: {$hotel->city}\n";
    echo "   State: {$hotel->state}\n";
    
    // Get hotel rooms
    $rooms = DB::table('hotel_rooms')
        ->where('property_id', $hotel->id)
        ->select('id', 'room_number', 'availability_type', 'price_per_night')
        ->get();
    
    echo "   Rooms: {$rooms->count()}\n";
    foreach ($rooms as $room) {
        $status = $room->availability_type == 2 ? "CLOSED" : "OPEN";
        echo "     Room {$room->id} (#{$room->room_number}) - Type: {$room->availability_type} ({$status})\n";
    }
    
    // Check availability periods
    echo "   Availability Periods:\n";
    $periods = DB::table('available_dates_hotel_rooms')
        ->where('property_id', $hotel->id)
        ->orderBy('from_date')
        ->get();
    
    foreach ($periods as $period) {
        $status = ($period->type == 'dead' || $period->type == 'closed') ? "CLOSED" : "OPEN";
        echo "     {$period->from_date} to {$period->to_date} - Type: {$period->type} ({$status}) - Price: {$period->price}\n";
    }
    
    // Check reservations for specific dates mentioned
    echo "   Reservations for Jan 21-22 and Feb 21-22:\n";
    
    $datesToCheck = ['2026-01-21', '2026-01-22', '2026-02-21', '2026-02-22'];
    
    foreach ($datesToCheck as $date) {
        $reservations = DB::table('reservations')
            ->where('property_id', $hotel->id)
            ->where(function($query) use ($date) {
                $query->where('check_in_date', '<=', $date)
                      ->where('check_out_date', '>', $date);
            })
            ->select('check_in_date', 'check_out_date', 'status', 'reservable_id', 'payment_method')
            ->get();
        
        echo "     {$date}: {$reservations->count()} reservation(s)\n";
        foreach ($reservations as $reservation) {
            echo "        {$reservation->check_in_date} to {$reservation->check_out_date} - Room {$reservation->reservable_id} - Status: {$reservation->status} - Payment: {$reservation->payment_method}\n";
        }
    }
}

echo "\n=== SEARCH COMPLETE ===\n";
