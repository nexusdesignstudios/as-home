<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;

class GuaranteedCheckoutReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkout:guaranteed-reminders {--email= : Test email address} {--force : Force send even if not checkout day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guaranteed checkout reminder emails with multiple fallback methods';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $testEmail = $this->option('email');
        $force = $this->option('force');
        
        $this->info('Starting guaranteed checkout reminder emails...');
        
        if ($testEmail) {
            $this->warn("Test mode: Sending only to {$testEmail}");
        }

        try {
            $today = Carbon::today();
            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // Method 1: Find reservations checking out today
            $reservations = $this->getCheckoutReservations($today, $testEmail);
            
            if ($reservations->isEmpty() && !$force) {
                $this->info('No reservations checking out today.');
                return Command::SUCCESS;
            }

            foreach ($reservations as $reservation) {
                try {
                    $result = $this->sendCheckoutReminder($reservation, $testEmail);
                    if ($result) {
                        $sentCount++;
                        $this->info("✓ Checkout reminder sent for reservation {$reservation->id}");
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Reservation {$reservation->id}: " . $e->getMessage();
                    $this->error("✗ Failed reservation {$reservation->id}: " . $e->getMessage());
                }
            }

            // Method 2: Check for any missed checkout reminders from previous days
            if (!$testEmail) {
                $this->processMissedCheckoutReminders($testEmail);
            }

            $this->info("Checkout reminder process completed!");
            $this->info("Sent: {$sentCount}, Failed: {$failedCount}");

            if (!empty($errors)) {
                $this->error("Errors encountered:");
                foreach ($errors as $error) {
                    $this->error("- {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process checkout reminders: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get reservations checking out today
     */
    private function getCheckoutReservations($today, $testEmail = null)
    {
        $query = Reservation::whereDate('check_out_date', $today)
            ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->with(['customer', 'reservable']);

        if ($testEmail) {
            $query->whereHas('customer', function($q) use ($testEmail) {
                $q->where('email', $testEmail);
            });
        }

        return $query->get();
    }

    /**
     * Send checkout reminder email
     */
    private function sendCheckoutReminder($reservation, $testEmail = null)
    {
        $customer = $reservation->customer;
        if (!$customer || !$customer->email) {
            throw new \Exception("Customer or email not found");
        }

        // Use test email if provided
        $email = $testEmail ?: $customer->email;

        // Determine property details
        $property = null;
        $propertyName = 'N/A';
        $propertyAddress = 'N/A';

        if ($reservation->reservable_type === 'App\\Models\\Property') {
            $property = $reservation->reservable;
            if ($property) {
                $propertyName = $property->title;
                $propertyAddress = $property->address ?? 'N/A';
            }
        } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
            $hotelRoom = $reservation->reservable;
            if ($hotelRoom && $hotelRoom->property) {
                $property = $hotelRoom->property;
                $propertyName = $property->title;
                $propertyAddress = $property->address ?? 'N/A';
            }
        }

        $variables = [
            'app_name' => config('app.name'),
            'customer_name' => $customer->name,
            'property_name' => $propertyName,
            'property_address' => $propertyAddress,
            'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
            'check_out_time' => '11:00 AM', // Default checkout time
            'reservation_id' => $reservation->id,
            'number_of_guests' => $reservation->number_of_guests ?? 'N/A',
            'special_requests' => $reservation->special_requests ?? 'None',
            'contact_phone' => system_setting('contact_phone') ?? 'N/A',
            'contact_email' => system_setting('contact_email') ?? config('mail.from.address'),
        ];

        // Get email template
        $emailTypeData = HelperService::getEmailTemplatesTypes("checkout_reminder");
        $emailTemplateData = system_setting('checkout_reminder_mail_template');

        if (empty($emailTemplateData)) {
            $emailTemplateData = 'Dear {customer_name},

This is a friendly reminder that your stay at {property_name} is coming to an end.

Check-out Details:
- Property: {property_name}
- Address: {property_address}
- Check-out Date: {check_out_date}
- Check-out Time: {check_out_time}
- Reservation ID: {reservation_id}
- Number of Guests: {number_of_guests}

Please ensure you have:
✓ Packed all your belongings
✓ Checked all drawers and closets
✓ Returned any keys or access cards
✓ Left the property in good condition

If you need to extend your stay or have any questions, please contact us immediately at {contact_phone} or {contact_email}.

Thank you for choosing {app_name}!

Best regards,
{app_name} Team';
        }

        $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

        $data = [
            'email' => $email,
            'title' => $emailTypeData['title'] ?? 'Checkout Reminder - ' . config('app.name'),
            'email_template' => $emailContent
        ];

        HelperService::sendMail($data);

        return true;
    }

    /**
     * Process missed checkout reminders
     */
    private function processMissedCheckoutReminders($testEmail = null)
    {
        $this->info("Checking for missed checkout reminders...");
        
        // Find reservations from last 3 days that should have received reminders but didn't
        $startDate = Carbon::today()->subDays(3);
        $endDate = Carbon::today()->subDay();
        
        $missedReservations = Reservation::whereBetween('check_out_date', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->with(['customer', 'reservable'])
            ->get();

        $this->info("Found {$missedReservations->count()} missed checkout reminders");

        foreach ($missedReservations as $reservation) {
            try {
                $this->sendCheckoutReminder($reservation, $testEmail);
                $this->info("✓ Sent missed checkout reminder for reservation {$reservation->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed missed checkout reminder for reservation {$reservation->id}: " . $e->getMessage());
            }
        }
    }
}
