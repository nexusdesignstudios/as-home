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
        $emailTemplate = '<p>Dear <strong>{owner_name}</strong>,</p>
<p>We are pleased to provide your monthly tax invoice for <strong>{month_year}</strong> for your Hotel Property - <strong>Flexible Rate Reservations (Manual/Cash Payments)</strong>.</p>
<p><strong>Summary:</strong></p>
<ul>
<li><strong>Total Reservations:</strong> {total_reservations}</li>
<li><strong>Total Revenue:</strong> {currency_symbol}{total_revenue}</li>
<li><strong>Revenue After Taxes:</strong> {currency_symbol}{revenue_after_taxes}</li>
<li><strong>Commission Rate:</strong> {commission_rate}% (As-home)</li>
<li><strong>Commission Amount:</strong> {currency_symbol}{commission_amount}</li>
<li><strong>Hotel Rate:</strong> {hotel_rate}% (Hotel)</li>
<li><strong>Hotel Amount:</strong> {currency_symbol}{hotel_amount}</li>
<li><strong>Net Amount to Hotel:</strong> {currency_symbol}{net_amount}</li>
</ul>
<p><strong>Reservation Details:</strong></p>
{reservation_details}
<p><strong>Property Summary:</strong></p>
{property_summary}
<p>A detailed PDF invoice is attached to this email.</p>
<p>As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.</p>
<p>If you have any questions regarding this invoice or your hotel services, please don\'t hesitate to contact our hotel partner support team.</p>
<p>Thank you for your continued partnership.</p>
<p>Best regards,<br>
The <strong>{app_name}</strong> Team</p>';

        Setting::updateOrCreate(
            ['type' => 'hotel_booking_tax_invoice_flexible_mail_template'],
            ['data' => $emailTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('type', 'hotel_booking_tax_invoice_flexible_mail_template')->delete();
    }
};

