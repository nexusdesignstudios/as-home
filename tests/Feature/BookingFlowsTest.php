<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;

class BookingFlowsTest extends TestCase
{
    use WithFaker;

    protected $owner;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Owner
        $this->owner = Customer::firstOrCreate(
            ['email' => 'owner.flow@test.com'],
            ['isActive' => 1, 'name' => 'Owner', 'mobile' => '1234567890', 'password' => bcrypt('password')]
        );
        // Create User for Owner (needed for some logic potentially)
        User::firstOrCreate(
            ['email' => 'owner.flow@test.com'],
            ['status' => 1, 'name' => 'Owner', 'password' => bcrypt('password')]
        );

        // Create Booker
        $this->customer = Customer::firstOrCreate(
            ['email' => 'booker.flow@test.com'],
            ['isActive' => 1, 'name' => 'Booker', 'mobile' => '0987654321', 'password' => bcrypt('password')]
        );
    }

    private function createProperty($isInstant)
    {
        $data = [
            'title' => 'Test Property ' . ($isInstant ? 'Instant' : 'Request') . uniqid(),
            'slug_id' => 'test-prop-' . uniqid(),
            'address' => '123 Flow St',
            'city' => 'Cairo',
            'country' => 'Egypt',
            'property_classification' => 5, // 5 = Hotel (triggers logic)
            'property_type' => 1,
            'price' => 1000,
            'status' => 1,
            'added_by' => $this->owner->id,
            'instant_booking' => $isInstant ? 1 : 0
        ];
        return Property::create($data);
    }

    private function getPayload($property, $isFlexible)
    {
        return [
            'property_id' => $property->id,
            'property_owner_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->mobile,
            'customer_email' => $this->customer->email,
            'reservable_type' => 'property',
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(12)->format('Y-m-d'),
            'number_of_guests' => 2,
            'payment_method' => 'pay_at_property',
            'amount' => 1000,
            'booking_type' => $isFlexible ? 'flexible_booking' : 'reservation_request',
            // Dummy card data
            'card_number' => '1234567812345678',
            'expiry_date' => '12/30',
            'cvv' => '123'
        ];
    }

    public function test_flow_1_instant_and_flexible()
    {
        $property = $this->createProperty(true); // Instant
        $payload = $this->getPayload($property, true); // Flexible

        $response = $this->postJson('/api/submit-payment-form', $payload);
        
        $response->assertStatus(200);
        $reservationId = $response->json('data.reservation_id');
        $reservation = \App\Models\Reservation::find($reservationId);

        Log::info("Flow 1 (Instant + Flexible): Status = {$reservation->status}");
        // Expect Confirmed
        $this->assertEquals('confirmed', $reservation->status);
    }

    public function test_flow_2_instant_and_standard()
    {
        $property = $this->createProperty(true); // Instant
        $payload = $this->getPayload($property, false); // Standard

        $response = $this->postJson('/api/submit-payment-form', $payload);
        
        $response->assertStatus(200);
        $reservationId = $response->json('data.reservation_id');
        $reservation = \App\Models\Reservation::find($reservationId);

        Log::info("Flow 2 (Instant + Standard): Status = {$reservation->status}");
        // Expect Confirmed
        $this->assertEquals('confirmed', $reservation->status);
    }

    public function test_flow_3_non_instant_and_flexible()
    {
        $property = $this->createProperty(false); // Non-Instant
        $payload = $this->getPayload($property, true); // Flexible

        $response = $this->postJson('/api/submit-payment-form', $payload);
        
        $response->assertStatus(200);
        $reservationId = $response->json('data.reservation_id');
        $reservation = \App\Models\Reservation::find($reservationId);

        Log::info("Flow 3 (Non-Instant + Flexible): Status = {$reservation->status}");
        
        // Correctly Pending after fix
        $this->assertEquals('pending', $reservation->status); 
    }

    public function test_flow_4_non_instant_and_standard()
    {
        $property = $this->createProperty(false); // Non-Instant
        $payload = $this->getPayload($property, false); // Standard

        $response = $this->postJson('/api/submit-payment-form', $payload);
        
        $response->assertStatus(200);
        $reservationId = $response->json('data.reservation_id');
        $reservation = \App\Models\Reservation::find($reservationId);

        Log::info("Flow 4 (Non-Instant + Standard): Status = {$reservation->status}");
        // Expect Pending because the Non-Instant check sets status=pending explicitly
        $this->assertEquals('pending', $reservation->status);
    }
}
