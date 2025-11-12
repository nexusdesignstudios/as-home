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
        // Add the subscription success email template
        Setting::updateOrCreate(
            ['type' => 'subscription_success_mail_template'],
            ['data' => 'Dear {customer_name},

Thank you for your subscription to {package_name} on As-home!

We are pleased to confirm that your payment has been successfully processed and your subscription is now active.

Subscription Details:
• Package: {package_name}
• Start Date: {start_date}
• End Date: {end_date}
• Amount Paid: {amount_paid}

You can now:
• Access your dashboard
• List and manage properties
• Explore all features included in your plan

If you have any questions or need assistance, please don\'t hesitate to contact our support team.

Welcome to As-home — we\'re excited to have you with us!

Best regards,
As-home Team

www.ashome-eg.com']
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('type', 'subscription_success_mail_template')->delete();
    }
};

