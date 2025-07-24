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

    'iframe_id' => env('PAYMOB_IFRAME_ID', ''),

    'hmac_secret' => env('PAYMOB_HMAC_SECRET', ''),

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
];
