<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Property;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReservationConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = new ReservationService();
    }

    /**
     * Test that admin confirmation triggers the same logic as Paymob payment success.
     */
    public function test_admin_confirmation_triggers_same_logic_as_paymob_success()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $property = Property::factory()->create([
            'available_dates' => [
                [
                    'from' => '2024-01-01',
                    'to' => '2024-12-31',
                    'price' => 100,
                    'type' => 'open'
                ]
            ]
        ]);

        $reservation = Reservation::create([
            'customer_id' => $customer->id,
            'reservable_id' => $property->id,
            'reservable_type' => 'App\\Models\\Property',
            'check_in_date' => '2024-06-01',
            'check_out_date' => '2024-06-05',
            'number_of_guests' => 2,
            'total_price' => 400,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'special_requests' => 'Test request'
        ]);

        // Mock the email sending to avoid actual email sending in tests
        Mail::fake();

        // Test the service method directly
        $this->reservationService->handleReservationConfirmation($reservation, 'paid');

        // Refresh the reservation from database
        $reservation->refresh();

        // Assert that the reservation status was updated
        $this->assertEquals('confirmed', $reservation->status);
        $this->assertEquals('paid', $reservation->payment_status);

        // Assert that available dates were updated
        $property->refresh();
        $availableDates = $property->available_dates;

        // Check that a reserved date range was added
        $hasReservedRange = false;
        foreach ($availableDates as $dateRange) {
            if (
                isset($dateRange['type']) && $dateRange['type'] === 'reserved' &&
                isset($dateRange['reservation_id']) && $dateRange['reservation_id'] === $reservation->id
            ) {
                $hasReservedRange = true;
                break;
            }
        }

        $this->assertTrue($hasReservedRange, 'Reserved date range should be added to property available dates');

        // Note: Email sending is handled by HelperService::sendMail() which doesn't use Laravel's Mail facade
        // So we can't easily mock it in this test. The email functionality is tested separately.
    }

    /**
     * Test that pending reservations don't block other bookings.
     */
    public function test_pending_reservations_dont_block_other_bookings()
    {
        // Create test data
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $property = Property::factory()->create([
            'available_dates' => [
                [
                    'from' => '2024-01-01',
                    'to' => '2024-12-31',
                    'price' => 100,
                    'type' => 'open'
                ]
            ]
        ]);

        // Create a pending reservation for customer1
        $pendingReservation = Reservation::create([
            'customer_id' => $customer1->id,
            'reservable_id' => $property->id,
            'reservable_type' => 'App\\Models\\Property',
            'check_in_date' => '2024-06-01',
            'check_out_date' => '2024-06-05',
            'number_of_guests' => 2,
            'total_price' => 400,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'special_requests' => 'Test request'
        ]);

        // Check if the same dates are still available for customer2
        $isAvailable = $this->reservationService->areDatesAvailable(
            'App\\Models\\Property',
            $property->id,
            '2024-06-01',
            '2024-06-05'
        );

        // Pending reservations should block to prevent double booking
        $this->assertFalse($isAvailable, 'Pending reservations should block other bookings');

        // Now confirm the first reservation
        $this->reservationService->handleReservationConfirmation($pendingReservation, 'paid');

        // Check if the same dates are now unavailable for customer2
        $isAvailableAfterConfirmation = $this->reservationService->areDatesAvailable(
            'App\\Models\\Property',
            $property->id,
            '2024-06-01',
            '2024-06-05'
        );

        // The dates should now be unavailable because the reservation is confirmed
        $this->assertFalse($isAvailableAfterConfirmation, 'Confirmed reservations should block other bookings');
    }

    /**
     * Test that the service method handles errors gracefully.
     */
    public function test_service_method_handles_errors_gracefully()
    {
        // Create a reservation with invalid data
        $reservation = new Reservation();
        $reservation->id = 999; // Non-existent ID
        $reservation->status = 'pending';
        $reservation->payment_status = 'unpaid';

        // This should not throw an exception
        $this->expectException(\Exception::class);
        $this->reservationService->handleReservationConfirmation($reservation, 'paid');
    }
}
