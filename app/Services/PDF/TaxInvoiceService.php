<?php

declare(strict_types=1);

namespace App\Services\PDF;

use App\Models\Setting;
use App\Models\Customer;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class TaxInvoiceService
{
    /**
     * Generate a PDF tax invoice
     *
     * @param Customer $owner
     * @param array $invoiceData
     * @param string $monthYear
     * @param string $templateType
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePDF(Customer $owner, array $invoiceData, string $monthYear, string $templateType = 'monthly_tax_invoice')
    {
        // Get system settings
        $settings = $this->getSettings();

        // Convert company logo to base64 for embedding in PDF
        $companyLogo = $settings['company_logo'] ?? 'logo.png';
        $logoPath = public_path('assets/images/logo/' . $companyLogo);
        $settings['logo'] = '';
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            // Detect image type based on file extension
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mimeType = 'image/png'; // default
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $mimeType = 'image/jpeg';
            } elseif ($extension === 'png') {
                $mimeType = 'image/png';
            } elseif ($extension === 'gif') {
                $mimeType = 'image/gif';
            } elseif ($extension === 'svg') {
                $mimeType = 'image/svg+xml';
            }
            $settings['logo'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }

        // Format month year for display
        $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('F Y');

        // Use Booking.com-style template for hotel_booking_tax_invoice templates
        $viewTemplate = 'invoices.tax_invoice';
        if (in_array($templateType, ['hotel_booking_tax_invoice', 'hotel_booking_tax_invoice_flexible', 'hotel_booking_tax_invoice_non_refundable'])) {
            $viewTemplate = 'invoices.booking_style_tax_invoice';
        }

        // Generate PDF
        $pdf = PDF::loadView($viewTemplate, [
            'owner' => $owner,
            'invoiceData' => $invoiceData,
            'monthYear' => $monthYear,
            'monthYearDisplay' => $monthYearDisplay,
            'templateType' => $templateType,
            'settings' => $settings
        ]);

        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'isPhpEnabled' => true,
            'chroot' => public_path(),
            'enable-local-file-access' => true
        ]);

        return $pdf;
    }

    /**
     * Generate HTML version of tax invoice
     *
     * @param Customer $owner
     * @param array $invoiceData
     * @param string $monthYear
     * @param string $templateType
     * @return string
     */
    public function generateHTML(Customer $owner, array $invoiceData, string $monthYear, string $templateType = 'monthly_tax_invoice')
    {
        // Get system settings
        $settings = $this->getSettings();

        // Convert company logo to base64 for embedding in HTML
        $companyLogo = $settings['company_logo'] ?? 'logo.png';
        $logoUrl = URL::to('assets/images/logo/' . $companyLogo);
        $settings['logo'] = $logoUrl;

        // Format month year for display
        $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('F Y');

        // Use Booking.com-style template for hotel_booking_tax_invoice templates
        $viewTemplate = 'invoices.tax_invoice';
        if (in_array($templateType, ['hotel_booking_tax_invoice', 'hotel_booking_tax_invoice_flexible', 'hotel_booking_tax_invoice_non_refundable'])) {
            $viewTemplate = 'invoices.booking_style_tax_invoice';
        }

        // Generate HTML directly by rendering the view
        $html = view($viewTemplate, [
            'owner' => $owner,
            'invoiceData' => $invoiceData,
            'monthYear' => $monthYear,
            'monthYearDisplay' => $monthYearDisplay,
            'templateType' => $templateType,
            'settings' => $settings
        ])->render();

        return $html;
    }

    /**
     * Download a PDF tax invoice
     *
     * @param Customer $owner
     * @param array $invoiceData
     * @param string $monthYear
     * @param string $templateType
     * @return Response
     */
    public function downloadPDF(Customer $owner, array $invoiceData, string $monthYear, string $templateType = 'monthly_tax_invoice')
    {
        $pdf = $this->generatePDF($owner, $invoiceData, $monthYear, $templateType);
        return $pdf->download($this->getFileName($owner, $monthYear));
    }

    /**
     * Stream a PDF tax invoice
     *
     * @param Customer $owner
     * @param array $invoiceData
     * @param string $monthYear
     * @param string $templateType
     * @return Response
     */
    public function streamPDF(Customer $owner, array $invoiceData, string $monthYear, string $templateType = 'monthly_tax_invoice')
    {
        $pdf = $this->generatePDF($owner, $invoiceData, $monthYear, $templateType);
        return $pdf->stream($this->getFileName($owner, $monthYear));
    }

    /**
     * Get the encoded PDF for a tax invoice
     *
     * @param Customer $owner
     * @param array $invoiceData
     * @param string $monthYear
     * @param string $templateType
     * @return string The encoded PDF
     */
    public function getEncodedPDF(Customer $owner, array $invoiceData, string $monthYear, string $templateType = 'monthly_tax_invoice'): string
    {
        $pdf = $this->generatePDF($owner, $invoiceData, $monthYear, $templateType);
        return base64_encode($pdf->output());
    }

    /**
     * Get the filename for the PDF tax invoice
     *
     * @param Customer $owner
     * @param string $monthYear
     * @return string
     */
    private function getFileName(Customer $owner, string $monthYear): string
    {
        $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('Y-m');
        return 'tax_invoice_' . $owner->id . '_' . $monthYearDisplay . '.pdf';
    }

    /**
     * Get system settings
     *
     * @return array
     */
    private function getSettings(): array
    {
        $types = [
            'company_name', 
            'company_address', 
            'company_phone', 
            'company_email', 
            'company_logo', 
            'company_tel1', 
            'company_tel2', 
            'currency_symbol', 
            'currency_code',
            'hotel_service_charge_rate',
            'hotel_sales_tax_rate',
            'hotel_city_tax_rate',
            'bank_name',
            'bank_branch',
            'bank_address',
            'bank_account_number',
            'bank_routing_number',
            'bank_swift_code',
            'bank_cif',
            'bank_iban',
            'bank_account_holder'
        ];
        
        $settings = Setting::whereIn('type', $types)->get()->pluck('data', 'type')->toArray();
        
        // Set defaults for missing settings
        $defaults = [
            'company_name' => 'As-Home for Asset Management',
            'company_address' => 'P.O Box 25 – Hurghada, Egypt',
            'company_phone' => 'l M. +2 (0155) 379 7794',
            'company_email' => 'info@as-home.com',
            'company_logo' => 'logo.png',
            'currency_symbol' => 'EGP',
            'currency_code' => 'EGP',
            'hotel_service_charge_rate' => 10,
            'hotel_sales_tax_rate' => 14,
            'hotel_city_tax_rate' => 5,
            'bank_name' => 'National Bank of Egypt',
            'bank_branch' => 'Hurghada Branch',
            'bank_address' => 'EL Kawthar Hurghada Branch',
            'bank_account_number' => '3413131856116201017',
            'bank_routing_number' => '987654321',
            'bank_swift_code' => 'NBEGEGCX341',
            'bank_cif' => '',
            'bank_iban' => 'EG100003034131318561162010170',
            'bank_account_holder' => 'As Home for Asset Management'
        ];
        
        $mergedSettings = array_merge($defaults, $settings);
        
        // CRITICAL: Always enforce "As Home for Asset Management" as Beneficiary Name
        $mergedSettings['bank_account_holder'] = 'As Home for Asset Management';
        
        return $mergedSettings;
    }
}

