<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Http\Controllers\ReservationsAdminController;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use App\Services\ReservationService;

class TestCancellationEmailFlow extends Command
{
    protected $signature = 'test:cancellation-email-flow';
    protected $description = 'Test cancellation email flow for both customer and owner';

    public function handle()
    {
        $this->info('Starting cancellation email flow test...');

        // Find a suitable reservation (Hotel or Property) that is confirmed/approved and has an owner
        // Simplified query to avoid polymorphic relationship issues
        $reservations = Reservation::whereIn('status', ['confirmed', 'approved', 'pending'])
            ->whereHas('customer')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();
        
        $reservation = null;
        
        foreach ($reservations as $res) {
            if ($res->reservable_type === 'App\\Models\\Property') {
                if ($res->reservable && $res->reservable->customer) {
                    $reservation = $res;
                    break;
                }
            } elseif ($res->reservable_type === 'App\\Models\\HotelRoom') {
                if ($res->reservable && $res->reservable->property && $res->reservable->property->customer) {
                    $reservation = $res;
                    break;
                }
            }
        }

        if (!$reservation) {
            $this->error('No suitable reservation found for testing.');
            return;
        }

        $this->info("Found reservation ID: {$reservation->id}");
        $this->info("Original Status: {$reservation->status}");

        // Get customer and owner
        $customer = $reservation->customer;
        $owner = null;
        if ($reservation->reservable_type === 'App\\Models\\Property') {
            $owner = $reservation->reservable->customer;
        } else {
            $owner = $reservation->reservable->property->customer;
        }

        if (!$owner) {
            $this->error('Owner not found (unexpected).');
            return;
        }

        $this->info("Customer: {$customer->name} ({$customer->email})");
        $this->info("Owner: {$owner->name} ({$owner->email})");

        // Backup original emails
        $originalCustomerEmail = $customer->email;
        $originalOwnerEmail = $owner->email;
        $originalStatus = $reservation->status;

        try {
            // Update emails to test email
            $testEmail = 'nexlancer.eg@gmail.com';
            $customer->email = $testEmail;
            $customer->save();
            
            $owner->email = $testEmail;
            $owner->save();

            $this->info("Updated emails to {$testEmail} for testing.");

            // Create controller instance
            // We need to resolve dependencies manually since we're not using the route
            $apiResponseService = app(ApiResponseService::class);
            
            // We can't easily instantiate the controller because of dependency injection in constructor if not resolved by container
            // But Laravel's app() helper can resolve it
            $controller = app(ReservationsAdminController::class);

            // Create a mock request
            $request = new Request();
            $request->merge(['status' => 'cancelled']);

            $this->info('Calling updateStatusApi...');
            
            // Call the method
            $response = $controller->updateStatusApi($request, $reservation->id);
            
            $this->info('Method called. Response status: ' . $response->getStatusCode());
            $this->info('Response content: ' . json_encode($response->getData()));

            // Verify emails were sent (we can't check mail logs easily here, but we can assume if no exception and logic flowed)
            $this->info('Check your inbox at ' . $testEmail);

        } catch (\Exception $e) {
            $this->error('Error during test: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        } finally {
            // Restore original data
            $customer->email = $originalCustomerEmail;
            $customer->save();
            
            $owner->email = $originalOwnerEmail;
            $owner->save();
            
            // Restore status if it was changed
            $reservation->status = $originalStatus;
            $reservation->save();

            $this->info('Restored original emails and reservation status.');
        }
    }
}
