<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting debug script v6...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    // Settings
    $propertyId = 393;
    $roomId = 844;
    $checkIn = '2026-01-28';
    $checkOut = '2026-01-29';

    echo "--- Testing Availability Logic for Room ID: $roomId ---\n";
    echo "Check-In: $checkIn, Check-Out: $checkOut\n\n";

    // 1. Direct Reservation Query
    echo "1. Checking Existing Reservations (Direct DB Query)...\n";
    $reservations = \App\Models\Reservation::where('reservable_id', $roomId)
        ->where('reservable_type', 'hotel_room') // Check exact string usage
        ->whereIn('status', ['confirmed', 'approved', 'pending'])
        ->get();
    
    echo "Found " . $reservations->count() . " total reservations for this room.\n";
    foreach ($reservations as $res) {
        echo " - ID: {$res->id}, Status: {$res->status}, In: {$res->check_in_date}, Out: {$res->check_out_date}\n";
    }

    // 2. Testing datesOverlap Method
    echo "\n2. Testing Reservation::datesOverlap()...\n";
    $hasOverlap = \App\Models\Reservation::datesOverlap($checkIn, $checkOut, $roomId, 'hotel_room');
    echo "Result: " . ($hasOverlap ? "❌ OVERLAP DETECTED" : "✅ NO OVERLAP") . "\n";

    // 3. Testing ApiController::findAvailableHotelRoom logic
    echo "\n3. Testing ApiController logic simulation...\n";
    
    // Simulate the exact query used in ApiController
    $availableRoom = \App\Models\HotelRoom::where('property_id', $propertyId)
        ->where('id', $roomId)
        ->where('status', 1)
        ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
            $query->whereIn('status', ['confirmed', 'approved', 'pending'])
                ->where(function ($dateQuery) use ($checkIn, $checkOut) {
                    $dateQuery->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '=', $checkIn)
                            ->where('check_out_date', '=', $checkOut);
                    })
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<', $checkOut)
                            ->where('check_out_date', '>', $checkIn);
                    });
                });
        })
        ->first();

    if ($availableRoom) {
        echo "✅ ApiController Query FOUND the room available.\n";
    } else {
        echo "❌ ApiController Query found NO available room.\n";
        
        // Debug why it failed
        $roomExists = \App\Models\HotelRoom::find($roomId);
        if (!$roomExists) echo "   -> Room ID $roomId does not exist in DB.\n";
        elseif ($roomExists->status != 1) echo "   -> Room status is " . $roomExists->status . " (inactive).\n";
        elseif ($roomExists->property_id != $propertyId) echo "   -> Room belongs to property " . $roomExists->property_id . ", not $propertyId.\n";
        else echo "   -> Blocked by reservation query constraint.\n";
    }

} catch (\Throwable $e) {
    echo "\n❌ CRITICAL ERROR:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
