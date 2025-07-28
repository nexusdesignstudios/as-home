<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add monthly tax invoice email template
        $monthlyTaxInvoiceTemplate = '<p>Dear <strong>{owner_name}</strong>,</p> <p>Please find below your monthly tax invoice for <strong>{month_year}</strong>.</p> <p><strong>Invoice Summary:</strong></p> <ul> <li><strong>Total Reservations:</strong> {total_reservations}</li> <li><strong>Total Revenue:</strong> {currency_symbol} {total_revenue}</li> <li><strong>Commission Rate:</strong> {commission_rate}%</li> <li><strong>Commission Amount:</strong> {currency_symbol} {commission_amount}</li> <li><strong>Net Amount:</strong> {currency_symbol} {net_amount}</li> </ul> <p><strong>Reservation Details:</strong></p> {reservation_details} <p><strong>Property Summary:</strong></p> {property_summary} <p>If you have any questions regarding this invoice, please do not hesitate to contact our support team.</p> <p>Best regards,</p> <p>The <strong>{app_name}</strong> Team</p>';

        Setting::updateOrCreate(
            ['type' => 'monthly_tax_invoice_mail_template'],
            ['data' => $monthlyTaxInvoiceTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the monthly tax invoice email template
        Setting::where('type', 'monthly_tax_invoice_mail_template')->delete();
    }
};
