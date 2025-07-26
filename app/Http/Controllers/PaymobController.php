<?php

namespace App\Http\Controllers;

use App\Models\PaymobPayment;
use App\Services\ApiResponseService;
use App\Services\Payment\PaymentService;
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

            $transactionId = $data['obj']['order']['merchant_order_id'];
            $paymentStatus = $data['obj']['success'] ? 'succeed' : 'failed';
            $paymobOrderId = $data['obj']['order']['id'];
            $paymobTransactionId = $data['obj']['id'];

            Log::info('Paymob callback received - Transaction ID:', ['transaction_id' => $transactionId]);
            Log::info('Paymob callback received - Payment Status:', ['status' => $paymentStatus]);
            Log::info('Paymob callback received - Paymob Order ID:', ['order_id' => $paymobOrderId]);
            Log::info('Paymob callback received - Paymob Transaction ID:', ['paymob_transaction_id' => $paymobTransactionId]);

            try {
                $payment = PaymobPayment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    $payment->status = $paymentStatus;
                    $payment->paymob_order_id = $paymobOrderId;
                    $payment->paymob_transaction_id = $paymobTransactionId;
                    $payment->transaction_data = json_encode($data);
                    $payment->save();

                    // Update reservation status
                    if ($payment->reservation_id) {
                        $reservation = Reservation::find($payment->reservation_id);
                        if ($reservation) {
                            $reservation->status = $paymentStatus === 'succeed' ? 'confirmed' : 'cancelled';
                            $reservation->payment_status = $paymentStatus === 'succeed' ? 'paid' : 'failed';
                            $reservation->save();

                            Log::info('Reservation status updated', [
                                'reservation_id' => $reservation->id,
                                'status' => $reservation->status,
                                'payment_status' => $reservation->payment_status
                            ]);

                            // Only update available dates if payment was successful
                            if ($paymentStatus === 'succeed') {
                                try {
                                    $reservationService = app(\App\Services\ReservationService::class);
                                    $reservationService->updateAvailableDates(
                                        $reservation->reservable_type,
                                        $reservation->reservable_id,
                                        $reservation->check_in_date,
                                        $reservation->check_out_date,
                                        $reservation->id
                                    );

                                    Log::info('Available dates updated successfully', [
                                        'reservation_id' => $reservation->id
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('Failed to update available dates', [
                                        'error' => $e->getMessage(),
                                        'reservation_id' => $reservation->id,
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
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
                return redirect()->route('payment.success', [
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
                return redirect()->route('payment.failed', [
                    'transaction_id' => $transactionId,
                    'source' => 'paymob'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Paymob return error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('payment.failed', ['source' => 'paymob']);
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

            // Generate a unique transaction ID
            $transactionId = Str::uuid()->toString();

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
