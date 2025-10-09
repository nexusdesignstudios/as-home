<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddHotelBookingTaxInvoiceEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {owner_name},

We are pleased to provide your monthly tax invoice for {month_year} for your Hotel Property.

Summary:
- Total Reservations: {total_reservations}
- Total Revenue: {currency_symbol}{total_revenue}
- Commission Rate: {commission_rate}% (Base Tax + 15%)
- Commission Amount: {currency_symbol}{commission_amount}
- Net Amount: {currency_symbol}{net_amount}

Reservation Details:
{reservation_details}

Property Summary:
{property_summary}

As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.

If you have any questions regarding this invoice or your hotel services, please don\'t hesitate to contact our hotel partner support team.

Thank you for your continued partnership.

Best regards,
The {app_name} Team';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'hotel_booking_tax_invoice_mail_template'],
            ['data' => $emailTemplate]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the email template from the settings table
        Setting::where('type', 'hotel_booking_tax_invoice_mail_template')->delete();
    }
}
