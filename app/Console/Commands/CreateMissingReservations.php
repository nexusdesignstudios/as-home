<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMissingReservations extends Command
{
    protected $signature = 'create:missing-reservations';
    protected $description = 'Create missing reservations for Amazing 4 Star Hotel';

    public function handle()
    {
        $this->info('Creating missing reservations for Amazing 4 Star Hotel...');
        
        // Get existing customer ID from reservation 944
        $existingReservation = DB::table('reservations')->where('id', 944)->first();
        $customerId = $existingReservation->customer_id;
        
        $this->info('Using customer ID: ' . $customerId);
        
        // Create reservation for room 764 (Superior Room)
        DB::table('reservations')->insert([
            'customer_id' => $customerId,
            'property_id' => 357,
            'reservable_id' => 764,
            'reservable_type' => 'App\\Models\\HotelRoom',
            'check_in_date' => '2026-01-23',
            'check_out_date' => '2026-01-24',
            'status' => 'confirmed',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
            'total_price' => 1500,
            'number_of_guests' => 2,
            'transaction_id' => 'TEST_764',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->info('Created reservation for room 764');
        
        // Create reservation for room 765 (Superior Room)
        DB::table('reservations')->insert([
            'customer_id' => $customerId,
            'property_id' => 357,
            'reservable_id' => 765,
            'reservable_type' => 'App\\Models\\HotelRoom',
            'check_in_date' => '2026-01-23',
            'check_out_date' => '2026-01-24',
            'status' => 'confirmed',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
            'total_price' => 1500,
            'number_of_guests' => 2,
            'transaction_id' => 'TEST_765',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->info('Created reservation for room 765');
        
        // Create reservation for room 766 (Superior Room)
        DB::table('reservations')->insert([
            'customer_id' => $customerId,
            'property_id' => 357,
            'reservable_id' => 766,
            'reservable_type' => 'App\\Models\\HotelRoom',
            'check_in_date' => '2026-01-23',
            'check_out_date' => '2026-01-24',
            'status' => 'confirmed',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
            'total_price' => 1500,
            'number_of_guests' => 2,
            'transaction_id' => 'TEST_766',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->info('Created reservation for room 766');
        
        // Create reservation for room 768 (Standard Room)
        DB::table('reservations')->insert([
            'customer_id' => $customerId,
            'property_id' => 357,
            'reservable_id' => 768,
            'reservable_type' => 'App\\Models\\HotelRoom',
            'check_in_date' => '2026-01-23',
            'check_out_date' => '2026-01-24',
            'status' => 'confirmed',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
            'total_price' => 800,
            'number_of_guests' => 1,
            'transaction_id' => 'TEST_768',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->info('Created reservation for room 768');
        
        // Create reservation for room 769 (Standard Room)
        DB::table('reservations')->insert([
            'customer_id' => $customerId,
            'property_id' => 357,
            'reservable_id' => 769,
            'reservable_type' => 'App\\Models\\HotelRoom',
            'check_in_date' => '2026-01-23',
            'check_out_date' => '2026-01-24',
            'status' => 'confirmed',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
            'total_price' => 800,
            'number_of_guests' => 1,
            'transaction_id' => 'TEST_769',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->info('Created reservation for room 769');
        
        $this->info('Successfully created missing reservations for rooms 764, 765, 766, 768, 769');
        
        return 0;
    }
}
