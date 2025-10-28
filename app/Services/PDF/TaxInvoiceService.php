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
            $settings['logo'] = 'data:image/png;base64,' . base64_encode($imageData);
        }

        // Format month year for display
        $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('F Y');

        // Generate PDF
        $pdf = PDF::loadView('invoices.tax_invoice', [
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
            'defaultFont' => 'Arial'
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

        // Generate HTML directly by rendering the view
        $html = view('invoices.tax_invoice', [
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
            'bank_account_number',
            'bank_routing_number',
            'bank_swift_code',
            'bank_account_holder'
        ];
        
        $settings = Setting::whereIn('type', $types)->get()->pluck('data', 'type')->toArray();
        
        // Set defaults for missing settings
        $defaults = [
            'company_name' => 'As-home',
            'company_address' => '123 Business Street, City, Country',
            'company_phone' => '+1-234-567-8900',
            'company_email' => 'info@as-home.com',
            'company_logo' => 'logo.png',
            'currency_symbol' => 'EGP',
            'currency_code' => 'EGP',
            'hotel_service_charge_rate' => 10,
            'hotel_sales_tax_rate' => 14,
            'hotel_city_tax_rate' => 5,
            'bank_name' => 'As-home Bank',
            'bank_account_number' => '1234567890',
            'bank_routing_number' => '987654321',
            'bank_swift_code' => 'ASHOMEXX',
            'bank_account_holder' => 'As-home Group'
        ];
        
        return array_merge($defaults, $settings);
    }
}

