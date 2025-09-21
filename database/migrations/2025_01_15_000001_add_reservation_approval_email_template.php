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
        // Insert the reservation approval email template
        DB::table('settings')->insert([
            'type' => 'reservation_approval_mail_template',
            'data' => 'Dear {{user_name}},

Your reservation has been approved!

Reservation Details:
- Reservation ID: {{reservation_id}}
- Property: {{property_name}}
- Check-in Date: {{check_in_date}}
- Check-out Date: {{check_out_date}}
- Number of Guests: {{number_of_guests}}
- Total Price: {{currency_symbol}}{{total_price}}
- Payment Status: {{payment_status}}
- Special Requests: {{special_requests}}

Thank you for choosing our service!

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
        // Remove the reservation approval email template
        DB::table('settings')->where('type', 'reservation_approval_mail_template')->delete();
    }
};
