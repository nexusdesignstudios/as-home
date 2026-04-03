<?php
// Script to safely update the 'hotel_booking_tax_invoice_flexible_mail_template' setting
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$htmlTemplate = '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #eee;">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="vertical-align: top;">
                <div style="font-size: 24px; font-weight: bold; color: #003580;">{app_name}</div>
            </td>
            <td style="vertical-align: top; text-align: right; font-size: 12px; color: #666;">
                <strong>As-Home for Asset Management</strong><br>
                P.O Box 25 – Hurghada, Egypt<br>
                Phone: +2 (0155) 379 7794<br>
                Email: info@as-home-group.com<br>
                Tax Number: 4332 - 1233 - 7598
            </td>
        </tr>
    </table>

    <h2 style="color: #003580; border-bottom: 2px solid #003580; padding-bottom: 10px; margin-top: 0;">Monthly Tax Invoice Summary</h2>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr><td style="padding: 5px; font-weight: bold; width: 150px;">Owner Name:</td><td>{owner_name}</td></tr>
        <tr><td style="padding: 5px; font-weight: bold;">Invoice Month:</td><td>{month_year}</td></tr>
        <tr><td style="padding: 5px; font-weight: bold;">Invoice Number:</td><td>{invoice_number}</td></tr>
        <tr><td style="padding: 5px; font-weight: bold;">Date:</td><td>{invoice_date}</td></tr>
        <tr><td style="padding: 5px; font-weight: bold;">Period:</td><td>{invoice_period}</td></tr>
    </table>

    <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <strong>{property_summary}</strong><br>
        {property_location_link}
    </div>

    <p style="margin-bottom: 20px;">We hereby provide your monthly tax invoice for the month of {month_year}. A detailed PDF invoice is attached to this email.</p>
    
    <div style="text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 4px; margin-bottom: 30px; text-transform: uppercase;">TAX INVOICE</div>

    <div style="margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #003580;">Invoice Breakdown</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr><th style="text-align: left; padding: 8px; border-bottom: 2px solid #eee;">Description</th><th style="text-align: right; padding: 8px; border-bottom: 2px solid #eee;">Amount (EGP)</th></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;">Total Revenue</td><td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">{total_revenue} {currency_symbol}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;">Commission ({commission_rate}%)</td><td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">{commission_amount} {currency_symbol}</td></tr>
            <tr style="font-weight: bold; background-color: #f8f9fa;"><td style="padding: 8px;">Total Amount Due</td><td style="text-align: right; padding: 8px;">{commission_amount} {currency_symbol}</td></tr>
        </table>
    </div>

    <div style="margin-top: 30px; padding: 20px; background-color: #f1f3f5; border-radius: 5px;">
        <h4 style="margin-top: 0; color: #495057;">Payment Details</h4>
        <p><strong>Payment Due Date:</strong> {payment_due_date}</p>
        <p>Please transfer the due amount to our bank account below by the Payment Due date. Be sure to include INVOICE {invoice_number} and ACCOMMODATION NUMBER {payment_code} with your payment instructions.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr><td style="padding: 5px; font-weight: bold; width: 150px; border-bottom: 1px solid #ddd;">Bank Name</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">National Bank of Egypt</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Branch</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">Hurghada Branch</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Bank Address</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">EL Kawthar Hurghada Branch</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Currency</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">EGP</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Swift Code</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">NBEGEGCX341</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Account No.</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">3413131856116201017</td></tr>
            <tr><td style="padding: 5px; font-weight: bold; border-bottom: 1px solid #ddd;">Beneficiary Name</td><td style="padding: 5px; border-bottom: 1px solid #ddd;">As Home for Asset Management</td></tr>
            <tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">IBAN</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">EG100003034131318561162010170</td></tr>
        </table>
        
        <p style="margin-top: 15px;"><strong>Payment Code:</strong> {payment_code}</p>
        <p style="background-color: #eee; padding: 10px; font-weight: bold; text-align: center; border-radius: 3px;">PLEASE NOTIFY YOUR BANKTELLER THAT THIS IS A VIRTUAL ACCOUNT NUMBER</p>
    </div>

    <div style="margin-top: 30px; padding: 15px; background-color: #fff3cd; border-left: 5px solid #ffc107; border-radius: 3px; font-size: 11px;">
        <strong>Important Note:</strong> OUR INVOICES ARE BASED ON DEPARTURE DATE AND NOT ON ARRIVAL DATE. Please transfer the commission amount ({commission_amount} {currency_symbol}) within 7 days.
    </div>

    <div style="margin-top: 30px; font-size: 11px; color: #999; text-align: center;">
        As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.<br>
        If you have any inquiries, please contact our support team at info@as-home-group.com.
    </div>
</div>';

$updated = DB::table('settings')
    ->where('type', 'hotel_booking_tax_invoice_flexible_mail_template')
    ->update(['data' => $htmlTemplate, 'updated_at' => now()]);

if ($updated) {
    echo "SUCCESS: Updated email template with ALL requested sections and formatting.\n";
} else {
    echo "ERROR: Could not update setting.\n";
}
