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
                'data' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #ffffff;">
    <h2 style="color: #333333; margin-bottom: 20px; text-align: center;">New Payment Form Submission</h2>
    
    <p style="color: #555555; font-size: 16px; line-height: 1.5;">Dear <strong>{property_owner_name}</strong>,</p>
    
    <p style="color: #555555; font-size: 16px; line-height: 1.5;">A new payment form submission has been received for your property "<strong>{property_name}</strong>".</p>
    
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-top: 0;">Customer Details</h3>
        <p style="margin: 8px 0;"><strong>Name:</strong> {customer_name}</p>
        <p style="margin: 8px 0;"><strong>Email:</strong> <a href="mailto:{customer_email}" style="color: #007bff; text-decoration: none;">{customer_email}</a></p>
        <p style="margin: 8px 0;"><strong>Phone:</strong> {customer_phone}</p>
    </div>

    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-top: 0;">Property Details</h3>
        <p style="margin: 8px 0;"><strong>Property:</strong> {property_name}</p>
        <p style="margin: 8px 0;"><strong>Address:</strong> {property_address}</p>
        <div style="margin: 8px 0;"><strong>Room Type:</strong> <br>{room_type}</div>
    </div>

    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-top: 0;">Booking Details</h3>
        <p style="margin: 8px 0;"><strong>Booking ID:</strong> <span style="background-color: #e8f0fe; color: #1967d2; padding: 2px 6px; border-radius: 4px; font-weight: bold;">#{reservation_id}</span></p>
        <p style="margin: 8px 0;"><strong>Check-in Date:</strong> {check_in_date}</p>
        <p style="margin: 8px 0;"><strong>Check-out Date:</strong> {check_out_date}</p>
        <p style="margin: 8px 0;"><strong>Number of Guests:</strong> {number_of_guests}</p>
        <p style="margin: 8px 0;"><strong>Total Amount:</strong> {total_amount} {currency_symbol}</p>
    </div>

    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-top: 0;">Payment Details</h3>
        <p style="margin: 8px 0;"><strong>Card Number:</strong> {card_number_masked}</p>
        <p style="margin: 8px 0;"><strong>Special Requests:</strong> {special_requests}</p>
    </div>

    <p style="color: #777777; font-size: 14px; margin-top: 20px; border-top: 1px solid #e0e0e0; padding-top: 10px;">
        Submission Date: {submission_date}
    </p>

    <p style="color: #555555; font-size: 16px; line-height: 1.5;">Please review this submission and take appropriate action.</p>

    <p style="color: #333333; font-weight: bold; margin-top: 30px;">Best regards,<br>{app_name} Team</p>
</div>'
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
};
