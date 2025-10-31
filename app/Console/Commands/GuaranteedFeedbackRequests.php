<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;

class GuaranteedFeedbackRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feedback:guaranteed-send {--email= : Test email address} {--force : Force send even if not checkout day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guaranteed feedback request emails sent ONLY on the checkout date of each reservation (runs daily to check for today\'s checkouts)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $testEmail = $this->option('email');
        $force = $this->option('force');
        
        $this->info('Starting guaranteed feedback request emails...');
        $this->info('NOTE: This command sends emails ONLY for reservations checking out TODAY.');
        
        if ($testEmail) {
            $this->warn("Test mode: Sending only to {$testEmail}");
        }

        try {
            $today = Carbon::today();
            $this->info("Looking for reservations with checkout date: {$today->format('Y-m-d')}");
            
            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // Method 1: Find reservations checking out TODAY (exact date match)
            $reservations = $this->getCheckoutReservations($today, $testEmail);
            
            if ($reservations->isEmpty()) {
                if ($force && $testEmail) {
                    // When force flag is used with test email, find all eligible reservations for this customer
                    $this->warn('No reservations checking out today. Looking for all eligible reservations for this customer...');
                    $reservations = $this->getAllEligibleReservations($testEmail);
                    if ($reservations->isEmpty()) {
                        $this->info('No eligible reservations found for this customer.');
                        return Command::SUCCESS;
                    } else {
                        $this->info("Found {$reservations->count()} eligible reservation(s) for this customer.");
                    }
                } elseif ($force) {
                    $this->warn('No reservations checking out today. Continuing with force flag...');
                } else {
                    $this->info('No reservations checking out today. Emails will be sent on their checkout dates.');
                    return Command::SUCCESS;
                }
            } else {
                $this->info("Found {$reservations->count()} reservation(s) checking out today.");
            }

            foreach ($reservations as $reservation) {
                try {
                    $result = $this->sendFeedbackRequest($reservation, $testEmail, $force);
                    if ($result) {
                        $sentCount++;
                        $this->info("✓ Feedback request sent for reservation {$reservation->id}");
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Reservation {$reservation->id}: " . $e->getMessage();
                    $this->error("✗ Failed reservation {$reservation->id}: " . $e->getMessage());
                }
            }

            // Method 2: Check for any missed feedback requests from previous days
            if (!$testEmail) {
                $this->processMissedFeedbackRequests($testEmail);
            }

            $this->info("Feedback request process completed!");
            $this->info("Sent: {$sentCount}, Failed: {$failedCount}");

            if (!empty($errors)) {
                $this->error("Errors encountered:");
                foreach ($errors as $error) {
                    $this->error("- {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process feedback requests: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get reservations checking out on the exact date specified
     * This ensures emails are sent ONLY on the checkout date
     */
    private function getCheckoutReservations($checkoutDate, $testEmail = null)
    {
        $query = Reservation::whereDate('check_out_date', $checkoutDate->format('Y-m-d'))
            ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->whereNull('feedback_email_sent_at')
            ->whereNull('feedback_token')
            ->whereHas('customer')
            ->with(['customer', 'reservable']);

        if ($testEmail) {
            $query->whereHas('customer', function($q) use ($testEmail) {
                $q->where('email', $testEmail);
            });
        }

        return $query->get();
    }

    /**
     * Get all eligible reservations for a customer (for force mode with test email)
     * This finds all confirmed/approved/completed reservations without feedback sent
     */
    private function getAllEligibleReservations($testEmail)
    {
        $query = Reservation::whereIn('status', ['confirmed', 'approved', 'completed'])
            ->whereNull('feedback_email_sent_at')
            ->whereNull('feedback_token')
            ->whereHas('customer', function($q) use ($testEmail) {
                $q->where('email', $testEmail);
            })
            ->whereHas('customer')
            ->with(['customer', 'reservable'])
            ->orderBy('check_out_date', 'desc');

        return $query->get();
    }

    /**
     * Send feedback request email
     */
    private function sendFeedbackRequest($reservation, $testEmail = null, $force = false)
    {
        $customer = $reservation->customer;
        if (!$customer || !$customer->email) {
            throw new \Exception("Customer or email not found");
        }

        // Use test email if provided
        $email = $testEmail ?: $customer->email;

        // Generate token
        $token = Str::random(60);

        // Determine property and form type
        $property = null;
        $formType = null;
        $propertyName = 'N/A';

        if (in_array($reservation->reservable_type, ['App\\Models\\Property', 'property'])) {
            $property = $reservation->reservable;
            if ($property) {
                $propertyName = $property->title;
                $propertyClassification = $property->getRawOriginal('property_classification');
                if ($propertyClassification == 4) {
                    $formType = 'vacation_homes';
                } elseif ($propertyClassification == 5) {
                    $formType = 'hotel_booking';
                }
            }
        } elseif (in_array($reservation->reservable_type, ['App\\Models\\HotelRoom', 'hotel_room'])) {
            $hotelRoom = $reservation->reservable;
            if ($hotelRoom && $hotelRoom->property) {
                $property = $hotelRoom->property;
                $propertyName = $property->title;
                $formType = 'hotel_booking';
            }
        }

        // Fallback: if morph relation is missing, try direct property relation on reservation
        if (!$property || !$formType) {
            $fallbackProperty = $reservation->property ?? null;
            if ($fallbackProperty) {
                $property = $fallbackProperty;
                $propertyName = $fallbackProperty->title ?? ($fallbackProperty->name ?? 'N/A');
                // Check property classification to determine form type
                $propertyClassification = $fallbackProperty->getRawOriginal('property_classification');
                if ($propertyClassification == 4) {
                    $formType = 'vacation_homes';
                } elseif ($propertyClassification == 5) {
                    $formType = 'hotel_booking';
                } elseif (Str::contains($reservation->reservable_type ?? '', 'Hotel')) {
                    // Fallback heuristic if classification not set
                    $formType = 'hotel_booking';
                } else {
                    $formType = 'vacation_homes';
                }
            }
        }

        if (!$property || !$formType) {
            throw new \Exception("Could not determine property or form type");
        }

        // Build absolute feedback link using configured public URL if available
        $baseUrl = function_exists('system_setting') ? (system_setting('web_url') ?: null) : null;
        if (empty($baseUrl)) {
            $baseUrl = 'https://ashome-eg.com';
        }
        $baseUrl = rtrim($baseUrl ?: (config('app.url') ?: ''), '/');
        // Include property_id in the URL for easier frontend access
        $propertyId = $property->id ?? null;
        $feedbackUrl = $baseUrl . "/feedback/{$token}" . ($propertyId ? "?property_id={$propertyId}" : '');

        $variables = [
            'app_name' => config('app.name'),
            'customer_name' => $customer->name,
            'user_name' => $customer->name,
            'property_name' => $propertyName,
            'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
            'reservation_id' => $reservation->id,
            'feedback_link' => $feedbackUrl,
            'form_type' => $formType === 'vacation_homes' ? 'Vacation Home' : 'Hotel',
        ];

        // Get email template
        $emailTypeData = HelperService::getEmailTemplatesTypes("feedback_request");
        $emailTemplateData = system_setting('feedback_request_mail_template');

        if (empty($emailTemplateData)) {
            $emailTemplateData = 'Dear {customer_name},

Thank you for choosing {app_name} for your recent stay!

We hope you had a wonderful experience at {property_name}. Your feedback is extremely valuable to us and helps us improve our services.

Please take a moment to share your experience by clicking on the link below to complete our feedback form:

{feedback_link}

Your feedback helps us:
- Improve our property amenities and services
- Enhance the guest experience for future visitors
- Maintain the highest quality standards

We appreciate your time and look forward to hearing from you!

Best regards,
{app_name} Team

Note: This feedback link is valid and unique to your reservation.';
        }

        $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

        // Save token to database BEFORE sending email
        // Save if not test email, OR if force mode is active (to actually save the feedback token)
        if (!$testEmail || $force) {
            $reservation->feedback_token = $token;
            $reservation->feedback_email_sent_at = Carbon::now();
            $reservation->save();
        }

        $data = [
            'email' => $email,
            'title' => $emailTypeData['title'] ?? 'Share Your Feedback - ' . config('app.name'),
            'email_template' => $emailContent
        ];

        HelperService::sendMail($data);

        return true;
    }

    /**
     * Process missed feedback requests
     * Only processes reservations that checked out in the past but should have received feedback
     */
    private function processMissedFeedbackRequests($testEmail = null)
    {
        $this->info("Checking for missed feedback requests from past checkout dates...");
        
        // Find reservations from last 7 days that checked out but didn't receive feedback
        // Note: These should have been sent on their checkout date
        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today()->subDay();
        
        $missedReservations = Reservation::whereBetween('check_out_date', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->whereNull('feedback_email_sent_at')
            ->whereNull('feedback_token')
            ->whereHas('customer')
            ->with(['customer', 'reservable'])
            ->get();

        if ($missedReservations->isEmpty()) {
            $this->info("No missed feedback requests found.");
            return;
        }

        $this->info("Found {$missedReservations->count()} missed feedback request(s) from past checkout dates.");

        foreach ($missedReservations as $reservation) {
            try {
                $checkoutDate = Carbon::parse($reservation->check_out_date);
                $this->info("Processing missed feedback for reservation {$reservation->id} (checked out: {$checkoutDate->format('Y-m-d')})");
                
                $this->sendFeedbackRequest($reservation, $testEmail);
                $this->info("✓ Sent missed feedback request for reservation {$reservation->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed missed feedback request for reservation {$reservation->id}: " . $e->getMessage());
            }
        }
    }
}
