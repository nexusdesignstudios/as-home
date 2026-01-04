<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestReservations extends Command
{
    protected $signature = 'create:test-reservations';
    protected $description = 'Create test reservations for Amazing 4 Star Hotel';

    public function handle()
    {
        $this->info('Creating test reservations for Amazing 4 Star Hotel...');
        
        // Get existing customer ID from reservation 944
        $existingReservation = DB::table('reservations')->where('id', 944)->first();
        $customerId = $existingReservation->customer_id;
        
        $this->info('Using customer ID: ' . $customerId);
        
        // Get room IDs
        $room767 = DB::table('hotel_rooms')->where('property_id', 357)->where('room_number', '767')->first();
        $room763 = DB::table('hotel_rooms')->where('property_id', 357)->where('room_number', '763')->first();
        $room768 = DB::table('hotel_rooms')->where('property_id', 357)->where('room_number', '768')->first();
        $room769 = DB::table('hotel_rooms')->where('property_id', 357)->where('room_number', '769')->first();
        
        // Create reservation 942
        if ($room767) {
            DB::table('reservations')->insert([
                'customer_id' => $customerId,
                'property_id' => 357,
                'reservable_id' => $room767->id,
                'reservable_type' => 'App\\Models\\HotelRoom',
                'check_in_date' => '2026-01-23',
                'check_out_date' => '2026-01-24',
                'status' => 'confirmed',
                'payment_method' => 'manual',
                'payment_status' => 'unpaid',
                'total_price' => 1000,
                'number_of_guests' => 2,
                'transaction_id' => 'TEST_942',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info('Created reservation 942 for room 767');
        }
        
        // Create reservation 945
        if ($room763) {
            DB::table('reservations')->insert([
                'customer_id' => $customerId,
                'property_id' => 357,
                'reservable_id' => $room763->id,
                'reservable_type' => 'App\\Models\\HotelRoom',
                'check_in_date' => '2026-01-23',
                'check_out_date' => '2026-01-24',
                'status' => 'confirmed',
                'payment_method' => 'manual',
                'payment_status' => 'unpaid',
                'total_price' => 1500,
                'number_of_guests' => 2,
                'transaction_id' => 'TEST_945',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info('Created reservation 945 for room 763');
        }
        
        // Create reservation 946
        if ($room768) {
            DB::table('reservations')->insert([
                'customer_id' => $customerId,
                'property_id' => 357,
                'reservable_id' => $room768->id,
                'reservable_type' => 'App\\Models\\HotelRoom',
                'check_in_date' => '2026-01-23',
                'check_out_date' => '2026-01-24',
                'status' => 'confirmed',
                'payment_method' => 'manual',
                'payment_status' => 'unpaid',
                'total_price' => 800,
                'number_of_guests' => 1,
                'transaction_id' => 'TEST_946',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info('Created reservation 946 for room 768');
        }
        
        // Create reservation 947
        if ($room769) {
            DB::table('reservations')->insert([
                'customer_id' => $customerId,
                'property_id' => 357,
                'reservable_id' => $room769->id,
                'reservable_type' => 'App\\Models\\HotelRoom',
                'check_in_date' => '2026-01-23',
                'check_out_date' => '2026-01-24',
                'status' => 'confirmed',
                'payment_method' => 'manual',
                'payment_status' => 'unpaid',
                'total_price' => 800,
                'number_of_guests' => 1,
                'transaction_id' => 'TEST_947',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info('Created reservation 947 for room 769');
        }
        
        $this->info('Successfully created test reservations: 942, 945, 946, 947');
        
        return 0;
    }
}
