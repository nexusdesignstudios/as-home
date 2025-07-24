<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\ApiResponseService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymobController extends Controller
{
    /**
     * Handle the callback from Paymob after payment processing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request)
    {
        Log::info('Paymob callback received', $request->all());

        try {
            // Validate HMAC if provided
            if (config('paymob.hmac_secret')) {
                $this->validateHmac($request);
            }

            $transactionId = $request->input('obj.order.merchant_order_id');
            $paymentStatus = $request->input('obj.success') ? 'succeed' : 'failed';

            // Find the payment transaction
            $paymentTransaction = PaymentTransaction::where('id', $transactionId)
                ->orWhere('transaction_id', $transactionId)
                ->first();

            if (!$paymentTransaction) {
                ApiResponseService::errorResponse('Payment transaction not found', null, 404);
                return;
            }

            // Update payment transaction status
            $paymentTransaction->status = $paymentStatus;
            $paymentTransaction->transaction_data = json_encode($request->all());
            $paymentTransaction->save();

            // Process successful payment
            if ($paymentStatus === 'succeed') {
                // Add your business logic here for successful payment
                // For example, update subscription status, send confirmation email, etc.
            }

            ApiResponseService::successResponse('Payment callback processed successfully');
            return;
        } catch (\Exception $e) {
            Log::error('Paymob callback error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to process payment callback: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Handle the return from Paymob payment page
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleReturn(Request $request)
    {
        Log::info('Paymob return received', $request->all());

        try {
            $success = $request->input('success') === 'true';
            $transactionId = $request->input('merchant_order_id');

            if ($success) {
                return redirect()->route('payment.success', ['transaction_id' => $transactionId]);
            } else {
                return redirect()->route('payment.failed', ['transaction_id' => $transactionId]);
            }
        } catch (\Exception $e) {
            Log::error('Paymob return error: ' . $e->getMessage());
            return redirect()->route('payment.failed');
        }
    }

    /**
     * Validate HMAC signature from Paymob
     *
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    private function validateHmac(Request $request)
    {
        $hmacSecret = config('paymob.hmac_secret');
        $receivedHmac = $request->header('HMAC');

        if (!$receivedHmac) {
            throw new \Exception('HMAC signature missing from request');
        }

        $requestData = $request->getContent();
        $calculatedHmac = hash_hmac('sha512', $requestData, $hmacSecret);

        if ($calculatedHmac !== $receivedHmac) {
            throw new \Exception('HMAC signature validation failed');
        }

        return true;
    }

    /**
     * Create a payment intent with Paymob
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'email' => 'required|email',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'required|string',
                'payment_transaction_id' => 'required|string',
            ]);

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            $amount = $request->input('amount');
            $metadata = $request->except('amount');

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Create payment intent
            $paymentIntent = $paymentService->createAndFormatPaymentIntent($amount, $metadata);

            ApiResponseService::successResponse('Payment intent created successfully', $paymentIntent);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payment intent error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to create payment intent: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Process a refund for a transaction
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processRefund(Request $request)
    {
        try {
            $request->validate([
                'transaction_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'reason' => 'nullable|string|max:255',
            ]);

            $transactionId = $request->input('transaction_id');
            $amount = $request->input('amount');
            $reason = $request->input('reason', 'Customer requested refund');

            // Find the payment transaction to verify it exists and is valid for refund
            $paymentTransaction = PaymentTransaction::where('id', $transactionId)
                ->orWhere('transaction_id', $transactionId)
                ->first();

            if (!$paymentTransaction) {
                ApiResponseService::errorResponse('Payment transaction not found', null, 404);
                return;
            }

            if ($paymentTransaction->status !== 'succeed') {
                ApiResponseService::errorResponse('Cannot refund a transaction that is not successful', null, 400);
                return;
            }

            // Get the Paymob transaction ID from the stored transaction data
            $transactionData = json_decode($paymentTransaction->transaction_data, true);
            $paymobTransactionId = $transactionData['id'] ?? $transactionId;

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Process refund
            $refundResult = $paymentService->refundTransaction($paymobTransactionId, $amount, $reason);

            // Update payment transaction status
            $paymentTransaction->status = 'refunded';
            $paymentTransaction->refund_data = json_encode($refundResult);
            $paymentTransaction->save();

            ApiResponseService::successResponse('Refund processed successfully', $refundResult);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob refund error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to process refund: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Check the status of a refund
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRefundStatus(Request $request)
    {
        try {
            $request->validate([
                'refund_id' => 'required|string',
            ]);

            $refundId = $request->input('refund_id');

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Get refund status
            $refundStatus = $paymentService->getRefundStatus($refundId);

            ApiResponseService::successResponse('Refund status retrieved successfully', $refundStatus);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob refund status error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to get refund status: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Process a payout to a recipient
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayout(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'beneficiary_name' => 'required|string',
                'disbursement_type' => 'required|string|in:bank_wallet,bank_card,mobile_wallet',
                'reference_id' => 'nullable|string',
                'notes' => 'nullable|string',
                // Bank account fields
                'account_number' => 'required_if:disbursement_type,bank_wallet,bank_card|nullable|string',
                'bank_code' => 'required_if:disbursement_type,bank_wallet,bank_card|nullable|string',
                'swift_code' => 'nullable|string',
                'iban' => 'nullable|string',
                // Mobile wallet fields
                'mobile_number' => 'required_if:disbursement_type,mobile_wallet|nullable|string',
                'wallet_issuer' => 'required_if:disbursement_type,mobile_wallet|nullable|string',
                'wallet_number' => 'required_if:disbursement_type,mobile_wallet|nullable|string',
                // Additional fields
                'email' => 'nullable|email',
                'beneficiary_type' => 'nullable|string|in:person,company',
            ]);

            $payoutData = $request->all();

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Process payout
            $payoutResult = $paymentService->createPayout($payoutData);

            // Store payout record in database if needed
            // You might want to create a PayoutTransaction model for this

            ApiResponseService::successResponse('Payout processed successfully', $payoutResult);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payout error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to process payout: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Check the status of a payout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayoutStatus(Request $request)
    {
        try {
            $request->validate([
                'payout_id' => 'required|string',
            ]);

            $payoutId = $request->input('payout_id');

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Get payout status
            $payoutStatus = $paymentService->getPayoutStatus($payoutId);

            ApiResponseService::successResponse('Payout status retrieved successfully', $payoutStatus);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payout status error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to get payout status: ' . $e->getMessage(), null, 500);
            return;
        }
    }
}
