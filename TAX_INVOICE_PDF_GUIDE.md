# Tax Invoice System with PDF Attachments

This document explains how to use the enhanced tax invoice system that includes PDF attachments with company logos.

## Features

- ✅ Email templates for different property types
- ✅ PDF generation with company logo
- ✅ Email attachments support
- ✅ Test data and real data support
- ✅ Multiple template types (flexible, non-refundable, vacation homes)

## Usage

### 1. Test Email Templates with PDF Attachments

#### Basic Test (Sample Data + PDF)
```bash
php artisan test:hotel-emails your-email@example.com --with-pdf
```

#### Test Specific Template with PDF
```bash
php artisan test:hotel-emails your-email@example.com --template=flexible --with-pdf
php artisan test:hotel-emails your-email@example.com --template=non-refundable --with-pdf
```

#### Test with Real Data + PDF
```bash
php artisan test:hotel-emails your-email@example.com --owner-email=owner@example.com --month=2025-01 --with-pdf
php artisan test:hotel-emails your-email@example.com --owner-id=123 --month=2025-01 --with-pdf
```

### 2. Generate Real Tax Invoices with PDF Attachments

#### Generate for Current Month
```bash
php artisan tax:generate-monthly-invoices
```

#### Generate for Specific Month
```bash
php artisan tax:generate-monthly-invoices --month=2025-01
```

#### Dry Run (Test without sending emails)
```bash
php artisan tax:generate-monthly-invoices --month=2025-01 --dry-run
```

### 3. Test PDF Generation in Browser

Visit: `http://your-domain.com/test-tax-invoice-pdf`

This will generate and display a test PDF invoice in your browser.

## Email Templates Supported

1. **monthly_tax_invoice_hotels_flexible** - For flexible hotel bookings
2. **monthly_tax_invoice_hotels_non_refundable** - For non-refundable hotel bookings
3. **vacation_homes_premium_tax_invoice** - For premium vacation homes
4. **vacation_homes_basic_tax_invoice** - For basic vacation homes

## PDF Features

### Company Logo
- Automatically includes company logo from `public/assets/images/logo/`
- Converts logo to base64 for PDF embedding
- Falls back gracefully if logo not found

### Invoice Content
- **Header**: Company logo, name, address, contact info
- **Invoice Details**: Period, date, reservation count, type
- **Owner Information**: Name, email, phone
- **Financial Summary**: Revenue, taxes, commission, net amount
- **Reservation Details**: Table with all reservations
- **Property Summary**: Revenue breakdown by property
- **Bank Details**: For flexible hotels (commission payment info)
- **Footer**: Generation timestamp and contact info

### Styling
- Professional layout with company branding
- Responsive tables for reservation data
- Color-coded sections (blue headers, gray backgrounds)
- Print-friendly design

## Configuration

### System Settings Required
The system uses these settings from the database:

```php
// Company Information
'company_name' => 'As-home',
'company_address' => '123 Business Street, City, Country',
'company_phone' => '+1-234-567-8900',
'company_email' => 'info@as-home.com',
'company_logo' => 'logo.png',

// Tax Rates
'hotel_service_charge_rate' => 10, // 10%
'hotel_sales_tax_rate' => 14,      // 14%
'hotel_city_tax_rate' => 5,        // 5%

// Bank Details (for flexible hotels)
'bank_name' => 'As-home Bank',
'bank_account_number' => '1234567890',
'bank_routing_number' => '987654321',
'bank_swift_code' => 'ASHOMEXX',
'bank_account_holder' => 'As-home Group',

// Currency
'currency_symbol' => 'EGP',
'currency_code' => 'EGP'
```

### Logo Requirements
- Place company logo in: `public/assets/images/logo/`
- Supported formats: PNG, JPG, JPEG
- Recommended size: 200x80 pixels
- File should be named as specified in `company_logo` setting

## File Structure

```
app/
├── Services/
│   ├── PDF/
│   │   └── TaxInvoiceService.php          # PDF generation service
│   ├── MonthlyTaxInvoiceService.php        # Enhanced with PDF support
│   └── HelperService.php                   # Enhanced with attachment support
├── Console/Commands/
│   └── TestHotelEmailTemplate.php         # Enhanced test command
└── Http/Controllers/
    └── TaxInvoiceController.php            # Test controller

resources/views/
└── invoices/
    └── tax_invoice.blade.php              # PDF template

routes/
└── web.php                                # Test route added
```

## Technical Details

### PDF Generation
- Uses **DomPDF** library (already installed)
- Generates professional invoices with company branding
- Supports base64 image embedding for logos
- Optimized for A4 paper size

### Email Attachments
- Enhanced `HelperService::sendMail()` to support attachments
- Attachments array format:
```php
'attachments' => [
    [
        'content' => $pdfContent,      // Binary PDF content
        'filename' => 'invoice.pdf',   // Display filename
        'mime_type' => 'application/pdf'
    ]
]
```

### Error Handling
- Comprehensive logging for PDF generation
- Graceful fallbacks for missing logos/settings
- Detailed error messages in console output

## Testing Commands Summary

| Command | Purpose | PDF | Real Data |
|---------|---------|-----|-----------|
| `test:hotel-emails email@test.com` | Basic test | ❌ | ❌ |
| `test:hotel-emails email@test.com --with-pdf` | Test with PDF | ✅ | ❌ |
| `test:hotel-emails email@test.com --owner-email=owner@test.com --with-pdf` | Real data + PDF | ✅ | ✅ |
| `tax:generate-monthly-invoices` | Production invoices | ✅ | ✅ |

## Troubleshooting

### PDF Generation Issues
1. Check if DomPDF is properly installed: `composer show barryvdh/laravel-dompdf`
2. Verify logo file exists in `public/assets/images/logo/`
3. Check Laravel logs for detailed error messages

### Email Issues
1. Verify mail configuration in `.env`
2. Test basic email sending first
3. Check attachment size limits

### Template Issues
1. Verify email templates exist in system settings
2. Check template variable placeholders match data keys
3. Test with sample data first

## Examples

### Complete Test Workflow
```bash
# 1. Test PDF generation in browser
# Visit: http://your-domain.com/test-tax-invoice-pdf

# 2. Test email with sample data + PDF
php artisan test:hotel-emails test@example.com --with-pdf

# 3. Test with real owner data + PDF
php artisan test:hotel-emails test@example.com --owner-email=realowner@example.com --month=2025-01 --with-pdf

# 4. Generate production invoices
php artisan tax:generate-monthly-invoices --month=2025-01
```

This system provides a complete solution for generating and sending professional tax invoices with PDF attachments, supporting both testing and production scenarios.

