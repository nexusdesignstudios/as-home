<?php

namespace App\Http\Controllers;

use App\Models\SendMoney;
use App\Services\ApiResponseService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SendMoneyController extends Controller
{
    /**
     * Create a send money transaction with payment intent.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSendMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'recipient_customer_id' => 'required|integer|exists:customers,id',
            'notes' => 'nullable|string|max:500',
            'payment' => 'required|array',
            'payment.email' => 'required|email',
            'payment.first_name' => 'required|string',
            'payment.last_name' => 'required|string',
            'payment.phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        try {
            $customerId = Auth::guard('sanctum')->user()->id;

            // Generate a unique transaction ID
            $transactionId = 'SEND_' . time() . '_' . $customerId . '_' . rand(1000, 9999);

            // Get recipient customer details
            $recipientCustomer = Customer::find($request->recipient_customer_id);
            if (!$recipientCustomer) {
                return ApiResponseService::errorResponse('Recipient customer not found');
            }

            // Create send money record
            $sendMoney = SendMoney::create([
                'customer_id' => $customerId,
                'transaction_id' => $transactionId,
                'amount' => $request->amount,
                'currency' => config('paymob.currency', 'EGP'),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => 'paymob',
                'recipient_customer_id' => $request->recipient_customer_id,
                'notes' => $request->notes,
            ]);

            // Create payment data for Paymob
            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            $metadata = [
                'email' => $request->payment['email'],
                'first_name' => $request->payment['first_name'],
                'last_name' => $request->payment['last_name'],
                'phone' => $request->payment['phone'],
                'payment_transaction_id' => $transactionId,
                'send_money_id' => $sendMoney->id,
                'recipient_customer_id' => $request->recipient_customer_id,
                'recipient_name' => $recipientCustomer->name,
                'recipient_email' => $recipientCustomer->email,
                'recipient_phone' => $recipientCustomer->mobile,
            ];

            // Create payment service
            $paymentService = PaymentService::create($paymentData);

            // Create payment intent
            $paymentIntent = $paymentService->createAndFormatPaymentIntent($request->amount, $metadata);

            // Update send money record with payment data
            $sendMoney->update([
                'payment_data' => $paymentIntent,
            ]);

            return ApiResponseService::successResponse('Send money transaction created successfully', [
                'send_money' => $sendMoney,
                'payment_intent' => $paymentIntent,
                'transaction_id' => $transactionId,
            ]);

        } catch (\Exception $e) {
            Log::error('Send money creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up the created record if payment intent creation fails
            if (isset($sendMoney)) {
                try {
                    $sendMoney->delete();
                } catch (\Exception $cleanupException) {
                    Log::error('Failed to cleanup send money record after payment intent failure', [
                        'send_money_id' => $sendMoney->id ?? null,
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
            }

            return ApiResponseService::errorResponse('Failed to create send money transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get send money transactions for the authenticated customer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerSendMoney(Request $request)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $status = $request->status ? explode(',', $request->status) : null;

        $query = SendMoney::where('customer_id', $customerId);

        if ($status) {
            $query->whereIn('status', $status);
        }

        $sendMoneyTransactions = $query->with('recipient:id,name,email,mobile')->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponseService::successResponse('Send money transactions retrieved successfully', [
            'transactions' => $sendMoneyTransactions
        ]);
    }

    /**
     * Get a specific send money transaction.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSendMoney($id)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $sendMoney = SendMoney::where('id', $id)
            ->where('customer_id', $customerId)
            ->with('recipient:id,name,email,mobile')
            ->first();

        if (!$sendMoney) {
            return ApiResponseService::errorResponse('Send money transaction not found');
        }

        return ApiResponseService::successResponse('Send money transaction retrieved successfully', [
            'transaction' => $sendMoney
        ]);
    }

    /**
     * Cancel a send money transaction.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSendMoney($id)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $sendMoney = SendMoney::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();

        if (!$sendMoney) {
            return ApiResponseService::errorResponse('Send money transaction not found');
        }

        if ($sendMoney->status !== 'pending') {
            return ApiResponseService::errorResponse('This transaction cannot be cancelled');
        }

        try {
            $sendMoney->update([
                'status' => 'cancelled',
                'payment_status' => 'failed'
            ]);

            return ApiResponseService::successResponse('Send money transaction cancelled successfully', [
                'transaction' => $sendMoney
            ]);
        } catch (\Exception $e) {
            return ApiResponseService::errorResponse('Failed to cancel transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get list of customers for send money selection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomersForSendMoney(Request $request)
    {
        $search = $request->input('search', '');

        $query = Customer::select('id', 'name', 'email', 'mobile')
            ->where('isActive', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $customers = $query->limit(20)->get();

        return ApiResponseService::successResponse('Customers retrieved successfully', [
            'customers' => $customers
        ]);
    }

    /**
     * Get all send money transactions (admin only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSendMoney(Request $request)
    {
        $status = $request->status ? explode(',', $request->status) : null;
        $customerId = $request->customer_id;

        $query = SendMoney::with(['customer', 'recipient:id,name,email,mobile']);

        if ($status) {
            $query->whereIn('status', $status);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $sendMoneyTransactions = $query->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponseService::successResponse('Send money transactions retrieved successfully', [
            'transactions' => $sendMoneyTransactions
        ]);
    }

    /**
     * Update send money transaction status (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSendMoneyStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,failed,cancelled',
            'payment_status' => 'nullable|in:unpaid,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        $sendMoney = SendMoney::find($id);

        if (!$sendMoney) {
            return ApiResponseService::errorResponse('Send money transaction not found');
        }

        try {
            $sendMoney->status = $request->status;

            if ($request->has('payment_status')) {
                $sendMoney->payment_status = $request->payment_status;
            }

            $sendMoney->save();

            return ApiResponseService::successResponse('Send money transaction status updated successfully', [
                'transaction' => $sendMoney
            ]);
        } catch (\Exception $e) {
            return ApiResponseService::errorResponse('Failed to update transaction status: ' . $e->getMessage());
        }
    }

    /**
     * Process a refund for a send money transaction.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundSendMoney(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        $customerId = Auth::guard('sanctum')->user()->id;
        $sendMoney = SendMoney::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();

        if (!$sendMoney) {
            return ApiResponseService::errorResponse('Send money transaction not found');
        }

        if (!$sendMoney->canBeRefunded()) {
            return ApiResponseService::errorResponse('This transaction cannot be refunded');
        }

        try {
            // Get Paymob transaction ID
            $paymobTransactionId = $sendMoney->paymob_transaction_id ?? $sendMoney->transaction_id;

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
            $refundResult = $paymentService->refundTransaction($paymobTransactionId, $sendMoney->amount, $request->reason ?? 'Customer requested refund');

            // Update send money transaction
            $sendMoney->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'refund_data' => $refundResult
            ]);

            return ApiResponseService::successResponse('Refund processed successfully', [
                'transaction' => $sendMoney,
                'refund_result' => $refundResult
            ]);

        } catch (\Exception $e) {
            Log::error('Send money refund error: ' . $e->getMessage());
            return ApiResponseService::errorResponse('Failed to process refund: ' . $e->getMessage());
        }
    }
}
