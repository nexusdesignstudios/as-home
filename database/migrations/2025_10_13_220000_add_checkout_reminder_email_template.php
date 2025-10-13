<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddCheckoutReminderEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {customer_name},

This is a friendly reminder that your reservation is checking out today.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {currency_symbol}{total_price}

Please ensure you have completed the checkout process and returned any keys or access cards as required.

If you have any questions or need assistance, please don\'t hesitate to contact our support team.

Thank you for choosing As-home. We hope you had a wonderful stay!

Best regards,
As-home Asset Management Team';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'checkout_reminder_mail_template'],
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
        Setting::where('type', 'checkout_reminder_mail_template')->delete();
    }
}
