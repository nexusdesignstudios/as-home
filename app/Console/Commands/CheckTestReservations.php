<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTestReservations extends Command
{
    protected $signature = 'check:test-reservations';
    protected $description = 'Check test reservations for Amazing 4 Star Hotel';

    public function handle()
    {
        $this->info('Checking test reservations...');
        
        // Check all test reservations
        $testReservations = DB::table('reservations')->where('transaction_id', 'like', 'TEST_%')->get();
        
        $this->info('Found ' . $testReservations->count() . ' test reservations:');
        
        foreach ($testReservations as $res) {
            $this->info('Reservation ID: ' . $res->id . ', Room: ' . $res->reservable_id . ', Transaction: ' . $res->transaction_id . ', Status: ' . $res->status);
        }
        
        // Check all reservations for Jan 23-24
        $janReservations = DB::table('reservations')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->get();
            
        $this->info('Found ' . $janReservations->count() . ' total reservations for Jan 23-24:');
        
        foreach ($janReservations as $res) {
            $this->info('Reservation ID: ' . $res->id . ', Room: ' . $res->reservable_id . ', Transaction: ' . $res->transaction_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        return 0;
    }
}
