<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class UpdateRefundApprovalEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = 'Dear {user_name},
We\'re writing to confirm that your refund for the cancelled reservation has been successfully processed.

Refund Details
Reservation ID: {reservation_id}
Property: {property_name}
Check-in Date: {check_in_date}
Check-out Date: {check_out_date}
Refund Amount: {currency_symbol}{refund_amount}
Refund Method: {refund_method}
Refund Date: {refund_date}

Please note that depending on your payment provider or bank, it may take up to {refund_processing_time} business days for the refunded amount to appear in your account.

We appreciate your patience and understanding. If you have any questions regarding your refund, feel free to contact our support team at support@as-home.com.

Thank you for choosing As-home. We hope to have the opportunity to host you again soon at one of our vacation homes or hotel stays.

Warm regards,
As-home Asset Management Team';

        // Update the email template in the settings table
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
        // Revert to the old template
        $oldEmailTemplate = 'Dear {customer_name},

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

        Setting::updateOrCreate(
            ['type' => 'refund_approval_mail_template'],
            ['data' => $oldEmailTemplate]
        );
    }
}
