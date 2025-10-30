<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ config('app.name') }} - Monthly Tax Invoice</title>
</head>
<body style="margin:0;padding:0;background:#f9f9f9;color:#444;font-family:Segoe UI, Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f9f9f9;padding:16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:720px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:20px 24px;background:#ffffff;border-bottom:1px solid #e5e7eb;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if(!empty($app_logo))
                                            <img src="{{ $app_logo }}" alt="{{ $app_name ?? config('app.name') }}" style="max-height:32px;display:inline-block;"/>
                                        @endif
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <div style="font-size:22px;font-weight:700;color:#111827;">TAX INVOICE</div>
                                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">Date: {{ $invoice_date }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Invoice Information -->
                    <tr>
                        <td style="padding:20px 24px;background:#ffffff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Accommodation Number</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $accommodation_number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">VAT Number</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $vat_number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Invoice Number</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Invoice Date</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $invoice_date }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;font-weight:600;color:#374151;">Invoice Period</td>
                                    <td style="padding:12px 14px;color:#111827;">{{ $invoice_period_start }} - {{ $invoice_period_end }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Reservation & Owner Overview -->
                    <tr>
                        <td style="padding:6px 24px;">
                            <div style="font-size:14px;font-weight:700;color:#111827;margin:8px 0 6px;">Reservation & Property Overview</div>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Reservations</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $reservations_count ?? $total_reservations }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Property Title(s)</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $property_title_list ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Property Address(es)</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $property_address_list ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Owner</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $owner_full_name ?? '' }} ({{ $owner_email ?? '' }})</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Invoice Summary Table -->
                    <tr>
                        <td style="padding:6px 24px;">
                            <div style="font-size:14px;font-weight:700;color:#111827;margin:8px 0 6px;">Invoice Summary</div>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <thead>
                                    <tr style="background:#f3f4f6;">
                                        <th align="left" style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#374151;font-size:13px;">Description</th>
                                        <th align="right" style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#374151;font-size:13px;">Amount ({{ $currency_symbol }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">Room Sales</td>
                                        <td align="right" style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $room_sales }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">Commission</td>
                                        <td align="right" style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">{{ $commission_amount }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#111827;font-weight:700;">Total Amount Due</td>
                                        <td align="right" style="padding:12px 14px;color:#111827;font-weight:700;">{{ $total_due }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Payment Details -->
                    <tr>
                        <td style="padding:12px 24px;">
                            <div style="font-size:14px;font-weight:700;color:#111827;margin:8px 0 6px;">Payment Details</div>
                            <div style="font-size:13px;color:#111827;margin:4px 0 12px;">Payment Due Date: <strong>{{ $payment_due_date }}</strong></div>
                            <div style="font-size:13px;color:#374151;line-height:1.6;">
                                Please transfer the total amount due to the bank account below by the payment due date. Include <strong>INVOICE {{ $invoice_number }}</strong> and <strong>ACCOMMODATION NUMBER {{ $accommodation_number }}</strong> in your payment instructions.
                            </div>
                        </td>
                    </tr>

                    <!-- Bank Information -->
                    <tr>
                        <td style="padding:12px 24px;">
                            <div style="font-size:14px;font-weight:700;color:#111827;margin:8px 0 6px;">Bank Information</div>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;width:35%;font-weight:600;color:#374151;">Bank Name</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">National Bank of Egypt</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Branch</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">Hurghada Branch</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Address</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">El Kawthar, Hurghada, Egypt</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Swift Code</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">NBEGEGCX341</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Account Number</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">3413131856116201017</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">IBAN</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">EG100003034131318561162010170</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Beneficiary Name</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">As Home for Asset Management</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;">Currency</td>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#111827;">EGP</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f3f4f6;font-weight:600;color:#374151;">Payment Code</td>
                                    <td style="padding:12px 14px;color:#111827;">{{ $accommodation_number }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 24px;">
                            <div style="font-size:13px;color:#444;line-height:1.7;margin-bottom:12px;">
                                • This invoice includes all flexible hotel bookings for the specified period.<br/>
                                • Commission rate: {{ $commission_rate }}%<br/>
                                • Support: support@{{ $app_domain ?? 'ashom-eg.com' }}
                            </div>
                            <div style="font-size:13px;color:#111827;margin-bottom:8px;">Thank you for your continued partnership with {{ $app_name ?? config('app.name') }}.</div>
                            <div style="font-size:12px;color:#6b7280;">This is an automated invoice generated by the {{ $app_name ?? config('app.name') }} system.</div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>


