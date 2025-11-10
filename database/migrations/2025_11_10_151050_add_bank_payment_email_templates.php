<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the bank payment accepted email template
        Setting::create([
            'type' => 'bank_payment_accepted_mail_template',
            'data' => 'Dear {user_name},

We are pleased to inform you that your bank transfer payment has been accepted.

Payment Details:
- Package: {package_name}
- Amount: {amount} {currency_symbol}
- Transaction ID: {transaction_id}

Your subscription has started on {subscription_start_date}. You can now enjoy all the features of your selected package.

Thank you for your payment!

Best regards,
{app_name} Team'
        ]);

        // Add the bank payment rejected email template
        Setting::create([
            'type' => 'bank_payment_rejected_mail_template',
            'data' => 'Dear {user_name},

We regret to inform you that your bank transfer payment has been rejected.

Payment Details:
- Package: {package_name}
- Amount: {amount} {currency_symbol}
- Transaction ID: {transaction_id}
- Rejection Reason: {reject_reason}

Please review the rejection reason and contact our support team if you have any questions or need assistance with resubmitting your payment.

Best regards,
{app_name} Team'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('type', 'bank_payment_accepted_mail_template')->delete();
        Setting::where('type', 'bank_payment_rejected_mail_template')->delete();
    }
};
