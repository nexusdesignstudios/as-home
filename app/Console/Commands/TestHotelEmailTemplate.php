<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HelperService;
use App\Models\Customer;

class TestHotelEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hotel-emails
                            {email : The email address to send test emails to}
                            {--template=both : Template to test (flexible, non-refundable, or both)}
                            {--month=2025-01 : Month to use for testing}
                            {--owner-email= : Owner email to get actual data from (optional)}
                            {--owner-id= : Owner ID to get actual data from (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hotel email templates by sending test emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $template = $this->option('template');
        $month = $this->option('month');
        $ownerEmail = $this->option('owner-email');
        $ownerId = $this->option('owner-id');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        $this->info("Testing hotel email templates...");
        $this->info("Email: {$email}");
        $this->info("Month: {$month}");
        $this->info("Template: {$template}");
        if ($ownerId) {
            $this->info("Using actual data from owner ID: {$ownerId}");
        } elseif ($ownerEmail) {
            $this->info("Using actual data from owner: {$ownerEmail}");
        } else {
            $this->info("Using sample data");
        }
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        // Test flexible template
        if ($template === 'both' || $template === 'flexible') {
            $this->info("Testing Flexible Hotel Template...");
            if ($this->sendTestEmail($email, 'monthly_tax_invoice_hotels_flexible', $month, $ownerEmail, $ownerId)) {
                $this->info("✅ Flexible hotel email sent successfully!");
                $successCount++;
            } else {
                $this->error("❌ Failed to send flexible hotel email");
                $errorCount++;
            }
            $this->newLine();
        }

        // Test non-refundable template
        if ($template === 'both' || $template === 'non-refundable') {
            $this->info("Testing Non-Refundable Hotel Template...");
            if ($this->sendTestEmail($email, 'monthly_tax_invoice_hotels_non_refundable', $month, $ownerEmail, $ownerId)) {
                $this->info("✅ Non-refundable hotel email sent successfully!");
                $successCount++;
            } else {
                $this->error("❌ Failed to send non-refundable hotel email");
                $errorCount++;
            }
            $this->newLine();
        }

        // Summary
        $this->info("Test Summary:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$errorCount}");

        if ($errorCount > 0) {
            $this->error("Some tests failed. Check your email configuration and template settings.");
            return 1;
        }

        $this->info("All tests completed successfully!");
        return 0;
    }

    /**
     * Send a test email for the specified template
     *
     * @param string $email
     * @param string $templateType
     * @param string $month
     * @param string|null $ownerEmail
     * @param int|null $ownerId
     * @return bool
     */
    private function sendTestEmail($email, $templateType, $month, $ownerEmail = null, $ownerId = null)
    {
        try {
            // Get actual data if owner email or ID is provided, otherwise use sample data
            if ($ownerId || $ownerEmail) {
                $actualData = $this->getActualOwnerData($ownerEmail, $month, $templateType, $ownerId);
                if (!$actualData) {
                    $this->error("No data found for owner: " . ($ownerId ? "ID {$ownerId}" : $ownerEmail));
                    return false;
                }
                $variables = $actualData;
            } else {
                // Sample data for testing
                $totalRevenue = 4250.00;
                $serviceChargeRate = 10;
                $salesTaxRate = 14;
                $cityTaxRate = 5;
                $serviceChargeAmount = $totalRevenue * ($serviceChargeRate / 100);
                $salesTaxAmount = $totalRevenue * ($salesTaxRate / 100);
                $cityTaxAmount = $totalRevenue * ($cityTaxRate / 100);
                $totalTaxesAmount = $serviceChargeAmount + $salesTaxAmount + $cityTaxAmount;
                $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;
                $commissionRate = 15;
                $commissionAmount = $revenueAfterTaxes * ($commissionRate / 100);
                $netAmount = $revenueAfterTaxes - $commissionAmount;

                $variables = [
                    'app_name' => env('APP_NAME', 'As-home'),
                    'owner_name' => 'Test Property Owner',
                    'month_year' => date('F Y', strtotime($month . '-01')),
                    'total_reservations' => '8',
                    'total_revenue' => number_format($totalRevenue, 2),
                    'currency_symbol' => system_setting('currency_symbol') ?? 'EGP',
                    'service_charge_rate' => $serviceChargeRate,
                    'service_charge_amount' => number_format($serviceChargeAmount, 2),
                    'sales_tax_rate' => $salesTaxRate,
                    'sales_tax_amount' => number_format($salesTaxAmount, 2),
                    'city_tax_rate' => $cityTaxRate,
                    'city_tax_amount' => number_format($cityTaxAmount, 2),
                    'total_taxes_amount' => number_format($totalTaxesAmount, 2),
                    'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
                    'commission_rate' => $commissionRate,
                    'commission_amount' => number_format($commissionAmount, 2),
                    'net_amount' => number_format($netAmount, 2),
                    'reservation_details' => $this->generateTestReservationDetails(),
                    'property_summary' => $this->generateTestPropertySummary(),
                ];
            }

            // Add bank details for flexible template using the same logic as MonthlyTaxInvoiceService
            if ($templateType === 'monthly_tax_invoice_hotels_flexible') {
                $variables['bank_account_details'] = $this->generateBankAccountDetailsHtml();
            }

            // Get template data
            $emailTypeData = HelperService::getEmailTemplatesTypes($templateType);

            if (!$emailTypeData) {
                $this->error("Template type '{$templateType}' not found in HelperService");
                return false;
            }

            $templateData = system_setting($emailTypeData['type']);

            if (empty($templateData)) {
                $this->warn("No template content found for '{$templateType}'. Using default template.");
                $templateData = $this->getDefaultTemplate($templateType);
            }

            $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

            $data = [
                'email_template' => $emailTemplate,
                'email' => $email,
                'title' => $emailTypeData['title'] . ' - TEST EMAIL',
            ];

            HelperService::sendMail($data);

            return true;
        } catch (\Exception $e) {
            $this->error("Error sending test email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate test reservation details HTML
     *
     * @return string
     */
    private function generateTestReservationDetails()
    {
        return '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservation ID</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-in</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-out</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Guests</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">#12345</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">Test Hotel - Room 101</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">15 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">18 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">2</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">EGP 750.00</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">#12346</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">Test Hotel - Room 205</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">20 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">22 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">EGP 400.00</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">#12347</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">Test Hotel - Room 301</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">25 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">28 Jan 2025</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">3</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">EGP 1,200.00</td>
                </tr>
            </tbody>
        </table>';
    }

    /**
     * Generate test property summary HTML
     *
     * @return string
     */
    private function generateTestPropertySummary()
    {
        return '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservations</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">Test Hotel</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">8</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">EGP 4,250.00</td>
                </tr>
            </tbody>
        </table>';
    }

    /**
     * Generate test bank details HTML
     *
     * @return string
     */
    private function generateTestBankDetails()
    {
        return '<div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
            <h3 style="color: #495057; margin-bottom: 15px;">Bank Account Details for Commission Payment</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 8px; font-weight: bold; width: 30%;">Bank Name:</td><td style="padding: 8px;">As-home Bank</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Account Holder:</td><td style="padding: 8px;">As-home Group</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Account Number:</td><td style="padding: 8px;">1234567890</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Routing Number:</td><td style="padding: 8px;">987654321</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">SWIFT Code:</td><td style="padding: 8px;">ASHOMEXX</td></tr>
            </table>
            <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                <strong>Note:</strong> Please transfer the commission amount ({commission_amount} {currency_symbol}) to the above account within 7 days of receiving this invoice.
            </p>
        </div>';
    }

    /**
     * Get default template content if none is set
     *
     * @param string $templateType
     * @return string
     */
    private function getDefaultTemplate($templateType)
    {
        $monthYear = date('F Y', strtotime('2025-01-01'));

        if ($templateType === 'monthly_tax_invoice_hotels_flexible') {
            return "Monthly Tax Invoice - {$monthYear}\n\nDear {owner_name},\n\nPlease find below your monthly tax invoice for {$monthYear} for your hotel properties with flexible booking policies.\n\nInvoice Summary:\nTotal Reservations: {total_reservations}\nTotal Revenue: {currency_symbol} {total_revenue}\n\nProperty Taxes:\nService Charge ({service_charge_rate}%): {currency_symbol} {service_charge_amount}\nSales Tax ({sales_tax_rate}%): {currency_symbol} {sales_tax_amount}\nCity Tax ({city_tax_rate}%): {currency_symbol} {city_tax_amount}\nTotal Taxes: {currency_symbol} {total_taxes_amount}\n\nRevenue After Taxes: {currency_symbol} {revenue_after_taxes}\n\nAs-home Commission:\nCommission Rate: {commission_rate}%\nCommission Amount: {currency_symbol} {commission_amount}\nNet Amount: {currency_symbol} {net_amount}\n\nReservation Details:\n{reservation_details}\n\nProperty Summary:\n{property_summary}\n\nBank Account Details:\n{bank_account_details}\n\nThank you for your partnership with {app_name}!";
        } else {
            return "Monthly Tax Invoice - {$monthYear}\n\nDear {owner_name},\n\nPlease find below your monthly tax invoice for {$monthYear} for your hotel properties with non-refundable booking policies.\n\nInvoice Summary:\nTotal Reservations: {total_reservations}\nTotal Revenue: {currency_symbol} {total_revenue}\n\nProperty Taxes:\nService Charge ({service_charge_rate}%): {currency_symbol} {service_charge_amount}\nSales Tax ({sales_tax_rate}%): {currency_symbol} {sales_tax_amount}\nCity Tax ({city_tax_rate}%): {currency_symbol} {city_tax_amount}\nTotal Taxes: {currency_symbol} {total_taxes_amount}\n\nRevenue After Taxes: {currency_symbol} {revenue_after_taxes}\n\nAs-home Commission:\nCommission Rate: {commission_rate}%\nCommission Amount: {currency_symbol} {commission_amount}\nNet Amount: {currency_symbol} {net_amount}\n\nReservation Details:\n{reservation_details}\n\nProperty Summary:\n{property_summary}\n\nThank you for your partnership with {app_name}!";
        }
    }

    /**
     * Get actual data from owner's properties and reservations using MonthlyTaxInvoiceService logic
     *
     * @param string|null $ownerEmail
     * @param string $month
     * @param string $templateType
     * @param int|null $ownerId
     * @return array|null
     */
    private function getActualOwnerData($ownerEmail, $month, $templateType, $ownerId = null)
    {
        try {
            // Find the owner by ID or email
            if ($ownerId) {
                $owner = \App\Models\Customer::find($ownerId);
                if (!$owner) {
                    $this->error("Owner not found with ID: {$ownerId}");
                    return null;
                }
            } else {
                $owner = \App\Models\Customer::where('email', $ownerEmail)->first();
                if (!$owner) {
                    $this->error("Owner not found with email: {$ownerEmail}");
                    return null;
                }
            }

            // Use the same logic as MonthlyTaxInvoiceService
            $monthYear = $month . '-01';
            $startDate = \Carbon\Carbon::parse($monthYear)->startOfMonth();
            $endDate = \Carbon\Carbon::parse($monthYear)->endOfMonth();

           // Get reservations using the same logic as MonthlyTaxInvoiceService
$reservations = \App\Models\Reservation::where(function ($query) use ($owner) {
    // Handle both direct property reservations and hotel room reservations
    $query->where(function ($subQuery) use ($owner) {
        // Direct property reservations
        $subQuery->where('reservable_type', 'App\\Models\\Property')
                 ->whereHas('reservable', function ($propertyQuery) use ($owner) {
                     $propertyQuery->where('added_by', $owner->id)
                                   ->where('property_classification', 5);
                 });
    })->orWhere(function ($subQuery) use ($owner) {
        // Hotel room reservations - Handle both formats
        $subQuery->where(function ($hotelQuery) use ($owner) {
            $hotelQuery->where('reservable_type', 'App\\Models\\HotelRoom')
                       ->whereHas('reservable', function ($roomQuery) use ($owner) {
                           $roomQuery->whereHas('property', function ($propertyQuery) use ($owner) {
                               $propertyQuery->where('added_by', $owner->id)
                                             ->where('property_classification', 5);
                           });
                       });
        })->orWhere(function ($hotelQuery) use ($owner) {
            $hotelQuery->where('reservable_type', 'App\\Models\\HotelRoom')
                       ->whereHas('reservable', function ($roomQuery) use ($owner) {
                           $roomQuery->whereHas('property', function ($propertyQuery) use ($owner) {
                               $propertyQuery->where('added_by', $owner->id)
                                             ->where('property_classification', 5);
                           });
                       });
        });
    });
})
->whereBetween('check_in_date', [$startDate, $endDate])  // Also fix the date field name
->where('status', 'confirmed')
->with(['reservable.property'])
->get();

            if ($reservations->isEmpty()) {
                $this->warn("No reservations found for owner {$ownerEmail} in {$month}");
                return null;
            }

            // Filter reservations based on template type (flexible vs non-refundable)
            if ($templateType === 'monthly_tax_invoice_hotels_flexible') {
                $reservations = $reservations->filter(function ($reservation) {
                    $property = $this->getPropertyFromReservation($reservation);
                    return $property && $property->rent_package === 'flexible';
                });
            } else {
                $reservations = $reservations->filter(function ($reservation) {
                    $property = $this->getPropertyFromReservation($reservation);
                    return $property && $property->rent_package !== 'flexible';
                });
            }

            if ($reservations->isEmpty()) {
                $this->warn("No reservations found for {$templateType} properties for owner {$ownerEmail} in {$month}");
                return null;
            }

            // Use the service's logic to extract variables
            $monthYearDisplay = \Carbon\Carbon::parse($monthYear)->format('F Y');
            return $this->extractVariablesFromService($owner, $reservations, $monthYearDisplay, $templateType);

        } catch (\Exception $e) {
            $this->error("Error fetching actual data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract variables using the same logic as MonthlyTaxInvoiceService
     */
    private function extractVariablesFromService($owner, $reservations, $monthYearDisplay, $templateType)
    {
        // Use the same calculation logic as MonthlyTaxInvoiceService
        $totalRevenue = $reservations->sum('total_price');

        // Calculate property taxes (same as MonthlyTaxInvoiceService)
        $serviceChargeRate = system_setting('hotel_service_charge_rate') ?? 10;
        $salesTaxRate = system_setting('hotel_sales_tax_rate') ?? 14;
        $cityTaxRate = system_setting('hotel_city_tax_rate') ?? 5;

        $serviceChargeAmount = $totalRevenue * ($serviceChargeRate / 100);
        $salesTaxAmount = $totalRevenue * ($salesTaxRate / 100);
        $cityTaxAmount = $totalRevenue * ($cityTaxRate / 100);
        $totalTaxesAmount = $serviceChargeAmount + $salesTaxAmount + $cityTaxAmount;

        // Calculate revenue after taxes
        $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;

        // Calculate commission using the same logic as MonthlyTaxInvoiceService
        $firstReservation = $reservations->first();
        $property = $this->getPropertyFromReservation($firstReservation);

        if ($property) {
            $propertyClassification = $property->getRawOriginal('property_classification');
            $rentPackage = $property->rent_package;
            $commissionRate = \App\Models\PropertyTax::getCommissionRate($propertyClassification, $rentPackage);
        } else {
            $commissionRate = 15; // Default fallback
        }

        // Calculate As-home commission on revenue after taxes
        $commissionAmount = $revenueAfterTaxes * ($commissionRate / 100);
        $netAmount = $revenueAfterTaxes - $commissionAmount;

        // Get currency symbol
        $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

        // Generate HTML using the same methods as MonthlyTaxInvoiceService
        $reservationDetails = $this->generateReservationDetailsHtml($reservations);
        $propertySummary = $this->generatePropertySummaryHtml($reservations);

        $appName = env("APP_NAME") ?? "eBroker";

        return [
            'app_name' => $appName,
            'owner_name' => $owner->name,
            'month_year' => $monthYearDisplay,
            'total_reservations' => $reservations->count(),
            'total_revenue' => number_format($totalRevenue, 2),
            'currency_symbol' => $currencySymbol,
            'service_charge_rate' => $serviceChargeRate,
            'service_charge_amount' => number_format($serviceChargeAmount, 2),
            'sales_tax_rate' => $salesTaxRate,
            'sales_tax_amount' => number_format($salesTaxAmount, 2),
            'city_tax_rate' => $cityTaxRate,
            'city_tax_amount' => number_format($cityTaxAmount, 2),
            'total_taxes_amount' => number_format($totalTaxesAmount, 2),
            'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
            'commission_rate' => $commissionRate,
            'commission_amount' => number_format($commissionAmount, 2),
            'net_amount' => number_format($netAmount, 2),
            'reservation_details' => $reservationDetails,
            'property_summary' => $propertySummary,
        ];
    }

    /**
     * Generate reservation details HTML using the same logic as MonthlyTaxInvoiceService
     *
     * @param \Illuminate\Support\Collection $reservations
     * @return string
     */
    private function generateReservationDetailsHtml($reservations)
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservation ID</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-in</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-out</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Guests</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Amount</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($reservations as $reservation) {
            $property = $this->getPropertyFromReservation($reservation);
            $propertyName = $property ? $property->title : 'Unknown Property';

            $html .= '<tr>
                <td style="border: 1px solid #ddd; padding: 8px;">#' . $reservation->id . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $propertyName . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_in_date->format('d M Y') . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_out_date->format('d M Y') . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->number_of_guests . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . (system_setting('currency_symbol') ?? 'EGP') . ' ' . number_format($reservation->total_price, 2) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate property summary HTML using the same logic as MonthlyTaxInvoiceService
     *
     * @param \Illuminate\Support\Collection $reservations
     * @return string
     */
    private function generatePropertySummaryHtml($reservations)
    {
        // Group reservations by property
        $propertyGroups = $reservations->groupBy(function ($reservation) {
            $property = $this->getPropertyFromReservation($reservation);
            return $property ? $property->id : 'unknown';
        });

        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservations</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Revenue</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($propertyGroups as $propertyId => $propertyReservations) {
            $property = $this->getPropertyFromReservation($propertyReservations->first());
            $propertyName = $property ? $property->title : 'Unknown Property';
            $totalRevenue = $propertyReservations->sum('total_price');

            $html .= '<tr>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $propertyName . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . $propertyReservations->count() . '</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . (system_setting('currency_symbol') ?? 'EGP') . ' ' . number_format($totalRevenue, 2) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate bank account details HTML using the same logic as MonthlyTaxInvoiceService
     *
     * @return string
     */
    private function generateBankAccountDetailsHtml()
    {
        // Get bank account details from system settings or use default
        $bankName = system_setting('bank_name') ?? 'As-home Bank';
        $accountNumber = system_setting('bank_account_number') ?? '1234567890';
        $routingNumber = system_setting('bank_routing_number') ?? '987654321';
        $swiftCode = system_setting('bank_swift_code') ?? 'ASHOMEXX';
        $accountHolder = system_setting('bank_account_holder') ?? 'As-home Group';

        $html = '<div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
        $html .= '<h3 style="color: #495057; margin-bottom: 15px;">Bank Account Details for Commission Payment</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 8px; font-weight: bold; width: 30%;">Bank Name:</td><td style="padding: 8px;">' . $bankName . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Holder:</td><td style="padding: 8px;">' . $accountHolder . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Number:</td><td style="padding: 8px;">' . $accountNumber . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Routing Number:</td><td style="padding: 8px;">' . $routingNumber . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">SWIFT Code:</td><td style="padding: 8px;">' . $swiftCode . '</td></tr>';
        $html .= '</table>';
        $html .= '<p style="margin-top: 15px; color: #6c757d; font-size: 14px;">';
        $html .= '<strong>Note:</strong> Please transfer the commission amount ({commission_amount} {currency_symbol}) to the above account within 7 days of receiving this invoice.';
        $html .= '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get property from reservation (handles both direct property and hotel room reservations)
     *
     * @param \App\Models\Reservation $reservation
     * @return \App\Models\Property|null
     */
    private function getPropertyFromReservation($reservation)
    {
        try {
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                // Direct property reservation
                return $reservation->reservable;
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                // Hotel room reservation - get the property from the room
                return $reservation->reservable->property ?? null;
            }
            return null;
        } catch (\Exception $e) {
            $this->warn("Error getting property from reservation: " . $e->getMessage());
            return null;
        }
    }
}
