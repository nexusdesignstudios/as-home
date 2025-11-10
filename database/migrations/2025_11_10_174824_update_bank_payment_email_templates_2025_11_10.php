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
        // Update the bank payment accepted email template
        Setting::updateOrCreate(
            ['type' => 'bank_payment_accepted_mail_template'],
            ['data' => 'Dear {customer_name},

Thank you for submitting your payment receipt for your {package_name} subscription on As-home.

We are pleased to inform you that your payment has been successfully verified and your subscription is now active. 

You can now:

•	Access your dashboard

•	List and manage properties

•	Explore our services based on your selected plan

If you have any questions or need assistance, we are here to help.

Welcome to As-home — we\'re glad to have you with us.

Warm regards,

As-home Team

www.ashome-eg.com']
        );

        // Update the bank payment rejected email template
        Setting::updateOrCreate(
            ['type' => 'bank_payment_rejected_mail_template'],
            ['data' => 'Dear {customer_name},

Thank you for submitting your payment receipt for your {package_name} subscription on As-home.

Unfortunately, we were unable to verify the payment based on the receipt provided. This can happen due to:

•	Blurry or unclear receipt image

•	Missing transaction details

•	Payment not yet processed by the bank

To proceed, kindly upload a clearer receipt or re-transfer the payment if needed.

You can re-upload your receipt by logging into your account:

{receipt_upload_link}

If you believe this message is a mistake, please contact us and we\'ll gladly review it again.

Best regards,

As-home Team

www.as-home.com']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous templates if needed
        // This would require storing the old templates, which we'll skip for now
    }
};
