<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HelperService;
use App\Services\PDF\TaxInvoiceService;
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
                            {--owner-id= : Owner ID to get actual data from (optional)}
                            {--with-pdf : Include PDF attachment in the email}';

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
        $withPdf = $this->option('with-pdf');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        $this->info("Testing hotel email templates...");
        $this->info("Email: {$email}");
        $this->info("Month: {$month}");
        $this->info("Template: {$template}");
        $this->info("PDF Attachment: " . ($withPdf ? 'Yes' : 'No'));
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
            if ($this->sendTestEmail($email, 'monthly_tax_invoice_hotels_flexible', $month, $ownerEmail, $ownerId, $withPdf)) {
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
            if ($this->sendTestEmail($email, 'monthly_tax_invoice_hotels_non_refundable', $month, $ownerEmail, $ownerId, $withPdf)) {
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
     * @param bool $withPdf
     * @return bool
     */
    private function sendTestEmail($email, $templateType, $month, $ownerEmail = null, $ownerId = null, $withPdf = false)
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

                // Create a test owner object
                $testOwner = new Customer();
                $testOwner->id = 999;
                $testOwner->name = 'Test Property Owner';
                $testOwner->email = 'testowner@example.com';
                $testOwner->mobile = '+1234567890';

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
                    'owner' => $testOwner, // Add owner object for PDF generation
                ];
            }

            // Add bank details for flexible template using the same logic as MonthlyTaxInvoiceService
            if ($templateType === 'monthly_tax_invoice_hotels_flexible') {
                $variables['bank_account_details'] = $this->generateBankAccountDetailsHtml($variables);
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

            // Add PDF attachment if requested
            if ($withPdf) {
                // Get owner from variables
                $owner = $variables['owner'] ?? null;
                if ($owner) {
                    // Prepare invoiceData for PDF with numeric values (not formatted strings)
                    $invoiceDataForPdf = $this->prepareInvoiceDataForPdf($variables);
                    $pdfData = $this->generatePdfAttachment($owner, $invoiceDataForPdf, $templateType, $month);
                    if ($pdfData) {
                        $data['attachments'] = [$pdfData];
                    }
                } else {
                    $this->warn("No owner data available for PDF generation");
                }
            }

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
                // Get all properties owned by this owner
                $propertyIds = \App\Models\Property::where('added_by', $owner->id)
                    ->where('property_classification', 5)
                    ->pluck('id');

                // Get hotel room IDs for this owner's properties
                $hotelRoomIds = \App\Models\HotelRoom::whereIn('property_id', $propertyIds)->pluck('id');

                // Get reservations for properties and hotel rooms
                $query->where(function ($q) use ($propertyIds) {
                    $q->where('reservable_type', 'App\\Models\\Property')
                        ->whereIn('reservable_id', $propertyIds);
                })->orWhere(function ($q) use ($hotelRoomIds) {
                    $q->where('reservable_type', 'App\\Models\\HotelRoom')
                        ->whereIn('reservable_id', $hotelRoomIds);
                });
            })
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->with(['reservable', 'customer'])
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
            $variables = $this->extractVariablesFromService($owner, $reservations, $monthYearDisplay, $templateType);
            $variables['owner'] = $owner; // Add owner object for PDF generation
            return $variables;

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
        $totalRevenue = (float) $reservations->sum('total_price');

        // Calculate property taxes (same as MonthlyTaxInvoiceService)
        $serviceChargeRate = (float) (system_setting('hotel_service_charge_rate') ?? 10);
        $salesTaxRate = (float) (system_setting('hotel_sales_tax_rate') ?? 14);
        $cityTaxRate = (float) (system_setting('hotel_city_tax_rate') ?? 5);

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
     * @param array $variables
     * @return string
     */
    private function generateBankAccountDetailsHtml($variables = [])
    {
        // Get bank account details from system settings or use defaults (matching MonthlyTaxInvoiceService)
        $bankName = system_setting('bank_name') ?? 'National Bank of Egypt';
        $accountNumber = system_setting('bank_account_number') ?? '3413131856116201017';
        $routingNumber = system_setting('bank_routing_number') ?? '';
        $swiftCode = system_setting('bank_swift_code') ?? 'NBEGEGCX341';
        $iban = system_setting('bank_iban') ?? 'EG100003034131318561162010170';
        $accountHolder = 'As Home for Asset Management'; // Always use this value

        // Get commission amount and currency from variables
        $commissionAmount = $variables['commission_amount'] ?? '0.00';
        $currencySymbol = $variables['currency_symbol'] ?? 'EGP';

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;background:#ffffff;border:1px solid #e9ecef;border-radius:8px;padding:16px;margin:0 0 16px 0;">';
        $html .= '<h3 style="margin:0 0 8px 0;color:#0d6efd;">Bank Details</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;">';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:30%;"><strong>Bank Name</strong></td><td style="padding:8px;border:1px solid #e9ecef;">' . e($bankName) . '</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Branch</strong></td><td style="padding:8px;border:1px solid #e9ecef;">Hurghada Branch</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Bank Address</strong></td><td style="padding:8px;border:1px solid #e9ecef;">EL Kawthar Hurghada Branch</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Currency</strong></td><td style="padding:8px;border:1px solid #e9ecef;">EGP</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Swift Code</strong></td><td style="padding:8px;border:1px solid #e9ecef;">' . e($swiftCode) . '</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Account No.</strong></td><td style="padding:8px;border:1px solid #e9ecef;">' . e($accountNumber) . '</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Beneficiary Name</strong></td><td style="padding:8px;border:1px solid #e9ecef;">' . e($accountHolder) . '</td></tr>';
        $html .= '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>IBAN</strong></td><td style="padding:8px;border:1px solid #e9ecef;">' . e($iban) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Add important notes section
        $html .= '<div style="font-family:Segoe UI,Arial,sans-serif;background:#fff7e6;border:1px solid #ffe8cc;border-radius:8px;padding:16px;margin:0 0 16px 0;">';
        $html .= '<h4 style="margin:0 0 8px 0;color:#d48806;">IMPORTANT NOTES</h4>';
        $html .= '<ul style="margin:0 0 0 18px;padding:0;">';
        $html .= '<li>This invoice covers all flexible hotel bookings for the month of ' . e($variables['month_year'] ?? date('F Y')) . '</li>';
        $html .= '<li>Commission has been calculated based on the standard rate of ' . e($variables['commission_rate'] ?? 15) . '%</li>';
        $html .= '<li>All amounts are in ' . e($currencySymbol) . '</li>';
        $html .= '<li>Please transfer the commission amount (' . e($currencySymbol) . ' ' . e($commissionAmount) . ') to the provided bank account within 7 days</li>';
        $html .= '<li>Please keep this invoice for your tax records</li>';
        $html .= '<li>For any questions regarding this invoice or payment, please contact our support team</li>';
        $html .= '</ul>';
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

    /**
     * Prepare invoice data for PDF (ensure numeric values, not formatted strings)
     *
     * @param array $variables
     * @return array
     */
    private function prepareInvoiceDataForPdf($variables)
    {
        // Convert formatted strings back to floats for PDF template
        $invoiceData = [
            'total_reservations' => (int)($variables['total_reservations'] ?? 0),
            'total_revenue' => (float)str_replace(',', '', $variables['total_revenue'] ?? 0),
            'currency_symbol' => $variables['currency_symbol'] ?? 'EGP',
            'service_charge_rate' => (float)($variables['service_charge_rate'] ?? 0),
            'service_charge_amount' => (float)str_replace(',', '', $variables['service_charge_amount'] ?? 0),
            'sales_tax_rate' => (float)($variables['sales_tax_rate'] ?? 0),
            'sales_tax_amount' => (float)str_replace(',', '', $variables['sales_tax_amount'] ?? 0),
            'city_tax_rate' => (float)($variables['city_tax_rate'] ?? 0),
            'city_tax_amount' => (float)str_replace(',', '', $variables['city_tax_amount'] ?? 0),
            'total_taxes_amount' => (float)str_replace(',', '', $variables['total_taxes_amount'] ?? 0),
            'revenue_after_taxes' => (float)str_replace(',', '', $variables['revenue_after_taxes'] ?? 0),
            'commission_rate' => (float)($variables['commission_rate'] ?? 0),
            'commission_amount' => (float)str_replace(',', '', $variables['commission_amount'] ?? 0),
            'net_amount' => (float)str_replace(',', '', $variables['net_amount'] ?? 0),
            'reservation_details' => $variables['reservation_details'] ?? '',
            'property_summary' => $variables['property_summary'] ?? '',
        ];

        return $invoiceData;
    }

    /**
     * Generate PDF attachment for the tax invoice
     *
     * @param \App\Models\Customer $owner
     * @param array $invoiceData
     * @param string $templateType
     * @param string $month
     * @return array|null
     */
    private function generatePdfAttachment($owner, $invoiceData, $templateType, $month)
    {
        try {
            $taxInvoiceService = new TaxInvoiceService();
            
            if (!$owner) {
                $this->warn("No owner data available for PDF generation");
                return null;
            }

            // Convert template type for PDF service
            $pdfTemplateType = 'monthly_tax_invoice_hotels_flexible';
            if ($templateType === 'monthly_tax_invoice_hotels_non_refundable') {
                $pdfTemplateType = 'monthly_tax_invoice_hotels_non_refundable';
            }

            // Generate PDF
            $pdf = $taxInvoiceService->generatePDF($owner, $invoiceData, $month, $pdfTemplateType);
            $pdfContent = $pdf->output();
            
            // Generate filename
            $monthYearDisplay = \Carbon\Carbon::parse($month . '-01')->format('Y-m');
            $filename = 'tax_invoice_' . $owner->id . '_' . $monthYearDisplay . '.pdf';
            
            $this->info("📄 PDF generated: {$filename}");
            
            return [
                'content' => $pdfContent,
                'filename' => $filename,
                'mime_type' => 'application/pdf'
            ];
            
        } catch (\Exception $e) {
            $this->error("Error generating PDF attachment: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
}
