<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HelperService;

class SendTestPaymentEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-payment {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test payment form submission email to the specified address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetEmail = $this->argument('email');
        $this->info("Sending test payment email to: $targetEmail");

        try {
            $emailTypeData = HelperService::getEmailTemplatesTypes('payment_form_submission');
            $templateData = system_setting('payment_form_submission_mail_template');

            if (empty($templateData)) {
                $this->error("Email template 'payment_form_submission_mail_template' not found in system settings.");
                return 1;
            }

            // Dummy variables for testing
                $totalAmount = 1500.00;
                $currencySymbol = 'EGP';
                
                // Calculate dummy breakdown
                $taxPercentage = 28.8; // Standard hotel tax
                $serviceChargeRate = 12.00;
                $salesTaxRate = 15.68;
                $cityTaxRate = 1.12;

                $baseRoomRevenue = $totalAmount / (1 + ($taxPercentage / 100));
                
                $serviceChargeAmount = $baseRoomRevenue * ($serviceChargeRate / 100);
                $salesTaxAmount = $baseRoomRevenue * ($salesTaxRate / 100);
                $cityTaxAmount = $baseRoomRevenue * ($cityTaxRate / 100);

                $platformCommission = $baseRoomRevenue * 0.15;
                $totalNetPayout = $baseRoomRevenue - $platformCommission;

                $paymentBreakdown = "
                    <div style='margin-top: 20px; margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 15px;'>
                        <h3 style='font-size: 16px; margin-bottom: 10px; color: #333;'>Payment Breakdown</h3>
                        <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                            <tbody>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Total Guest Payment</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; font-weight: bold;'>" . number_format($totalAmount, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Base Room Revenue</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right;'>" . number_format($baseRoomRevenue, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Platform Commission (15%)</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($platformCommission, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Service Charge ({$serviceChargeRate}%)</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($serviceChargeAmount, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Sales Tax ({$salesTaxRate}%)</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($salesTaxAmount, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>City Tax ({$cityTaxRate}%)</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($cityTaxAmount, 2) . " {$currencySymbol}</td>
                                </tr>
                                <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                    <td style='padding: 10px; border-top: 2px solid #ddd;'>Net Room Revenue</td>
                                    <td style='padding: 10px; border-top: 2px solid #ddd; text-align: right; color: #28a745; font-size: 16px;'>" . number_format($totalNetPayout, 2) . " {$currencySymbol}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                ";

                $variables = array(
                    'app_name' => env('APP_NAME') ?? 'As-home',
                    'property_owner_name' => 'Test Property Owner',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john.doe@example.com',
                    'customer_phone' => '+1234567890',
                    'property_name' => 'Luxury Villa in Cairo',
                    'property_address' => '123 Nile View, Cairo, Egypt',
                    'room_type' => 'Deluxe Suite (Package: Breakfast Included)',
                    'payment_breakdown' => $paymentBreakdown,
                    'check_in_date' => now()->addDays(5)->format('Y-m-d'),
                'check_out_date' => now()->addDays(10)->format('Y-m-d'),
                'number_of_guests' => 2,
                'total_amount' => '1,500.00',
                'currency_symbol' => 'EGP',
                'card_number_masked' => '**** **** **** 1234',
                'special_requests' => 'Late check-in requested, extra pillows.',
                'submission_date' => now()->format('Y-m-d H:i:s'),
                'current_date_today' => now()->format('d M Y, h:i A'),
                'reservation_id' => 12345,
                'transaction_id' => 'TXN-987654321',
                'approval_status' => 'pending',
                'booking_type' => 'reservation_request'
            );

            $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

            $data = array(
                'email_template' => $emailTemplate,
                'email' => $targetEmail,
                'title' => $emailTypeData['title'],
            );

            // Send the email
            // Note: HelperService::sendMail might generate a PDF if not skipped. 
            // We'll let it behave as production does (default args).
            HelperService::sendMail($data);

            $this->info("Email sent successfully!");
            
            $this->line("----------------------------------------");
            $this->line("Raw Template Content (from DB):");
            $this->line("----------------------------------------");
            $this->line($templateData);
            $this->line("----------------------------------------");
            $this->line("Email Content Preview (with dummy data):");
            $this->line("----------------------------------------");
            $this->line($emailTemplate);
            $this->line("----------------------------------------");

        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
