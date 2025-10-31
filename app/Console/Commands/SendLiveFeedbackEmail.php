<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Services\HelperService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SendLiveFeedbackEmail extends Command
{
    protected $signature = 'feedback:send-live 
                            {--email= : Email address to send to}
                            {--reservation-id= : Specific reservation ID}';

    protected $description = 'Send a live (non-test) feedback email and save token to database';

    public function handle()
    {
        $email = $this->option('email');
        $reservationId = $this->option('reservation-id');

        if (!$email) {
            $this->error('Please provide --email option');
            return Command::FAILURE;
        }

        // Find reservation
        if ($reservationId) {
            $reservation = Reservation::with(['customer', 'reservable'])->find($reservationId);
            if (!$reservation) {
                $this->error("Reservation {$reservationId} not found");
                return Command::FAILURE;
            }
        } else {
            // Find any confirmed hotel reservation for this customer email
            $reservation = Reservation::whereHas('customer', function($q) use ($email) {
                $q->where('email', $email);
            })
            ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->whereIn('reservable_type', ['App\\Models\\HotelRoom', 'App\\Models\\Property'])
            ->with(['customer', 'reservable'])
            ->latest('check_out_date')
            ->first();

            if (!$reservation) {
                $this->error("No eligible reservation found for {$email}");
                return Command::FAILURE;
            }
        }

        $this->info("Sending LIVE feedback email to: {$email}");
        $this->info("Reservation ID: {$reservation->id}");

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

        if (!$property || !$formType) {
            // Fallback to property relation
            $property = $reservation->property ?? null;
            if ($property) {
                $propertyName = $property->title ?? 'N/A';
                $propertyClassification = $property->getRawOriginal('property_classification');
                if ($propertyClassification == 4) {
                    $formType = 'vacation_homes';
                } elseif ($propertyClassification == 5) {
                    $formType = 'hotel_booking';
                }
            }
        }

        if (!$property || !$formType) {
            $this->error("Could not determine property or form type");
            return Command::FAILURE;
        }

        // Build feedback URL
        $baseUrl = function_exists('system_setting') ? (system_setting('web_url') ?: null) : null;
        if (empty($baseUrl)) {
            $baseUrl = 'https://ashome-eg.com';
        }
        $baseUrl = rtrim($baseUrl ?: (config('app.url') ?: ''), '/');
        $propertyId = $property->id ?? null;
        $feedbackUrl = $baseUrl . "/feedback/{$token}" . ($propertyId ? "?property_id={$propertyId}" : '');

        $variables = [
            'app_name' => config('app.name'),
            'customer_name' => $reservation->customer->name ?? 'Guest',
            'user_name' => $reservation->customer->name ?? 'Guest',
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

        // Save token to database BEFORE sending email (LIVE - not test)
        $reservation->feedback_token = $token;
        $reservation->feedback_email_sent_at = Carbon::now();
        $reservation->save();

        $this->info("✓ Token saved to database");

        // Send email (LIVE - not test)
        $data = [
            'email' => $email,
            'title' => $emailTypeData['title'] ?? 'Share Your Feedback - ' . config('app.name'),
            'email_template' => $emailContent
        ];

        try {
            HelperService::sendMail($data);
            $this->info("✓ LIVE feedback email sent successfully!");
            $this->info("  - Property: {$propertyName}");
            $this->info("  - Form Type: {$formType}");
            $this->info("  - Feedback URL: {$feedbackUrl}");
            $this->info("  - Token saved to reservation #{$reservation->id}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to send email: " . $e->getMessage());
            // Rollback token save on error
            $reservation->feedback_token = null;
            $reservation->feedback_email_sent_at = null;
            $reservation->save();
            return Command::FAILURE;
        }
    }
}

