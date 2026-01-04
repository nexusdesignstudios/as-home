<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckRoomDetails extends Command
{
    protected $signature = 'check:room-details';
    protected $description = 'Check room details for Amazing 4 Star Hotel';

    public function handle()
    {
        $this->info('Checking room details for Amazing 4 Star Hotel...');
        
        // Get all rooms for property 357
        $rooms = DB::table('hotel_rooms')
            ->where('property_id', 357)
            ->orderBy('room_number')
            ->get();
            
        $this->info('Found ' . $rooms->count() . ' rooms:');
        
        foreach ($rooms as $room) {
            $this->info('Room ID: ' . $room->id . ', Room Number: ' . $room->room_number . ', Room Type ID: ' . $room->room_type_id);
        }
        
        // Get reservations for Jan 23-24
        $reservations = DB::table('reservations')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->get();
            
        $this->info('Found ' . $reservations->count() . ' reservations for Jan 23-24:');
        
        foreach ($reservations as $res) {
            $this->info('Reservation ID: ' . $res->id . ', Room ID: ' . $res->reservable_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        return 0;
    }
}
