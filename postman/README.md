# AS Home Dashboard API Postman Collection

This Postman collection provides API endpoints for the AS Home Dashboard Admin application, focusing on authentication, customer management, and related operations.

## Setup Instructions

### Prerequisites
- [Postman](https://www.postman.com/downloads/) installed on your system
- AS Home Dashboard API server running (default: http://localhost:8000)

### Import Collection and Environment
1. Open Postman
2. Click on "Import" button
3. Select the `as-home-dashboard-api.json` file to import the collection
4. Click on "Import" button again
5. Select the `postman_environment.json` file to import the environment variables
6. Select the "AS Home Dashboard API Environment" from the environment dropdown in the top-right corner

## Authentication Flow

The collection includes automatic token handling. When you successfully register or login, the auth_token will be automatically saved to your environment variables.

### Complete Registration Process with OTP Verification
1. **Request OTP**: Use the "Get OTP" request with your email or "Get OTP (Mobile)" with your phone number
2. **Verify OTP**: Use the "Verify OTP (Email)" or "Verify OTP (Mobile)" request with the received OTP
   - After successful verification, the auth_id will be automatically saved to environment variables
3. **Register**: Use the "Register (New Method)" request to create a new account
   - This will create a user account and send verification emails with OTP

### Traditional Login
1. Use the "Login" request to authenticate with existing credentials
2. After successful authentication, the auth_token will be automatically saved

### Password Recovery
1. Use the "Forgot Password" request to receive a password reset link via email

### Using Protected Endpoints
All protected endpoints in the collection are already set up to use the Bearer token from your environment variables. No manual token copying is needed.

## Customer Types

The collection includes requests for different customer types. When registering or updating a profile, you must specify the `customer_type` field:

### Property Owners (`customer_type: "property_owner"`)
- Self-managed property owners: Use "Update Profile - Property Owner (Self-Managed)" with `management_type: "himself"`
- AS Home managed property owners: Use "Update Profile - Property Owner (AS Home Managed)" with `management_type: "as home"`

### Agents (`customer_type: "agent"`)
- Individual agents: Use "Update Profile - Individual Agent"
- Company agents: Use "Update Profile - Company Agent"

## Bank Details and Company Management

The collection includes CRUD operations for:
- Bank details management (required for agents)
- Company information management (required for company agents)

## Environment Variables

The following environment variables are used:
- `base_url`: The base URL of the API (default: http://localhost:8000)
- `auth_token`: Authentication token (automatically set after login/register)
- `user_id`: Current user ID (automatically set after login/register)
- `auth_id`: Authentication ID received after OTP verification (used for registration)
- `bank_details_id`: ID of bank details for testing bank detail endpoints
- `company_id`: ID of company for testing company endpoints

## Troubleshooting

- If authentication fails, check that the server is running and credentials are correct
- If endpoints return 401 Unauthorized, your token may have expired - try logging in again
- If you're getting 404 Not Found, check that the base_url is correctly set in your environment 
- If OTP verification fails, ensure you're using the correct OTP and it hasn't expired (OTPs expire after 10 minutes)
