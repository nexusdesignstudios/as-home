<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\PaymobPayment;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;

class PaymobCallbackTest extends TestCase
{
    use WithFaker;

    protected $owner;
    protected $customer;
    protected $property;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Owner
        $this->owner = Customer::firstOrCreate(
            ['email' => 'owner.callback@test.com'],
            ['isActive' => 1, 'name' => 'Owner Callback', 'mobile' => '1122334455', 'password' => bcrypt('password')]
        );

        // Create Booker
        $this->customer = Customer::firstOrCreate(
            ['email' => 'booker.callback@test.com'],
            ['isActive' => 1, 'name' => 'Booker Callback', 'mobile' => '5544332211', 'password' => bcrypt('password')]
        );

        // Create Property
        $this->property = Property::create([
            'title' => 'Callback Test Property ' . uniqid(),
            'slug_id' => 'cb-prop-' . uniqid(),
            'address' => '456 Callback Lane',
            'city' => 'Alexandria',
            'country' => 'Egypt',
            'property_classification' => 5,
            'property_type' => 1,
            'price' => 2000,
            'status' => 1,
            'added_by' => $this->owner->id,
            'instant_booking' => 0 // Non-instant, so reservation starts as pending
        ]);
    }

    public function test_non_refundable_payment_confirmation()
    {
        // 1. Create a pending reservation (simulating Non-Refundable flow start)
        $reservation = Reservation::create([
            'property_id' => $this->property->id,
            'reservable_type' => 'property',
            'reservable_id' => $this->property->id,
            'customer_id' => $this->customer->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(12)->format('Y-m-d'),
            'total_price' => 2000,
            'status' => 'pending', // Starts pending
            'payment_status' => 'unpaid',
            'payment_method' => 'online_payment',
            'booking_type' => 'reservation_request',
            'refund_policy' => 'non-refundable',
            'approval_status' => 'pending',
            'requires_approval' => true
        ]);

        $transactionId = 'RES_' . time() . '_' . $this->customer->id . '_' . uniqid();
        $paymobOrderId = 'ORDER_' . uniqid();

        // 2. Create a pending PaymobPayment record
        $payment = PaymobPayment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $this->customer->id,
            'amount' => 2000,
            'currency' => 'EGP',
            'status' => 'pending',
            'transaction_id' => $transactionId,
            'paymob_order_id' => $paymobOrderId
        ]);

        // 3. Simulate Paymob Callback (Success)
        $callbackData = [
            'type' => 'TRANSACTION',
            'obj' => [
                'id' => 123456,
                'pending' => false,
                'success' => true,
                'is_void' => false,
                'is_refund' => false,
                'order' => [
                    'id' => $paymobOrderId,
                    'merchant_order_id' => $transactionId
                ],
                'data' => [
                    'message' => 'Approved'
                ]
            ],
            'hmac' => 'dummy_hmac' // We might need to mock validation if enabled
        ];

        Log::info('Simulating Paymob Callback for Reservation ID: ' . $reservation->id);

        $response = $this->postJson('/api/payments/paymob/callback', $callbackData);

        // 4. Assertions
        $response->assertStatus(200);

        // Refresh models
        $reservation->refresh();
        $payment->refresh();

        // Verify Payment Status
        $this->assertEquals('succeed', $payment->status, 'Payment status should be succeed');

        // Verify Reservation Status (Should be confirmed after payment)
        $this->assertEquals('confirmed', $reservation->status, 'Reservation status should be confirmed');
        $this->assertEquals('paid', $reservation->payment_status, 'Reservation payment status should be paid');
    }
}
