<?php

namespace App\Services\Payment;

use Throwable;
use Razorpay\Api\Api;
use RuntimeException;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;

class RazorpayPayment implements PaymentInterface
{
    private Api $api;
    private string $currencyCode;

    /**
     * RazorpayPayment constructor.
     * @param $secretKey
     * @param $publicKey
     * @param $currencyCode
     */
    public function __construct($paymentData)
    {
        $publicKey = $paymentData['razor_key'];
        $secretKey = $paymentData['razor_secret'];
        $currencyCode = HelperService::getSettingData('currency_code');
        // Call Razorpay Class and Create Payment Intent
        $this->api = new Api($publicKey, $secretKey);
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return mixed
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            $orderData = [
                'amount'   => $this->minimumAmountValidation($this->currencyCode, $amount),
                'currency' => $this->currencyCode,
                'notes'    => $customMetaData,
            ];
            return $this->api->order->create($orderData);
        } catch (Throwable $e) {
            Log::error('Failed to create payment intent: ' . $e->getMessage());
            throw new RuntimeException($e->getMessage());
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
        return $this->format($response);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws Throwable
     */
    public function retrievePaymentIntent($paymentId): array
    {
        try {
            return $this->api->order->fetch($paymentId);
        } catch (Throwable $e) {
            throw $e;
        }
    }


    /**
     * @param $currency
     * @param $amount
     * @return float|int
     */
    public function minimumAmountValidation($currency, $amount)
    {
        return match ($currency) {
            "BHD", "IQD", "JOD", "KWD", "OMR", "TND" => $amount * 1000,
            "AED", "ALL", "AMD", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BZD", "CAD", "CHF",
            "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DKK", "DOP", "DZD", "EGP", "ETB", "EUR", "FJD", "GBP", "GHS", "GIP", "GMD", "GTQ", "GYD", "HKD", "HNL",
            "HTG", "HUF", "IDR", "ILS", "INR", "JMD", "KES", "KGS", "KHR", "KYD", "KZT", "LAK", "LKR", "LRD", "LSL", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT",
            "MOP", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "PEN", "PGK", "PHP", "PKR", "PLN", "QAR", "RON", "RSD",
            "RUB", "SAR", "SCR", "SEK", "SGD", "SLL", "SOS", "SSP", "SVC", "SZL", "THB", "TTD", "TWD", "TZS", "UAH", "USD", "UYU", "UZS", "XCD", "YER", "ZAR", "ZMW" => $amount * 100,
            "BIF", "CLP", "DJF", "GNF", "ISK", "JPY", "KMF", "KRW", "PYG", "RWF", "UGX", "VND", "VUV", "XAF", "XOF", "XPF", "HRK" => $amount,
        };
    }

    /**
     * @param $paymentIntent
     * @return array
     */
    private function format($paymentIntent)
    {
        return $this->formatPaymentIntent($paymentIntent->id, $paymentIntent->amount, $paymentIntent->currency, $paymentIntent->status, $paymentIntent->notes->toArray(), $paymentIntent->toArray());
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
                "failed" => "failed", //NOTE : Failed status is not known, please test the failure status
                "created", "attempted" => "pending",
                "paid" => "succeed",
            },
            'payment_gateway_response' => $paymentIntent
        ];
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
            $refundData = [
                'amount' => $this->minimumAmountValidation($this->currencyCode, $amount),
                'notes' => [
                    'reason' => $reason
                ]
            ];

            $refund = $this->api->payment->fetch($transactionId)->refund($refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount,
                'currency' => $this->currencyCode,
                'status' => $refund->status,
                'transaction_id' => $transactionId,
                'data' => $refund->toArray()
            ];
        } catch (Throwable $e) {
            Log::error('Failed to process refund: ' . $e->getMessage());
            throw new RuntimeException('Failed to process refund: ' . $e->getMessage());
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
            $refund = $this->api->refund->fetch($refundId);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
                'currency' => $refund->currency,
                'data' => $refund->toArray()
            ];
        } catch (Throwable $e) {
            Log::error('Failed to get refund status: ' . $e->getMessage());
            throw new RuntimeException('Failed to get refund status: ' . $e->getMessage());
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
            // First create a contact if not exists
            $contactData = [
                'name' => $payoutData['beneficiary_name'],
                'email' => $payoutData['email'] ?? '',
                'contact' => $payoutData['mobile_number'] ?? '',
                'type' => $payoutData['beneficiary_type'] ?? 'customer',
                'reference_id' => $payoutData['reference_id'] ?? uniqid('contact_')
            ];

            $contact = $this->api->contact->create($contactData);

            // Create a fund account for the contact
            $fundAccountData = [
                'contact_id' => $contact->id,
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $payoutData['beneficiary_name'],
                    'ifsc' => $payoutData['bank_code'] ?? '',
                    'account_number' => $payoutData['account_number'] ?? ''
                ]
            ];

            $fundAccount = $this->api->fundAccount->create($fundAccountData);

            // Create a payout
            $payoutRequestData = [
                'account_number' => $this->api->account->fetch()->id,
                'fund_account_id' => $fundAccount->id,
                'amount' => $this->minimumAmountValidation($this->currencyCode, $payoutData['amount']),
                'currency' => $payoutData['currency'] ?? $this->currencyCode,
                'mode' => $payoutData['disbursement_type'] ?? 'IMPS',
                'purpose' => $payoutData['notes'] ?? 'payout',
                'queue_if_low_balance' => true,
                'reference_id' => $payoutData['reference_id'] ?? uniqid('payout_')
            ];

            $payout = $this->api->payout->create($payoutRequestData);

            return [
                'success' => true,
                'payout_id' => $payout->id,
                'amount' => $payoutData['amount'],
                'currency' => $payoutData['currency'] ?? $this->currencyCode,
                'status' => $payout->status,
                'reference_id' => $payoutData['reference_id'] ?? '',
                'data' => $payout->toArray()
            ];
        } catch (Throwable $e) {
            Log::error('Failed to create payout: ' . $e->getMessage());
            throw new RuntimeException('Failed to create payout: ' . $e->getMessage());
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
            $payout = $this->api->payout->fetch($payoutId);

            return [
                'success' => true,
                'payout_id' => $payout->id,
                'status' => $payout->status,
                'amount' => $payout->amount / 100,
                'currency' => $payout->currency,
                'data' => $payout->toArray()
            ];
        } catch (Throwable $e) {
            Log::error('Failed to get payout status: ' . $e->getMessage());
            throw new RuntimeException('Failed to get payout status: ' . $e->getMessage());
        }
    }
}
