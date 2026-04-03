<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestFeedbackRequestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:feedback-request-email 
                            {reservation : The reservation ID to send test email for}
                            {--email= : Override the customer email address (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test feedback request email for a specific reservation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reservationId = $this->argument('reservation');
        $overrideEmail = $this->option('email');

        $this->info("Sending test feedback request email for reservation ID: {$reservationId}");

        try {
            // Find the reservation
            $reservation = Reservation::with(['customer', 'reservable'])->find($reservationId);

            if (!$reservation) {
                $this->error("Reservation with ID {$reservationId} not found.");
                return Command::FAILURE;
            }

            $customer = $reservation->customer;
            if (!$customer) {
                $this->error("Customer not found for reservation {$reservationId}.");
                return Command::FAILURE;
            }

            // Determine email to send to
            $emailTo = $overrideEmail ?: $customer->email;
            
            if (!$emailTo) {
                $this->error("No email address found. Provide one using --email option.");
                return Command::FAILURE;
            }

            // Generate unique token for this reservation
            $token = Str::random(60);
            
            // Determine property classification and form type
            $propertyClassification = null;
            $formType = null;
            
            if ($reservation->reservable_type === 'App\\Models\\Property' || $reservation->reservable_type === 'property') {
                $property = $reservation->reservable;
                if ($property) {
                    $propertyClassification = $property->getRawOriginal('property_classification');
                    if ($propertyClassification == 4) {
                        $formType = 'vacation_homes';
                    }
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room') {
                $hotelRoom = $reservation->reservable;
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyClassification = $hotelRoom->property->getRawOriginal('property_classification');
                    if ($propertyClassification == 5) {
                        $formType = 'hotel_booking';
                    }
                }
            }

            if (!$formType || !in_array($propertyClassification, [4, 5])) {
                $this->warn("Reservation {$reservationId} is not a vacation home (4) or hotel booking (5).");
                $this->warn("Property classification: " . ($propertyClassification ?? 'Unknown'));
                $this->warn("This may still work, but proceed with caution.");
                
                // Ask for confirmation
                if (!$this->confirm('Do you want to proceed anyway?', false)) {
                    $this->info('Test email cancelled.');
                    return Command::SUCCESS;
                }
            }

            $propertyName = '';
            $propertyId = '';
            
            if ($reservation->reservable_type === 'App\\Models\\Property' || $reservation->reservable_type === 'property') {
                $propertyName = $reservation->reservable->title ?? 'N/A';
                $propertyId = $reservation->reservable_id;
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room') {
                $propertyName = $reservation->reservable->property->title ?? 'N/A';
                $propertyId = $reservation->reservable->property->id ?? '';
            }

            // Generate feedback form URL
            $baseUrl = function_exists('system_setting') ? (system_setting('web_url') ?: null) : null;
            if (empty($baseUrl)) {
                $baseUrl = 'https://ashome-eg.com';
            }
            $baseUrl = rtrim($baseUrl ?: (config('app.url') ?: 'https://ashome-eg.com'), '/');
            $feedbackUrl = "{$baseUrl}/feedback/{$token}" . ($propertyId ? "?property_id={$propertyId}" : '');

            // Prepare email variables
            $appName = env("APP_NAME") ?? "As-home";

            $variables = [
                'app_name' => $appName,
                'user_name' => $customer->name,
                'customer_name' => $customer->name,
                'property_name' => $propertyName,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'reservation_id' => $reservation->id,
                'feedback_link' => $feedbackUrl,
                'form_type' => $formType === 'vacation_homes' ? 'Vacation Home' : ($formType === 'hotel_booking' ? 'Hotel' : 'Property'),
            ];

            // Get or create email template
            $emailTypeData = HelperService::getEmailTemplatesTypes("feedback_request");
            $emailTemplateData = system_setting('feedback_request_mail_template');

            // Default template if none exists
            if (empty($emailTemplateData)) {
                $emailTemplateData = '<p>Dear <strong>{customer_name}</strong>,</p>
<p>Thank you for choosing <strong>{app_name}</strong> for your recent stay!</p>
<p>We hope you had a wonderful experience at <strong>{property_name}</strong>. Your feedback is extremely valuable to us and helps us improve our services.</p>
<p>Please take a moment to share your experience by clicking on the link below to complete our feedback form:</p>
<p><a href="{feedback_link}">{feedback_link}</a></p>
<p>Your feedback helps us:</p>
<ul>
<li>Improve our property amenities and services</li>
<li>Enhance the guest experience for future visitors</li>
<li>Maintain the highest quality standards</li>
</ul>
<p>We appreciate your time and look forward to hearing from you!</p>
<p>Best regards,<br>
The <strong>{app_name}</strong> Team</p>
<p><em>Note: This feedback link is valid and unique to your reservation.</em></p>';
            }

            // Replace variables in template
            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email with TEST prefix in title
            $data = [
                'email' => $emailTo,
                'title' => '[TEST] ' . ($emailTypeData['title'] ?? 'Share Your Feedback - ' . $appName),
                'email_template' => $emailContent
            ];

            $this->info("Attempting to send email...");
            $this->info("Email configuration:");
            $this->info("  MAIL_MAILER: " . env('MAIL_MAILER', 'not set'));
            $this->info("  MAIL_HOST: " . env('MAIL_HOST', 'not set'));
            $this->info("  MAIL_PORT: " . env('MAIL_PORT', 'not set'));
            $this->info("  MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS', 'not set'));
            
            try {
                HelperService::sendMail($data, true); // Use requiredEmailException=true to see errors
                $this->info("✓ Email sent successfully!");
            } catch (\Exception $e) {
                $this->error("✗ Email sending failed: " . $e->getMessage());
                throw $e; // Re-throw to show full error
            }

            $this->info("✓ Test feedback email sent successfully to: {$emailTo}");
            $this->info("  Reservation ID: {$reservation->id}");
            $this->info("  Customer: {$customer->name}");
            $this->info("  Property: {$propertyName}");
            $this->info("  Feedback URL: {$feedbackUrl}");
            
            // Note: We don't update the reservation with token during testing
            // to avoid interfering with actual feedback tracking
            $this->warn("\nNote: The reservation was NOT updated with feedback_token (this is a test).");
            $this->warn("The feedback link will work, but it won't be tracked in the reservation.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to send test email: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            Log::error('Failed to send test feedback request email', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
