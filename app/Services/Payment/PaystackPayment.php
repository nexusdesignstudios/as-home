<?php

namespace App\Services\Payment;

use Throwable;
use RuntimeException;
use Unicodeveloper\Paystack\Paystack;

class PaystackPayment extends Paystack implements PaymentInterface
{
    private Paystack $paystack;
    private string $currencyCode;

    /**
     * PaystackPayment constructor.
     * @param $currencyCode
     */
    public function __construct($paymentData)
    {
        // Call Paystack Class and Create Payment Intent
        $currency = $paymentData['paystack_currency'];
        $this->paystack = new Paystack();
        $this->currencyCode = $currency;
        parent::__construct();
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     */
    public function createPaymentIntent($amount, $customMetaData)
    {

        try {

            if (empty($customMetaData['email'])) {
                throw new RuntimeException("Email cannot be empty");
            }
            if ($customMetaData['platform_type'] == 'app') {
                $callbackUrl = route('paystack.success');
            } else {
                $callbackUrl = route('paystack.success.web');
            }

            // Create cancel URL
            $cancelUrl = route('paystack.cancel', ['payment_transaction_id' => $customMetaData['payment_transaction_id']]);

            $finalAmount = $amount * 100;
            $reference = $this->genTranxRef();

            // Add the metadata with cancel_action
            $metadata = $customMetaData;
            $metadata['cancel_action'] = $cancelUrl;

            $data = [
                'amount'   => $finalAmount,
                'currency' => $this->currencyCode,
                'email'    => $customMetaData['email'],
                'metadata' => $metadata,
                'reference' => $reference,
                'callback_url' => $callbackUrl
            ];

            return $this->paystack->getAuthorizationResponse($data);
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
     * @throws Throwable
     */
    public function retrievePaymentIntent($paymentId): array
    {
        try {
            $relativeUrl = "/transaction/verify/{$paymentId}";
            $this->response = $this->client->get($this->baseUrl . $relativeUrl, []);
            $response = json_decode($this->response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return $this->format($response['data'], $response['data']['amount'], $response['data']['currency'], $response['data']['metadata']);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param $currency
     * @param $amount
     */
    public function minimumAmountValidation($currency, $amount)
    {
        // TODO: Implement minimumAmountValidation() method.
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
        return $this->formatPaymentIntent($paymentIntent['data']['reference'], $amount, $currencyCode, $paymentIntent['status'], $metadata, $paymentIntent);
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
                "abandoned" => "failed",
                "succeed" => "succeed",
                default => $status ?? true
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
        throw new RuntimeException('Refund functionality not implemented for Paystack');
    }

    /**
     * Check the status of a refund
     *
     * @param string $refundId The refund ID to check
     * @return array
     */
    public function getRefundStatus($refundId): array
    {
        throw new RuntimeException('Refund status functionality not implemented for Paystack');
    }

    /**
     * Process a payout to a recipient
     *
     * @param array $payoutData The payout data including recipient details and amount
     * @return array
     */
    public function createPayout(array $payoutData): array
    {
        throw new RuntimeException('Payout functionality not implemented for Paystack');
    }

    /**
     * Check the status of a payout
     *
     * @param string $payoutId The payout ID to check
     * @return array
     */
    public function getPayoutStatus($payoutId): array
    {
        throw new RuntimeException('Payout status functionality not implemented for Paystack');
    }
}
