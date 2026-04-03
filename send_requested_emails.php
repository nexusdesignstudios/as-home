<?php
// Script to send the three requested tax invoice email types to nexlancer.eg@gmail.com
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HelperService;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Property;
use Carbon\Carbon;

$targetEmail = 'nexlancer.eg@gmail.com';
echo "Preparing to send 3 email types to $targetEmail\n";

$monthDisplay = Carbon::now()->subMonth()->format('F Y');
$currency = system_setting('currency_symbol') ?? 'EGP';

// Generic variables that most templates use
$baseVariables = [
    'app_name' => env('APP_NAME', 'As-home'),
    'owner_name' => 'Nexus Lancer',
    'month_year' => $monthDisplay,
    'total_reservations' => '5',
    'total_revenue' => '10,000.00',
    'currency_symbol' => $currency,
    'service_charge_rate' => '10',
    'service_charge_amount' => '1,000.00',
    'sales_tax_rate' => '14',
    'sales_tax_amount' => '1,400.00',
    'city_tax_rate' => '5',
    'city_tax_amount' => '500.00',
    'total_taxes_amount' => '2,900.00',
    'revenue_after_taxes' => '7,100.00',
    'commission_rate' => '15',
    'commission_amount' => '1,065.00',
    'net_amount' => '6,035.00',
    'hotel_rate' => '85',
    'hotel_amount' => '6,035.00',
];

// Sample HTML for reservation details
$reservationDetailsHtml = '
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background-color: #f8f9fa;">
            <th style="border: 1px solid #ddd; padding: 8px;">ID</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Property</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Check-out</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px;">#1001</td>
            <td style="border: 1px solid #ddd; padding: 8px;">Grand Hotel - Room 202</td>
            <td style="border: 1px solid #ddd; padding: 8px;">2026-03-15</td>
            <td style="border: 1px solid #ddd; padding: 8px;">Confirmed</td>
            <td style="border: 1px solid #ddd; padding: 8px;">' . $currency . ' 2,000.00</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px;">#1002</td>
            <td style="border: 1px solid #ddd; padding: 8px;">Sea View Suite</td>
            <td style="border: 1px solid #ddd; padding: 8px;">2026-03-20</td>
            <td style="border: 1px solid #ddd; padding: 8px;">Confirmed</td>
            <td style="border: 1px solid #ddd; padding: 8px;">' . $currency . ' 3,000.00</td>
        </tr>
    </tbody>
</table>';

$propertySummaryHtml = '
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f8f9fa;">
            <th style="border: 1px solid #ddd; padding: 8px;">Property</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Bookings</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Revenue</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px;">Grand Hotel</td>
            <td style="border: 1px solid #ddd; padding: 8px;">3</td>
            <td style="border: 1px solid #ddd; padding: 8px;">' . $currency . ' 6,000.00</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px;">Luxury Apartments</td>
            <td style="border: 1px solid #ddd; padding: 8px;">2</td>
            <td style="border: 1px solid #ddd; padding: 8px;">' . $currency . ' 4,000.00</td>
        </tr>
    </tbody>
</table>';

$bankDetailsHtml = '
<div style="background: #f1f8ff; padding: 15px; border: 1px solid #cce5ff; border-radius: 5px;">
    <h4 style="margin-top:0">Bank Transfer Details</h4>
    <p>Please transfer the commission amount to:</p>
    <strong>Bank:</strong> National Bank of Egypt<br>
    <strong>Account:</strong> 3413131856116201017<br>
    <strong>IBAN:</strong> EG100003034131318561162010170<br>
    <strong>Swift:</strong> NBEGEGCX341
</div>';

$baseVariables['reservation_details'] = $reservationDetailsHtml;
$baseVariables['property_summary'] = $propertySummaryHtml;
$baseVariables['bank_account_details'] = $bankDetailsHtml;

$emailTypes = [
    'monthly_tax_invoice_hotels_flexible' => '46 - Monthly Tax Invoice (Hotels Flexible)',
    'hotel_booking_tax_invoice' => '47 - Hotel Booking Tax Invoice',
    'hotel_booking_tax_invoice_flexible' => '48 - Hotel Booking Tax Invoice - Flexible (Manual/Cash)'
];

foreach ($emailTypes as $type => $label) {
    try {
        echo "Sending $label...\n";
        
        $emailTypeData = HelperService::getEmailTemplatesTypes($type);
        if (!$emailTypeData) {
            echo "Error: Type $type not found in HelperService\n";
            continue;
        }

        $templateContent = system_setting($emailTypeData['type']);
        if (empty($templateContent)) {
            echo "Warning: Template content for $type is empty in settings. Using a placeholder.\n";
            $templateContent = "Dear {owner_name},<br><br>This is a test for {title}.<br><br>{reservation_details}<br><br>{bank_account_details}";
        }

        $processedContent = HelperService::replaceEmailVariables($templateContent, array_merge($baseVariables, ['title' => $emailTypeData['title']]));

        $data = [
            'email' => $targetEmail,
            'title' => $emailTypeData['title'] . " - TEST",
            'email_template' => $processedContent,
        ];

        HelperService::sendMail($data);
        echo "Success: $label sent.\n";
    } catch (\Exception $e) {
        echo "Failed: $label. Error: " . $e->getMessage() . "\n";
    }
}

echo "\nAll requests processed.\n";
