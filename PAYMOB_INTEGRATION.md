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
