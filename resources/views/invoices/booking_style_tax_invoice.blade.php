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
            max-height: 50px;
            max-width: 200px;
            margin-bottom: 10px;
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
                <div class="booking-blue" style="font-size: 20px; font-weight: bold;">{{ $settings['company_name'] ?? 'As-home' }}</div>
            @endif
            
            <div class="recipient-info" style="margin-top: 15px;">
                <div><strong>{{ $owner->name }}</strong></div>
                @if($owner->email)
                    <div>{{ $owner->email }}</div>
                @endif
                @if($owner->mobile)
                    <div>{{ $owner->mobile }}</div>
                @endif
                @if($owner->address)
                    <div>{{ $owner->address }}</div>
                @endif
            </div>
        </div>
        
        <div class="company-info">
            <div class="company-name">{{ $settings['company_name'] ?? 'As-home' }}</div>
            <div>{{ $settings['company_address'] ?? '' }}</div>
            @if($settings['company_phone'])
                <div>Phone: {{ $settings['company_phone'] }}</div>
            @endif
            @if($settings['company_email'])
                <div>Email: {{ $settings['company_email'] }}</div>
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

    <!-- Invoice Title -->
    <div class="invoice-title-section">
        <div class="invoice-title">TAX INVOICE</div>
    </div>

    <!-- Reservations Table: Description, Room Sales, Commission -->
    <table class="reservations-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Room Sales</th>
                <th class="amount">Commission</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Reservations</td>
                <td class="amount">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['revenue_after_taxes'], 2, '.', ',') }}</td>
                <td class="amount">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['hotel_amount'], 2, '.', ',') }}</td>
            </tr>
            <tr class="total-row double-underline">
                <td><strong>Total Amount Due</strong></td>
                <td class="amount"></td>
                <td class="amount"><strong>{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['hotel_amount'], 2, '.', ',') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Payment Information Section -->
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
                <div><strong>Bank Name:</strong> {{ $settings['bank_name'] }}</div>
                @if($settings['bank_address'] ?? null)
                    <div><strong>Bank Address:</strong> {{ $settings['bank_address'] }}</div>
                @endif
                @if($settings['bank_swift_code'] ?? null)
                    <div><strong>Swift:</strong> {{ $settings['bank_swift_code'] }}</div>
                @endif
                @if($settings['bank_account_number'] ?? null)
                    <div><strong>ACCOUNT:</strong> {{ $settings['bank_account_number'] }}</div>
                @endif
                @if($settings['bank_iban'] ?? null)
                    <div><strong>IBAN:</strong> {{ $settings['bank_iban'] }}</div>
                @endif
                @if($settings['bank_account_holder'] ?? null)
                    <div><strong>ACCOUNT HOLDER:</strong> {{ $settings['bank_account_holder'] }}</div>
                @endif
                @if($settings['currency_symbol'] ?? null)
                    <div><strong>ACCOUNT CURRENCY:</strong> {{ $settings['currency_symbol'] }}</div>
                @endif
            </div>
        @endif

        @if($invoiceData['accommodation_number'] ?? null)
            <div class="payment-code">
                <strong>Payment Code:</strong> {{ $invoiceData['accommodation_number'] }}
            </div>
        @endif

        @if($settings['bank_name'] ?? null)
            <div class="virtual-account-note">
                PLEASE NOTIFY YOUR BANKTELLER THAT IS A VIRTUAL ACCOUNT NUMBER
                <br>
                <span style="direction: rtl; display: block; margin-top: 5px;">
                    الرجاء إخبار البنك الذي تتعامل معه عند تنفيذ التحويل أن الحساب الذي سيتم تحويل المبلغ إليه هو حساب إفتراضي
                </span>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>{{ $settings['company_name'] ?? 'As-home' }}</div>
        <div>{{ $settings['company_address'] ?? '' }}</div>
    </div>
</body>
</html>

