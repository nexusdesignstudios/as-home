<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $monthYearDisplay }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .logo {
            max-height: 80px;
            max-width: 200px;
        }
        
        .company-info {
            text-align: right;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 12px;
            color: #666;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin: 30px 0;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            flex: 1;
            margin-right: 20px;
        }
        
        .owner-details {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 5px;
            flex: 1;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
        }
        
        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .summary-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        
        .summary-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #e9ecef !important;
            font-weight: bold;
        }
        
        .reservations-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .reservations-table th,
        .reservations-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .reservations-table th {
            background-color: #6c757d;
            color: white;
            font-weight: bold;
        }
        
        .reservations-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .bank-details {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .bank-details h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .bank-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bank-table td {
            padding: 8px;
            border: none;
        }
        
        .bank-table td:first-child {
            font-weight: bold;
            width: 30%;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        
        .note {
            font-style: italic;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div>
            @if($settings['logo'])
                <img src="{{ $settings['logo'] }}" alt="Company Logo" class="logo">
            @endif
        </div>
        <div class="company-info">
            <div class="company-name">{{ $settings['company_name'] }}</div>
            <div class="company-details">
                {{ $settings['company_address'] }}<br>
                Phone: {{ $settings['company_phone'] }}<br>
                Email: {{ $settings['company_email'] }}
            </div>
        </div>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        Monthly Tax Invoice - {{ $monthYearDisplay }}
    </div>

    <!-- Invoice Information -->
    <div class="invoice-info">
        <div class="invoice-details">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Period:</strong> {{ $monthYearDisplay }}</p>
            <p><strong>Invoice Date:</strong> {{ date('d M Y') }}</p>
            <p><strong>Total Reservations:</strong> {{ $invoiceData['total_reservations'] }}</p>
            <p><strong>Invoice Type:</strong> 
                @if($templateType === 'monthly_tax_invoice_hotels_flexible')
                    Flexible Hotel Booking
                @elseif($templateType === 'monthly_tax_invoice_hotels_non_refundable')
                    Non-Refundable Hotel Booking
                @elseif($templateType === 'vacation_homes_premium_tax_invoice')
                    Premium Vacation Home
                @elseif($templateType === 'vacation_homes_basic_tax_invoice')
                    Basic Vacation Home
                @else
                    Monthly Tax Invoice
                @endif
            </p>
        </div>
        
        <div class="owner-details">
            <h3>Property Owner</h3>
            <p><strong>Name:</strong> {{ $owner->name }}</p>
            <p><strong>Email:</strong> {{ $owner->email }}</p>
            @if($owner->mobile)
                <p><strong>Phone:</strong> {{ $owner->mobile }}</p>
            @endif
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="section-title">Financial Summary</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Rate</th>
                <th>Amount ({{ $invoiceData['currency_symbol'] }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue</td>
                <td>-</td>
                <td class="amount">{{ number_format($invoiceData['total_revenue'], 2) }}</td>
            </tr>
            <tr>
                <td>Service Charge</td>
                <td>{{ $invoiceData['service_charge_rate'] }}%</td>
                <td class="amount">{{ number_format($invoiceData['service_charge_amount'], 2) }}</td>
            </tr>
            <tr>
                <td>Sales Tax</td>
                <td>{{ $invoiceData['sales_tax_rate'] }}%</td>
                <td class="amount">{{ number_format($invoiceData['sales_tax_amount'], 2) }}</td>
            </tr>
            <tr>
                <td>City Tax</td>
                <td>{{ $invoiceData['city_tax_rate'] }}%</td>
                <td class="amount">{{ number_format($invoiceData['city_tax_amount'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Taxes</strong></td>
                <td><strong>-</strong></td>
                <td class="amount"><strong>{{ number_format($invoiceData['total_taxes_amount'], 2) }}</strong></td>
            </tr>
            <tr>
                <td>Revenue After Taxes</td>
                <td>-</td>
                <td class="amount">{{ number_format($invoiceData['revenue_after_taxes'], 2) }}</td>
            </tr>
            <tr>
                <td>{{ $settings['company_name'] }} Commission</td>
                <td>{{ $invoiceData['commission_rate'] }}%</td>
                <td class="amount">{{ number_format($invoiceData['commission_amount'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Net Amount to Owner</strong></td>
                <td><strong>-</strong></td>
                <td class="amount"><strong>{{ number_format($invoiceData['net_amount'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Reservation Details -->
    <div class="section-title">Reservation Details</div>
    {!! $invoiceData['reservation_details'] !!}

    <!-- Property Summary -->
    <div class="section-title">Property Summary</div>
    {!! $invoiceData['property_summary'] !!}

    <!-- Bank Details (for flexible hotels) -->
    @if($templateType === 'monthly_tax_invoice_hotels_flexible')
        <div class="bank-details">
            <h3>Bank Account Details for Commission Payment</h3>
            <table class="bank-table">
                <tr>
                    <td>Bank Name:</td>
                    <td>{{ $settings['bank_name'] }}</td>
                </tr>
                <tr>
                    <td>Account Holder:</td>
                    <td>As Home for Asset Management</td>
                </tr>
                <tr>
                    <td>Account Number:</td>
                    <td>{{ $settings['bank_account_number'] }}</td>
                </tr>
                <tr>
                    <td>Routing Number:</td>
                    <td>{{ $settings['bank_routing_number'] }}</td>
                </tr>
                <tr>
                    <td>SWIFT Code:</td>
                    <td>{{ $settings['bank_swift_code'] }}</td>
                </tr>
            </table>
            <div class="highlight">
                <strong>Note:</strong> Please transfer the commission amount ({{ number_format($invoiceData['commission_amount'], 2) }} {{ $invoiceData['currency_symbol'] }}) to the above account within 7 days of receiving this invoice.
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This invoice was generated automatically by {{ $settings['company_name'] }} system.</p>
        <p>For any questions regarding this invoice, please contact us at {{ $settings['company_email'] }}</p>
        <p>Generated on {{ date('d M Y, h:i A') }}</p>
    </div>
</body>
</html>

