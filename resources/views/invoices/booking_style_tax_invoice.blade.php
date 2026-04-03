<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $monthYearDisplay }}</title>
    <style>
        @page {
            margin: 0.5in;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-bottom: 0;
            margin-bottom: 20px;
        }

        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #cca44a; /* Golden color from screenshot */
            margin-bottom: 5px;
        }

        .property-info {
            font-size: 11px;
            color: #000;
        }

        .property-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .location-link {
            color: #003580;
            text-decoration: underline;
        }

        .company-profile-box {
            text-align: right;
            font-size: 10px;
            color: #444;
            line-height: 1.3;
        }

        .company-profile-box strong {
            color: #cca44a;
            font-size: 11px;
        }

        .invoice-meta-table {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .invoice-meta-table td {
            padding: 2px 0;
        }

        .meta-label {
            font-weight: bold;
            width: 150px;
        }

        .statement-box {
            background-color: #f8f9fa;
            border-left: 5px solid #003580;
            padding: 15px;
            margin: 20px 0;
            font-size: 12px;
        }

        .invoice-title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 6px;
            margin: 40px 0;
            color: #000;
            text-transform: uppercase;
        }

        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .financial-table th {
            text-align: left;
            padding: 10px;
            background-color: #f2f2f2;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .financial-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .amount-col {
            text-align: right;
            width: 150px;
        }

        .total-row {
            font-weight: bold;
            font-size: 12px;
        }

        .total-row td {
            border-bottom: 4px double #000;
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .bank-section {
            margin-top: 40px;
        }

        .payment-due-note {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .bank-details-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #eee;
        }

        .bank-details-table td {
            padding: 8px 12px;
            border: 1px solid #eee;
        }

        .bank-label {
            width: 35%;
            background-color: #fcfcf4; /* Very light yellow tint */
            font-weight: bold;
            color: #555;
        }

        .bank-value {
            width: 65%;
            background-color: #fcfcf4;
        }

        .footer-note {
            margin-top: 30px;
            font-size: 11px;
            line-height: 1.5;
        }

        .departure-warning {
            margin-top: 30px;
            padding: 15px;
            background-color: #fffdec;
            border: 1px solid #ffe8cc;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="brand-name">As-Home for Asset Management</div>
                <div class="property-info">
                    <div class="property-name">{{ $invoiceData['property_name'] ?? 'Hotel' }}</div>
                    <div>{{ $invoiceData['property_address'] ?? '' }}</div>
                    @if(!empty($invoiceData['property_address']))
                        @php
                            $addressEncoded = urlencode($invoiceData['property_address']);
                            $mapsLink = "https://www.google.com/maps/search/?api=1&query=" . $addressEncoded;
                        @endphp
                        <div><a href="{{ $mapsLink }}" class="location-link" target="_blank">Location</a></div>
                    @endif
                    <div>VAT: {{ $invoiceData['property_vat'] ?? '' }}</div>
                </div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="company-profile-box">
                    <strong>As-Home for Asset Management</strong><br>
                    P.O Box 25 – Hurghada, Egypt<br>
                    Phone: +2 (0155) 379 7794<br>
                    Email: info@as-home-group.com<br>
                    Tax Number: 4332 - 1233 - 7598
                </div>
            </td>
        </tr>
    </table>

    <!-- Invoice Meta -->
    <table class="invoice-meta-table">
        <tr>
            <td class="meta-label">Accommodation number:</td>
            <td>{{ $invoiceData['accommodation_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Invoice number:</td>
            <td>{{ $invoiceData['invoice_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date:</td>
            <td>{{ date('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Period:</td>
            <td>{{ date('d/m/Y', strtotime($monthYear . '-01')) }} - {{ date('d/m/Y', strtotime($monthYear . '-' . date('t', strtotime($monthYear . '-01')))) }}</td>
        </tr>
    </table>

    <!-- Statement Box -->
    <div class="statement-box">
        We hereby provide your monthly tax invoice for the month of {{ $monthYearDisplay }} for <strong>{{ $invoiceData['property_name'] ?? 'Hotel' }}</strong>.
    </div>

    <!-- Title -->
    <div class="invoice-title">TAX INVOICE</div>

    <!-- Financial Summary -->
    <table class="financial-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount-col">Amount ({{ $invoiceData['currency_symbol'] }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue</td>
                <td class="amount-col">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['room_sales_raw'] ?? ($invoiceData['room_sales'] ?? $invoiceData['total_revenue']), 2) }}</td>
            </tr>
            <tr>
                <td>Commission</td>
                <td class="amount-col">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['commission_amount_raw'] ?? $invoiceData['commission_amount'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Amount Due</td>
                <td class="amount-col">{{ $invoiceData['currency_symbol'] }} {{ number_format($invoiceData['total_amount_due_raw'] ?? ($invoiceData['total_amount_due'] ?? $invoiceData['commission_amount']), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Payment Section -->
    <div class="bank-section">
        <div class="payment-due-note">
            Payment Due Date: {{ date('F d, Y', strtotime('+14 days')) }}
        </div>
        <p class="footer-note">
            Please transfer the due amount to our bank account below by the Payment Due date. Be sure to include INVOICE {{ $invoiceData['invoice_number'] }} and ACCOMMODATION NUMBER {{ $invoiceData['accommodation_number'] }} with your payment instructions.
        </p>

        <table class="bank-details-table">
            <tr>
                <td class="bank-label">Bank Name</td>
                <td class="bank-value">National Bank of Egypt</td>
            </tr>
            <tr>
                <td class="bank-label">Branch</td>
                <td class="bank-value">Hurghada Branch</td>
            </tr>
            <tr>
                <td class="bank-label">Bank Address</td>
                <td class="bank-value">EL Kawthar Hurghada Branch</td>
            </tr>
            <tr>
                <td class="bank-label">Currency</td>
                <td class="bank-value">EGP</td>
            </tr>
            <tr>
                <td class="bank-label">Swift Code</td>
                <td class="bank-value">NBEGEGCX341</td>
            </tr>
            <tr>
                <td class="bank-label">Account No.</td>
                <td class="bank-value">3413131856116201017</td>
            </tr>
            <tr>
                <td class="bank-label">Beneficiary Name</td>
                <td class="bank-value">As Home for Asset Management</td>
            </tr>
            <tr>
                <td class="bank-label">IBAN</td>
                <td class="bank-value">EG100003034131318561162010170</td>
            </tr>
        </table>
    </div>

    <!-- Warning -->
    <div class="departure-warning">
        PLEASE BE AWARE THAT OUR INVOICES ARE BASED ON DEPARTURE DATE AND NOT ON ARRIVAL DATE
    </div>
</body>
</html>
