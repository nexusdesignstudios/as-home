<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreReservation942 extends Command
{
    protected $signature = 'restore:reservation-942';
    protected $description = 'Restore missing reservation 942';

    public function handle()
    {
        $this->info('Checking for reservation 942...');
        
        $res = DB::table('reservations')->where('id', 942)->first();
        
        if ($res) {
            $this->info('Found reservation 942: Room ' . $res->reservable_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        } else {
            $this->info('Reservation 942 NOT FOUND - recreating it...');
            
            // Recreate reservation 942
            DB::table('reservations')->insert([
                'customer_id' => 14,
                'property_id' => 357,
                'reservable_id' => 767,
                'reservable_type' => 'App\\Models\\HotelRoom',
                'check_in_date' => '2026-01-23',
                'check_out_date' => '2026-01-24',
                'status' => 'confirmed',
                'payment_method' => 'cash',
                'payment_status' => 'unpaid',
                'total_price' => 1000,
                'number_of_guests' => 2,
                'transaction_id' => 'TEST_942_RESTORED',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->info('Reservation 942 recreated successfully');
        }
        
        // Verify the result
        $this->info('');
        $this->info('Verifying all reservations for Jan 23-24:');
        
        $reservations = DB::table('reservations')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->where('property_id', 357)
            ->where('reservable_type', 'App\\Models\\HotelRoom')
            ->get();
            
        $this->info('Found ' . $reservations->count() . ' reservations:');
        
        foreach ($reservations as $res) {
            $this->info('  Reservation ID: ' . $res->id . ', Room ID: ' . $res->reservable_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        return 0;
    }
}
