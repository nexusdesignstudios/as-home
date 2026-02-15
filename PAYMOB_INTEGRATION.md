# Paymob Payment Integration

This document provides instructions on how to integrate Paymob payment gateway into your Laravel application.

## Configuration

Add the following environment variables to your `.env` file:

```
# Paymob Configuration
PAYMOB_API_KEY=your_api_key
PAYMOB_INTEGRATION_ID=your_integration_id
PAYMOB_IFRAME_ID=your_iframe_id
PAYMOB_HMAC_SECRET=your_hmac_secret
PAYMOB_CURRENCY=EGP
PAYMOB_CALLBACK_URL="${APP_URL}/api/payments/paymob/callback"
PAYMOB_RETURN_URL="${APP_URL}/payments/paymob/return"
```

## Files Created/Modified

1. **New Files:**
   - `app/Services/Payment/PaymobPayment.php`: Implementation of the Paymob payment gateway
   - `app/Http/Controllers/PaymobController.php`: Controller for handling Paymob payment requests and callbacks
   - `config/paymob.php`: Configuration file for Paymob
   - `resources/views/payments/responses/success.blade.php`: Success response view
   - `resources/views/payments/responses/failed.blade.php`: Failed response view

2. **Modified Files:**
   - `app/Services/Payment/PaymentService.php`: Added Paymob to the payment service
   - `app/Services/Payment/PaymentInterface.php`: Updated to include refund and payout methods
   - `routes/api.php`: Added routes for Paymob payment callbacks, refunds, and payouts
   - `routes/web.php`: Added success and failure routes for payment returns

## How to Use

### Creating a Payment Intent

To create a payment intent with Paymob, make a POST request to the following endpoint:

```
POST /api/create-paymob-payment
```

with the following parameters:

```json
{
  "amount": 100.00,
  "email": "customer@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "payment_transaction_id": "unique_transaction_id"
}
```

The response will include an `iframe_url` that you can use to redirect the user to the Paymob payment page.

### Processing Refunds

To process a refund for a transaction, make a POST request to the following endpoint:

```
POST /api/paymob-refund
```

with the following parameters:

```json
{
  "transaction_id": "transaction_id_to_refund",
  "amount": 100.00,
  "reason": "Customer requested refund"
}
```

### Checking Refund Status

To check the status of a refund, make a GET request to the following endpoint:

```
GET /api/paymob-refund-status?refund_id=refund_id_to_check
```

### Processing Payouts

To process a payout to a recipient, make a POST request to the following endpoint:

```
POST /api/paymob-payout
```

with the following parameters:

```json
{
  "amount": 100.00,
  "beneficiary_name": "John Doe",
  "disbursement_type": "bank_wallet",
  "account_number": "123456789",
  "bank_code": "BANK123",
  "email": "recipient@example.com",
  "mobile_number": "1234567890",
  "reference_id": "unique_reference_id",
  "notes": "Payout for services rendered"
}
```

For mobile wallet payouts, use:

```json
{
  "amount": 100.00,
  "beneficiary_name": "John Doe",
  "disbursement_type": "mobile_wallet",
  "mobile_number": "1234567890",
  "wallet_issuer": "WALLET_PROVIDER",
  "wallet_number": "wallet_number",
  "reference_id": "unique_reference_id",
  "notes": "Payout for services rendered"
}
```

### Checking Payout Status

To check the status of a payout, make a GET request to the following endpoint:

```
GET /api/paymob-payout-status?payout_id=payout_id_to_check
```

### Handling Callbacks

Paymob will send a callback to the URL specified in your Paymob dashboard or in the `PAYMOB_CALLBACK_URL` environment variable. The callback will be handled by the `handleCallback` method in the `PaymobController`.

### Return URL

After the payment is processed, Paymob will redirect the user to the URL specified in your Paymob dashboard or in the `PAYMOB_RETURN_URL` environment variable. The return URL will be handled by the `handleReturn` method in the `PaymobController`.

## Testing

To test the integration, you can use the Paymob sandbox environment. Create a test account on the Paymob dashboard and use the sandbox credentials in your `.env` file.

## Paymob Documentation

For more information, refer to the [Paymob API Documentation](https://docs.paymob.com/docs/accept-standard-redirect). 

## Mobile SDK Integration (Reservations)

### Endpoint
- `POST /api/create-paymob-payment` (requires Sanctum auth)

### Request Body
```json
{
  "amount": 100.00,
  "email": "customer@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "01000000000",
  "reservable_id": 764,
  "reservable_type": "hotel_room", // or "property"
  "check_in_date": "2026-02-20",
  "check_out_date": "2026-02-22",
  "number_of_guests": 2,
  "special_requests": "Near elevator"
}
```

### Response (Fields relevant to Mobile)
```json
{
  "error": false,
  "message": "Payment intent created successfully",
  "data": {
    "payment_intent": {
      "id": "123456789",          // Paymob order id
      "amount": 100.0,
      "currency": "EGP",
      "status": "pending",
      "payment_gateway_response": {
        "status": true,
        "data": {
          "reference": "123456789",
          "iframe_url": "https://accept.paymob.com/api/acceptance/iframes/IFRAME_ID?payment_token=PAYMENT_KEY",
          "payment_key": "PAYMENT_KEY"
        }
      }
    },
    "transaction_id": "RES_1739612345_42_9876",
    "reservation_id": 112233,
    "mobile_payment": {
      "order_id": "123456789",
      "payment_key": "PAYMENT_KEY",
      "integration_id": "INTEGRATION_ID",
      "amount_cents": 10000,
      "currency": "EGP",
      "callback_url": "https://your-domain.com/api/payments/paymob/callback",
      "return_url": "https://your-domain.com/payments/paymob/return"
    }
  }
}
```

### Mobile Usage Notes
- Use `data.mobile_payment.payment_key` to initialize the Paymob mobile SDK.
- Track the flow using:
  - `data.transaction_id` (merchant_order_id format `RES_*`)
  - `data.mobile_payment.order_id` (Paymob order id)
- After payment:
  - Paymob calls backend callback (`PAYMOB_CALLBACK_URL`)
  - Backend updates `PaymobPayment` and auto-confirms the reservation if succeed
  - User-facing redirect goes to `PAYMOB_RETURN_URL` (optional for app; SDK-driven flows may not need this)

### Required Configuration
- Ensure `.env` contains `PAYMOB_API_KEY`, `PAYMOB_INTEGRATION_ID`, `PAYMOB_IFRAME_ID`, `PAYMOB_HMAC_SECRET`, `PAYMOB_CURRENCY`, `PAYMOB_CALLBACK_URL`, `PAYMOB_RETURN_URL`.
- The backend uses these to generate `payment_key` and to validate callbacks.

---

## Arabic Localization APIs (ar_apis)

### Overview
- Backend sends bilingual content for listings when available:
  - Properties/Projects include Arabic fields alongside English fields.
  - Mobile app should prefer Arabic fields when device language is Arabic, otherwise fallback to English.

### Property Fields
- English: `title`, `description`, `area_description`
- Arabic: `title_ar`, `description_ar`, `area_description_ar`

Example Property object:
```json
{
  "id": 123,
  "title": "Beachfront Villa",
  "title_ar": "فيلا على الشاطئ",
  "description": "Spacious 3BR villa by the beach.",
  "description_ar": "فيلا واسعة بثلاث غرف نوم على الشاطئ.",
  "area_description": "Intercontinental district, Hurghada.",
  "area_description_ar": "حي القارات، الغردقة.",
  "city": "Hurghada",
  "state": "Red Sea",
  "country": "Egypt"
}
```

### Project Fields
- English: `title`, `description`, `area_description`
- Arabic: `title_ar`, `description_ar`, `area_description_ar`

### Mobile App Guidance
- Prefer Arabic if the app language code starts with `ar`:
  - Title: `title_ar ?? title`
  - Description: `description_ar ?? description`
  - Area: `area_description_ar ?? area_description`
- For miscellaneous free-text from backend (e.g., dynamic section headings), use the app’s translation map:
  - Map English phrase to Arabic via your localization assets (e.g., `ai-ar.json`).
  - Fallback to original English when Arabic is unavailable.

### Backend Notes
- Arabic fields are persisted and returned by numerous endpoints in `ApiController`:
  - Property listing/selects include `title_ar`, `description_ar`, `area_description_ar`.
  - Project endpoints include the same Arabic fields when populated.
- No header or query parameter is required; both language variants are returned, and the mobile app selects appropriately.

### Testing Checklist
- Verify property/project endpoints return both English and Arabic fields (null when not set).
- Confirm mobile displays Arabic fields when device language is Arabic, else English.
- Validate fallback behavior when an Arabic field is empty.
