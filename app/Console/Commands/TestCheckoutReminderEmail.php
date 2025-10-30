<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestCheckoutReminderEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:checkout-reminder-email 
                            {reservation : The reservation ID to send test email for}
                            {--email= : Override the customer email address (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test checkout reminder email for a specific reservation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reservationId = $this->argument('reservation');
        $overrideEmail = $this->option('email');

        $this->info("Sending test checkout reminder email for reservation ID: {$reservationId}");

        try {
            // Find the reservation
            $reservation = Reservation::with(['customer', 'reservable', 'reservable.property'])->find($reservationId);

            if (!$reservation) {
                $this->error("Reservation with ID {$reservationId} not found.");
                return Command::FAILURE;
            }

            $customer = $reservation->customer;

            if (!$customer) {
                $this->error("Customer not found for reservation ID {$reservationId}.");
                return Command::FAILURE;
            }

            // Use override email if provided, otherwise use customer email
            $emailAddress = $overrideEmail ?? $customer->email;

            if (!$emailAddress) {
                $this->error("No email address found for customer. Please provide --email option.");
                return Command::FAILURE;
            }

            $this->info("Customer: {$customer->name} ({$emailAddress})");

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            $this->info("Property: {$propertyName}");
            $this->info("Check-out Date: " . ($reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : 'N/A'));

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Prepare email variables
            $variables = [
                'app_name' => config('app.name') ?? env("APP_NAME") ?? "As-home",
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'total_price' => number_format($reservation->total_price ?? 0, 2),
                'currency_symbol' => $currencySymbol,
                'number_of_guests' => $reservation->number_of_guests ?? 0,
                'special_requests' => $reservation->special_requests ?? 'None',
                'current_date_today' => now()->format('d M Y, h:i A'),
            ];

            // Get email template
            $emailTypeData = HelperService::getEmailTemplatesTypes("checkout_reminder");
            $emailTemplateData = system_setting('checkout_reminder_mail_template');

            // Default template if none exists
            if (empty($emailTemplateData)) {
                $this->warn('Checkout reminder email template not found in database, using default template.');
                $emailTemplateData = 'Dear {customer_name},

This is a friendly reminder that your reservation is checking out today.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {currency_symbol}{total_price}
- Special Requests: {special_requests}

Please ensure you have completed the checkout process and returned any keys or access cards as required.

If you have any questions or need assistance, please don\'t hesitate to contact our support team.

Thank you for choosing {app_name}. We hope you had a wonderful stay!

Best regards,
{app_name} Asset Management Team';
            }

            // Replace variables in template
            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $emailAddress,
                'title' => ($emailTypeData['title'] ?? 'Checkout Reminder - Your Reservation Ends Today') . ' [TEST]',
                'email_template' => $emailContent
            ];

            $this->info("Preparing to send email to: {$emailAddress}");
            $this->info("Email Subject: " . $data['title']);

            // Log current email configuration
            $this->info("\nCurrent Email Configuration:");
            $this->info("MAIL_MAILER: " . config('mail.mailers.smtp.transport', 'N/A'));
            $this->info("MAIL_HOST: " . config('mail.mailers.smtp.host', 'N/A'));
            $this->info("MAIL_PORT: " . config('mail.mailers.smtp.port', 'N/A'));
            $this->info("MAIL_USERNAME: " . config('mail.mailers.smtp.username', 'N/A'));
            $this->info("MAIL_FROM_ADDRESS: " . config('mail.from.address', 'N/A'));
            
            if (config('mail.mailers.smtp.host') === 'mailpit') {
                $this->warn("\n⚠️  MAIL_HOST is set to 'mailpit'. Email will be sent to local Mailpit instance at http://localhost:8025");
                $this->warn("   To send to external email, configure SMTP settings in admin panel Email Configurations.");
            }

            try {
                HelperService::sendMail($data, true); // Pass true for requiredEmailException to get detailed errors
                
                $this->info("\n✓ Test checkout reminder email sent successfully!");
                $this->info("Email sent to: {$emailAddress}");
                
                if (config('mail.mailers.smtp.host') === 'mailpit') {
                    $this->info("\nTo view the email, open Mailpit at: http://localhost:8025");
                } else {
                    $this->info("\nPlease check the inbox (and spam folder) for: {$emailAddress}");
                }
                
                Log::info('Test checkout reminder email sent', [
                    'reservation_id' => $reservationId,
                    'customer_email' => $emailAddress,
                    'property_name' => $propertyName,
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : 'N/A'
                ]);

                return Command::SUCCESS;

            } catch (\Exception $e) {
                $this->error("\n✗ Email sending failed: " . $e->getMessage());
                $this->error("Please check your SMTP configuration in the admin panel Email Configurations.");
                Log::error('Test checkout reminder email failed', [
                    'reservation_id' => $reservationId,
                    'customer_email' => $emailAddress,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Test checkout reminder email command failed', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}

