<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $monthYearDisplay }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
            padding: 20px;
            background-color: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }

        .logo-section {
            flex: 1;
        }

        .logo {
            max-height: 80px;
            max-width: 250px;
            margin-bottom: 10px;
            object-fit: contain;
        }

        .recipient-info {
            margin-top: 10px;
            font-size: 11px;
        }

        .recipient-info strong {
            font-size: 12px;
        }

        .company-info {
            flex: 1;
            text-align: right;
            font-size: 11px;
            line-height: 1.5;
        }

        .company-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .invoice-title-section {
            text-align: center;
            margin: 25px 0 20px 0;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .invoice-details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .invoice-meta {
            flex: 1;
        }

        .invoice-meta-row {
            margin-bottom: 5px;
        }

        .invoice-meta-label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11px;
        }

        .reservations-table th,
        .reservations-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .reservations-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }

        .reservations-table td {
            border-bottom: 1px solid #ddd;
        }

        .reservations-table .amount {
            text-align: right;
        }

        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .total-row td {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .double-underline {
            border-bottom: 3px double #000;
        }

        .payment-section {
            margin-top: 35px;
            font-size: 11px;
        }

        .payment-due {
            margin-bottom: 15px;
            font-weight: bold;
        }

        .payment-instructions {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .bank-details {
            margin-top: 20px;
            line-height: 1.8;
        }

        .bank-details strong {
            display: inline-block;
            width: 130px;
        }

        .bank-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11px;
        }

        .bank-details-table th {
            background-color: #003580;
            color: #ffffff;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #003580;
            width: 40%;
        }

        .bank-details-table td {
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #ddd;
            background-color: #f5f5dc;
            color: #333;
            width: 60%;
        }

        .bank-details-table td.label-cell {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 40%;
        }

        .payment-code {
            margin-top: 15px;
            font-weight: bold;
        }

        .virtual-account-note {
            margin-top: 15px;
            font-weight: bold;
            padding: 10px;
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
            font-size: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Booking.com blue color */
        .booking-blue {
            color: #003580;
        }
    </style>
</head>
<body>
    <!-- Header: Logo (Left) and Company Info (Right) -->
    <div class="header">
        <div class="logo-section">
            @if($settings['logo'])
                <img src="{{ $settings['logo'] }}" alt="Company Logo" class="logo">
            @else
                <div style="font-size: 20px; font-weight: bold; color: #e8c15e;">{{ $settings['company_name'] ?? 'As-Home for Asset Management' }}</div>
            @endif
            
            <div class="recipient-info" style="margin-top: 15px;">
                @if($invoiceData['property_name'] ?? null)
                    <div><strong>{{ $invoiceData['property_name'] }}</strong></div>
                @endif
                @if($invoiceData['property_address'] ?? null)
                    <div>{{ $invoiceData['property_address'] }}</div>
                @endif
                <div>VAT: {{ $invoiceData['property_vat'] ?? '' }}</div>
            </div>
        </div>
        
        <div class="company-info">
            <div class="company-name" style="color: #e8c15e;">{{ $settings['company_name'] ?? 'As-Home for Asset Management' }}</div>
            <div>{{ $settings['company_address'] ?? 'P.O Box 25 – Hurghada, Egypt' }}</div>
            <div>Phone: {{ $settings['company_phone'] ?? 'l M. +2 (0155) 379 7794' }}</div>
            @if($settings['company_email'])
                <div>Email: {{ $settings['company_email'] }}</div>
                <div>Tax Number: 4332 - 1233 - 7598</div>
            @endif
            @if($settings['company_vat_number'] ?? null)
                <div>VAT: {{ $settings['company_vat_number'] }}</div>
            @endif
        </div>
    </div>

    <!-- Invoice Details Row -->
    <div class="invoice-details-row">
        <div class="invoice-meta">
            @if($invoiceData['accommodation_number'] ?? null)
                <div class="invoice-meta-row">
                    <span class="invoice-meta-label">Accommodation number:</span>
                    <span>{{ $invoiceData['accommodation_number'] }}</span>
                </div>
            @endif
            @if($invoiceData['vat_number'] ?? null)
                <div class="invoice-meta-row">
                    <span class="invoice-meta-label">VAT number:</span>
                    <span>{{ $invoiceData['vat_number'] }}</span>
                </div>
            @endif
            <div class="invoice-meta-row">
                <span class="invoice-meta-label">Invoice number:</span>
                <span>{{ $invoiceData['invoice_number'] ?? $invoiceData['accommodation_number'] . '-' . date('Ymd') }}</span>
            </div>
            <div class="invoice-meta-row">
                <span class="invoice-meta-label">Date:</span>
                <span>{{ date('d/m/Y') }}</span>
            </div>
            <div class="invoice-meta-row">
                <span class="invoice-meta-label">Period:</span>
                <span>{{ date('d/m/Y', strtotime($monthYear . '-01')) }} - {{ date('d/m/Y', strtotime($monthYear . '-' . date('t', strtotime($monthYear . '-01')))) }}</span>
            </div>
        </div>
    </div>

    <!-- Invoice Opening Statement -->
    <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #003580; font-size: 12px; line-height: 1.6;">
        @if(strpos($templateType, 'non_refundable') !== false || strpos($templateType, 'non-refundable') !== false)
            <p style="margin: 0;"><strong>We hereby provide your monthly tax invoice for the month of {{ $monthYearDisplay }} for Non-Refundable Reservations.</strong></p>
        @else
            <p style="margin: 0;"><strong>We hereby provide your monthly tax invoice for the month of {{ $monthYearDisplay }} for <span style="color: #003580;">{{ $invoiceData['property_name'] ?? 'Hotel' }}</span>.</strong></p>
        @endif
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title-section">
        <div class="invoice-title">TAX INVOICE</div>
    </div>

    <!-- Invoice Summary Table: Total Revenue, Commission, Total Amount Due -->
    <table class="reservations-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Amount ({{ $invoiceData['currency_symbol'] }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue</td>
                <td class="amount">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['room_sales_raw'] ?? ($invoiceData['room_sales'] ?? $invoiceData['total_revenue']), 2, '.', ',') }}</td>
            </tr>
            @if(strpos($templateType, 'non_refundable') !== false || strpos($templateType, 'non-refundable') !== false)
                {{-- For non-refundable: Show Hotel Commission (85%) --}}
                <tr>
                    <td>Hotel Commission</td>
                    <td class="amount">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['hotel_amount_raw'] ?? ($invoiceData['hotel_amount'] ?? 0), 2, '.', ',') }}</td>
                </tr>
                <tr class="total-row double-underline">
                    <td><strong>Total Amount Due</strong></td>
                    <td class="amount"><strong>{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['hotel_amount_raw'] ?? ($invoiceData['hotel_amount'] ?? 0), 2, '.', ',') }}</strong></td>
                </tr>
            @else
                {{-- For flexible: Show As-home Commission (15%) --}}
                <tr>
                    <td>Commission</td>
                    <td class="amount">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['commission_amount_raw'] ?? $invoiceData['commission_amount'], 2, '.', ',') }}</td>
                </tr>
                <tr class="total-row double-underline">
                    <td><strong>Total Amount Due</strong></td>
                    <td class="amount"><strong>{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['total_amount_due_raw'] ?? ($invoiceData['total_amount_due'] ?? $invoiceData['commission_amount']), 2, '.', ',') }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Payment Information Section -->
    @if(strpos($templateType, 'non_refundable') === false && strpos($templateType, 'non-refundable') === false)
        {{-- Show payment section only for flexible invoices --}}
        <div class="payment-section">
            <div class="payment-due">
                <strong>Payment Due Date:</strong> {{ date('F d, Y', strtotime('+14 days')) }}
            </div>

            <div class="payment-instructions">
                Please transfer the due amount to our bank account below by the Payment Due date. 
                Be sure to include INVOICE {{ $invoiceData['invoice_number'] ?? $invoiceData['accommodation_number'] . '-' . date('Ymd') }} 
                @if($invoiceData['accommodation_number'] ?? null)
                    and ACCOMMODATION NUMBER {{ $invoiceData['accommodation_number'] }}
                @endif
                with your payment instructions.
            </div>

            @if($settings['bank_name'] ?? null)
                <div class="bank-details">
                    <table class="bank-details-table">
                        <tbody>
                            <tr>
                                <td class="label-cell">Bank Name</td>
                                <td>{{ $settings['bank_name'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Branch</td>
                                <td>{{ $settings['bank_branch'] ?? 'Hurghada Branch' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Bank Address</td>
                                <td>{{ $settings['bank_address'] ?? 'EL Kawthar Hurghada Branch' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Currency</td>
                                <td>{{ $settings['currency_symbol'] ?? 'EGP' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Swift Code</td>
                                <td>{{ $settings['bank_swift_code'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Account No.</td>
                                <td>{{ $settings['bank_account_number'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Beneficiary Name</td>
                                <td><span style="color: #e8c15e;">As Home for Asset Management</span></td>
                            </tr>
                            <tr>
                                <td class="label-cell">IBAN</td>
                                <td>{{ $settings['bank_iban'] ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            @if($invoiceData['accommodation_number'] ?? null)
                <div class="payment-code">
                    <strong>Payment Code:</strong> {{ $invoiceData['accommodation_number'] }}
                </div>
            @endif

            @if($settings['bank_name'] ?? null)
                <div class="virtual-account-note">
                    <strong>PLEASE NOTIFY YOUR BANKTELLER THAT THIS IS A VIRTUAL ACCOUNT NUMBER</strong>
                </div>
            @endif
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div style="color: #e8c15e;">{{ $settings['company_name'] ?? 'As-Home for Asset Management' }}</div>
        <div>{{ $settings['company_address'] ?? 'P.O Box 25 – Hurghada, Egypt' }}</div>
    </div>
    
    <!-- Important Notice -->
    <div style="margin-top: 30px; padding: 15px; background-color: #fff7e6; border: 1px solid #ffe8cc; border-radius: 5px; font-size: 11px; line-height: 1.6;">
        <strong style="color: #d48806;">PLEASE BE AWARE THAT OUR INVOICES ARE BASED ON DEPARTURE DATE AND NOT ON ARRIVAL DATE</strong>
    </div>
</body>
</html>

