# Paymob Payout API Implementation

This document describes the implementation of Paymob's payout API according to the official documentation.

## Overview

The Paymob payout system allows you to disburse E-Money to anonymous recipients through various channels including:
- Mobile wallets (Vodafone, Etisalat, Orange, Aman)
- Bank wallets
- Bank cards/accounts

## Configuration

Add the following environment variables to your `.env` file:

```env
# Paymob Payout Configuration
PAYMOB_PAYOUT_CLIENT_ID=your_payout_client_id
PAYMOB_PAYOUT_CLIENT_SECRET=your_payout_client_secret
PAYMOB_PAYOUT_USERNAME=your_payout_username
PAYMOB_PAYOUT_PASSWORD=your_payout_password
PAYMOB_ENVIRONMENT=staging  # or production
PAYMOB_PAYOUT_CALLBACK_URL=https://your-domain.com/api/payments/paymob/payout-callback
```

## API Endpoints

### 1. Process Payout (Instant Cashin)

**Endpoint:** `POST /api/payments/paymob-payout`

**Description:** Process a payout to a recipient through various channels.

**Request Body:**

For Mobile Wallets (Vodafone, Etisalat, Orange, Aman, Bank Wallet):
```json
{
    "issuer": "vodafone",
    "amount": 100.50,
    "msisdn": "01020304050",
    "client_reference_id": "optional-uuid",
    "notes": "Optional notes"
}
```

For Aman (additional fields required):
```json
{
    "issuer": "aman",
    "amount": 100.50,
    "msisdn": "01020304050",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "client_reference_id": "optional-uuid",
    "notes": "Optional notes"
}
```

For Bank Cards/Accounts:
```json
{
    "issuer": "bank_card",
    "amount": 100.50,
    "bank_card_number": "1111222233334444",
    "bank_transaction_type": "cash_transfer",
    "bank_code": "CIB",
    "full_name": "John Doe",
    "client_reference_id": "optional-uuid",
    "notes": "Optional notes"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Payout processed successfully",
    "data": {
        "transaction_id": "92134d2b-d1a5-4dde-859c-a1175e94582c",
        "issuer": "vodafone",
        "amount": 100.50,
        "disbursement_status": "success",
        "status_description": "تم إيداع 100.50 جنيه إلى رقم 01020304050 بنجاح",
        "reference_number": null,
        "aman_cashing_details": null
    }
}
```

### 2. Get Payout Status

**Endpoint:** `GET /api/payments/paymob-payout-status`

**Description:** Check the status of a payout transaction.

**Query Parameters:**
- `transaction_id` (required): The transaction ID to check

**Response:**
```json
{
    "success": true,
    "message": "Payout status retrieved successfully",
    "data": {
        "transaction_id": "92134d2b-d1a5-4dde-859c-a1175e94582c",
        "issuer": "vodafone",
        "amount": 100.50,
        "disbursement_status": "success",
        "status_code": "200",
        "status_description": "تم إيداع 100.50 جنيه إلى رقم 01020304050 بنجاح",
        "reference_number": null,
        "paid": null,
        "aman_cashing_details": null,
        "created_at": "2024-01-01T12:00:00Z",
        "updated_at": "2024-01-01T12:01:00Z"
    }
}
```

### 3. Cancel Aman Transaction

**Endpoint:** `POST /api/payments/paymob-cancel-aman-transaction`

**Description:** Cancel an Aman transaction.

**Request Body:**
```json
{
    "transaction_id": "607f2a5a-1109-43d2-a12c-9327ab2dca18"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Aman transaction cancelled successfully",
    "data": {
        "transaction_id": "607f2a5a-1109-43d2-a12c-9327ab2dca18",
        "issuer": "aman",
        "amount": 91.54,
        "disbursement_status": "successful",
        "status_code": "200",
        "status_description": "Transaction cancelled successfully",
        "reference_number": "5164539",
        "paid": true,
        "aman_cashing_details": {
            "bill_reference": 5164539,
            "is_paid": true,
            "is_cancelled": true
        }
    }
}
```

### 4. Bulk Transaction Inquiry

**Endpoint:** `POST /api/payments/paymob-bulk-transaction-inquiry`

**Description:** Check the status of multiple transactions at once.

**Request Body:**
```json
{
    "transaction_ids": [
        "607f2a5a-1109-43d2-a12c-9327ab2dca18",
        "2a08d70c-49a9-48ed-bcbf-734343065477"
    ],
    "is_bank_transactions": false
}
```

**Response:**
```json
{
    "success": true,
    "message": "Bulk transaction inquiry completed",
    "data": {
        "count": 2,
        "next": null,
        "previous": null,
        "results": [
            {
                "transaction_id": "607f2a5a-1109-43d2-a12c-9327ab2dca18",
                "issuer": "aman",
                "msisdn": "01020304050",
                "amount": 20.5,
                "full_name": "Tom Bernard",
                "disbursement_status": "successful",
                "status_code": "200",
                "status_description": "برجاء التوجه إلى فرع أمان...",
                "aman_cashing_details": {
                    "bill_reference": 3943627,
                    "is_paid": false
                },
                "created_at": "2024-01-01T12:00:00Z",
                "updated_at": "2024-01-01T12:01:00Z"
            }
        ]
    }
}
```

### 5. Get User Budget

**Endpoint:** `GET /api/payments/paymob-user-budget`

**Description:** Get the current balance/budget of the user.

**Response:**
```json
{
    "success": true,
    "message": "User budget retrieved successfully",
    "data": {
        "current_budget": "Your current budget is 888.25 LE",
        "status_description": null,
        "status_code": null
    }
}
```

### 6. Get Bank Codes

**Endpoint:** `GET /api/payments/paymob-bank-codes`

**Description:** Get the list of supported bank codes.

**Response:**
```json
{
    "success": true,
    "message": "Bank codes retrieved successfully",
    "data": {
        "bank_codes": {
            "AUB": "Ahli United Bank",
            "MIDB": "MIDBANK",
            "BDC": "Banque Du Caire",
            "HSBC": "HSBC Bank Egypt S.A.E",
            "CIB": "Commercial International Bank - Egypt S.A.E",
            "MISR": "Banque Misr",
            "NBE": "National Bank of Egypt"
        }
    }
}
```

### 7. Get Bank Transaction Types

**Endpoint:** `GET /api/payments/paymob-bank-transaction-types`

**Description:** Get the list of supported bank transaction types.

**Response:**
```json
{
    "success": true,
    "message": "Bank transaction types retrieved successfully",
    "data": {
        "bank_transaction_types": {
            "salary": "For concurrent or repeated payments",
            "credit_card": "For credit cards payments",
            "prepaid_card": "For prepaid cards and Meeza cards payments",
            "cash_transfer": "For bank accounts, debit cards etc."
        }
    }
}
```

### 8. Get Payout Transactions

**Endpoint:** `GET /api/payments/paymob-payout-transactions`

**Description:** Get paginated list of payout transactions with filters.

**Query Parameters:**
- `per_page` (optional): Number of records per page (1-100, default: 15)
- `status` (optional): Filter by status (success, successful, failed, pending)
- `issuer` (optional): Filter by issuer (vodafone, etisalat, orange, aman, bank_wallet, bank_card)
- `customer_id` (optional): Filter by customer ID
- `date_from` (optional): Filter by start date (YYYY-MM-DD)
- `date_to` (optional): Filter by end date (YYYY-MM-DD)

**Response:**
```json
{
    "success": true,
    "message": "Payout transactions retrieved successfully",
    "data": {
        "transactions": [
            {
                "id": 1,
                "customer_id": 123,
                "transaction_id": "92134d2b-d1a5-4dde-859c-a1175e94582c",
                "issuer": "vodafone",
                "amount": "100.50",
                "msisdn": "01020304050",
                "disbursement_status": "success",
                "status_code": "200",
                "status_description": "تم إيداع 100.50 جنيه إلى رقم 01020304050 بنجاح",
                "created_at": "2024-01-01T12:00:00Z",
                "updated_at": "2024-01-01T12:01:00Z",
                "customer": {
                    "id": 123,
                    "name": "John Doe",
                    "email": "john.doe@example.com"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 15,
            "total": 75,
            "from": 1,
            "to": 15
        }
    }
}
```

### 9. Payout Callback

**Endpoint:** `POST /api/payments/paymob-payout-callback`

**Description:** Webhook endpoint for Paymob to notify about transaction status changes (Aman and bank transactions only).

**Request Body (Bank Transaction):**
```json
{
    "transaction_id": "1e886593-03b1-4af9-b9e0-72b39fef479b",
    "issuer": "bank_card",
    "amount": "100.00",
    "bank_card_number": "8881914753038370",
    "full_name": "Tom Bernard Ceisar",
    "bank_code": "ADCB",
    "bank_transaction_type": "cash_transfer",
    "disbursement_status": "failed",
    "status_code": "8002",
    "status_description": "Invalid bank code",
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:01:00Z"
}
```

**Request Body (Aman Transaction):**
```json
{
    "transaction_id": "607f2a5a-1109-43d2-a12c-9327ab2dca18",
    "issuer": "aman",
    "msisdn": "01020304050",
    "amount": 20.5,
    "full_name": "Tom Bernard",
    "disbursement_status": "successful",
    "status_code": "200",
    "status_description": "برجاء التوجه إلى فرع أمان...",
    "aman_cashing_details": {
        "bill_reference": 3943627,
        "is_paid": true
    },
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:01:00Z"
}
```

## Supported Issuers

### Mobile Wallets
- **vodafone**: Vodafone Cash
- **etisalat**: Etisalat Cash
- **orange**: Orange Money
- **aman**: Aman (requires first_name, last_name, email)
- **bank_wallet**: Bank Mobile Wallets

### Bank Cards/Accounts
- **bank_card**: Bank accounts and cards

## Bank Codes

The system supports all major Egyptian banks including:
- AUB (Ahli United Bank)
- CIB (Commercial International Bank)
- MISR (Banque Misr)
- NBE (National Bank of Egypt)
- HSBC (HSBC Bank Egypt)
- And many more...

## Bank Transaction Types

- **salary**: For concurrent or repeated payments
- **credit_card**: For credit cards payments
- **prepaid_card**: For prepaid cards and Meeza cards payments
- **cash_transfer**: For bank accounts, debit cards etc.

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "data": null,
    "status_code": 400
}
```

Common error scenarios:
- Invalid credentials
- Insufficient balance
- Invalid transaction parameters
- Transaction not found
- Network errors

## Rate Limiting

- Bulk transaction inquiry: 5 requests per minute
- Budget inquiry: 5 requests per minute
- Other endpoints: No specific limits

## Testing

For testing purposes, use these test numbers in staging environment:

- **Vodafone**: 01023456789
- **Etisalat**: 01123456789
- **Orange**: 01223456789
- **Bank Wallet**: 01123416789
- **Bank Card**: 1111222233334444
- **Bank IBAN**: EG829299835722904511873050307

## Security

- OAuth 2.0 authentication with automatic token refresh
- HMAC validation for callbacks (optional)
- Input validation and sanitization
- Comprehensive logging for debugging

## Database Schema

The system creates a `paymob_payout_transactions` table with the following structure:

```sql
CREATE TABLE paymob_payout_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NULL,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    issuer VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    msisdn VARCHAR(11) NULL,
    full_name VARCHAR(255) NULL,
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    bank_card_number VARCHAR(255) NULL,
    bank_transaction_type VARCHAR(50) NULL,
    bank_code VARCHAR(50) NULL,
    client_reference_id VARCHAR(255) NULL,
    disbursement_status VARCHAR(50) NOT NULL,
    status_code VARCHAR(50) NULL,
    status_description TEXT NULL,
    reference_number VARCHAR(255) NULL,
    paid BOOLEAN NULL,
    aman_cashing_details JSON NULL,
    transaction_data JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_issuer (issuer),
    INDEX idx_disbursement_status (disbursement_status),
    INDEX idx_client_reference_id (client_reference_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
```

## Implementation Notes

1. **Token Management**: The system automatically handles OAuth 2.0 token generation and refresh
2. **Caching**: Access tokens are cached for 55 minutes to avoid frequent API calls
3. **Database Storage**: All transactions are stored locally for tracking and reporting
4. **Callback Handling**: Supports webhook callbacks for status updates
5. **Validation**: Comprehensive input validation based on issuer requirements
6. **Logging**: Detailed logging for debugging and monitoring

## Migration

Run the following command to create the required database table:

```bash
php artisan migrate
```

This will create the `paymob_payout_transactions` table with all necessary indexes and foreign key constraints. 
