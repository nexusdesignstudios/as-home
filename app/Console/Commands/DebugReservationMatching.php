<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugReservationMatching extends Command
{
    protected $signature = 'debug:reservation-matching';
    protected $description = 'Debug reservation matching issues';

    public function handle()
    {
        $this->info('Debugging reservation matching for Room 765...');
        
        // Check reservation 947 details
        $reservation947 = DB::table('reservations')->where('id', 947)->first();
        
        $this->info('Reservation 947 details:');
        $this->info('  ID: ' . $reservation947->id);
        $this->info('  Reservable ID: ' . $reservation947->reservable_id);
        $this->info('  Reservable Type: ' . $reservation947->reservable_type);
        $this->info('  Check-in: ' . $reservation947->check_in_date);
        $this->info('  Check-out: ' . $reservation947->check_out_date);
        $this->info('  Status: ' . $reservation947->status);
        $this->info('  Payment Method: ' . $reservation947->payment_method);
        $this->info('  Payment Status: ' . $reservation947->payment_status);
        
        // Check room 765 details
        $room765 = DB::table('hotel_rooms')->where('id', 765)->first();
        
        $this->info('Room 765 details:');
        $this->info('  ID: ' . $room765->id);
        $this->info('  Room Number: ' . $room765->room_number);
        $this->info('  Room Type ID: ' . $room765->room_type_id);
        $this->info('  Property ID: ' . $room765->property_id);
        
        // Check if they match
        $this->info('Matching check:');
        $this->info('  Reservation reservable_id: ' . $reservation947->reservable_id);
        $this->info('  Room ID: ' . $room765->id);
        $this->info('  Do they match? ' . ($reservation947->reservable_id == $room765->id ? 'YES' : 'NO'));
        
        // Check all reservations for Room 765
        $room765Reservations = DB::table('reservations')
            ->where('reservable_id', 765)
            ->where('reservable_type', 'App\\Models\\HotelRoom')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->get();
            
        $this->info('Found ' . $room765Reservations->count() . ' reservations for Room 765 on Jan 23-24:');
        
        foreach ($room765Reservations as $res) {
            $this->info('  Reservation ID: ' . $res->id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        return 0;
    }
}
