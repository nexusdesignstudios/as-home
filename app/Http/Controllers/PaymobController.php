<?php

namespace App\Http\Controllers;

use App\Models\PaymobPayment;
use App\Models\PaymobPayoutTransaction;
use App\Models\SendMoney;
use App\Services\ApiResponseService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymobPayoutService;
use App\Services\HelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Reservation;

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
        try {
            Log::info('Paymob callback received', $request->all());

            // Validate HMAC if provided
            // if (config('paymob.hmac_secret')) {
            //     $this->validateHmac($request);
            // }
            $data = $request->all();
            Log::info('Paymob callback received - Parsed to json:', ['data' => json_encode($data)]);

            // Add more detailed logging to debug the transaction ID issue
            Log::info('Paymob callback - Full data structure:', [
                'obj_exists' => isset($data['obj']),
                'order_exists' => isset($data['obj']['order']),
                'merchant_order_id_exists' => isset($data['obj']['order']['merchant_order_id']),
                'merchant_order_id_value' => $data['obj']['order']['merchant_order_id'] ?? 'NOT_FOUND',
                'full_obj' => $data['obj'] ?? 'NOT_FOUND'
            ]);

            $transactionId = $data['obj']['order']['merchant_order_id'];
            $paymentStatus = $data['obj']['success'] ? 'succeed' : 'failed';
            $paymobOrderId = $data['obj']['order']['id'];
            $paymobTransactionId = $data['obj']['id'];

            Log::info('Paymob callback received - Transaction ID:', ['transaction_id' => $transactionId]);
            Log::info('Paymob callback received - Payment Status:', ['status' => $paymentStatus]);
            Log::info('Paymob callback received - Paymob Order ID:', ['order_id' => $paymobOrderId]);
            Log::info('Paymob callback received - Paymob Transaction ID:', ['paymob_transaction_id' => $paymobTransactionId]);

            try {
                // Log all payment records to debug the transaction ID issue
                $allPayments = PaymobPayment::where('status', 'pending')->get(['id', 'transaction_id', 'reservation_id']);
                Log::info('Paymob callback - All pending payments:', [
                    'payments' => $allPayments->toArray(),
                    'searching_for_transaction_id' => $transactionId
                ]);

                $payment = PaymobPayment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    // Only update if not already updated by the fallback logic
                    if ($payment->status === 'pending') {
                        $payment->status = $paymentStatus;
                        $payment->paymob_order_id = $paymobOrderId;
                        $payment->paymob_transaction_id = $paymobTransactionId;
                        $payment->transaction_data = json_encode($data);
                        $payment->save();
                    }

                    // Update reservation status
                    if ($payment && $payment->reservation_id) {
                        $reservation = Reservation::find($payment->reservation_id);
                        if ($reservation) {
                            if ($paymentStatus === 'succeed') {
                                // Use the new service method for successful payments
                                $reservationService = app(\App\Services\ReservationService::class);
                                $reservationService->handleReservationConfirmation($reservation, 'paid');
                            } else {
                                // Handle failed payments
                                $reservation->status = 'cancelled';
                                $reservation->payment_status = 'failed';
                                $reservation->save();

                                Log::info('Reservation cancelled due to failed payment', [
                                    'reservation_id' => $reservation->id,
                                    'status' => $reservation->status,
                                    'payment_status' => $reservation->payment_status
                                ]);
                            }
                        } else {
                            Log::warning('Reservation not found', [
                                'reservation_id' => $payment->reservation_id,
                                'payment_id' => $payment->id
                            ]);
                        }
                    }

                    Log::info('Payment updated successfully', [
                        'payment_id' => $payment->id,
                        'status' => $paymentStatus
                    ]);
                } else {
                    Log::warning('Payment not found for transaction', [
                        'transaction_id' => $transactionId
                    ]);

                    // Try to find payment by Paymob transaction ID as fallback
                    $paymentByPaymobId = PaymobPayment::where('paymob_transaction_id', $paymobTransactionId)->first();
                    if ($paymentByPaymobId) {
                        Log::info('Payment found by Paymob transaction ID', [
                            'paymob_transaction_id' => $paymobTransactionId,
                            'payment_id' => $paymentByPaymobId->id
                        ]);

                        // Update the payment with the correct transaction ID
                        $paymentByPaymobId->transaction_id = $transactionId;
                        $paymentByPaymobId->status = $paymentStatus;
                        $paymentByPaymobId->paymob_order_id = $paymobOrderId;
                        $paymentByPaymobId->paymob_transaction_id = $paymobTransactionId;
                        $paymentByPaymobId->transaction_data = json_encode($data);
                        $paymentByPaymobId->save();

                        $payment = $paymentByPaymobId;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing payment data', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transactionId,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            ApiResponseService::successResponse('Payment callback processed successfully');
            return;
        } catch (\Exception $e) {
            Log::error('Paymob callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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

            Log::info('Paymob return details', [
                'success' => $success,
                'transaction_id' => $transactionId
            ]);

            // Find the payment
            $payment = PaymobPayment::where('transaction_id', $transactionId)->first();

            if ($payment && $success) {
                Log::info('Payment found and successful', [
                    'payment_id' => $payment->id,
                    'reservation_id' => $payment->reservation_id
                ]);
                return redirect()->route('payments.paymob-success', [
                    'transaction_id' => $transactionId,
                    'reservation_id' => $payment->reservation_id,
                    'source' => 'paymob'
                ]);
            } else {
                if (!$payment) {
                    Log::warning('Payment not found for transaction', [
                        'transaction_id' => $transactionId
                    ]);
                } else {
                    Log::warning('Payment found but not successful', [
                        'payment_id' => $payment->id,
                        'success' => $success
                    ]);
                }
                return redirect()->route('payments.paymob-failed', [
                    'transaction_id' => $transactionId,
                    'source' => 'paymob'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Paymob return error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('payments.paymob-failed', ['source' => 'paymob']);
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
     * Create a payment intent with Paymob for a reservation
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
                'reservable_id' => 'required|integer',
                'reservable_type' => 'required|in:property,hotel_room',
                'check_in_date' => 'required|date|after_or_equal:today',
                'check_out_date' => 'required|date|after:check_in_date',
                'number_of_guests' => 'integer|min:1',
                'special_requests' => 'nullable|string',
            ]);

            // Map the reservable type to the model class
            $reservableType = $request->reservable_type === 'property'
                ? 'App\\Models\\Property'
                : 'App\\Models\\HotelRoom';

            // Generate a unique transaction ID that's compatible with Paymob
            // Paymob expects merchant_order_id to be a string, so we'll use a timestamp-based ID
            $transactionId = 'RES_' . time() . '_' . Auth::guard('sanctum')->user()->id . '_' . rand(1000, 9999);

            // Create temporary reservation to hold the details
            $reservation = Reservation::create([
                'customer_id' => Auth::guard('sanctum')->user()->id,
                'reservable_id' => $request->reservable_id,
                'reservable_type' => $reservableType,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'number_of_guests' => $request->number_of_guests ?? 1,
                'total_price' => $request->amount,
                'special_requests' => $request->special_requests,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => 'paymob',
                'transaction_id' => $transactionId,
            ]);

            // Create payment record
            $payment = PaymobPayment::create([
                'customer_id' => Auth::guard('sanctum')->user()->id,
                'transaction_id' => $transactionId,
                'amount' => $request->amount,
                'currency' => config('paymob.currency', 'EGP'),
                'status' => 'pending',
                'payment_method' => 'paymob',
                'reservable_id' => $request->reservable_id,
                'reservable_type' => $reservableType,
                'reservation_id' => $reservation->id,
            ]);

            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            $metadata = [
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'payment_transaction_id' => $transactionId,
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Create payment intent
            $paymentIntent = $paymentService->createAndFormatPaymentIntent($request->amount, $metadata);

            ApiResponseService::successResponse('Payment intent created successfully', [
                'payment_intent' => $paymentIntent,
                'transaction_id' => $transactionId,
                'reservation_id' => $reservation->id
            ]);
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
            $payment = PaymobPayment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                ApiResponseService::errorResponse('Payment transaction not found', null, 404);
                return;
            }

            if ($payment->status !== 'succeed') {
                ApiResponseService::errorResponse('Cannot refund a transaction that is not successful', null, 400);
                return;
            }

            // Get the Paymob transaction ID from the stored transaction data
            $paymobTransactionId = $payment->paymob_transaction_id ?? $transactionId;

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
            $payment->status = 'refunded';
            $payment->refund_data = json_encode($refundResult);
            $payment->save();

            // If there's an associated reservation, cancel it
            if ($payment->reservation_id) {
                $reservation = Reservation::find($payment->reservation_id);
                if ($reservation) {
                    $reservation->status = 'cancelled';
                    $reservation->payment_status = 'refunded';
                    $reservation->save();

                    // Use the reservation service to handle the cancellation
                    $reservationService = app(\App\Services\ReservationService::class);
                    $reservationService->cancelReservation($payment->reservation_id);
                }
            }

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
     * Process instant cashin (payout) to a recipient
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayout(Request $request)
    {
        try {
            $request->validate([
                'issuer' => 'required|string|in:vodafone,etisalat,orange,aman,bank_wallet,bank_card',
                'amount' => 'required|numeric|min:0.01',
                'msisdn' => 'required_if:issuer,vodafone,etisalat,orange,aman,bank_wallet|nullable|string|regex:/^[0-9]{11}$/',
                'first_name' => 'required_if:issuer,aman|nullable|string',
                'last_name' => 'required_if:issuer,aman|nullable|string',
                'email' => 'nullable|email',
                'bank_card_number' => 'required_if:issuer,bank_card|nullable|string',
                'bank_transaction_type' => 'required_if:issuer,bank_card|nullable|string|in:salary,credit_card,prepaid_card,cash_transfer',
                'bank_code' => 'required_if:issuer,bank_card|nullable|string',
                'full_name' => 'required_if:issuer,bank_card|nullable|string',
                'client_reference_id' => 'nullable|string|uuid',
                'notes' => 'nullable|string',
            ]);

            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Prepare payout data
            $payoutData = $request->all();
            $payoutData['customer_id'] = Auth::guard('sanctum')->user()->id ?? null;

            // Process the payout
            $result = $payoutService->processInstantCashin($payoutData);

            // Store the transaction in database
            $payoutTransaction = PaymobPayoutTransaction::create([
                'customer_id' => $payoutData['customer_id'],
                'transaction_id' => $result['transaction_id'],
                'issuer' => $result['issuer'],
                'amount' => $result['amount'],
                'msisdn' => $payoutData['msisdn'] ?? null,
                'full_name' => $payoutData['full_name'] ?? null,
                'first_name' => $payoutData['first_name'] ?? null,
                'last_name' => $payoutData['last_name'] ?? null,
                'email' => $payoutData['email'] ?? null,
                'bank_card_number' => $payoutData['bank_card_number'] ?? null,
                'bank_transaction_type' => $payoutData['bank_transaction_type'] ?? null,
                'bank_code' => $payoutData['bank_code'] ?? null,
                'client_reference_id' => $payoutData['client_reference_id'] ?? null,
                'disbursement_status' => $result['disbursement_status'],
                'status_code' => $result['status_code'],
                'status_description' => $result['status_description'],
                'reference_number' => $result['reference_number'],
                'paid' => $result['paid'],
                'aman_cashing_details' => $result['aman_cashing_details'],
                'transaction_data' => $result,
                'notes' => $payoutData['notes'] ?? null,
            ]);

            Log::info('Paymob payout transaction created', [
                'transaction_id' => $payoutTransaction->transaction_id,
                'issuer' => $payoutTransaction->issuer,
                'amount' => $payoutTransaction->amount,
                'status' => $payoutTransaction->disbursement_status
            ]);

            ApiResponseService::successResponse('Payout processed successfully', [
                'transaction_id' => $result['transaction_id'],
                'issuer' => $result['issuer'],
                'amount' => $result['amount'],
                'disbursement_status' => $result['disbursement_status'],
                'status_description' => $result['status_description'],
                'reference_number' => $result['reference_number'],
                'aman_cashing_details' => $result['aman_cashing_details'],
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payout error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to process payout: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Check the status of a payout transaction
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayoutStatus(Request $request)
    {
        try {
            $request->validate([
                'transaction_id' => 'required|string',
            ]);

            $transactionId = $request->input('transaction_id');

            // First check if we have the transaction in our database
            $payoutTransaction = PaymobPayoutTransaction::where('transaction_id', $transactionId)->first();

            if (!$payoutTransaction) {
                ApiResponseService::errorResponse('Payout transaction not found', null, 404);
                return;
            }

            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Get updated status from Paymob
            $result = $payoutService->bulkTransactionInquiry([$transactionId]);

            if ($result['success'] && !empty($result['results'])) {
                $transactionData = $result['results'][0];

                // Update the transaction in our database
                $payoutTransaction->update([
                    'disbursement_status' => $transactionData['disbursement_status'],
                    'status_code' => $transactionData['status_code'],
                    'status_description' => $transactionData['status_description'],
                    'reference_number' => $transactionData['reference_number'] ?? null,
                    'paid' => $transactionData['paid'] ?? null,
                    'aman_cashing_details' => $transactionData['aman_cashing_details'] ?? null,
                    'transaction_data' => $transactionData,
                ]);

                ApiResponseService::successResponse('Payout status retrieved successfully', [
                    'transaction_id' => $transactionData['transaction_id'],
                    'issuer' => $transactionData['issuer'],
                    'amount' => $transactionData['amount'],
                    'disbursement_status' => $transactionData['disbursement_status'],
                    'status_code' => $transactionData['status_code'],
                    'status_description' => $transactionData['status_description'],
                    'reference_number' => $transactionData['reference_number'] ?? null,
                    'paid' => $transactionData['paid'] ?? null,
                    'aman_cashing_details' => $transactionData['aman_cashing_details'] ?? null,
                    'created_at' => $transactionData['created_at'],
                    'updated_at' => $transactionData['updated_at']
                ]);
            } else {
                // Return the stored transaction data if API call fails
                ApiResponseService::successResponse('Payout status retrieved from database', [
                    'transaction_id' => $payoutTransaction->transaction_id,
                    'issuer' => $payoutTransaction->issuer,
                    'amount' => $payoutTransaction->amount,
                    'disbursement_status' => $payoutTransaction->disbursement_status,
                    'status_code' => $payoutTransaction->status_code,
                    'status_description' => $payoutTransaction->status_description,
                    'reference_number' => $payoutTransaction->reference_number,
                    'paid' => $payoutTransaction->paid,
                    'aman_cashing_details' => $payoutTransaction->aman_cashing_details,
                    'created_at' => $payoutTransaction->created_at,
                    'updated_at' => $payoutTransaction->updated_at
                ]);
            }
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payout status error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to get payout status: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Cancel Aman transaction
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAmanTransaction(Request $request)
    {
        try {
            $request->validate([
                'transaction_id' => 'required|string|uuid',
            ]);

            $transactionId = $request->input('transaction_id');

            // Check if we have the transaction in our database
            $payoutTransaction = PaymobPayoutTransaction::where('transaction_id', $transactionId)->first();

            if (!$payoutTransaction) {
                ApiResponseService::errorResponse('Payout transaction not found', null, 404);
                return;
            }

            if ($payoutTransaction->issuer !== 'aman') {
                ApiResponseService::errorResponse('Only Aman transactions can be cancelled', null, 400);
                return;
            }

            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Cancel the Aman transaction
            $result = $payoutService->cancelAmanTransaction($transactionId);

            // Update the transaction in our database
            $payoutTransaction->update([
                'disbursement_status' => $result['disbursement_status'],
                'status_code' => $result['status_code'],
                'status_description' => $result['status_description'],
                'reference_number' => $result['reference_number'] ?? null,
                'paid' => $result['paid'] ?? null,
                'aman_cashing_details' => $result['aman_cashing_details'] ?? null,
                'transaction_data' => $result,
            ]);

            Log::info('Aman transaction cancelled', [
                'transaction_id' => $transactionId,
                'status' => $result['disbursement_status']
            ]);

            ApiResponseService::successResponse('Aman transaction cancelled successfully', [
                'transaction_id' => $result['transaction_id'],
                'issuer' => $result['issuer'],
                'amount' => $result['amount'],
                'disbursement_status' => $result['disbursement_status'],
                'status_code' => $result['status_code'],
                'status_description' => $result['status_description'],
                'reference_number' => $result['reference_number'] ?? null,
                'paid' => $result['paid'] ?? null,
                'aman_cashing_details' => $result['aman_cashing_details'] ?? null,
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob cancel Aman transaction error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to cancel Aman transaction: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Bulk transaction inquiry
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkTransactionInquiry(Request $request)
    {
        try {
            $request->validate([
                'transaction_ids' => 'required|array|min:1|max:50',
                'transaction_ids.*' => 'string|uuid',
                'is_bank_transactions' => 'boolean',
            ]);

            $transactionIds = $request->input('transaction_ids');
            $isBankTransactions = $request->input('is_bank_transactions', false);

            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Get transaction statuses
            $result = $payoutService->bulkTransactionInquiry($transactionIds, $isBankTransactions);

            // Update transactions in our database
            if ($result['success'] && !empty($result['results'])) {
                foreach ($result['results'] as $transactionData) {
                    $payoutTransaction = PaymobPayoutTransaction::where('transaction_id', $transactionData['transaction_id'])->first();

                    if ($payoutTransaction) {
                        $payoutTransaction->update([
                            'disbursement_status' => $transactionData['disbursement_status'],
                            'status_code' => $transactionData['status_code'],
                            'status_description' => $transactionData['status_description'],
                            'reference_number' => $transactionData['reference_number'] ?? null,
                            'paid' => $transactionData['paid'] ?? null,
                            'aman_cashing_details' => $transactionData['aman_cashing_details'] ?? null,
                            'transaction_data' => $transactionData,
                        ]);
                    }
                }
            }

            ApiResponseService::successResponse('Bulk transaction inquiry completed', [
                'count' => $result['count'],
                'next' => $result['next'] ?? null,
                'previous' => $result['previous'] ?? null,
                'results' => $result['results']
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob bulk transaction inquiry error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to inquire transactions: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get user budget (balance)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserBudget(Request $request)
    {
        try {
            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Get user budget
            $result = $payoutService->getUserBudget();

            ApiResponseService::successResponse('User budget retrieved successfully', [
                'current_budget' => $result['current_budget'],
                'status_description' => $result['status_description'] ?? null,
                'status_code' => $result['status_code'] ?? null
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob get user budget error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to get user budget: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get bank codes
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankCodes(Request $request)
    {
        try {
            $payoutService = new PaymobPayoutService();
            $bankCodes = $payoutService->getBankCodes();

            ApiResponseService::successResponse('Bank codes retrieved successfully', [
                'bank_codes' => $bankCodes
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob get bank codes error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to get bank codes: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get bank transaction types
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankTransactionTypes(Request $request)
    {
        try {
            $payoutService = new PaymobPayoutService();
            $bankTransactionTypes = $payoutService->getBankTransactionTypes();

            ApiResponseService::successResponse('Bank transaction types retrieved successfully', [
                'bank_transaction_types' => $bankTransactionTypes
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob get bank transaction types error: ' . $e->getMessage());
            ApiResponseService::errorResponse('Failed to get bank transaction types: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get payout transactions (with pagination and filters)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayoutTransactions(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'status' => 'string|in:success,successful,failed,pending',
                'issuer' => 'string|in:vodafone,etisalat,orange,aman,bank_wallet,bank_card',
                'customer_id' => 'integer|exists:customers,id',
                'date_from' => 'date',
                'date_to' => 'date|after_or_equal:date_from',
            ]);

            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            $issuer = $request->input('issuer');
            $customerId = $request->input('customer_id');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query = PaymobPayoutTransaction::query();

            // Apply filters
            if ($status) {
                $query->byStatus($status);
            }

            if ($issuer) {
                $query->byIssuer($issuer);
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Get paginated results
            $transactions = $query->with('customer')->paginate($perPage);

            ApiResponseService::successResponse('Payout transactions retrieved successfully', [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ]
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Paymob get payout transactions error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to get payout transactions: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Handle payout callback from Paymob
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePayoutCallback(Request $request)
    {
        try {
            Log::info('Paymob payout callback received', $request->all());

            $data = $request->all();
            $transactionId = $data['transaction_id'];

            // Find the payout transaction
            $payoutTransaction = PaymobPayoutTransaction::where('transaction_id', $transactionId)->first();

            if ($payoutTransaction) {
                // Update the transaction with the latest data
                $payoutTransaction->update([
                    'disbursement_status' => $data['disbursement_status'],
                    'status_code' => $data['status_code'],
                    'status_description' => $data['status_description'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'paid' => $data['paid'] ?? null,
                    'aman_cashing_details' => $data['aman_cashing_details'] ?? null,
                    'transaction_data' => $data,
                ]);

                Log::info('Payout transaction updated from callback', [
                    'transaction_id' => $transactionId,
                    'status' => $data['disbursement_status']
                ]);
            } else {
                Log::warning('Payout transaction not found for callback', [
                    'transaction_id' => $transactionId
                ]);
            }

            ApiResponseService::successResponse('Payout callback processed successfully');
            return;
        } catch (\Exception $e) {
            Log::error('Paymob payout callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to process payout callback: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Handle the callback from Paymob for send money transactions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSendMoneyCallback(Request $request)
    {
        try {
            Log::info('Paymob send money callback received', $request->all());

            // Validate HMAC if configured
            if (config('paymob.hmac_secret')) {
                $this->validateHmac($request);
            }

            $data = $request->all();
            $transactionId = $data['obj']['order']['merchant_order_id'];
            $paymentStatus = $data['obj']['success'] ? 'succeed' : 'failed';
            $paymobOrderId = $data['obj']['order']['id'];
            $paymobTransactionId = $data['obj']['id'];

            Log::info('Paymob send money callback - Transaction ID:', ['transaction_id' => $transactionId]);
            Log::info('Paymob send money callback - Payment Status:', ['status' => $paymentStatus]);

            // Find the send money transaction
            $sendMoney = SendMoney::where('transaction_id', $transactionId)->first();

            if ($sendMoney) {
                // Only update if not already updated
                if ($sendMoney->status === 'pending') {
                    $sendMoney->status = $paymentStatus === 'succeed' ? 'completed' : 'failed';
                    $sendMoney->payment_status = $paymentStatus === 'succeed' ? 'paid' : 'failed';
                    $sendMoney->paymob_order_id = $paymobOrderId;
                    $sendMoney->paymob_transaction_id = $paymobTransactionId;
                    $sendMoney->transaction_data = json_encode($data);
                    $sendMoney->save();

                    Log::info('Send money transaction updated successfully', [
                        'send_money_id' => $sendMoney->id,
                        'status' => $sendMoney->status,
                        'payment_status' => $sendMoney->payment_status
                    ]);
                }
            } else {
                Log::warning('Send money transaction not found for callback', [
                    'transaction_id' => $transactionId
                ]);

                // Try to find by Paymob transaction ID as fallback
                $sendMoneyByPaymobId = SendMoney::where('paymob_transaction_id', $paymobTransactionId)->first();
                if ($sendMoneyByPaymobId) {
                    Log::info('Send money transaction found by Paymob transaction ID', [
                        'paymob_transaction_id' => $paymobTransactionId,
                        'send_money_id' => $sendMoneyByPaymobId->id
                    ]);

                    // Update the transaction with the correct transaction ID
                    $sendMoneyByPaymobId->transaction_id = $transactionId;
                    $sendMoneyByPaymobId->status = $paymentStatus === 'succeed' ? 'completed' : 'failed';
                    $sendMoneyByPaymobId->payment_status = $paymentStatus === 'succeed' ? 'paid' : 'failed';
                    $sendMoneyByPaymobId->paymob_order_id = $paymobOrderId;
                    $sendMoneyByPaymobId->paymob_transaction_id = $paymobTransactionId;
                    $sendMoneyByPaymobId->transaction_data = json_encode($data);
                    $sendMoneyByPaymobId->save();

                    $sendMoney = $sendMoneyByPaymobId;
                }
            }

            ApiResponseService::successResponse('Send money callback processed successfully');
            return;
        } catch (\Exception $e) {
            Log::error('Paymob send money callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to process send money callback: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Handle the return from Paymob send money payment page
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleSendMoneyReturn(Request $request)
    {
        Log::info('Paymob send money return received', $request->all());

        try {
            $success = $request->input('success') === 'true';
            $transactionId = $request->input('merchant_order_id');

            Log::info('Paymob send money return details', [
                'success' => $success,
                'transaction_id' => $transactionId
            ]);

            // Find the send money transaction
            $sendMoney = SendMoney::where('transaction_id', $transactionId)->first();

            if ($sendMoney && $success) {
                Log::info('Send money transaction found and successful', [
                    'send_money_id' => $sendMoney->id,
                    'transaction_id' => $transactionId
                ]);
                return redirect()->route('send-money.success', [
                    'transaction_id' => $transactionId,
                    'send_money_id' => $sendMoney->id,
                    'source' => 'paymob'
                ]);
            } else {
                if (!$sendMoney) {
                    Log::warning('Send money transaction not found for return', [
                        'transaction_id' => $transactionId
                    ]);
                } else {
                    Log::warning('Send money transaction found but not successful', [
                        'send_money_id' => $sendMoney->id,
                        'success' => $success
                    ]);
                }
                return redirect()->route('send-money.failed', [
                    'transaction_id' => $transactionId,
                    'source' => 'paymob'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Paymob send money return error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('send-money.failed', ['source' => 'paymob']);
        }
    }
}
