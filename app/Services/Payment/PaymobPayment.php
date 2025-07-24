<?php

namespace App\Services\Payment;

use Throwable;
use RuntimeException;
use Illuminate\Support\Facades\Http;

class PaymobPayment implements PaymentInterface
{
    private string $apiKey;
    private string $integrationId;
    private string $frameId;
    private string $currencyCode;
    private string $baseUrl = 'https://accept.paymob.com/api';

    /**
     * PaymobPayment constructor.
     * @param array $paymentData
     */
    public function __construct($paymentData)
    {
        $this->apiKey = $paymentData['paymob_api_key'];
        $this->integrationId = $paymentData['paymob_integration_id'];
        $this->frameId = $paymentData['paymob_iframe_id'];
        $this->currencyCode = $paymentData['paymob_currency'] ?? 'EGP';
    }

    /**
     * Get Paymob authentication token
     * @return string
     * @throws RuntimeException
     */
    private function getAuthToken()
    {
        try {
            $response = Http::post($this->baseUrl . '/auth/tokens', [
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json('token');
            }

            throw new RuntimeException('Failed to authenticate with Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to authenticate with Paymob: ' . $e->getMessage());
        }
    }

    /**
     * Create an order on Paymob
     * @param string $token
     * @param float $amount
     * @param array $metadata
     * @return array
     * @throws RuntimeException
     */
    private function createOrder($token, $amount, $metadata)
    {
        try {
            $response = Http::post($this->baseUrl . '/ecommerce/orders', [
                'auth_token' => $token,
                'delivery_needed' => false,
                'amount_cents' => $amount * 100,
                'currency' => $this->currencyCode,
                'merchant_order_id' => $metadata['payment_transaction_id'] ?? time(),
                'items' => []
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new RuntimeException('Failed to create order with Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to create order with Paymob: ' . $e->getMessage());
        }
    }

    /**
     * Create a payment key
     * @param string $token
     * @param int $orderId
     * @param float $amount
     * @param array $metadata
     * @return string
     * @throws RuntimeException
     */
    private function getPaymentKey($token, $orderId, $amount, $metadata)
    {
        try {
            $billingData = [
                'apartment' => 'NA',
                'email' => $metadata['email'] ?? 'customer@example.com',
                'floor' => 'NA',
                'first_name' => $metadata['first_name'] ?? 'Customer',
                'street' => 'NA',
                'building' => 'NA',
                'phone_number' => $metadata['phone'] ?? 'NA',
                'shipping_method' => 'NA',
                'postal_code' => 'NA',
                'city' => 'NA',
                'country' => 'NA',
                'last_name' => $metadata['last_name'] ?? 'Customer',
                'state' => 'NA',
            ];

            $response = Http::post($this->baseUrl . '/acceptance/payment_keys', [
                'auth_token' => $token,
                'amount_cents' => $amount * 100,
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => $billingData,
                'currency' => $this->currencyCode,
                'integration_id' => $this->integrationId,
                'lock_order_when_paid' => true
            ]);

            if ($response->successful()) {
                return $response->json('token');
            }

            throw new RuntimeException('Failed to create payment key with Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to create payment key with Paymob: ' . $e->getMessage());
        }
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     * @throws RuntimeException
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            // Get authentication token
            $token = $this->getAuthToken();

            // Create order
            $orderResponse = $this->createOrder($token, $amount, $customMetaData);
            $orderId = $orderResponse['id'];

            // Get payment key
            $paymentKey = $this->getPaymentKey($token, $orderId, $amount, $customMetaData);

            // Create iframe URL
            $iframeUrl = "https://accept.paymob.com/api/acceptance/iframes/{$this->frameId}?payment_token={$paymentKey}";

            return [
                'status' => true,
                'data' => [
                    'reference' => (string) $orderId,
                    'iframe_url' => $iframeUrl,
                    'payment_key' => $paymentKey,
                ]
            ];
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     */
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $response = $this->createPaymentIntent($amount, $customMetaData);
        return $this->format($response, $amount, $this->currencyCode, $customMetaData);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws RuntimeException
     */
    public function retrievePaymentIntent($paymentId): array
    {
        try {
            $token = $this->getAuthToken();

            $response = Http::get($this->baseUrl . '/acceptance/transactions/' . $paymentId, [
                'auth_token' => $token
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $amount = $data['amount_cents'] / 100;
                $currency = $data['currency'];
                $metadata = $data['order']['shipping_data'] ?? [];

                return $this->format([
                    'status' => $data['success'] ? true : false,
                    'data' => $data
                ], $amount, $currency, $metadata);
            }

            throw new RuntimeException('Failed to retrieve payment from Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * Process a refund for a transaction
     *
     * @param string $transactionId The transaction ID to refund
     * @param float $amount The amount to refund (can be partial)
     * @param string $reason Reason for the refund
     * @return array
     * @throws RuntimeException
     */
    public function refundTransaction($transactionId, $amount, $reason = ''): array
    {
        try {
            $token = $this->getAuthToken();

            $response = Http::post($this->baseUrl . '/acceptance/void_refund/refund', [
                'auth_token' => $token,
                'transaction_id' => $transactionId,
                'amount_cents' => $amount * 100,
                'reason' => $reason
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'refund_id' => $data['id'] ?? null,
                    'amount' => $amount,
                    'currency' => $this->currencyCode,
                    'status' => $data['success'] ? 'succeed' : 'failed',
                    'transaction_id' => $transactionId,
                    'data' => $data
                ];
            }

            throw new RuntimeException('Failed to process refund with Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Check the status of a refund
     *
     * @param string $refundId The refund ID to check
     * @return array
     * @throws RuntimeException
     */
    public function getRefundStatus($refundId): array
    {
        try {
            $token = $this->getAuthToken();

            $response = Http::get($this->baseUrl . '/acceptance/void_refund/status', [
                'auth_token' => $token,
                'refund_id' => $refundId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'refund_id' => $refundId,
                    'status' => $data['status'] ?? 'unknown',
                    'amount' => ($data['amount_cents'] ?? 0) / 100,
                    'currency' => $data['currency'] ?? $this->currencyCode,
                    'data' => $data
                ];
            }

            throw new RuntimeException('Failed to get refund status from Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to get refund status: ' . $e->getMessage());
        }
    }

    /**
     * Process a payout to a recipient
     *
     * @param array $payoutData The payout data including recipient details and amount
     * @return array
     * @throws RuntimeException
     */
    public function createPayout(array $payoutData): array
    {
        try {
            $token = $this->getAuthToken();

            // Format the payout data according to Paymob's requirements
            $formattedData = [
                'auth_token' => $token,
                'amount_cents' => $payoutData['amount'] * 100,
                'currency' => $payoutData['currency'] ?? $this->currencyCode,
                'disbursement_type' => $payoutData['disbursement_type'] ?? 'bank_wallet', // bank_wallet, bank_card, mobile_wallet
                'beneficiary' => [
                    'type' => $payoutData['beneficiary_type'] ?? 'person', // person, company
                    'name' => $payoutData['beneficiary_name'],
                    'mobile_number' => $payoutData['mobile_number'] ?? null,
                    'email' => $payoutData['email'] ?? null,
                    'bank_account' => [
                        'account_number' => $payoutData['account_number'] ?? null,
                        'bank_code' => $payoutData['bank_code'] ?? null,
                        'swift_code' => $payoutData['swift_code'] ?? null,
                        'iban' => $payoutData['iban'] ?? null,
                    ]
                ],
                'notes' => $payoutData['notes'] ?? '',
                'reference_id' => $payoutData['reference_id'] ?? uniqid('payout_')
            ];

            // Remove null values from the beneficiary bank account
            $formattedData['beneficiary']['bank_account'] = array_filter($formattedData['beneficiary']['bank_account']);

            // If mobile wallet is used, add the wallet details
            if ($payoutData['disbursement_type'] === 'mobile_wallet') {
                $formattedData['wallet_issuer'] = $payoutData['wallet_issuer'] ?? '';
                $formattedData['wallet_number'] = $payoutData['wallet_number'] ?? '';
            }

            $response = Http::post($this->baseUrl . '/disbursements', $formattedData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payout_id' => $data['id'] ?? null,
                    'amount' => $payoutData['amount'],
                    'currency' => $payoutData['currency'] ?? $this->currencyCode,
                    'status' => $data['status'] ?? 'pending',
                    'reference_id' => $payoutData['reference_id'] ?? '',
                    'data' => $data
                ];
            }

            throw new RuntimeException('Failed to create payout with Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to create payout: ' . $e->getMessage());
        }
    }

    /**
     * Check the status of a payout
     *
     * @param string $payoutId The payout ID to check
     * @return array
     * @throws RuntimeException
     */
    public function getPayoutStatus($payoutId): array
    {
        try {
            $token = $this->getAuthToken();

            $response = Http::get($this->baseUrl . '/disbursements/' . $payoutId, [
                'auth_token' => $token
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payout_id' => $payoutId,
                    'status' => $data['status'] ?? 'unknown',
                    'amount' => ($data['amount_cents'] ?? 0) / 100,
                    'currency' => $data['currency'] ?? $this->currencyCode,
                    'data' => $data
                ];
            }

            throw new RuntimeException('Failed to get payout status from Paymob: ' . $response->body());
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to get payout status: ' . $e->getMessage());
        }
    }

    /**
     * @param $currency
     * @param $amount
     */
    public function minimumAmountValidation($currency, $amount)
    {
        // Paymob doesn't have minimum amount restrictions
        return true;
    }

    /**
     * @param $paymentIntent
     * @param $amount
     * @param $currencyCode
     * @param $metadata
     * @return array
     */
    public function format($paymentIntent, $amount, $currencyCode, $metadata)
    {
        return $this->formatPaymentIntent(
            $paymentIntent['data']['reference'],
            $amount,
            $currencyCode,
            $paymentIntent['status'],
            $metadata,
            $paymentIntent
        );
    }

    /**
     * @param $id
     * @param $amount
     * @param $currency
     * @param $status
     * @param $metadata
     * @param $paymentIntent
     * @return array
     */
    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array
    {
        return [
            'id'                       => $id,
            'amount'                   => $amount,
            'currency'                 => $currency,
            'metadata'                 => $metadata,
            'status'                   => match ($status) {
                false => "failed",
                true => "succeed",
                default => $status ?? "pending"
            },
            'payment_gateway_response' => $paymentIntent,
            'iframe_url'               => $paymentIntent['data']['iframe_url'] ?? null
        ];
    }
}
