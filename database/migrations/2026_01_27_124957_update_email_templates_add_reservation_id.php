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
        $setting = Setting::where('type', 'payment_form_submission_mail_template')->first();

        if ($setting) {
            $setting->update([
                'data' => 'Dear {property_owner_name},

A new payment form submission has been received for your property "{property_name}".

Customer Details:
- Name: {customer_name}
- Email: {customer_email}
- Phone: {customer_phone}

Property Details:
- Property: {property_name}
- Address: {property_address}
- Room Type: {room_type}

Booking Details:
- Booking ID: #{reservation_id}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {total_amount} {currency_symbol}

Payment Details:
- Card Number: {card_number_masked}
- Special Requests: {special_requests}

Submission Date: {submission_date}

Please review this submission and take appropriate action.

Best regards,
{app_name} Team'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $setting = Setting::where('type', 'payment_form_submission_mail_template')->first();

        if ($setting) {
            $setting->update([
                'data' => 'Dear {property_owner_name},

A new payment form submission has been received for your property "{property_name}".

Customer Details:
- Name: {customer_name}
- Email: {customer_email}
- Phone: {customer_phone}

Property Details:
- Property: {property_name}
- Address: {property_address}
- Room Type: {room_type}

Booking Details:
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {total_amount} {currency_symbol}

Payment Details:
- Card Number: {card_number_masked}
- Special Requests: {special_requests}

Submission Date: {submission_date}

Please review this submission and take appropriate action.

Best regards,
{app_name} Team'
            ]);
        }
    }
};
