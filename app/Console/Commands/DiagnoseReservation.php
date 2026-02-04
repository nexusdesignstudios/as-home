<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\HotelRoom;

class DiagnoseReservation extends Command
{
    protected $signature = 'diagnose:reservation {id?}';
    protected $description = 'Diagnose reservation data and relations';

    public function handle()
    {
        $id = $this->argument('id');
        
        if ($id) {
            $reservation = Reservation::find($id);
        } else {
            $reservation = Reservation::whereHasMorph('reservable', [HotelRoom::class])
                ->latest()
                ->first();
        }

        if (!$reservation) {
            $this->error("No reservation found.");
            return;
        }

        $this->info("Reservation ID: " . $reservation->id);
        $this->info("Reservable Type: " . $reservation->reservable_type);
        $this->info("Reservable ID: " . $reservation->reservable_id);

        // Access relation
        $hotelRoom = $reservation->reservable;
        
        if (!$hotelRoom) {
            $this->error("Reservable (HotelRoom) is NULL!");
            return;
        }

        $this->info("Hotel Room ID: " . $hotelRoom->id);
        $this->info("Custom Room Type: " . ($hotelRoom->custom_room_type ?? 'NULL'));
        $this->info("Room Type ID: " . ($hotelRoom->room_type_id ?? 'NULL'));

        // Check RoomType relation
        $roomType = $hotelRoom->roomType;
        
        if ($roomType) {
            $this->info("Room Type Relation Found:");
            $this->info("  ID: " . $roomType->id);
            $this->info("  Name: " . $roomType->name);
        } else {
            $this->warn("Room Type Relation is NULL!");
        }

        // Simulate logic
        $calculatedRoomType = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->roomType)->name ?? 'Standard Room');
        $this->info("Calculated Room Type for Email: '$calculatedRoomType'");
    }
}
