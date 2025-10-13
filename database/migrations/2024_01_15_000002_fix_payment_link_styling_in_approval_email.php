<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the reservation approval payment email template with proper button styling
        $updatedTemplate = 'Dear {user_name},

We are pleased to inform you that your reservation has been approved!

To secure your booking, please complete your payment using the button below:

<div style="text-align: center; margin: 20px 0;">
    <a href="{payment_link}" style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Complete Payment</a>
</div>

Alternatively, you can complete your payment through your As-home dashboard.

Your reservation will be fully confirmed once the payment is successfully processed.
Thank you for choosing As-home. We look forward to hosting you soon!

Warm regards,
As-home Asset Management Team';

        DB::table('settings')
            ->where('type', 'reservation_approval_payment_mail_template')
            ->update(['data' => $updatedTemplate]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original template
        $originalTemplate = 'Dear {user_name},

Your reservation has been approved! Please complete your payment to confirm your booking.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Price: {currency_symbol}{total_price}
- Payment Status: {payment_status}
- Special Requests: {special_requests}

Please complete your payment using the link below:

<a href="{payment_link}">Complete Payment</a>

Your reservation will be confirmed once payment is completed.

Thank you for choosing our service!

Best regards,
The Team';

        DB::table('settings')
            ->where('type', 'reservation_approval_payment_mail_template')
            ->update(['data' => $originalTemplate]);
    }
};
