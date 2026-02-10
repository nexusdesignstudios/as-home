<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaypalServerSdk
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $accessToken;
    public $lastError = null;

    public function __construct()
    {
        $sandbox = system_setting('paypal_test_mode');
        if ($sandbox === '') {
            $sandbox = config('services.paypal.sandbox', true);
        }
        
        // Ensure boolean
        $isSandbox = ($sandbox == '1' || $sandbox === true || $sandbox === 'true');

        $this->clientId = system_setting('paypal_client_id');
        if (empty($this->clientId)) {
            $this->clientId = config('services.paypal.client_id');
        }

        $this->clientSecret = system_setting('paypal_secret');
        if (empty($this->clientSecret)) {
            $this->clientSecret = config('services.paypal.secret');
        }
        
        $this->baseUrl = $isSandbox 
            ? 'https://api-m.sandbox.paypal.com' 
            : 'https://api-m.paypal.com';
    }

    protected function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->lastError = 'Missing Client ID or Secret. Please check your settings or .env configuration.';
            Log::error('PayPal Auth Failed: ' . $this->lastError);
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                return $this->accessToken;
            } else {
                $this->lastError = 'PayPal Auth Response: ' . $response->body();
                Log::error('PayPal Auth Failed: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            $this->lastError = 'Exception: ' . $e->getMessage();
            Log::error('PayPal Auth Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function createOrder($amount, $currency, $returnUrl, $cancelUrl, $customId = null)
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => true, 'message' => 'Could not authenticate with PayPal: ' . ($this->lastError ?? 'Unknown error')];
        }

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ],
                    'custom_id' => $customId,
                    'reference_id' => $customId
                ]
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW'
            ]
        ];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->post("{$this->baseUrl}/v2/checkout/orders", $body);

            if ($response->successful()) {
                $data = $response->json();
                $approveLink = null;
                foreach ($data['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        $approveLink = $link['href'];
                        break;
                    }
                }
                return [
                    'success' => true, 
                    'id' => $data['id'], 
                    'approve_link' => $approveLink,
                    'status' => $data['status']
                ];
            } else {
                Log::error('PayPal Create Order Failed: ' . $response->body());
                return ['error' => true, 'message' => 'Failed to create PayPal order', 'details' => $response->json()];
            }
        } catch (\Exception $e) {
            Log::error('PayPal Create Order Exception: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Exception creating PayPal order'];
        }
    }

    public function captureOrder($orderId)
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => true, 'message' => 'Could not authenticate with PayPal'];
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            } else {
                Log::error('PayPal Capture Failed: ' . $response->body());
                return ['error' => true, 'message' => 'Failed to capture PayPal order', 'details' => $response->json()];
            }
        } catch (\Exception $e) {
            Log::error('PayPal Capture Exception: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Exception capturing PayPal order'];
        }
    }
}
