<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddRefundRejectionEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {user_name},

We regret to inform you that your refund request has been declined.

Refund Request Details:
Reservation ID: {reservation_id}
Property: {property_name}
Check-in Date: {check_in_date}
Check-out Date: {check_out_date}
Requested Refund Amount: {currency_symbol}{refund_amount}
Rejection Date: {rejection_date}

Reason for Rejection:
{rejection_reason}

We understand this may be disappointing, and we apologize for any inconvenience this may cause. If you believe this decision was made in error or if you have additional information that might change our assessment, please contact our support team at support@as-home.com.

We value your business and hope to have the opportunity to serve you better in the future.

Best regards,
As-home Asset Management Team';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'refund_rejection_mail_template'],
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
        Setting::where('type', 'refund_rejection_mail_template')->delete();
    }
}
