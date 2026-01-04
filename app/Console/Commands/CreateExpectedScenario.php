<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateExpectedScenario extends Command
{
    protected $signature = 'create:expected-scenario';
    protected $description = 'Create expected scenario: 1 of 4 Superior available, 1 of 3 Standard available';

    public function handle()
    {
        $this->info('Creating expected scenario for Amazing 4 Star Hotel...');
        
        // Expected: 1 of 4 Superior available, 1 of 3 Standard available
        // This means: 3 Superior blocked, 2 Standard blocked
        
        // Keep these reservations (blocked):
        // Superior: 763, 764, 765 (3 blocked)
        // Standard: 767, 768 (2 blocked)
        
        // Remove these reservations (make available):
        // Superior: 766 (make available)
        // Standard: 769 (make available)
        
        $this->info('Removing reservation for room 766 (Superior) to make it available...');
        DB::table('reservations')
            ->where('reservable_id', 766)
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->delete();
            
        $this->info('Removing reservation for room 769 (Standard) to make it available...');
        DB::table('reservations')
            ->where('reservable_id', 769)
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->delete();
        
        // Verify the result
        $reservations = DB::table('reservations')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->get();
            
        $this->info('Final result: ' . $reservations->count() . ' reservations for Jan 23-24');
        
        foreach ($reservations as $res) {
            $this->info('Reservation ID: ' . $res->id . ', Room ID: ' . $res->reservable_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        $this->info('Expected scenario created:');
        $this->info('- Superior Rooms: 3 blocked (763, 764, 765), 1 available (766)');
        $this->info('- Standard Rooms: 2 blocked (767, 768), 1 available (769)');
        
        return 0;
    }
}
