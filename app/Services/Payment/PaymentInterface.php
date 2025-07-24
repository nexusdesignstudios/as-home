<?php

namespace App\Services\Payment;

interface PaymentInterface
{
    public function createPaymentIntent($amount, $customMetaData);

    public function createAndFormatPaymentIntent($amount, $customMetaData): array;

    public function retrievePaymentIntent($paymentId): array;

    public function minimumAmountValidation($currency, $amount);

    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array;

    /**
     * Process a refund for a transaction
     *
     * @param string $transactionId The transaction ID to refund
     * @param float $amount The amount to refund (can be partial)
     * @param string $reason Reason for the refund
     * @return array
     */
    public function refundTransaction($transactionId, $amount, $reason = ''): array;

    /**
     * Check the status of a refund
     *
     * @param string $refundId The refund ID to check
     * @return array
     */
    public function getRefundStatus($refundId): array;

    /**
     * Process a payout to a recipient
     *
     * @param array $payoutData The payout data including recipient details and amount
     * @return array
     */
    public function createPayout(array $payoutData): array;

    /**
     * Check the status of a payout
     *
     * @param string $payoutId The payout ID to check
     * @return array
     */
    public function getPayoutStatus($payoutId): array;
}
