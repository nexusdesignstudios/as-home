<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paymob Keys
    |--------------------------------------------------------------------------
    |
    | The Paymob API key and integration IDs for your account.
    | You can get these from your Paymob dashboard.
    |
    */

    'api_key' => env('PAYMOB_API_KEY', ''),

    'integration_id' => env('PAYMOB_INTEGRATION_ID', ''),

    'send_money_integration_id' => env('PAYMOB_SEND_MONEY_INTEGRATION_ID', '5307586'),

    'iframe_id' => env('PAYMOB_IFRAME_ID', ''),

    'hmac_secret' => env('PAYMOB_HMAC_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Paymob Payout Configuration
    |--------------------------------------------------------------------------
    |
    | The Paymob payout API credentials for disbursements.
    | These are separate from the payment API credentials.
    |
    */

    'payout_client_id' => env('PAYMOB_PAYOUT_CLIENT_ID', ''),

    'payout_client_secret' => env('PAYMOB_PAYOUT_CLIENT_SECRET', ''),

    'payout_username' => env('PAYMOB_PAYOUT_USERNAME', ''),

    'payout_password' => env('PAYMOB_PAYOUT_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Paymob Environment
    |--------------------------------------------------------------------------
    |
    | The environment to use for Paymob API calls.
    | Use 'staging' for testing and 'production' for live.
    |
    */

    'environment' => env('PAYMOB_ENVIRONMENT', 'staging'),

    /*
    |--------------------------------------------------------------------------
    | Paymob Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use for Paymob transactions.
    | Typically EGP for Egyptian Pounds.
    |
    */

    'currency' => env('PAYMOB_CURRENCY', 'EGP'),

    /*
    |--------------------------------------------------------------------------
    | Paymob Callback URLs
    |--------------------------------------------------------------------------
    |
    | The URLs that Paymob will redirect to after payment processing.
    |
    */

    'callback_url' => env('PAYMOB_CALLBACK_URL', config('app.url') . '/api/payments/paymob/callback'),

    'return_url' => env('PAYMOB_RETURN_URL', config('app.url') . '/payments/paymob/return'),

    /*
    |--------------------------------------------------------------------------
    | Paymob Payout Callback URL
    |--------------------------------------------------------------------------
    |
    | The URL that Paymob will call to notify about payout status changes.
    | Used for aman transactions and bank transactions only.
    |
    */

    'payout_callback_url' => env('PAYMOB_PAYOUT_CALLBACK_URL', config('app.url') . '/api/payments/paymob/payout-callback'),
];
