<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDF\TaxInvoiceService;
use App\Models\Customer;
use Carbon\Carbon;

class TaxInvoiceController extends Controller
{
    /**
     * Test PDF generation for tax invoice
     */
    public function testPdf(Request $request)
    {
        try {
            // Create a test owner
            $testOwner = new Customer();
            $testOwner->id = 999;
            $testOwner->name = 'Test Property Owner';
            $testOwner->email = 'testowner@example.com';
            $testOwner->mobile = '+1234567890';

            // Test invoice data
            $invoiceData = [
                'app_name' => env('APP_NAME', 'As-home'),
                'owner_name' => 'Test Property Owner',
                'month_year' => 'January 2025',
                'total_reservations' => '8',
                'total_revenue' => '4250.00',
                'currency_symbol' => 'EGP',
                'service_charge_rate' => 10,
                'service_charge_amount' => '425.00',
                'sales_tax_rate' => 14,
                'sales_tax_amount' => '595.00',
                'city_tax_rate' => 5,
                'city_tax_amount' => '212.50',
                'total_taxes_amount' => '1232.50',
                'revenue_after_taxes' => '3017.50',
                'commission_rate' => 15,
                'commission_amount' => '452.63',
                'net_amount' => '2564.87',
                'reservation_details' => $this->generateTestReservationDetails(),
                'property_summary' => $this->generateTestPropertySummary(),
            ];

            $taxInvoiceService = new TaxInvoiceService();
            $monthYear = '2025-01';
            $templateType = 'monthly_tax_invoice_hotels_flexible';

            // Generate PDF
            $pdf = $taxInvoiceService->generatePDF($testOwner, $invoiceData, $monthYear, $templateType);
            
            return $pdf->stream('test_tax_invoice.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate test reservation details HTML
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
            </tbody>
        </table>';
    }

    /**
     * Generate test property summary HTML
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
}

