<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the flexible hotel booking confirmation email template
        // Replace hardcoded check-in/out times with dynamic variables
        $updatedTemplate = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #27ae60; margin: 0; font-size: 28px;">✅ Hotel Booking Confirmed</h1>
            <p style="color: #7f8c8d; margin: 10px 0 0 0; font-size: 16px;">Your reservation has been confirmed successfully</p>
        </div>
        
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin-bottom: 25px;">
            <p style="margin: 0; color: #155724; font-weight: bold;">✅ Status: Confirmed</p>
            <p style="margin: 5px 0 0 0; color: #155724;">Your booking has been confirmed and your room is now reserved. No further approval is needed.</p>
        </div>
        
        <div style="margin-bottom: 25px;">
            <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">Booking Details</h2>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 15px;">
                <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 18px;">{hotel_name}</h3>
                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Address:</strong> {hotel_address}</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-in Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{check_in_date}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-out Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{check_out_date}</p>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Number of Guests</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{number_of_guests}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Total Price</p>
                    <p style="margin: 5px 0 0 0; color: #27ae60; font-size: 16px; font-weight: bold;">{currency_symbol}{total_price}</p>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Booking ID</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px; font-family: monospace;">#{reservation_id}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Payment Status</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{payment_status}</p>
            </div>
            
            <div style="background-color: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px;">
                <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 16px;">📍 Hotel Address</h3>
                <p style="margin: 0; color: #7f8c8d;">{hotel_address}</p>
            </div>
            
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 25px;">
                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">📋 Important Information</h3>
                <ul style="margin: 0; padding-left: 20px; color: #856404;">
                    <li>Please bring a valid ID for check-in</li>
                    <li>Check-in time: {check_in_time} - Check-out time: {check_out_time}</li>
                    <li>Free cancellation is available as per our flexible policy</li>
                    <li>For any assistance, please contact our support team</li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
            <p style="margin: 0; color: #7f8c8d; font-size: 14px;">We look forward to welcoming you and ensuring you have a pleasant stay.</p>
            <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">If you have any questions or need to make changes to your reservation, please don\'t hesitate to contact us.</p>
            <p style="margin: 10px 0 0 0; color: #7f8c8d; font-size: 14px;">Best regards,<br>The {app_name} Team</p>
        </div>
    </div>
</div>';

        DB::table('settings')
            ->where('type', 'flexible_hotel_booking_confirmation_mail_template')
            ->update([
                'data' => $updatedTemplate,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the previous template with hardcoded times
        $originalTemplate = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #27ae60; margin: 0; font-size: 28px;">✅ Hotel Booking Confirmed</h1>
            <p style="color: #7f8c8d; margin: 10px 0 0 0; font-size: 16px;">Your reservation has been confirmed successfully</p>
        </div>
        
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin-bottom: 25px;">
            <p style="margin: 0; color: #155724; font-weight: bold;">✅ Status: Confirmed</p>
            <p style="margin: 5px 0 0 0; color: #155724;">Your booking has been confirmed and your room is now reserved. No further approval is needed.</p>
        </div>
        
        <div style="margin-bottom: 25px;">
            <h2 style="color: #2c3e50; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">Booking Details</h2>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 15px;">
                <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 18px;">{hotel_name}</h3>
                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Address:</strong> {hotel_address}</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-in Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{check_in_date}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Check-out Date</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{check_out_date}</p>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Number of Guests</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{number_of_guests}</p>
                </div>
                <div style="flex: 1; margin-left: 10px;">
                    <p style="margin: 0; color: #2c3e50; font-weight: bold;">Total Price</p>
                    <p style="margin: 5px 0 0 0; color: #27ae60; font-size: 16px; font-weight: bold;">{currency_symbol}{total_price}</p>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Booking ID</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px; font-family: monospace;">#{reservation_id}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; color: #2c3e50; font-weight: bold;">Payment Status</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 16px;">{payment_status}</p>
            </div>
            
            <div style="background-color: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px;">
                <h3 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 16px;">📍 Hotel Address</h3>
                <p style="margin: 0; color: #7f8c8d;">{hotel_address}</p>
            </div>
            
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 25px;">
                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">📋 Important Information</h3>
                <ul style="margin: 0; padding-left: 20px; color: #856404;">
                    <li>Please bring a valid ID for check-in</li>
                    <li>Check-in time: 3:00 PM - Check-out time: 12:00 PM</li>
                    <li>Free cancellation is available as per our flexible policy</li>
                    <li>For any assistance, please contact our support team</li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
            <p style="margin: 0; color: #7f8c8d; font-size: 14px;">We look forward to welcoming you and ensuring you have a pleasant stay.</p>
            <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">If you have any questions or need to make changes to your reservation, please don\'t hesitate to contact us.</p>
            <p style="margin: 10px 0 0 0; color: #7f8c8d; font-size: 14px;">Best regards,<br>The {app_name} Team</p>
        </div>
    </div>
</div>';

        DB::table('settings')
            ->where('type', 'flexible_hotel_booking_confirmation_mail_template')
            ->update([
                'data' => $originalTemplate,
                'updated_at' => now(),
            ]);
    }
};
