<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddRefundApprovalEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {customer_name},

We are pleased to inform you that your refund request has been approved.

Refund Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Refund Amount: {currency_symbol}{refund_amount}
- Transaction ID: {transaction_id}

Your refund has been processed and should be reflected in your account within 3-5 business days, depending on your bank\'s processing time.

If you have any questions or need further assistance, please don\'t hesitate to contact our customer support team.

Thank you for your patience and understanding.

Best regards,
The {app_name} Team';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'refund_approval_mail_template'],
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
        Setting::where('type', 'refund_approval_mail_template')->delete();
    }
}
