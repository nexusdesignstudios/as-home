<?php

namespace App\Services\Payment;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePayment implements PaymentInterface
{
    private StripeClient $stripe;
    private string $currencyCode;
    private string $secretKey;

    /**
     * StripePayment constructor.
     * @param $secretKey
     * @param $currencyCode
     */
    public function __construct($paymentData)
    {
        // Call Stripe Class and Create Payment Intent
        $this->secretKey = $paymentData['stripe_secret_key'];
        $currency = $paymentData['stripe_currency'];
        $this->stripe = new StripeClient($this->secretKey);
        $this->currencyCode = $currency;
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            $amount = $this->minimumAmountValidation($this->currencyCode, $amount);
            $zeroDecimalCurrencies = [
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'JPY',
                'KMF',
                'KRW',
                'MGA',
                'PYG',
                'RWF',
                'UGX',
                'VND',
                'VUV',
                'XAF',
                'XOF',
                'XPF'
            ];

            if (!in_array($this->currencyCode, $zeroDecimalCurrencies)) {
                $amount *= 100;
            }
            return $this->stripe->paymentIntents->create(
                [
                    'amount'   => $amount,
                    'currency' => $this->currencyCode,
                    'description' => $customMetaData['description'],
                    'shipping' => array(
                        'name'      => $customMetaData['user_name'],
                        'address'   => array(
                            'line1' => $customMetaData['address_line1'],
                            'city'  => $customMetaData['address_city'],
                            'postal_code' => '',
                            'country' => '',
                        ),
                    ),
                    'metadata' => $customMetaData,
                ]
            );
        } catch (ApiErrorException $e) {
            throw $e;
        }
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     * @throws ApiErrorException
     */
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);
        return $this->format($paymentIntent);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent($paymentId): array
    {
        try {
            return $this->format($this->stripe->paymentIntents->retrieve($paymentId));
        } catch (ApiErrorException $e) {
            throw $e;
        }
    }

    /**
     * @param $paymentIntent
     * @return array
     */
    public function format($paymentIntent)
    {
        return $this->formatPaymentIntent($paymentIntent->id, $paymentIntent->amount, $paymentIntent->currency, $paymentIntent->status, $paymentIntent->metadata, $paymentIntent);
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
            'id'                       => $paymentIntent->id,
            'amount'                   => $paymentIntent->amount,
            'currency'                 => $paymentIntent->currency,
            'metadata'                 => $paymentIntent->metadata,
            'status'                   => match ($paymentIntent->status) {
                "canceled" => "failed",
                "succeeded" => "succeed",
                "processing", "requires_action", "requires_capture", "requires_confirmation", "requires_payment_method" => "pending",
            },
            'payment_gateway_response' => $paymentIntent
        ];
    }

    /**
     * @param $currency
     * @param $amount
     * @return float|int
     */
    public function minimumAmountValidation($currency, $amount)
    {
        $minimumAmount = match ($currency) {
            'USD', 'EUR', 'INR', 'NZD', 'SGD', 'BRL', 'CAD', 'AUD', 'CHF' => 0.50,
            'AED', 'PLN', 'RON' => 2.00,
            'BGN' => 1.00,
            'CZK' => 15.00,
            'DKK' => 2.50,
            'GBP' => 0.30,
            'HKD' => 4.00,
            'HUF' => 175.00,
            'JPY' => 50,
            'MXN', 'THB' => 10,
            'MYR' => 2,
            'NOK', 'SEK' => 3.00,
            'XAF' => 100
        };
        if (!empty($minimumAmount)) {
            if ($amount > $minimumAmount) {
                return $amount;
            }

            return $minimumAmount;
        }

        return $amount;
    }

    /**
     * Process a refund for a transaction
     *
     * @param string $transactionId The transaction ID to refund
     * @param float $amount The amount to refund (can be partial)
     * @param string $reason Reason for the refund
     * @return array
     */
    public function refundTransaction($transactionId, $amount, $reason = ''): array
    {
        try {
            $zeroDecimalCurrencies = [
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'JPY',
                'KMF',
                'KRW',
                'MGA',
                'PYG',
                'RWF',
                'UGX',
                'VND',
                'VUV',
                'XAF',
                'XOF',
                'XPF'
            ];

            if (!in_array($this->currencyCode, $zeroDecimalCurrencies)) {
                $amount *= 100;
            }

            $refund = $this->stripe->refunds->create([
                'payment_intent' => $transactionId,
                'amount' => (int)$amount,
                'reason' => $reason ? 'requested_by_customer' : null,
                'metadata' => [
                    'reason' => $reason
                ]
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'currency' => $refund->currency,
                'status' => $refund->status,
                'transaction_id' => $transactionId,
                'data' => $refund
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Check the status of a refund
     *
     * @param string $refundId The refund ID to check
     * @return array
     */
    public function getRefundStatus($refundId): array
    {
        try {
            $refund = $this->stripe->refunds->retrieve($refundId);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
                'currency' => $refund->currency,
                'data' => $refund
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get refund status: ' . $e->getMessage());
        }
    }

    /**
     * Process a payout to a recipient
     *
     * @param array $payoutData The payout data including recipient details and amount
     * @return array
     */
    public function createPayout(array $payoutData): array
    {
        try {
            // First create or retrieve a customer
            $customerParams = [
                'email' => $payoutData['email'] ?? null,
                'name' => $payoutData['beneficiary_name'],
                'phone' => $payoutData['mobile_number'] ?? null,
                'metadata' => [
                    'reference_id' => $payoutData['reference_id'] ?? uniqid('customer_')
                ]
            ];

            // Filter out null values
            $customerParams = array_filter($customerParams);

            // Create customer
            $customer = $this->stripe->customers->create($customerParams);

            // Create an external account (bank account or card)
            if ($payoutData['disbursement_type'] === 'bank_wallet' || $payoutData['disbursement_type'] === 'bank_card') {
                // Create a bank account token
                $bankAccountParams = [
                    'country' => $payoutData['country'] ?? 'US',
                    'currency' => $payoutData['currency'] ?? $this->currencyCode,
                    'account_holder_name' => $payoutData['beneficiary_name'],
                    'account_holder_type' => $payoutData['beneficiary_type'] === 'company' ? 'company' : 'individual',
                    'routing_number' => $payoutData['bank_code'] ?? null,
                    'account_number' => $payoutData['account_number']
                ];

                $bankAccount = $this->stripe->tokens->create([
                    'bank_account' => $bankAccountParams
                ]);

                // Add bank account to customer
                $externalAccount = $this->stripe->customers->createSource(
                    $customer->id,
                    ['source' => $bankAccount->id]
                );
            } else {
                // Create a card token (for card payouts)
                // This is a simplified example and would need actual card details in a real scenario
                throw new \RuntimeException('Card payouts require additional secure handling of card details');
            }

            // Create a transfer to the connected account
            $zeroDecimalCurrencies = [
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'JPY',
                'KMF',
                'KRW',
                'MGA',
                'PYG',
                'RWF',
                'UGX',
                'VND',
                'VUV',
                'XAF',
                'XOF',
                'XPF'
            ];

            $amount = $payoutData['amount'];
            if (!in_array($this->currencyCode, $zeroDecimalCurrencies)) {
                $amount *= 100;
            }

            $transfer = $this->stripe->transfers->create([
                'amount' => (int)$amount,
                'currency' => $payoutData['currency'] ?? $this->currencyCode,
                'destination' => $externalAccount->id,
                'metadata' => [
                    'reference_id' => $payoutData['reference_id'] ?? '',
                    'notes' => $payoutData['notes'] ?? ''
                ]
            ]);

            return [
                'success' => true,
                'payout_id' => $transfer->id,
                'amount' => $payoutData['amount'],
                'currency' => $payoutData['currency'] ?? $this->currencyCode,
                'status' => $transfer->status,
                'reference_id' => $payoutData['reference_id'] ?? '',
                'data' => $transfer
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create payout: ' . $e->getMessage());
        }
    }

    /**
     * Check the status of a payout
     *
     * @param string $payoutId The payout ID to check
     * @return array
     */
    public function getPayoutStatus($payoutId): array
    {
        try {
            $transfer = $this->stripe->transfers->retrieve($payoutId);

            return [
                'success' => true,
                'payout_id' => $transfer->id,
                'status' => $transfer->status,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'data' => $transfer
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get payout status: ' . $e->getMessage());
        }
    }
}
