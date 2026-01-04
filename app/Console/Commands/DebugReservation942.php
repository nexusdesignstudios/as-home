<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugReservation942 extends Command
{
    protected $signature = 'debug:reservation-942';
    protected $description = 'Debug why reservation 942 is not being counted';

    public function handle()
    {
        $this->info('=== DEBUGGING RESERVATION 942 ===');
        
        // Check reservation 942 directly
        $res942 = DB::table('reservations')->where('id', 942)->first();
        
        if ($res942) {
            $this->info('Reservation 942 details:');
            $this->info('  ID: ' . $res942->id);
            $this->info('  Property ID: ' . $res942->property_id);
            $this->info('  Reservable ID: ' . $res942->reservable_id);
            $this->info('  Reservable Type: ' . $res942->reservable_type);
            $this->info('  Check-in: ' . $res942->check_in_date);
            $this->info('  Check-out: ' . $res942->check_out_date);
            $this->info('  Status: ' . $res942->status);
            $this->info('  Payment Method: ' . $res942->payment_method);
            $this->info('  Payment Status: ' . $res942->payment_status);
            
            // Check if it matches our filter criteria
            $this->info('');
            $this->info('Filter criteria check:');
            $this->info('  check_in_date == 2026-01-23: ' . ($res942->check_in_date == '2026-01-23' ? 'YES' : 'NO'));
            $this->info('  check_out_date == 2026-01-24: ' . ($res942->check_out_date == '2026-01-24' ? 'YES' : 'NO'));
            $this->info('  status != cancelled: ' . ($res942->status != 'cancelled' ? 'YES' : 'NO'));
            $this->info('  status != rejected: ' . ($res942->status != 'rejected' ? 'YES' : 'NO'));
            $this->info('  property_id == 357: ' . ($res942->property_id == 357 ? 'YES' : 'NO'));
            $this->info('  reservable_type == App\\\\Models\\\\HotelRoom: ' . ($res942->reservable_type == 'App\\Models\\HotelRoom' ? 'YES' : 'NO'));
            
            // Test the exact query
            $this->info('');
            $this->info('Testing exact query:');
            
            $testQuery = DB::table('reservations')
                ->where('check_in_date', '2026-01-23')
                ->where('check_out_date', '2026-01-24')
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'rejected')
                ->where('property_id', 357)
                ->where('reservable_type', 'App\\Models\\HotelRoom')
                ->where('id', 942)
                ->get();
                
            $this->info('Found ' . $testQuery->count() . ' reservations with ID 942 in filtered query');
            
            // Check all reservations for room 767
            $this->info('');
            $this->info('All reservations for Room 767:');
            
            $room767Reservations = DB::table('reservations')
                ->where('reservable_id', 767)
                ->where('check_in_date', '2026-01-23')
                ->where('check_out_date', '2026-01-24')
                ->get();
                
            $this->info('Found ' . $room767Reservations->count() . ' reservations for Room 767:');
            
            foreach ($room767Reservations as $res) {
                $this->info('  Reservation ID: ' . $res->id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
            }
            
        } else {
            $this->info('Reservation 942 NOT FOUND');
        }
        
        return 0;
    }
}
