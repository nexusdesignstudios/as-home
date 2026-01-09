<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== Checking Specific Dates for Xiroses Property ===\n";

// Check Jan 7-19 and Jan 22-23 specifically
$datesToCheck = [
    '2026-01-07', '2026-01-08', '2026-01-09', '2026-01-10', '2026-01-11', '2026-01-12', '2026-01-13', '2026-01-14', '2026-01-15', '2026-01-16', '2026-01-17', '2026-01-18', '2026-01-19',
    '2026-01-20', '2026-01-21', '2026-01-22', '2026-01-23',
    '2026-02-18', '2026-02-19', '2026-02-20', '2026-02-21'
];

foreach ($datesToCheck as $date) {
    echo "\n=== Checking date: $date ===\n";
    
    // Check each room for this date
    $rooms = DB::table('hotel_rooms')
        ->where('property_id', 387)
        ->select('id', 'availability_type')
        ->get();
    
    foreach ($rooms as $room) {
        // Check available_dates_hotel_rooms for this room and date
        $dateEntry = DB::table('available_dates_hotel_rooms')
            ->where('hotel_room_id', $room->id)
            ->where('from_date', '<=', $date)
            ->where('to_date', '>=', $date)
            ->first();
        
        echo "Room {$room->id} (availability_type: {$room->availability_type}): ";
        
        if ($dateEntry) {
            echo "IN RANGE - {$dateEntry->from_date} to {$dateEntry->to_date} (type: {$dateEntry->type})\n";
        } else {
            echo "NOT IN ANY RANGE\n";
        }
    }
}
