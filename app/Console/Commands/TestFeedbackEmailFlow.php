<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Property;
use App\Models\PropertyQuestionAnswer;
use App\Models\PropertyQuestionField;
use App\Services\HelperService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TestFeedbackEmailFlow extends Command
{
    protected $signature = 'test:feedback-email-flow 
                            {--reservation-id= : Specific reservation ID to test}
                            {--property-id= : Property ID to use if no reservation}
                            {--email= : Override email address for testing}';
    
    protected $description = 'Test complete feedback email flow: send email, verify link, test submission, and check answers view';

    public function handle()
    {
        $this->info("=== Feedback Email Flow Test ===\n");

        try {
            // Step 1: Find or create a reservation
            $reservationId = $this->option('reservation-id');
            $propertyId = $this->option('property-id');
            $overrideEmail = $this->option('email');

            if ($reservationId) {
                $reservation = Reservation::with(['customer', 'reservable'])->find($reservationId);
                if (!$reservation) {
                    $this->error("Reservation ID {$reservationId} not found.");
                    return Command::FAILURE;
                }
            } elseif ($propertyId) {
                // Find a reservation for this property via HotelRoom
                $reservation = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                    ->whereIn('status', ['confirmed', 'approved', 'completed'])
                    ->with(['customer', 'reservable'])
                    ->get()
                    ->filter(function($res) use ($propertyId) {
                        if ($res->reservable && $res->reservable->property_id == $propertyId) {
                            return true;
                        }
                        return false;
                    })
                    ->first();

                if (!$reservation) {
                    $this->error("No reservation found for property ID {$propertyId}.");
                    return Command::FAILURE;
                }
            } else {
                // Find any hotel reservation
                $reservation = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                    ->whereIn('status', ['confirmed', 'approved', 'completed'])
                    ->with(['customer', 'reservable.property'])
                    ->first();

                if (!$reservation) {
                    $this->error("No hotel reservations found. Please provide --reservation-id or --property-id");
                    return Command::FAILURE;
                }
            }

            $this->info("✓ Step 1: Found Reservation");
            $this->info("  - Reservation ID: {$reservation->id}");
            $this->info("  - Customer: " . ($reservation->customer->name ?? 'N/A'));
            $this->info("  - Email: " . ($reservation->customer->email ?? 'N/A'));

            // Get property
            $property = null;
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = $reservation->reservable;
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $property = $reservation->reservable->property ?? null;
            }

            if (!$property) {
                $this->error("Could not determine property for reservation.");
                return Command::FAILURE;
            }

            $this->info("  - Property: ID {$property->id} - {$property->title}");

            // Step 2: Generate feedback token if not exists
            if (!$reservation->feedback_token) {
                $reservation->feedback_token = Str::random(60);
                $reservation->save();
                $this->info("\n✓ Step 2: Generated feedback token");
            } else {
                $this->info("\n✓ Step 2: Using existing feedback token");
            }

            $this->info("  - Token: {$reservation->feedback_token}");

            // Step 3: Send test email
            $this->info("\n=== Step 3: Sending Feedback Request Email ===");
            
            $customer = $reservation->customer;
            $customerEmail = $overrideEmail ?: $customer->email;

            if (!$customerEmail) {
                $this->error("No email address available for customer.");
                return Command::FAILURE;
            }

            // Build absolute feedback link using configured public URL if available
            $baseUrl = function_exists('system_setting') ? (system_setting('web_url') ?: null) : null;
            if (empty($baseUrl)) {
                $baseUrl = 'https://ashome-eg.com';
            }
            $baseUrl = rtrim($baseUrl ?: (config('app.url') ?: ''), '/');
            // Include property_id in the URL for easier frontend access
            $propertyId = $property->id ?? null;
            $feedbackUrl = $baseUrl . "/feedback/{$reservation->feedback_token}" . ($propertyId ? "?property_id={$propertyId}" : '');

            $propertyName = $property->title;
            $formType = null;
            $propertyClassification = $property->getRawOriginal('property_classification');
            
            if ($propertyClassification == 4) {
                $formType = 'Vacation Home';
            } elseif ($propertyClassification == 5) {
                $formType = 'Hotel';
            }

            $variables = [
                'app_name' => config('app.name', 'As-home'),
                'customer_name' => $customer->name ?? 'Guest',
                'user_name' => $customer->name ?? 'Guest',
                'property_name' => $propertyName,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'reservation_id' => $reservation->id,
                'feedback_link' => $feedbackUrl,
                'form_type' => $formType ?? 'Property',
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
{app_name} Team';
            }

            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            $this->info("  - Recipient: {$customerEmail}");
            $this->info("  - Subject: " . ($emailTypeData['title'] ?? 'Feedback Request') . ' [TEST]');
            $this->info("  - Feedback URL: {$feedbackUrl}");

            // Send email
            try {
                $data = [
                    'email' => $customerEmail,
                    'title' => ($emailTypeData['title'] ?? 'Feedback Request') . ' [TEST]',
                    'email_template' => $emailContent
                ];

                HelperService::sendMail($data);
                
                $this->info("\n✓ Email sent successfully!");
                
                // Update reservation
                if (!$reservation->feedback_email_sent_at) {
                    $reservation->feedback_email_sent_at = now();
                    $reservation->save();
                }

            } catch (\Exception $e) {
                $this->error("✗ Email sending failed: " . $e->getMessage());
                Log::error('Feedback email test failed', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
                return Command::FAILURE;
            }

            // Step 4: Verify feedback link
            $this->info("\n=== Step 4: Verifying Feedback Link ===");
            $this->info("  - Link should be accessible at: {$feedbackUrl}");
            $this->info("  - Token in database: " . ($reservation->fresh()->feedback_token ? '✓ Set' : '✗ Missing'));

            // Step 5: Check existing answers
            $this->info("\n=== Step 5: Checking Existing Answers ===");
            $existingAnswers = PropertyQuestionAnswer::where('reservation_id', $reservation->id)
                ->where('property_id', $property->id)
                ->count();

            if ($existingAnswers > 0) {
                $this->warn("  ⚠ {$existingAnswers} answer(s) already exist for this reservation.");
                $this->info("  - Link will show 'already submitted' message");
            } else {
                $this->info("  ✓ No existing answers - form will be available for submission");
            }

            // Step 6: Check available questions
            $this->info("\n=== Step 6: Available Questions for Property ===");
            $questions = PropertyQuestionField::where('property_classification', $propertyClassification)
                ->where('status', 'active')
                ->with('field_values')
                ->get();

            $this->info("  - Active questions: " . $questions->count());
            foreach ($questions->take(5) as $q) {
                $this->line("    • {$q->name} ({$q->field_type})");
            }
            if ($questions->count() > 5) {
                $this->line("    ... and " . ($questions->count() - 5) . " more");
            }

            // Step 7: Summary and instructions
            $this->info("\n=== Step 7: Test Instructions ===");
            $this->info("1. Check email inbox (or Mailpit at http://localhost:8025)");
            $this->info("2. Click the feedback link in the email");
            $this->info("3. Fill out and submit the form");
            $this->info("4. Verify answers appear in:");
            $this->info("   Admin Panel → Property Question Form → Select Property → ID {$property->id}");
            
            $this->info("\n=== Summary ===");
            $this->info("✓ Reservation: {$reservation->id}");
            $this->info("✓ Property: {$property->id} - {$property->title}");
            $this->info("✓ Email sent to: {$customerEmail}");
            $this->info("✓ Feedback token: {$reservation->feedback_token}");
            $this->info("✓ Feedback URL: {$feedbackUrl}");
            $this->info("✓ Questions available: {$questions->count()}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            Log::error('Feedback email flow test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}

