<?php

namespace App\Http\Controllers;

use App\Models\PaymobPayment;
use App\Models\PaymobPayoutTransaction;
use App\Models\SendMoney;
use App\Models\PaymentTransaction;
use App\Models\Package;
use App\Models\UserPackage;
use App\Models\PackageFeature;
use App\Models\UserPackageLimit;
use App\Services\ApiResponseService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymobPayoutService;
use App\Services\HelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
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

            // Check callback type and handle accordingly
            $callbackType = $data['type'] ?? 'UNKNOWN';
            Log::info('Paymob callback - Callback type:', ['type' => $callbackType]);

            // Initialize variables
            $transactionId = null;
            $paymentStatus = 'failed';
            $paymobOrderId = null;
            $paymobTransactionId = null;

            // Handle different callback types
            if ($callbackType === 'TOKEN') {
                Log::info('Paymob callback - TOKEN callback received', [
                    'token_id' => $data['obj']['id'] ?? 'unknown',
                    'order_id' => $data['obj']['order_id'] ?? 'unknown'
                ]);

                // For TOKEN callbacks, we need to find the payment by order_id
                $paymobOrderId = $data['obj']['order_id'] ?? null;
                if ($paymobOrderId) {
                    // Find payment by Paymob order ID
                    $payment = PaymobPayment::where('paymob_order_id', $paymobOrderId)->first();
                    if ($payment) {
                        $transactionId = $payment->transaction_id;
                        $paymentStatus = 'succeed'; // TOKEN callback means payment was successful
                        $paymobTransactionId = $data['obj']['id'] ?? null;

                        Log::info('Paymob callback - Payment found by order ID in TOKEN callback', [
                            'payment_id' => $payment->id,
                            'transaction_id' => $transactionId,
                            'reservation_id' => $payment->reservation_id,
                            'order_id' => $paymobOrderId
                        ]);

                        // Update the payment status immediately
                        $payment->status = $paymentStatus;
                        $payment->paymob_transaction_id = $paymobTransactionId;
                        $payment->transaction_data = json_encode($data);
                        $payment->save();

                        Log::info('Payment updated in TOKEN callback', [
                            'payment_id' => $payment->id,
                            'status' => $payment->status,
                            'reservation_id' => $payment->reservation_id
                        ]);
                    } else {
                        // Try to find by order ID in the transaction data
                        $paymentWithOrderIdInData = PaymobPayment::where('transaction_data', 'LIKE', '%"id":"' . $paymobOrderId . '"%')->first();
                        if ($paymentWithOrderIdInData) {
                            Log::info('Paymob callback - Payment found by order ID in transaction data', [
                                'payment_id' => $paymentWithOrderIdInData->id,
                                'transaction_id' => $paymentWithOrderIdInData->transaction_id,
                                'reservation_id' => $paymentWithOrderIdInData->reservation_id,
                                'order_id' => $paymobOrderId
                            ]);

                            $payment = $paymentWithOrderIdInData;
                            $transactionId = $payment->transaction_id;
                            $paymentStatus = 'succeed';
                            $paymobTransactionId = $data['obj']['id'] ?? null;

                            // Update the payment
                            $payment->status = $paymentStatus;
                            $payment->paymob_order_id = $paymobOrderId;
                            $payment->paymob_transaction_id = $paymobTransactionId;
                            $payment->transaction_data = json_encode($data);
                            $payment->save();
                        } else {
                            Log::warning('Paymob callback - No payment found for order ID', [
                                'order_id' => $paymobOrderId
                            ]);
                            return ApiResponseService::successResponse('TOKEN callback received but no payment found');
                        }
                    }
                } else {
                    Log::warning('Paymob callback - No order ID in TOKEN callback');
                    return ApiResponseService::successResponse('TOKEN callback received but no order ID');
                }
            } else {
                // Default case: TRANSACTION callback or unknown type (treat as TRANSACTION for backward compatibility)
                Log::info('Paymob callback - TRANSACTION callback received (default case)', [
                    'callback_type' => $callbackType
                ]);

                // Add more detailed logging to debug the transaction ID issue
                Log::info('Paymob callback - Full data structure:', [
                    'obj_exists' => isset($data['obj']),
                    'order_exists' => isset($data['obj']['order']),
                    'merchant_order_id_exists' => isset($data['obj']['order']['merchant_order_id']),
                    'merchant_order_id_value' => $data['obj']['order']['merchant_order_id'] ?? 'NOT_FOUND',
                    'full_obj' => $data['obj'] ?? 'NOT_FOUND'
                ]);

                // Safely extract data with proper error handling
                // Try multiple possible locations for merchant_order_id
                $transactionId = $data['obj']['order']['merchant_order_id'] 
                    ?? $data['obj']['merchant_order_id'] 
                    ?? $data['merchant_order_id'] 
                    ?? null;
                $paymentStatus = ($data['obj']['success'] ?? false) ? 'succeed' : 'failed';
                $paymobOrderId = $data['obj']['order']['id'] ?? $data['obj']['id'] ?? null;
                $paymobTransactionId = $data['obj']['id'] ?? null;
            }

            // Validate required data
            if (!$transactionId) {
                Log::error('Paymob callback - Missing transaction ID', [
                    'callback_type' => $callbackType,
                    'data_structure' => $data,
                    'obj_exists' => isset($data['obj']),
                    'order_exists' => isset($data['obj']['order']),
                    'merchant_order_id_exists' => isset($data['obj']['order']['merchant_order_id'])
                ]);
                return ApiResponseService::errorResponse('Missing transaction ID in callback data');
            }

            Log::info('Paymob callback received - Transaction ID:', ['transaction_id' => $transactionId]);
            Log::info('Paymob callback received - Payment Status:', ['status' => $paymentStatus]);
            Log::info('Paymob callback received - Paymob Order ID:', ['order_id' => $paymobOrderId]);
            Log::info('Paymob callback received - Paymob Transaction ID:', ['paymob_transaction_id' => $paymobTransactionId]);

            // Route callback based on transaction ID prefix
            $isSendMoney = strpos($transactionId, 'SEND_') === 0;
            $isReservation = strpos($transactionId, 'RES_') === 0;
            $isPackage = strpos($transactionId, 'PKG_') === 0;

            Log::info('Paymob callback - Checking transaction type', [
                'transaction_id' => $transactionId,
                'is_send_money' => $isSendMoney,
                'is_reservation' => $isReservation,
                'is_package' => $isPackage
            ]);

            if ($isSendMoney) {
                Log::info('Paymob callback - Send money transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                return $this->handleSendMoneyCallback($request);
            } elseif ($isPackage) {
                Log::info('Paymob callback - Package payment transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                return $this->handlePackagePaymentCallback($request, $transactionId, $paymentStatus, $paymobOrderId, $paymobTransactionId, $data);
            } elseif ($isReservation) {
                Log::info('Paymob callback - Reservation transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                // Continue with existing reservation logic
            } else {
                Log::warning('Paymob callback - Unknown transaction type', [
                    'transaction_id' => $transactionId
                ]);
                // Try to handle as reservation for backward compatibility
            }

            try {
                // Check for payment by Paymob order ID first (most reliable)
                $paymobOrderId = isset($data['obj']['order']['id']) ? $data['obj']['order']['id'] : null;

                if ($paymobOrderId) {
                    $paymentByOrderId = PaymobPayment::where('paymob_order_id', $paymobOrderId)->first();

                    if ($paymentByOrderId) {
                        Log::info('Paymob callback - Payment found by Paymob order ID', [
                            'payment_id' => $paymentByOrderId->id,
                            'paymob_order_id' => $paymobOrderId,
                            'reservation_id' => $paymentByOrderId->reservation_id,
                            'original_transaction_id' => $paymentByOrderId->transaction_id
                        ]);

                        // Update payment with current transaction ID and status
                        $paymentByOrderId->transaction_id = $transactionId;
                        $paymentByOrderId->status = $paymentStatus;
                        $paymentByOrderId->paymob_transaction_id = $paymobTransactionId;
                        $paymentByOrderId->transaction_data = json_encode($data);
                        $paymentByOrderId->save();

                        $payment = $paymentByOrderId;
                    }
                }

                // If not found by order ID, try by transaction ID
                if (!isset($payment)) {
                    // Log all payment records to debug the transaction ID issue
                    $allPayments = PaymobPayment::where('status', 'pending')->get(['id', 'transaction_id', 'paymob_order_id', 'reservation_id']);
                    Log::info('Paymob callback - All pending payments:', [
                        'payments' => $allPayments->toArray(),
                        'searching_for_transaction_id' => $transactionId,
                        'searching_for_order_id' => $paymobOrderId
                    ]);

                    // Also check all payments (not just pending) to see if the payment exists
                    $allPaymentsAllStatuses = PaymobPayment::where('transaction_id', $transactionId)->get(['id', 'transaction_id', 'paymob_order_id', 'status', 'reservation_id']);
                    Log::info('Paymob callback - All payments with this transaction ID:', [
                        'payments' => $allPaymentsAllStatuses->toArray(),
                        'transaction_id' => $transactionId
                    ]);

                    // Check for similar transaction IDs (in case of formatting issues)
                    $similarPayments = PaymobPayment::where('transaction_id', 'LIKE', '%' . substr($transactionId, -10) . '%')->get(['id', 'transaction_id', 'paymob_order_id', 'status', 'reservation_id']);
                    Log::info('Paymob callback - Similar transaction IDs found:', [
                        'payments' => $similarPayments->toArray(),
                        'search_pattern' => '%' . substr($transactionId, -10) . '%'
                    ]);

                    $payment = PaymobPayment::where('transaction_id', $transactionId)->first();
                }
                if ($payment) {
                    // Only update if not already updated by the fallback logic
                    if ($payment->status === 'pending') {
                        $payment->status = $paymentStatus;
                        $payment->paymob_order_id = $paymobOrderId;
                        $payment->paymob_transaction_id = $paymobTransactionId;
                        $payment->transaction_data = json_encode($data);
                        $payment->save();
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
                    } else {
                        // Try to find payment by reservation ID if it's a reservation transaction
                        if ($isReservation) {
                            // Extract reservation ID from transaction ID (format: RES_timestamp_customerId_random)
                            $transactionParts = explode('_', $transactionId);
                            if (count($transactionParts) >= 3) {
                                $customerId = $transactionParts[2];

                                // First, try to find payment by transaction ID directly
                                $paymentByTransactionId = PaymobPayment::where('transaction_id', $transactionId)->first();

                                if ($paymentByTransactionId) {
                                    Log::info('Payment found by exact transaction ID match', [
                                        'payment_id' => $paymentByTransactionId->id,
                                        'transaction_id' => $transactionId,
                                        'reservation_id' => $paymentByTransactionId->reservation_id
                                    ]);

                                    // Update payment with status and Paymob transaction ID
                                    $paymentByTransactionId->status = $paymentStatus;
                                    $paymentByTransactionId->paymob_order_id = $paymobOrderId;
                                    $paymentByTransactionId->paymob_transaction_id = $paymobTransactionId;
                                    $paymentByTransactionId->transaction_data = json_encode($data);
                                    $paymentByTransactionId->save();

                                    $payment = $paymentByTransactionId;
                                }

                                // If not found by transaction ID, try to find payment by Paymob order ID (more specific)
                                if (!isset($payment) && $paymobOrderId) {
                                    $paymentByOrderId = PaymobPayment::where('paymob_order_id', $paymobOrderId)->first();
                                    if ($paymentByOrderId) {
                                        Log::info('Payment found by Paymob order ID in fallback', [
                                            'paymob_order_id' => $paymobOrderId,
                                            'payment_id' => $paymentByOrderId->id,
                                            'original_transaction_id' => $paymentByOrderId->transaction_id
                                        ]);

                                        // Update the payment with the correct transaction ID
                                        $paymentByOrderId->transaction_id = $transactionId;
                                        $paymentByOrderId->status = $paymentStatus;
                                        $paymentByOrderId->paymob_order_id = $paymobOrderId;
                                        $paymentByOrderId->paymob_transaction_id = $paymobTransactionId;
                                        $paymentByOrderId->transaction_data = json_encode($data);
                                        $paymentByOrderId->save();

                                        $payment = $paymentByOrderId;
                                    }
                                }

                                // If still not found, try to find by customer ID and recent creation
                                if (!$payment) {
                                    // Try to find payment by similar transaction ID pattern first
                                    $similarTransactionId = 'RES_' . $transactionParts[1] . '_' . $customerId . '_%';
                                    $similarPayment = PaymobPayment::where('customer_id', $customerId)
                                        ->where('transaction_id', 'LIKE', $similarTransactionId)
                                        ->where('status', 'pending')
                                        ->orderBy('created_at', 'desc')
                                        ->first();

                                    if ($similarPayment) {
                                        Log::info('Payment found by similar transaction ID pattern', [
                                            'customer_id' => $customerId,
                                            'payment_id' => $similarPayment->id,
                                            'original_transaction_id' => $similarPayment->transaction_id,
                                            'pattern' => $similarTransactionId
                                        ]);

                                        // Update the payment with the correct transaction ID
                                        $similarPayment->transaction_id = $transactionId;
                                        $similarPayment->status = $paymentStatus;
                                        $similarPayment->paymob_order_id = $paymobOrderId;
                                        $similarPayment->paymob_transaction_id = $paymobTransactionId;
                                        $similarPayment->transaction_data = json_encode($data);
                                        $similarPayment->save();

                                        $payment = $similarPayment;
                                    }

                                    // If still not found, find the most recent payment for this customer
                                    if (!$payment) {
                                        $recentPayment = PaymobPayment::where('customer_id', $customerId)
                                            ->where('status', 'pending')
                                            ->orderBy('created_at', 'desc')
                                            ->first();

                                        if ($recentPayment) {
                                            Log::info('Payment found by customer ID and recent creation', [
                                                'customer_id' => $customerId,
                                                'payment_id' => $recentPayment->id,
                                                'original_transaction_id' => $recentPayment->transaction_id
                                            ]);

                                            // Update the payment with the correct transaction ID
                                            $recentPayment->transaction_id = $transactionId;
                                            $recentPayment->status = $paymentStatus;
                                            $recentPayment->paymob_order_id = $paymobOrderId;
                                            $recentPayment->paymob_transaction_id = $paymobTransactionId;
                                            $recentPayment->transaction_data = json_encode($data);
                                            $recentPayment->save();

                                            $payment = $recentPayment;
                                        } else {
                                            // Try to find any payment created in the last 5 minutes
                                            $recentPayments = PaymobPayment::where('customer_id', $customerId)
                                                ->where('created_at', '>=', now()->subMinutes(5))
                                                ->orderBy('created_at', 'desc')
                                                ->get();

                                            if ($recentPayments->isNotEmpty()) {
                                                Log::info('Found recent payments for customer', [
                                                    'customer_id' => $customerId,
                                                    'recent_payments_count' => $recentPayments->count(),
                                                    'recent_payments' => $recentPayments->pluck('transaction_id')->toArray()
                                                ]);

                                                // Use the most recent one
                                                $recentPayment = $recentPayments->first();

                                                Log::info('Using most recent payment as fallback', [
                                                    'payment_id' => $recentPayment->id,
                                                    'original_transaction_id' => $recentPayment->transaction_id
                                                ]);

                                                // Update the payment with the correct transaction ID
                                                $recentPayment->transaction_id = $transactionId;
                                                $recentPayment->status = $paymentStatus;
                                                $recentPayment->paymob_order_id = $paymobOrderId;
                                                $recentPayment->paymob_transaction_id = $paymobTransactionId;
                                                $recentPayment->transaction_data = json_encode($data);
                                                $recentPayment->save();

                                                $payment = $recentPayment;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Final fallback: if no payment is found, create one
                        if (!$payment && $isReservation) {
                            Log::warning('No payment found, creating fallback payment record', [
                                'transaction_id' => $transactionId,
                                'customer_id' => $customerId ?? 'unknown'
                            ]);

                            // Extract customer ID from transaction ID
                            $transactionParts = explode('_', $transactionId);
                            $customerId = count($transactionParts) >= 3 ? $transactionParts[2] : null;

                            if ($customerId) {
                                // Create a fallback payment record
                                $payment = PaymobPayment::create([
                                    'customer_id' => $customerId,
                                    'transaction_id' => $transactionId,
                                    'amount' => $data['obj']['amount_cents'] / 100, // Convert from cents
                                    'currency' => $data['obj']['currency'] ?? 'EGP',
                                    'status' => $paymentStatus,
                                    'payment_method' => 'paymob',
                                    'paymob_order_id' => $paymobOrderId,
                                    'paymob_transaction_id' => $paymobTransactionId,
                                    'transaction_data' => json_encode($data),
                                ]);

                                Log::info('Fallback payment record created', [
                                    'payment_id' => $payment->id,
                                    'transaction_id' => $payment->transaction_id
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing payment data', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transactionId,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Update reservation status if payment was found/updated
            if (isset($payment) && $payment && $payment->reservation_id) {
                $reservation = Reservation::find($payment->reservation_id);
                if ($reservation) {
                    if ($paymentStatus === 'succeed') {
                        // Use the new service method for successful payments
                        $reservationService = app(\App\Services\ReservationService::class);
                        $reservationService->handleReservationConfirmation($reservation, 'paid');

                        Log::info('Reservation confirmed due to successful payment', [
                            'reservation_id' => $reservation->id,
                            'status' => $reservation->status,
                            'payment_status' => $reservation->payment_status
                        ]);
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
                    Log::warning('Reservation not found for payment', [
                        'reservation_id' => $payment->reservation_id,
                        'payment_id' => $payment->id
                    ]);
                }
            }

            // Log final payment status
            if (isset($payment) && $payment) {
                Log::info('Payment callback processing completed successfully', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'status' => $payment->status,
                    'reservation_id' => $payment->reservation_id
                ]);
            } else {
                Log::error('Payment callback processing failed - no payment found or created', [
                    'transaction_id' => $transactionId,
                    'paymob_transaction_id' => $paymobTransactionId
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
            $paymobOrderId = $request->input('order');
            $paymobTransactionId = $request->input('id');

            Log::info('Paymob return details', [
                'success' => $success,
                'transaction_id' => $transactionId,
                'paymob_order_id' => $paymobOrderId,
                'paymob_transaction_id' => $paymobTransactionId
            ]);

            // Route return based on transaction ID prefix
            $isSendMoney = strpos($transactionId, 'SEND_') === 0;
            $isReservation = strpos($transactionId, 'RES_') === 0;
            $isPackage = strpos($transactionId, 'PKG_') === 0;
            
            // If transaction ID is numeric, try to find the payment transaction to determine type
            if (!$isSendMoney && !$isReservation && !$isPackage && is_numeric($transactionId)) {
                // Try to find by PaymentTransaction ID first
                $tempPaymentTransaction = PaymentTransaction::where('id', $transactionId)
                    ->where('payment_type', 'online payment')
                    ->first();
                
                if ($tempPaymentTransaction) {
                    // Check if it's a package transaction
                    if ($tempPaymentTransaction->package_id || strpos($tempPaymentTransaction->transaction_id, 'PKG_') === 0) {
                        $isPackage = true;
                    } elseif (strpos($tempPaymentTransaction->transaction_id, 'RES_') === 0) {
                        $isReservation = true;
                    } elseif (strpos($tempPaymentTransaction->transaction_id, 'SEND_') === 0) {
                        $isSendMoney = true;
                    }
                }
            }

            Log::info('Paymob return - Checking transaction type', [
                'transaction_id' => $transactionId,
                'is_send_money' => $isSendMoney,
                'is_reservation' => $isReservation,
                'is_package' => $isPackage,
                'is_numeric' => is_numeric($transactionId)
            ]);

            if ($isSendMoney) {
                Log::info('Paymob return - Send money transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                return $this->handleSendMoneyReturn($request);
            } elseif ($isPackage) {
                Log::info('Paymob return - Package payment transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                
                // Handle package payment return
                $paymentTransaction = null;
                
                // First try to find by order_id (most reliable - this is the Paymob order ID)
                if ($paymobOrderId) {
                    $paymentTransaction = PaymentTransaction::where('order_id', $paymobOrderId)
                        ->where('payment_type', 'online payment')
                        ->first();
                    
                    Log::info('Paymob package return - Search by order_id', [
                        'paymob_order_id' => $paymobOrderId,
                        'found' => $paymentTransaction ? true : false
                    ]);
                }
                
                // If not found by order_id, try by transaction_id (our internal transaction ID)
                if (!$paymentTransaction && $transactionId) {
                    // Try exact match first
                    $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)
                        ->where('payment_type', 'online payment')
                        ->first();
                    
                    // If not found, try partial match for group transactions
                    if (!$paymentTransaction) {
                        $paymentTransaction = PaymentTransaction::where('transaction_id', 'LIKE', $transactionId . '%')
                            ->where('payment_type', 'online payment')
                            ->first();
                    }
                    
                    // If still not found and transaction_id looks like a number, try to find by payment_transaction_id
                    // Sometimes Paymob returns the payment_transaction_id as the merchant_order_id
                    if (!$paymentTransaction && is_numeric($transactionId)) {
                        $paymentTransaction = PaymentTransaction::where('id', $transactionId)
                            ->where('payment_type', 'online payment')
                            ->first();
                    }
                    
                    Log::info('Paymob package return - Search by transaction_id', [
                        'transaction_id' => $transactionId,
                        'found' => $paymentTransaction ? true : false
                    ]);
                }
                
                if ($paymentTransaction) {
                    // Extract group transaction ID
                    $groupTransactionId = null;
                    if (strpos($paymentTransaction->transaction_id, 'PKG_GROUP_') === 0) {
                        $parts = explode('_', $paymentTransaction->transaction_id);
                        if (count($parts) >= 3) {
                            // Reconstruct group ID: PKG_GROUP_UUID
                            $groupTransactionId = $parts[0] . '_' . $parts[1] . '_' . $parts[2];
                        }
                    }
                    
                    // Find all transactions in group
                    $allPaymentTransactions = [];
                    if ($groupTransactionId) {
                        $allPaymentTransactions = PaymentTransaction::where('transaction_id', 'LIKE', $groupTransactionId . '%')
                            ->where('payment_type', 'online payment')
                            ->where('order_id', $paymentTransaction->order_id)
                            ->get();
                    } else {
                        // Fallback: find by order_id (for single package or backward compatibility)
                        $allPaymentTransactions = PaymentTransaction::where('order_id', $paymentTransaction->order_id)
                            ->where('payment_type', 'online payment')
                            ->get();
                    }
                    
                    if ($allPaymentTransactions->isEmpty()) {
                        $allPaymentTransactions = collect([$paymentTransaction]);
                    }
                    
                    // Update all transactions
                    foreach ($allPaymentTransactions as $pt) {
                        if ($pt->payment_status === 'pending') {
                            $pt->payment_status = $success ? 'success' : 'failed';
                            $pt->save();
                            
                            if ($success && $pt->package_id) {
                                $this->assignPackageToUser($pt);
                            }
                        }
                    }
                    
                    if ($success) {
                        return redirect()->route('payments.paymob-success', [
                            'transaction_id' => $transactionId ?: $paymentTransaction->transaction_id,
                            'source' => 'paymob',
                            'type' => 'package',
                            'package_count' => $allPaymentTransactions->count()
                        ]);
                    }
                } else {
                    // Payment transaction not found - log detailed error
                    Log::error('Paymob package return - Payment transaction not found', [
                        'transaction_id' => $transactionId,
                        'paymob_order_id' => $paymobOrderId,
                        'paymob_transaction_id' => $paymobTransactionId,
                        'success' => $success,
                        'request_data' => $request->all()
                    ]);
                }
                
                // Redirect to failed page if payment transaction not found or payment failed
                return redirect()->route('payments.paymob-failed', [
                    'transaction_id' => $transactionId,
                    'source' => 'paymob',
                    'type' => 'package'
                ]);
            } elseif ($isReservation) {
                Log::info('Paymob return - Reservation transaction detected', [
                    'transaction_id' => $transactionId
                ]);
                // Continue with existing reservation logic
            } else {
                Log::warning('Paymob return - Unknown transaction type', [
                    'transaction_id' => $transactionId
                ]);
                // Try to handle as reservation for backward compatibility
            }

            // Find the payment (for reservations)
            $payment = null;

            // First try to find by Paymob order ID (most reliable)
            if ($paymobOrderId) {
                $payment = PaymobPayment::where('paymob_order_id', $paymobOrderId)->first();
                if ($payment) {
                    Log::info('Payment found by Paymob order ID', [
                        'payment_id' => $payment->id,
                        'paymob_order_id' => $paymobOrderId,
                        'reservation_id' => $payment->reservation_id
                    ]);
                }
            }

            // If not found by order ID, try by transaction ID
            if (!$payment && $transactionId) {
                $payment = PaymobPayment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    Log::info('Payment found by transaction ID', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId,
                        'reservation_id' => $payment->reservation_id
                    ]);
                }
            }

            if ($payment) {
                // Update payment record with latest info
                if ($payment->status === 'pending') {
                    $payment->status = $success ? 'succeed' : 'failed';
                    if ($paymobOrderId) $payment->paymob_order_id = $paymobOrderId;
                    if ($paymobTransactionId) $payment->paymob_transaction_id = $paymobTransactionId;
                    if ($transactionId) $payment->transaction_id = $transactionId;
                    $payment->save();

                    Log::info('Payment record updated in handleReturn', [
                        'payment_id' => $payment->id,
                        'status' => $payment->status,
                        'reservation_id' => $payment->reservation_id
                    ]);

                    // Update reservation status if payment was successful
                    if ($success && $payment->reservation_id) {
                        $reservation = \App\Models\Reservation::find($payment->reservation_id);
                        if ($reservation) {
                            $reservationService = app(\App\Services\ReservationService::class);
                            $reservationService->handleReservationConfirmation($reservation, 'paid');

                            Log::info('Reservation confirmed in handleReturn', [
                                'reservation_id' => $reservation->id,
                                'status' => $reservation->status,
                                'payment_status' => $reservation->payment_status
                            ]);
                        }
                    }
                }

                if ($success) {
                    Log::info('Payment found and successful', [
                        'payment_id' => $payment->id,
                        'reservation_id' => $payment->reservation_id
                    ]);
                    return redirect()->route('payments.paymob-success', [
                        'transaction_id' => $transactionId ?: $payment->transaction_id,
                        'reservation_id' => $payment->reservation_id,
                        'source' => 'paymob'
                    ]);
                }
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

            // Check if there's an associated reservation
            $requiresApproval = false;
            $reservation = null;

            if ($payment->reservation_id) {
                $reservation = Reservation::find($payment->reservation_id);

                if ($reservation) {
                    // Check if the reservation has already started
                    $now = now();
                    $checkInDate = \Carbon\Carbon::parse($reservation->check_in_date);

                    // If current date is equal to or after check-in date, the reservation has started
                    if ($now->startOfDay()->gte($checkInDate->startOfDay())) {
                        $requiresApproval = true;
                        Log::info('Refund requires approval as reservation has already started', [
                            'reservation_id' => $reservation->id,
                            'check_in_date' => $reservation->check_in_date,
                            'current_date' => $now->toDateString()
                        ]);
                    }
                }
            }

            // Update payment with refund request details
            $payment->refund_status = $requiresApproval ? 'pending' : 'approved';
            $payment->refund_reason = $reason;
            $payment->requires_approval = $requiresApproval;
            $payment->refund_amount = $amount;
            $payment->save();

            // If approval is required, notify the property owner and return
            if ($requiresApproval) {
                // Get property owner information
                $propertyOwnerId = null;

                if ($reservation) {
                    if ($reservation->reservable_type === 'App\\Models\\Property') {
                        $property = \App\Models\Property::find($reservation->reservable_id);
                        if ($property) {
                            $propertyOwnerId = $property->added_by;
                        }
                    } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                        $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                        if ($hotelRoom && $hotelRoom->property) {
                            $propertyOwnerId = $hotelRoom->property->added_by;
                        }
                    }
                }

                if ($propertyOwnerId) {
                    // TODO: Send notification to property owner about refund request
                    Log::info('Notification should be sent to property owner', [
                        'property_owner_id' => $propertyOwnerId,
                        'payment_id' => $payment->id,
                        'refund_amount' => $amount
                    ]);
                }

                ApiResponseService::successResponse('Refund request submitted and pending approval from property owner', [
                    'payment_id' => $payment->id,
                    'refund_status' => $payment->refund_status,
                    'requires_approval' => $requiresApproval
                ]);
                return;
            }

            // If no approval is needed, process the refund immediately
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
            $payment->refund_status = 'completed';
            $payment->refund_data = json_encode($refundResult);
            $payment->save();

            // If there's an associated reservation, cancel it
            if ($reservation) {
                $reservation->status = 'cancelled';
                $reservation->payment_status = 'refunded';
                $reservation->save();

                // Use the reservation service to handle the cancellation
                $reservationService = app(\App\Services\ReservationService::class);
                $reservationService->cancelReservation($payment->reservation_id);
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
     * Update refund approval status
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRefundApprovalStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:approved,rejected',
                'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255',
            ]);

            $status = $request->input('status');
            $rejectionReason = $request->input('rejection_reason');
            $userId = Auth::guard('sanctum')->user()->id;

            // Find the payment record
            $payment = PaymobPayment::where('id', $id)
                ->where('requires_approval', true)
                ->where('refund_status', 'pending')
                ->first();

            if (!$payment) {
                ApiResponseService::errorResponse('Refund request not found or not pending approval', null, 404);
                return;
            }

            // Check if the user is the property owner
            $propertyOwnerId = null;
            $reservation = $payment->reservation;

            if ($reservation) {
                if ($reservation->reservable_type === 'App\\Models\\Property') {
                    $property = \App\Models\Property::find($reservation->reservable_id);
                    if ($property) {
                        $propertyOwnerId = $property->added_by;
                    }
                } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                    $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                    if ($hotelRoom && $hotelRoom->property) {
                        $propertyOwnerId = $hotelRoom->property->added_by;
                    }
                }
            }

            if ($propertyOwnerId !== $userId) {
                ApiResponseService::errorResponse('You are not authorized to update this refund request', null, 403);
                return;
            }

            // Update the payment record based on the approval status
            if ($status === 'approved') {
                // Process the refund
                $paymobTransactionId = $payment->paymob_transaction_id ?? $payment->transaction_id;
                $amount = $payment->refund_amount;
                $reason = $payment->refund_reason;

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
                $payment->refund_status = 'completed';
                $payment->refund_data = json_encode($refundResult);
                $payment->approved_by = $userId;
                $payment->approved_at = now();
                $payment->save();

                // If there's an associated reservation, cancel it
                if ($reservation) {
                    $reservation->status = 'cancelled';
                    $reservation->payment_status = 'refunded';
                    $reservation->save();

                    // Use the reservation service to handle the cancellation
                    $reservationService = app(\App\Services\ReservationService::class);
                    $reservationService->cancelReservation($payment->reservation_id);

                    // Send email notification to the customer
                    $this->sendRefundApprovalEmail($payment, $reservation);
                }

                ApiResponseService::successResponse('Refund approved and processed successfully', [
                    'payment_id' => $payment->id,
                    'refund_status' => $payment->refund_status,
                    'refund_result' => $refundResult
                ]);
            } else {
                // Reject the refund
                $payment->refund_status = 'rejected';
                $payment->rejection_reason = $rejectionReason;
                $payment->approved_by = $userId;
                $payment->approved_at = now();
                $payment->save();

                // Notify the customer about the rejection
                $this->sendRefundRejectionEmail($payment, $reservation, $rejectionReason);

                ApiResponseService::successResponse('Refund request rejected successfully', [
                    'payment_id' => $payment->id,
                    'refund_status' => $payment->refund_status,
                    'rejection_reason' => $payment->rejection_reason
                ]);
            }
            return;
        } catch (\Exception $e) {
            Log::error('Failed to update refund approval status: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to update refund approval status: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get refund approvals pending for property owner
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingRefundApprovals(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'property_id' => 'integer|exists:propertys,id',
                'status' => 'string|in:pending,approved,rejected,processing,completed,failed',
            ]);

            $perPage = $request->input('per_page', 15);
            $propertyId = $request->input('property_id');
            $status = $request->input('status', 'pending');
            $userId = Auth::guard('sanctum')->user()->id;

            // Start with payments that require approval
            $query = PaymobPayment::with(['reservation', 'customer'])
                ->where('refund_status', $status);

            if ($status === 'pending') {
                $query->where('requires_approval', true);
            }

            // Filter by property owner
            if ($propertyId) {
                // Filter by specific property
                $query->whereHas('reservation', function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                });
            } else {
                // Filter by all properties owned by this user
                $query->whereHas('reservation.property', function ($q) use ($userId) {
                    $q->where('added_by', $userId);
                });
            }

            // Get the results with pagination
            $refundApprovals = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transform the data to include more context
            $formattedRefunds = $refundApprovals->through(function ($payment) {
                $data = $payment->toArray();

                // Add reservation details if available
                if ($payment->reservation) {
                    $data['reservation_details'] = [
                        'id' => $payment->reservation->id,
                        'check_in_date' => $payment->reservation->check_in_date,
                        'check_out_date' => $payment->reservation->check_out_date,
                        'status' => $payment->reservation->status,
                        'total_price' => $payment->reservation->total_price,
                    ];
                }

                // Add customer details if available
                if ($payment->customer) {
                    $data['customer_details'] = [
                        'id' => $payment->customer->id,
                        'name' => $payment->customer->name,
                        'email' => $payment->customer->email,
                        'mobile' => $payment->customer->mobile,
                    ];
                }

                return $data;
            });

            ApiResponseService::successResponse('Refund approvals retrieved successfully', [
                'refund_approvals' => $formattedRefunds->items(),
                'pagination' => [
                    'current_page' => $formattedRefunds->currentPage(),
                    'last_page' => $formattedRefunds->lastPage(),
                    'per_page' => $formattedRefunds->perPage(),
                    'total' => $formattedRefunds->total(),
                ]
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Failed to get refund approvals: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to get refund approvals: ' . $e->getMessage(), null, 500);
            return;
        }
    }

    /**
     * Get customer refund requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerRefundRequests(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'status' => 'string|in:pending,approved,rejected,processing,completed,failed',
            ]);

            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            $customerId = Auth::guard('sanctum')->user()->id;

            // Start with payments for this customer
            $query = PaymobPayment::with(['reservation'])
                ->where('customer_id', $customerId)
                ->whereNotNull('refund_status');

            // Filter by status if provided
            if ($status) {
                $query->where('refund_status', $status);
            }

            // Get the results with pagination
            $refundRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transform the data to include more context
            $formattedRefunds = $refundRequests->through(function ($payment) {
                $data = $payment->toArray();

                // Add reservation details if available
                if ($payment->reservation) {
                    $data['reservation_details'] = [
                        'id' => $payment->reservation->id,
                        'check_in_date' => $payment->reservation->check_in_date,
                        'check_out_date' => $payment->reservation->check_out_date,
                        'status' => $payment->reservation->status,
                        'total_price' => $payment->reservation->total_price,
                    ];

                    // Add property details if available
                    if ($payment->reservation->property) {
                        $data['property_details'] = [
                            'id' => $payment->reservation->property->id,
                            'title' => $payment->reservation->property->title,
                            'title_image' => $payment->reservation->property->title_image ?? null,
                        ];
                    }
                }

                return $data;
            });

            ApiResponseService::successResponse('Refund requests retrieved successfully', [
                'refund_requests' => $formattedRefunds->items(),
                'pagination' => [
                    'current_page' => $formattedRefunds->currentPage(),
                    'last_page' => $formattedRefunds->lastPage(),
                    'per_page' => $formattedRefunds->perPage(),
                    'total' => $formattedRefunds->total(),
                ]
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Failed to get customer refund requests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            ApiResponseService::errorResponse('Failed to get customer refund requests: ' . $e->getMessage(), null, 500);
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

    /**
     * Send refund approval email to customer
     *
     * @param PaymobPayment $payment
     * @param Reservation $reservation
     * @return void
     */
    private function sendRefundApprovalEmail($payment, $reservation)
    {
        try {
            // Get customer information
            $customer = $payment->customer;

            if (!$customer || !$customer->email) {
                Log::warning('Cannot send refund approval email: customer or email not found', [
                    'payment_id' => $payment->id,
                    'customer_id' => $payment->customer_id
                ]);
                return;
            }

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = \App\Models\Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Prepare email variables
            $variables = [
                'app_name' => env("APP_NAME") ?? "eBroker",
                'user_name' => $customer->name,
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'refund_amount' => number_format($payment->refund_amount, 2),
                'currency_symbol' => $currencySymbol,
                'refund_method' => $payment->payment_method ?? 'Original Payment Method',
                'refund_date' => $payment->refund_date ? $payment->refund_date->format('d M Y') : now()->format('d M Y'),
                'cancellation_date' => $reservation->cancelled_at ? $reservation->cancelled_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A'),
                'refund_processing_time' => '3-5 business days',
                'current_date_today' => now()->format('d M Y, h:i A'),
                'transaction_id' => $payment->transaction_id,
            ];

            // Get email template
            $emailTemplateData = system_setting('refund_approval_mail_template');

            if (empty($emailTemplateData)) {
                Log::warning('Refund approval email template not found, using default template');
                $emailTemplateData = 'Dear {user_name},
We\'re writing to confirm that your refund for the cancelled reservation has been successfully processed.

Refund Details
Reservation ID: {reservation_id}
Property: {property_name}
Check-in Date: {check_in_date}
Check-out Date: {check_out_date}
Refund Amount: {currency_symbol}{refund_amount}
Refund Method: {refund_method}
Refund Date: {refund_date}

Please note that depending on your payment provider or bank, it may take up to {refund_processing_time} business days for the refunded amount to appear in your account.

We appreciate your patience and understanding. If you have any questions regarding your refund, feel free to contact our support team at support@as-home.com.

Thank you for choosing As-home. We hope to have the opportunity to host you again soon at one of our vacation homes or hotel stays.

Warm regards,
As-home Asset Management Team';
            }

            // Replace variables in template
            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $customer->email,
                'title' => 'Your Refund Request Has Been Approved',
                'email_template' => $emailContent
            ];

            HelperService::sendMail($data);

            Log::info('Refund approval email sent to customer', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'payment_id' => $payment->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send refund approval email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id
            ]);
        }
    }

    /**
     * Send refund rejection email to customer
     *
     * @param \App\Models\PaymobPayment $payment
     * @param \App\Models\Reservation $reservation
     * @param string $rejectionReason
     * @return void
     */
    private function sendRefundRejectionEmail($payment, $reservation, $rejectionReason)
    {
        try {
            // Get customer information
            $customer = $payment->customer;

            if (!$customer || !$customer->email) {
                Log::warning('Cannot send refund rejection email: customer or email not found', [
                    'payment_id' => $payment->id,
                    'customer_id' => $payment->customer_id
                ]);
                return;
            }

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = \App\Models\Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Prepare email variables
            $variables = [
                'app_name' => env("APP_NAME") ?? "eBroker",
                'user_name' => $customer->name,
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'refund_amount' => number_format($payment->refund_amount, 2),
                'currency_symbol' => $currencySymbol,
                'rejection_reason' => $rejectionReason,
                'rejection_date' => now()->format('d M Y'),
                'current_date_today' => now()->format('d M Y, h:i A'),
                'transaction_id' => $payment->transaction_id,
            ];

            // Get email template
            $emailTemplateData = system_setting('refund_rejection');

            if (empty($emailTemplateData)) {
                Log::warning('Refund rejection email template not found, using default template');
                $emailTemplateData = 'Dear {user_name},

We regret to inform you that your refund request has been declined.

Refund Request Details:
Reservation ID: {reservation_id}
Property: {property_name}
Check-in Date: {check_in_date}
Check-out Date: {check_out_date}
Requested Refund Amount: {currency_symbol}{refund_amount}
Rejection Date: {rejection_date}

Reason for Rejection:
{rejection_reason}

We understand this may be disappointing, and we apologize for any inconvenience this may cause. If you believe this decision was made in error or if you have additional information that might change our assessment, please contact our support team at support@as-home.com.

We value your business and hope to have the opportunity to serve you better in the future.

Best regards,
As-home Asset Management Team';
            }

            // Replace variables in template
            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $customer->email,
                'title' => 'Refund Request Declined',
                'email_template' => $emailContent
            ];

            HelperService::sendMail($data);

            Log::info('Refund rejection email sent to customer', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'payment_id' => $payment->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send refund rejection email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id
            ]);
        }
    }

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
            Log::info('Paymob send money callback received - METHOD CALLED', $request->all());

            // Validate HMAC if configured
            // if (config('paymob.hmac_secret')) {
            //     $this->validateHmac($request);
            // }

            $data = $request->all();

            // Safely extract data with proper error handling
            $transactionId = $data['merchant_order_id'] ?? null;
            $paymentStatus = ($data['success'] ?? false) ? 'succeed' : 'failed';
            $paymobOrderId = $data['order'] ?? null;
            $paymobTransactionId = $data['id'] ?? null;

            // Log full data structure for debugging
            Log::info('Paymob return - Full data structure:', [
                'data' => $data
            ]);

            // Validate required data
            if (!$transactionId && !$paymobOrderId) {
                Log::error('Paymob send money callback - Missing transaction ID and order ID', [
                    'data_structure' => $data
                ]);
                return ApiResponseService::errorResponse('Missing transaction ID and order ID in callback data');
            }

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
                } else {
                    Log::info('Send money transaction already processed', [
                        'send_money_id' => $sendMoney->id,
                        'current_status' => $sendMoney->status,
                        'current_payment_status' => $sendMoney->payment_status
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
                } else {
                    Log::error('Send money transaction not found in any table', [
                        'transaction_id' => $transactionId,
                        'paymob_transaction_id' => $paymobTransactionId
                    ]);
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
     * Handle package payment callback from Paymob
     *
     * @param Request $request
     * @param string $transactionId
     * @param string $paymentStatus
     * @param string|null $paymobOrderId
     * @param string|null $paymobTransactionId
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handlePackagePaymentCallback(Request $request, $transactionId, $paymentStatus, $paymobOrderId, $paymobTransactionId, $data)
    {
        try {
            Log::info('Paymob package payment callback - Processing', [
                'transaction_id' => $transactionId,
                'payment_status' => $paymentStatus,
                'paymob_order_id' => $paymobOrderId
            ]);

            // Find payment transaction by transaction ID or order ID
            $paymentTransaction = null;
            
            // First try to find by order_id (most reliable for Paymob)
            if ($paymobOrderId) {
                $paymentTransaction = PaymentTransaction::where('order_id', $paymobOrderId)
                    ->where('payment_type', 'online payment')
                    ->first();
                
                Log::info('Paymob package payment callback - Search by order_id', [
                    'paymob_order_id' => $paymobOrderId,
                    'found' => $paymentTransaction ? true : false
                ]);
            }

            // If not found by order_id, try by transaction_id
            if (!$paymentTransaction && $transactionId) {
                // Try exact match first
                $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)
                    ->where('payment_type', 'online payment')
                    ->first();
                
                // If not found, try partial match for group transactions
                if (!$paymentTransaction) {
                    $paymentTransaction = PaymentTransaction::where('transaction_id', 'LIKE', $transactionId . '%')
                        ->where('payment_type', 'online payment')
                        ->first();
                }
                
                // If still not found and transaction_id looks like a number, try to find by payment_transaction_id
                // Sometimes Paymob returns the payment_transaction_id as the merchant_order_id
                if (!$paymentTransaction && is_numeric($transactionId)) {
                    $paymentTransaction = PaymentTransaction::where('id', $transactionId)
                        ->where('payment_type', 'online payment')
                        ->first();
                }
                
                Log::info('Paymob package payment callback - Search by transaction_id', [
                    'transaction_id' => $transactionId,
                    'found' => $paymentTransaction ? true : false,
                    'is_numeric' => is_numeric($transactionId)
                ]);
            }

            if (!$paymentTransaction) {
                Log::error('Paymob package payment callback - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'paymob_order_id' => $paymobOrderId,
                    'callback_data' => json_encode($data)
                ]);
                return ApiResponseService::errorResponse('Payment transaction not found', null, 404);
            }

            // Extract group transaction ID from the transaction_id
            // Format: PKG_GROUP_UUID_PACKAGEID or PKG_UUID (for single package)
            $groupTransactionId = null;
            if (strpos($paymentTransaction->transaction_id, 'PKG_GROUP_') === 0) {
                $parts = explode('_', $paymentTransaction->transaction_id);
                if (count($parts) >= 3) {
                    // Reconstruct group ID: PKG_GROUP_UUID
                    $groupTransactionId = $parts[0] . '_' . $parts[1] . '_' . $parts[2];
                }
            }

            // Find all payment transactions in this group
            $allPaymentTransactions = [];
            if ($groupTransactionId) {
                // Find all transactions that start with the group ID
                $allPaymentTransactions = PaymentTransaction::where('transaction_id', 'LIKE', $groupTransactionId . '%')
                    ->where('payment_type', 'online payment')
                    ->where('order_id', $paymentTransaction->order_id)
                    ->get();
            } else {
                // Fallback: find by order_id (for single package or backward compatibility)
                $allPaymentTransactions = PaymentTransaction::where('order_id', $paymentTransaction->order_id)
                    ->where('payment_type', 'online payment')
                    ->get();
            }

            if ($allPaymentTransactions->isEmpty()) {
                // Fallback to single transaction
                $allPaymentTransactions = collect([$paymentTransaction]);
            }

            // Check if any payment is already processed
            $alreadyProcessed = $allPaymentTransactions->where('payment_status', 'success')->count();
            if ($alreadyProcessed > 0 && $alreadyProcessed === $allPaymentTransactions->count()) {
                Log::info('Paymob package payment callback - All payments already processed', [
                    'count' => $allPaymentTransactions->count()
                ]);
                return ApiResponseService::successResponse('All payments already processed');
            }

            DB::beginTransaction();

            // Update all payment transaction statuses
            foreach ($allPaymentTransactions as $pt) {
                if ($pt->payment_status !== 'success') {
                    $pt->payment_status = $paymentStatus === 'succeed' ? 'success' : 'failed';
                    // Keep original transaction ID (don't overwrite with callback transaction_id)
                    $pt->save();
                }
            }

            // If payment succeeded, assign all packages to user
            if ($paymentStatus === 'succeed') {
                foreach ($allPaymentTransactions as $pt) {
                    if ($pt->package_id) {
                        $this->assignPackageToUser($pt);
                    }
                }
            }

            DB::commit();

            Log::info('Paymob package payment callback - Successfully processed', [
                'payment_transaction_count' => $allPaymentTransactions->count(),
                'package_ids' => $allPaymentTransactions->pluck('package_id')->toArray(),
                'user_id' => $paymentTransaction->user_id,
                'payment_status' => $paymentStatus === 'succeed' ? 'success' : 'failed'
            ]);

            return ApiResponseService::successResponse('Package payment processed successfully', [
                'processed_count' => $allPaymentTransactions->count(),
                'package_ids' => $allPaymentTransactions->pluck('package_id')->toArray()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Paymob package payment callback error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'trace' => $e->getTraceAsString()
            ]);
            return ApiResponseService::errorResponse('Failed to process package payment: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Assign package to user after successful payment
     *
     * @param PaymentTransaction $paymentTransaction
     * @return void
     */
    private function assignPackageToUser($paymentTransaction)
    {
        try {
            $packageId = $paymentTransaction->package_id;
            $userId = $paymentTransaction->user_id;
            $package = Package::find($packageId);

            if (!$package) {
                Log::error('Package not found for assignment', [
                    'package_id' => $packageId,
                    'payment_transaction_id' => $paymentTransaction->id
                ]);
                return;
            }

            // Check if user already has an active package
            $existingPackage = UserPackage::where(['user_id' => $userId, 'package_id' => $packageId])
                ->onlyActive()
                ->first();

            if ($existingPackage) {
                Log::info('User already has active package', [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'user_package_id' => $existingPackage->id
                ]);
                return;
            }

            // Assign Package to user
            $userPackage = UserPackage::create([
                'package_id'  => $packageId,
                'user_id'     => $userId,
                'start_date'  => Carbon::now(),
                'end_date'    => $package->package_type == "unlimited" ? null : Carbon::now()->addHours($package->duration),
            ]);

            // Assign limited count feature to user with limits
            $packageFeatures = PackageFeature::where(['package_id' => $packageId, 'limit_type' => 'limited'])->get();
            if (collect($packageFeatures)->isNotEmpty()) {
                $userPackageLimitData = array();
                foreach ($packageFeatures as $key => $feature) {
                    $userPackageLimitData[] = array(
                        'user_package_id' => $userPackage->id,
                        'package_feature_id' => $feature->id,
                        'total_limit' => $feature->limit,
                        'used_limit' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    );
                }

                if (!empty($userPackageLimitData)) {
                    UserPackageLimit::insert($userPackageLimitData);
                }
            }

            Log::info('Package assigned to user successfully', [
                'user_id' => $userId,
                'package_id' => $packageId,
                'user_package_id' => $userPackage->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign package to user: ' . $e->getMessage(), [
                'payment_transaction_id' => $paymentTransaction->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
        Log::info('Paymob send money return received - METHOD CALLED', $request->all());

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
