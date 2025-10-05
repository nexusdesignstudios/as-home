<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert the send money payment email template
        DB::table('settings')->insert([
            'type' => 'send_money_payment_mail_template',
            'data' => 'Dear {sender_name},

You have initiated a send money transaction to {recipient_name}.

Transaction Details:
- Transaction ID: {transaction_id}
- Amount: {currency_symbol}{amount}
- Recipient: {recipient_name}
- Notes: {notes}

Please complete your payment using the link below:

<a href="{payment_link}">Complete Payment</a>

Your transaction will be processed once payment is completed.

Best regards,
The Team',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the send money payment email template
        DB::table('settings')->where('type', 'send_money_payment_mail_template')->delete();
    }
};
