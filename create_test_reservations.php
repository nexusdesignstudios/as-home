<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Models\Customer;

// Create the missing reservations for testing
try {
    $customer = Customer::first();
    $propertyId = 357;
    
    echo "Creating test reservations for Amazing 4 Star Hotel...\n";
    
    // Reservation 942 - Standard Room, Manual Payment
    $room767 = HotelRoom::where('property_id', $propertyId)->where('room_number', '767')->first();
    if ($room767) {
        $reservation942 = Reservation::create([
            'customer_id' => $customer->id,
            'property_id' => $propertyId,
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
            'updated_at' => now(),
        ]);
        echo "Created reservation 942 for room 767\n";
    }
    
    // Reservation 945 - Superior Room, Manual Payment  
    $room763_2 = HotelRoom::where('property_id', $propertyId)->where('room_number', '763')->where('id', '!=', $room767->id)->first();
    if ($room763_2) {
        $reservation945 = Reservation::create([
            'customer_id' => $customer->id,
            'property_id' => $propertyId,
            'reservable_id' => $room763_2->id,
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
            'updated_at' => now(),
        ]);
        echo "Created reservation 945 for room 763\n";
    }
    
    // Reservation 946 - Standard Room, Manual Payment
    $room768 = HotelRoom::where('property_id', $propertyId)->where('room_number', '768')->first();
    if ($room768) {
        $reservation946 = Reservation::create([
            'customer_id' => $customer->id,
            'property_id' => $propertyId,
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
            'updated_at' => now(),
        ]);
        echo "Created reservation 946 for room 768\n";
    }
    
    // Reservation 947 - Standard Room, Manual Payment
    $room769 = HotelRoom::where('property_id', $propertyId)->where('room_number', '769')->first();
    if ($room769) {
        $reservation947 = Reservation::create([
            'customer_id' => $customer->id,
            'property_id' => $propertyId,
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
            'updated_at' => now(),
        ]);
        echo "Created reservation 947 for room 769\n";
    }
    
    echo "Successfully created test reservations: 942, 945, 946, 947\n";
    
} catch (Exception $e) {
    echo "Error creating reservations: " . $e->getMessage() . "\n";
}
