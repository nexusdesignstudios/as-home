<?php
// Script to verify the COMPREHENSIVE system template with 12%
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Property;
use App\Services\HelperService;
use App\Services\PDF\TaxInvoiceService;
use Carbon\Carbon;

$recipientEmail = 'nexlancer.eg@gmail.com';
$ownerId = 102; 
$monthYear = '2026-03';
$templateType = 'hotel_booking_tax_invoice_flexible';

$owner = Customer::find($ownerId);
if (!$owner) { echo "Owner not found.\n"; exit; }

$resIds = [2959, 2960];
$reservations = Reservation::whereIn('id', $resIds)->get();
if ($reservations->count() === 0) { echo "No reservations found.\n"; exit; }

$totalRevenue = (float) $reservations->sum('total_price');
$serviceChargeRate = 10;
$salesTaxRate = 14;
$cityTaxRate = 5;

$serviceChargeAmount = $totalRevenue * ($serviceChargeRate / 100);
$salesTaxAmount = $totalRevenue * ($salesTaxRate / 100);
$cityTaxAmount = $totalRevenue * ($cityTaxRate / 100);
$totalTaxesAmount = $serviceChargeAmount + $salesTaxAmount + $cityTaxAmount;
$revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;

$property = Property::find(522);
$commissionRate = 12;
$commissionAmount = $revenueAfterTaxes * ($commissionRate / 100);
$netAmount = $revenueAfterTaxes - $commissionAmount;
$currencySymbol = 'EGP';
$monthYearDisplay = 'March 2026';

// Fetch the newly updated system template
$emailTypeData = HelperService::getEmailTemplatesTypes($templateType);
$templateData = system_setting($emailTypeData['type']);

// Map variables for the template placeholders
$variables = [
    'app_name' => 'As-Home',
    'owner_name' => $owner->name,
    'month_year' => $monthYearDisplay,
    'total_reservations' => $reservations->count(),
    'total_revenue' => number_format($totalRevenue, 2),
    'currency_symbol' => $currencySymbol,
    'commission_rate' => $commissionRate,
    'commission_amount' => number_format($commissionAmount, 2),
    'hotel_rate' => (100 - $commissionRate),
    'hotel_amount' => number_format($netAmount, 2),
    'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
    'net_amount' => number_format($netAmount, 2),
    'reservation_details' => 'Detailed list attached in PDF',
    'property_summary' => ($property ? $property->title : "Shellghada Hotel and Beach") . ' - ' . ($property ? $property->address : "Hurghada, Red Sea, Egypt"),
    'property_location_link' => '<a href="https://www.google.com/maps/search/?api=1&query=Shellghada+Hotel+and+Beach,+Old+Sheraton+Road,+Hurghada,+Egypt" target="_blank" style="color: #003580; text-decoration: underline;">Location</a>',
    'invoice_number' => '102-202603-F',
    'invoice_date' => '01/04/2026',
    'invoice_period' => '01/03/2026 - 31/03/2026',
    'payment_due_date' => 'April 15, 2026',
    'payment_code' => (string)$owner->id,
];

$finalBody = HelperService::replaceEmailVariables($templateData, $variables);

$data = [
    'email_template' => $finalBody,
    'email' => $recipientEmail,
    'title' => "Hotel Booking Tax Invoice - Flexible (Manual/Cash)",
];

// PDF Logic
$invoiceData = [
    'month_year' => $monthYearDisplay,
    'reservations' => $reservations,
    'total_revenue' => $totalRevenue,
    'room_sales' => $totalRevenue,
    'service_charge_amount' => $serviceChargeAmount,
    'sales_tax_amount' => $salesTaxAmount,
    'city_tax_amount' => $cityTaxAmount,
    'total_taxes_amount' => $totalTaxesAmount,
    'revenue_after_taxes' => $revenueAfterTaxes,
    'commission_rate' => $commissionRate,
    'commission_amount' => $commissionAmount,
    'total_amount_due' => $commissionAmount,
    'net_amount' => $netAmount,
    'currency_symbol' => $currencySymbol,
    'property_name' => $property->title,
    'property_address' => $property->address,
    'accommodation_number' => $property->id,
    'invoice_number' => $variables['invoice_number'],
];

try {
    $taxInvoiceService = new TaxInvoiceService();
    $pdf = $taxInvoiceService->generatePDF($owner, $invoiceData, $monthYear, $templateType);
    $pdfFile = $pdf->output();
    if ($pdfFile) {
        $pdfName = 'Tax_Invoice_' . str_replace(' ', '_', $owner->name) . '_Mar_2026.pdf';
        $data['attachments'] = [['content' => $pdfFile, 'name' => $pdfName, 'mime' => 'application/pdf']];
    }
} catch (\Exception $e) { echo "PDF Error: " . $e->getMessage() . "\n"; }

try {
    HelperService::sendMail($data);
    echo "SUCCESS: Sent COMPREHENSIVE Tax Invoice correctly.\n";
} catch (\Exception $e) { echo "Send Error: " . $e->getMessage() . "\n"; }
