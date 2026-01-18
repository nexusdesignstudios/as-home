<?php

try {
    $hotel = App\Models\Property::where('title', 'like', '%Test 1%')->first();
    
    if (!$hotel) {
        echo "Hotel 'Test 1' not found.\n";
        exit;
    }

    echo "Hotel: " . $hotel->title . " (ID: " . $hotel->id . ")\n";

    $rooms = App\Models\HotelRoom::where('property_id', $hotel->id)->get();
    echo "Total Rooms: " . $rooms->count() . "\n";

    echo "Rooms by Type:\n";
    $grouped = $rooms->groupBy('room_type_id');
    
    foreach ($grouped as $typeId => $group) {
        // Try to get room type name
        $typeName = "Unknown";
        if ($group->first()->room_type) {
            $typeName = $group->first()->room_type->name;
        } elseif ($group->first()->custom_room_type) {
             $typeName = $group->first()->custom_room_type . " (Custom)";
        }
        
        echo "- Type ID $typeId ($typeName): " . $group->count() . " rooms\n";
        foreach ($group as $room) {
             echo "  Room ID: " . $room->id . "\n";
        }
    }

    // Check for existing reservations for Feb 3-4, 2026
    $checkIn = '2026-02-03';
    $checkOut = '2026-02-04';
    
    echo "\nChecking reservations for $checkIn to $checkOut:\n";
    
    $reservations = App\Models\Reservation::where('property_id', $hotel->id)
        ->where(function($q) use ($checkIn, $checkOut) {
            $q->whereBetween('check_in_date', [$checkIn, $checkOut])
              ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
              ->orWhere(function($sq) use ($checkIn, $checkOut) {
                  $sq->where('check_in_date', '<=', $checkIn)
                     ->where('check_out_date', '>=', $checkOut);
              });
        })
        ->get();

    echo "Found " . $reservations->count() . " reservations.\n";
    foreach ($reservations as $res) {
        echo "- Res ID: " . $res->id . ", Room ID: " . $res->reservable_id . ", Status: " . $res->status . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
