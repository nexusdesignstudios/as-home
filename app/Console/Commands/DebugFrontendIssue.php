<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugFrontendIssue extends Command
{
    protected $signature = 'debug:frontend-issue';
    protected $description = 'Debug frontend availability issue';

    public function handle()
    {
        $this->info('=== DEBUGGING FRONTEND AVAILABILITY ISSUE ===');
        
        // Check current backend data
        $this->info('1. BACKEND DATA CHECK:');
        
        $reservations = DB::table('reservations')
            ->where('check_in_date', '2026-01-23')
            ->where('check_out_date', '2026-01-24')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->where('property_id', 357)
            ->where('reservable_type', 'App\\Models\\HotelRoom')
            ->get();
            
        $this->info('Found ' . $reservations->count() . ' reservations for Jan 23-24:');
        
        foreach ($reservations as $res) {
            $this->info('  Reservation ID: ' . $res->id . ', Room ID: ' . $res->reservable_id . ', Status: ' . $res->status . ', Payment: ' . $res->payment_method);
        }
        
        // Check room details
        $this->info('');
        $this->info('2. ROOM DETAILS:');
        
        $rooms = DB::table('hotel_rooms')
            ->where('property_id', 357)
            ->orderBy('room_type_id')
            ->orderBy('id')
            ->get();
            
        $this->info('Found ' . $rooms->count() . ' rooms:');
        
        foreach ($rooms as $room) {
            $reservationsForRoom = $reservations->where('reservable_id', $room->id);
            $this->info('  Room ID: ' . $room->id . ' (Type ' . $room->room_type_id . ') - ' . $reservationsForRoom->count() . ' reservations');
        }
        
        // Expected vs Actual
        $this->info('');
        $this->info('3. EXPECTED BEHAVIOR:');
        $this->info('  Superior Rooms (Type 5): 4 total, 3 blocked, 1 available');
        $this->info('  Standard Rooms (Type 1): 3 total, 2 blocked, 1 available');
        $this->info('  Total Available: 2 rooms');
        
        $this->info('');
        $this->info('4. FRONTEND DEBUGGING CHECKLIST:');
        $this->info('  ✅ Check if HotelBookingCard loads reservations');
        $this->info('  ✅ Check if reservations are filtered correctly');
        $this->info('  ✅ Check if room matching logic works');
        $this->info('  ✅ Check if date overlap logic works');
        $this->info('  ✅ Check if blocking logic works');
        
        $this->info('');
        $this->info('5. CONSOLE LOGS TO LOOK FOR:');
        $this->info('  🔍 HotelBookingCard - Filtered reservations: {totalFiltered: X, ...}');
        $this->info('  🔍 HotelBookingCard - Jan 23-24 reservations: {count: X, ...}');
        $this->info('  🔍 Room X - Checking availability with X reservations');
        $this->info('  🎯 TARGET RESERVATIONS FOUND: [...]');
        
        return 0;
    }
}
