<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SendFeedbackRequestEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:send-feedback-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send feedback request emails to customers whose reservations have checkout date TODAY (sent on checkout date only)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting feedback request emails process...');

        try {
            $today = Carbon::today();
            $this->info("Looking for reservations with checkout date: {$today->format('Y-m-d')}");
            
            // Find all confirmed/approved reservations checking out TODAY (exact date match)
            // Emails are sent ONLY on the checkout date for each reservation
            $reservations = Reservation::whereDate('check_out_date', $today->format('Y-m-d'))
                ->whereIn('status', ['confirmed', 'approved', 'completed'])
                ->whereNull('feedback_email_sent_at')
                ->whereNull('feedback_token')
                ->whereHas('customer')
                ->with(['customer', 'reservable'])
                ->get();

            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($reservations as $reservation) {
                try {
                    $customer = $reservation->customer;
                    if (!$customer || !$customer->email) {
                        $this->warn("Skipping reservation {$reservation->id}: No customer email");
                        continue;
                    }

                    // Generate unique token for this reservation
                    $token = Str::random(60);
                    
                    // Determine property classification and form type
                    $propertyClassification = null;
                    $formType = null;
                    
                    if ($reservation->reservable_type === 'App\\Models\\Property') {
                        $property = $reservation->reservable;
                        if ($property) {
                            $propertyClassification = $property->getRawOriginal('property_classification');
                            if ($propertyClassification == 4) {
                                $formType = 'vacation_homes';
                            }
                        }
                    } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                        $hotelRoom = $reservation->reservable;
                        if ($hotelRoom && $hotelRoom->property) {
                            $propertyClassification = $hotelRoom->property->getRawOriginal('property_classification');
                            if ($propertyClassification == 5) {
                                $formType = 'hotel_booking';
                            }
                        }
                    }

                    // Skip if not a vacation home or hotel booking
                    if (!$formType || !in_array($propertyClassification, [4, 5])) {
                        $this->warn("Skipping reservation {$reservation->id}: Not a vacation home or hotel booking");
                        continue;
                    }

                    // Generate feedback form URL
                    $feedbackUrl = route('feedback.form', [
                        'token' => $token,
                        'reservation_id' => $reservation->id
                    ]);

                    // Prepare email variables
                    $appName = env("APP_NAME") ?? "As-home";
                    $propertyName = '';
                    
                    if ($reservation->reservable_type === 'App\\Models\\Property') {
                        $propertyName = $reservation->reservable->title ?? 'N/A';
                    } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                        $propertyName = $reservation->reservable->property->title ?? 'N/A';
                    }

                    $variables = [
                        'app_name' => $appName,
                        'user_name' => $customer->name,
                        'customer_name' => $customer->name,
                        'property_name' => $propertyName,
                        'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                        'reservation_id' => $reservation->id,
                        'feedback_link' => $feedbackUrl,
                        'form_type' => $formType === 'vacation_homes' ? 'Vacation Home' : 'Hotel',
                    ];

                    // Get or create email template
                    $emailTypeData = HelperService::getEmailTemplatesTypes("feedback_request");
                    $emailTemplateData = system_setting('feedback_request_mail_template');

                    // Default template if none exists
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

                    // Replace variables in template
                    $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

                    // Send email
                    $data = [
                        'email' => $customer->email,
                        'title' => $emailTypeData['title'] ?? 'Share Your Feedback - ' . $appName,
                        'email_template' => $emailContent
                    ];

                    HelperService::sendMail($data);

                    // Update reservation with token and sent timestamp
                    $reservation->feedback_token = $token;
                    $reservation->feedback_email_sent_at = now();
                    $reservation->save();

                    $sentCount++;
                    $this->info("Feedback email sent to {$customer->email} for reservation {$reservation->id}");

                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMsg = "Failed to send feedback email for reservation {$reservation->id}: " . $e->getMessage();
                    $errors[] = $errorMsg;
                    $this->error($errorMsg);
                    
                    Log::error('Failed to send feedback request email', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("Feedback request emails process completed.");
            $this->info("Sent: {$sentCount}, Failed: {$failedCount}");
            
            if ($sentCount == 0 && $reservations->isEmpty()) {
                $this->info("No reservations checking out today. Emails will be sent on their checkout dates.");
            }

            if (!empty($errors)) {
                $this->warn('Errors encountered:');
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process feedback request emails: ' . $e->getMessage());
            Log::error('Feedback request emails command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
