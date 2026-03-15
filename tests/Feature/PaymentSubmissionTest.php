<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;

class PaymentSubmissionTest extends TestCase
{
    use WithFaker;

    /**
     * Test payment form submission with specific email.
     *
     * @return void
     */
    public function test_payment_form_submission_with_specific_email()
    {
        // 1. Setup Data
        $targetEmail = "nexlancer.eg@gmail.com";
        
        // Find or create customer (the booker)
        $customer = Customer::firstOrCreate(
            ['email' => $targetEmail],
            [
                'name' => 'Test User',
                'mobile' => '01000000000',
                'password' => bcrypt('password'),
                'status' => 1
            ]
        );

        // Find or create a property owner (MUST be a Customer for API validation)
        $ownerEmail = "owner.test@example.com";
        $ownerCustomer = Customer::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => 'Test Owner',
                'mobile' => '01200000000',
                'password' => bcrypt('password'),
                'status' => 1
            ]
        );

        // Also create a User record for the owner (since property links to User via added_by)
        $ownerUser = User::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => 'Test Owner',
                'password' => bcrypt('password'),
                'status' => 1
            ]
        );

        // Create a test property
        $property = Property::create([
            'title' => 'Test Property ' . uniqid(),
            'slug_id' => 'test-property-' . uniqid(),
            'address' => '123 Test St',
            'city' => 'Cairo',
            'country' => 'Egypt',
            'property_classification' => 4, // Vacation Home
            'property_type' => 1,
            'price' => 1000,
            'status' => 1,
            'added_by' => $ownerCustomer->id // Property::customer() relationship maps id -> added_by
        ]);
        
        // Manually link the property to the Customer owner if needed by the app logic
        // But the API validation 'property_owner_id' => 'nullable|exists:customers,id'
        // expects us to pass a Customer ID.

        // Prepare Request Data
        $checkInDate = now()->addDays(5)->format('Y-m-d');
        $checkOutDate = now()->addDays(7)->format('Y-m-d');
        
        $requestData = [
            'property_id' => $property->id,
            'property_owner_id' => $ownerCustomer->id, // Pass the CUSTOMER ID here
            'customer_id' => $customer->id,
            'property_title' => $property->title,
            'property_address' => $property->address,
            'property_city' => $property->city ?? 'Cairo',
            'property_country' => $property->country ?? 'Egypt',
            'property_classification' => $property->property_classification,
            'property_type' => $property->property_type,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->mobile,
            'customer_email' => $customer->email,
            'payment_method' => 'pay_at_property', 
            'amount' => 1000,
            'discount_percentage' => 0,
            'original_amount' => 1000,
            'currency' => 'EGP',
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'number_of_guests' => 2,
            'number_of_rooms' => 1,
            'special_requests' => 'Test booking from automated test',
            'reservable_type' => 'property', 
            'review_url' => 'http://localhost/review',
            'approval_status' => 'pending',
            'requires_approval' => true,
            'booking_type' => 'reservation_request',
            'created_at' => now()->toISOString(),
            'is_flexible_booking' => false,
            // Add dummy card data to pass validation for non-flexible bookings
            'card_number' => '1234567812345678',
            'expiry_date' => '12/30',
            'cvv' => '123'
        ];

        Log::info('Starting Payment Submission Test for ' . $targetEmail);

        // 2. Execute Request
        $response = $this->postJson('/api/submit-payment-form', $requestData);

        // 3. Log Result
        if ($response->status() !== 200) {
            Log::error('Test Failed: ' . $response->content());
        } else {
            Log::info('Test Successful: ' . $response->content());
        }

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data' => ['reservation_id']]);
        
        echo "\nTest completed. Check logs for details.\n";
    }
}
