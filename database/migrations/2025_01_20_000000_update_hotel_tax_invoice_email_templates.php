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
        // Update flexible email template
        $flexibleTemplate = '<p>We hereby provide your monthly tax invoice for the month of {month_year} for <strong>{property_name}</strong>.</p>
<p>A detailed PDF invoice is attached to this email.</p>
<p>As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.</p>
<p>If you have any Inquiries regarding the tax invoice. Do not hesitate to contact us.</p>
<p>Thanks and Best Regards,</p>';

        Setting::updateOrCreate(
            ['type' => 'hotel_booking_tax_invoice_flexible_mail_template'],
            ['data' => $flexibleTemplate]
        );

        // Update non-refundable email template
        $nonRefundableTemplate = '<p>We hereby provide your monthly tax invoice for the month of {month_year} for <strong>{property_name}</strong>.</p>
<p>A detailed PDF invoice is attached to this email.</p>
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
        // Revert to previous templates (can be left as is or implement if needed)
    }
};


