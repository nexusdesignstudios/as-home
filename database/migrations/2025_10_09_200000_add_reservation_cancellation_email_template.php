<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddReservationCancellationEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {customer_name},

We are writing to confirm that your reservation has been cancelled as requested.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Total Amount: {currency_symbol}{total_price}

If you requested a refund, please note that it will be processed according to our refund policy. Depending on your payment method, it may take 3-5 business days for the refund to appear in your account.

If you did not request this cancellation or have any questions, please contact our customer support team immediately.

Thank you for your understanding.

Best regards,
The {app_name} Team';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'reservation_cancellation_mail_template'],
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
        Setting::where('type', 'reservation_cancellation_mail_template')->delete();
    }
}
