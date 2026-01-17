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
        // Insert the flexible hotel booking confirmation email template
        DB::table('settings')->insert([
            'type' => 'flexible_hotel_booking_confirmation_mail_template',
            'data' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #27ae60; margin: 0; font-size: 28px;">✅ Hotel Booking Confirmed</h1>
            <p style="color: #7f8c8d; margin: 10px 0 0 0; font-size: 16px;">Your reservation has been confirmed successfully</p>
        </div>
        
        <div style="background-color: #e8f5e8; border-left: 4px solid #27ae60; padding: 15px; margin-bottom: 25px;">
            <p style="margin: 0; color: #27ae60; font-weight: bold;">✓ Status: Confirmed</p>
            <p style="margin: 5px 0 0 0; color: #27ae60;">Your booking has been confirmed and your room is reserved.</p>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 20px;">Booking Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Reservation ID:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">#{{reservation_id}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Hotel:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">{{hotel_name}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Room Number:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">{{room_number}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Check-in Date:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">{{check_in_date}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Check-out Date:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">{{check_out_date}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Number of Guests:</td>
                    <td style="padding: 8px 0; color: #333; border-bottom: 1px solid #eee;">{{number_of_guests}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555; border-bottom: 1px solid #eee;">Total Amount:</td>
                    <td style="padding: 8px 0; color: #333; font-weight: bold; border-bottom: 1px solid #eee;">{{total_price}} {{currency_symbol}}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #555;">Payment Status:</td>
                    <td style="padding: 8px 0; color: #333;"><span style="background-color: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">{{payment_status}}</span></td>
                </tr>
            </table>
        </div>

        <div style="background-color: #e8f5e8; border-left: 4px solid #27ae60; padding: 15px; margin-bottom: 25px;">
            <h4 style="color: #27ae60; margin: 0 0 10px 0;">📍 Hotel Address</h4>
            <p style="margin: 0; color: #555;">{{hotel_address}}</p>
        </div>

        {{special_requests}}

        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 25px;">
            <h4 style="color: #856404; margin: 0 0 10px 0;">📋 Important Information</h4>
            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                <li>Please bring a valid ID for check-in</li>
                <li>Check-in time: 3:00 PM - Check-out time: 12:00 PM</li>
                <li>Free cancellation is available as per our flexible policy</li>
                <li>For any assistance, please contact our support team</li>
            </ul>
        </div>

        <p style="font-size: 16px; color: #333333; margin-bottom: 20px;">We look forward to welcoming you and ensuring you have a pleasant stay.</p>

        <p style="font-size: 16px; color: #333333; margin-bottom: 20px;">If you have any questions or need to make changes to your reservation, please don\'t hesitate to contact us.</p>

        <p style="font-size: 16px; color: #333333;">Best regards,<br>
        <strong>The {{app_name}} Team</strong></p>
    </div>
</div>',
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
        DB::table('settings')
            ->where('type', 'flexible_hotel_booking_confirmation_mail_template')
            ->delete();
    }
};