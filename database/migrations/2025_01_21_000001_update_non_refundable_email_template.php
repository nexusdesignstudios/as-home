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
        // Update non-refundable email template
        $nonRefundableTemplate = '<p>We hereby provide your monthly tax invoice for the month of {month_year} for <strong>Non-Refundable Reservations</strong>.</p>
<p>This email confirms a non-refundable reservation made through AS Home for Asset Management\'s booking system.</p>
<p>The guest has completed full payment through our secure payment gateway.</p>
<p><strong>Reservation Details:</strong></p>
{reservation_details}
<p><strong>Property Summary:</strong></p>
{property_summary}
<p><strong>Hotel Commission (85% of Revenue After Taxes):</strong> {currency_symbol}{hotel_amount}</p>
<p><strong>Note:</strong> The As-home team will send the hotel commission ({currency_symbol}{hotel_amount}) to your account.</p>
<p>As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.</p>
<p>If you have any Inquiries regarding the tax invoice. Do not hesitate to contact us.</p>
<p>Thanks and Best Regards,</p>';

        Setting::updateOrCreate(
            ['type' => 'hotel_booking_tax_invoice_non_refundable_mail_template'],
            ['data' => $nonRefundableTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous template if needed
    }
};

