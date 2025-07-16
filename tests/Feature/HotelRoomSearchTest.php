<?php

namespace Tests\Feature;

use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class HotelRoomSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test searching for available rooms with available_days availability type
     *
     * @return void
     */
    public function testSearchAvailableRoomsWithAvailableDays()
    {
        // Create a room type
        $roomType = HotelRoomType::create([
            'name' => 'Test Room Type',
            'description' => 'Test Description',
            'status' => true
        ]);

        // Create a property
        $property = Property::create([
            'title' => 'Test Property',
            'status' => 1
        ]);

        // Create a room with availability_type = 1 (available_days)
        $room = HotelRoom::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => 'A101',
            'price_per_night' => 100.00,
            'discount_percentage' => 0,
            'status' => true,
            'availability_type' => 1, // available_days
            'available_dates' => [
                [
                    'from' => '2023-01-01',
                    'to' => '2023-01-05'
                ],
                [
                    'from' => '2023-01-10',
                    'to' => '2023-01-15'
                ]
            ]
        ]);

        // Test with dates that are within an available range
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-01&to_date=2023-01-03');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(1, 'data');

        // Test with dates that span across unavailable days
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-04&to_date=2023-01-12');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(0, 'data');

        // Test with dates that are completely outside available ranges
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-20&to_date=2023-01-25');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test searching for available rooms with busy_days availability type
     *
     * @return void
     */
    public function testSearchAvailableRoomsWithBusyDays()
    {
        // Create a room type
        $roomType = HotelRoomType::create([
            'name' => 'Test Room Type',
            'description' => 'Test Description',
            'status' => true
        ]);

        // Create a property
        $property = Property::create([
            'title' => 'Test Property',
            'status' => 1
        ]);

        // Create a room with availability_type = 2 (busy_days)
        $room = HotelRoom::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => 'B101',
            'price_per_night' => 150.00,
            'discount_percentage' => 0,
            'status' => true,
            'availability_type' => 2, // busy_days
            'available_dates' => [
                [
                    'from' => '2023-01-10',
                    'to' => '2023-01-15'
                ],
                [
                    'from' => '2023-01-20',
                    'to' => '2023-01-25'
                ]
            ]
        ]);

        // Test with dates that don't overlap with busy days
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-01&to_date=2023-01-05');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(1, 'data');

        // Test with dates that overlap with busy days
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-09&to_date=2023-01-12');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(0, 'data');

        // Test with dates that are completely within busy days
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-01-12&to_date=2023-01-14');
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test searching for available rooms with room type filter
     *
     * @return void
     */
    public function testSearchAvailableRoomsWithRoomTypeFilter()
    {
        // Create room types
        $roomType1 = HotelRoomType::create([
            'name' => 'Standard Room',
            'description' => 'Standard Room Description',
            'status' => true
        ]);

        $roomType2 = HotelRoomType::create([
            'name' => 'Deluxe Room',
            'description' => 'Deluxe Room Description',
            'status' => true
        ]);

        // Create a property
        $property = Property::create([
            'title' => 'Test Property',
            'status' => 1
        ]);

        // Create rooms with different types
        $standardRoom = HotelRoom::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType1->id,
            'room_number' => 'S101',
            'price_per_night' => 100.00,
            'discount_percentage' => 0,
            'status' => true,
            'availability_type' => 1, // available_days
            'available_dates' => [
                [
                    'from' => '2023-02-01',
                    'to' => '2023-02-10'
                ]
            ]
        ]);

        $deluxeRoom = HotelRoom::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType2->id,
            'room_number' => 'D101',
            'price_per_night' => 200.00,
            'discount_percentage' => 0,
            'status' => true,
            'availability_type' => 1, // available_days
            'available_dates' => [
                [
                    'from' => '2023-02-01',
                    'to' => '2023-02-10'
                ]
            ]
        ]);

        // Test with room type filter
        $response = $this->getJson('/api/search-available-rooms?from_date=2023-02-01&to_date=2023-02-03&room_type_id=' . $roomType1->id);
        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Available rooms fetched successfully',
            ])
            ->assertJsonCount(1, 'data');

        // Verify the room type is correct
        $this->assertEquals($roomType1->id, $response->json('data.0.room_type_id'));
    }
}
