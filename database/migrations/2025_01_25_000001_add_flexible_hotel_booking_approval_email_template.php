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
        // Insert the flexible hotel booking approval email template
        DB::table('settings')->insert([
            'type' => 'flexible_hotel_booking_approval_mail_template',
            'data' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2c3e50; margin: 0; font-size: 28px;">Hotel Booking Pending Approval</h1>
            <p style="color: #7f8c8d; margin: 10px 0 0 0; font-size: 16px;">Your booking is waiting for hotel approval</p>
        </div>
        
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 25px;">
            <p style="margin: 0; color: #856404; font-weight: bold;">⏳ Status: Pending Hotel Approval</p>
            <p style="margin: 5px 0 0 0; color: #856404;">Your booking request has been submitted and is currently being reviewed by the hotel management.</p>
        </div>
        
        <div style="margin-bottom: 25px;">
            <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">Booking Details</h2>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 15px;">
                <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 18px;">{{hotel_name}}</h3>
                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Room Type:</strong> {{room_type}}</p>
                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Room Number:</strong> {{room_number}}</p>
                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Address:</strong> {{hotel_address}}</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-in Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{{check_in_date}}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-out Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{{check_out_date}}</p>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Number of Guests</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{{number_of_guests}}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Total Price</p>
                    <p style="margin: 5px 0 0 0; color: #27ae60; font-size: 16px; font-weight: bold;">{{currency_symbol}}{{total_price}}</p>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Booking ID</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px; font-family: monospace;">{{reservation_id}}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Payment Status</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{{payment_status}}</p>
            </div>
            
            {{#if special_requests}}
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Special Requests</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{{special_requests}}</p>
            </div>
            {{/if}}
        </div>
        
        <div style="background-color: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px;">
            <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 16px;">What happens next?</h3>
            <ul style="margin: 0; padding-left: 20px; color: #7f8c8d;">
                <li>The hotel management will review your booking request</li>
                <li>You will receive an email notification once your booking is approved or declined</li>
                <li>If approved, you may need to complete payment (if not already done)</li>
                <li>You will receive a confirmation email with all final details</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
            <p style="margin: 0; color: #7f8c8d; font-size: 14px;">Thank you for choosing {{app_name}}!</p>
            <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">If you have any questions, please contact our support team.</p>
        </div>
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
        // Remove the flexible hotel booking approval email template
        DB::table('settings')->where('type', 'flexible_hotel_booking_approval_mail_template')->delete();
    }
};
