# Send Money API Documentation

## Overview

The Send Money feature allows users to send money to recipients using Paymob
payment gateway. This feature includes payment processing, transaction tracking,
and refund capabilities.

## Database Structure

### SendMoney Table

- `id`: Primary key
- `customer_id`: Foreign key to customers table
- `transaction_id`: Unique transaction identifier
- `amount`: Transaction amount
- `currency`: Currency code (default: EGP)
- `status`: Transaction status (pending, processing, completed, failed,
  cancelled)
- `payment_status`: Payment status (unpaid, paid, failed, refunded)
- `payment_method`: Payment method (default: paymob)
- `recipient_customer_id`: Foreign key to customers table (recipient)
- `notes`: Optional notes
- `payment_data`: JSON data for payment intent
- `paymob_order_id`: Paymob order ID
- `paymob_transaction_id`: Paymob transaction ID
- `transaction_data`: JSON data from Paymob callback
- `refund_data`: JSON data for refunds
- `created_at`, `updated_at`: Timestamps

## API Endpoints

### 1. Create Send Money Transaction

**POST** `/api/send-money`

**Authentication:** Required (Bearer token)

**Request Body:**

```json
{
  "amount": 100.0,
  "recipient_customer_id": 2,
  "notes": "Optional notes",
  "payment": {
    "email": "sender@example.com",
    "first_name": "Sender",
    "last_name": "Name",
    "phone": "01234567890"
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Send money transaction created successfully",
  "data": {
    "send_money": {
      "id": 1,
      "customer_id": 1,
      "transaction_id": "SEND_1640995200_1_1234",
      "amount": 100.0,
      "currency": "EGP",
      "status": "pending",
      "payment_status": "unpaid",
      "recipient_customer_id": 2,
      "recipient": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "mobile": "01234567890"
      },
      "notes": "Optional notes"
    },
    "payment_intent": {
      "id": "order_id",
      "amount": 100.0,
      "currency": "EGP",
      "status": "pending",
      "iframe_url": "https://accept.paymob.com/api/acceptance/iframes/..."
    },
    "transaction_id": "SEND_1640995200_1_1234"
  }
}
```

### 2. Get Customers for Send Money Selection

**GET** `/api/send-money-customers`

**Authentication:** Required (Bearer token)

**Query Parameters:**

- `search`: Search term to filter customers by name, email, or mobile

**Response:**

```json
{
  "success": true,
  "message": "Customers retrieved successfully",
  "data": {
    "customers": [
      {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "mobile": "01234567890"
      },
      {
        "id": 3,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "mobile": "01234567891"
      }
    ]
  }
}
```

### 3. Get Customer Send Money Transactions

**GET** `/api/send-money`

**Authentication:** Required (Bearer token)

**Query Parameters:**

- `status`: Filter by status (comma-separated: pending,completed,failed)
- `page`: Page number for pagination

**Response:**

```json
{
    "success": true,
    "message": "Send money transactions retrieved successfully",
    "data": {
        "transactions": {
            "data": [...],
            "current_page": 1,
            "last_page": 1,
            "per_page": 10,
            "total": 1
        }
    }
}
```

### 4. Get Specific Send Money Transaction

**GET** `/api/send-money/{id}`

**Authentication:** Required (Bearer token)

**Response:**

```json
{
  "success": true,
  "message": "Send money transaction retrieved successfully",
  "data": {
    "transaction": {
      "id": 1,
      "customer_id": 1,
      "transaction_id": "SEND_1640995200_1_1234",
      "amount": 100.0,
      "currency": "EGP",
      "status": "completed",
      "payment_status": "paid",
      "recipient_customer_id": 2,
      "recipient": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "mobile": "01234567890"
      },
      "notes": "Optional notes",
      "created_at": "2025-01-15T10:00:00.000000Z",
      "updated_at": "2025-01-15T10:05:00.000000Z"
    }
  }
}
```

### 5. Cancel Send Money Transaction

**POST** `/api/send-money/{id}/cancel`

**Authentication:** Required (Bearer token)

**Response:**

```json
{
  "success": true,
  "message": "Send money transaction cancelled successfully",
  "data": {
    "transaction": {
      "id": 1,
      "status": "cancelled",
      "payment_status": "failed"
    }
  }
}
```

### 6. Refund Send Money Transaction

**POST** `/api/send-money/{id}/refund`

**Authentication:** Required (Bearer token)

**Request Body:**

```json
{
  "reason": "Customer requested refund"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Refund processed successfully",
  "data": {
    "transaction": {
      "id": 1,
      "status": "cancelled",
      "payment_status": "refunded"
    },
    "refund_result": {
      "success": true,
      "refund_id": "refund_123",
      "amount": 100.0,
      "currency": "EGP",
      "status": "succeed"
    }
  }
}
```

## Admin Endpoints

### 1. Get All Send Money Transactions (Admin)

**GET** `/api/admin/send-money`

**Authentication:** Required (Admin Bearer token)

**Query Parameters:**

- `status`: Filter by status
- `customer_id`: Filter by customer ID
- `page`: Page number for pagination

### 2. Update Send Money Transaction Status (Admin)

**PUT** `/api/admin/send-money/{id}/status`

**Authentication:** Required (Admin Bearer token)

**Request Body:**

```json
{
  "status": "completed",
  "payment_status": "paid"
}
```

## Paymob Callback

### Send Money Callback

**POST** `/api/send-money/paymob/callback`

**Authentication:** Not required (Paymob webhook)

This endpoint is automatically called by Paymob when a payment is processed. It
updates the transaction status based on the payment result.

## Payment Flow

1. **Create Transaction**: User creates a send money transaction with recipient
   details
2. **Payment Intent**: System creates a Paymob payment intent and returns iframe
   URL
3. **Payment Processing**: User completes payment through Paymob iframe
4. **Callback Processing**: Paymob sends callback to update transaction status
5. **Transaction Completion**: Transaction is marked as completed or failed

## Error Handling

All endpoints return appropriate HTTP status codes:

- `200`: Success
- `400`: Bad Request (validation errors)
- `401`: Unauthorized
- `403`: Forbidden (admin only)
- `404`: Not Found
- `500`: Internal Server Error

Error responses include:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["validation error message"]
  }
}
```

## Security Considerations

1. All customer endpoints require authentication
2. Admin endpoints require admin authentication
3. Callback endpoints are public but should be secured with HMAC validation
4. Transaction IDs are unique and generated securely
5. All sensitive data is properly validated and sanitized

## Usage Examples

### Frontend Integration

```javascript
// Create send money transaction
const createSendMoney = async (data) => {
  const response = await fetch("/api/send-money", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(data),
  });
  return response.json();
};

// Redirect to Paymob iframe
const paymentIntent = await createSendMoney(sendMoneyData);
if (paymentIntent.success) {
  window.location.href = paymentIntent.data.payment_intent.iframe_url;
}
```

### Backend Integration

```php
// Create send money transaction
$sendMoney = SendMoney::create([
    'customer_id' => $customerId,
    'transaction_id' => $transactionId,
    'amount' => $amount,
    'recipient_name' => $recipientName,
    'recipient_email' => $recipientEmail,
    'recipient_phone' => $recipientPhone,
    'notes' => $notes,
]);
```
