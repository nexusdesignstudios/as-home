<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HelperService;
use App\Services\ResponseService;
use App\Models\PaymentTransaction;
use App\Services\BootstrapTableService;
use App\Services\PDF\PaymentReceiptService;

class TransactionsController extends Controller
{
    /**
     * Display the transactions view
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (!has_permissions('read', 'payment')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('transactions.index');
    }

    /**
     * Get transaction list for datatable
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionsList(Request $request)
    {
        if (!has_permissions('read', 'payment')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search');
        $payment_status = $request->input('payment_status');
        $payment_type = $request->input('payment_type');
        $date_range = $request->input('date_range');

        $query = PaymentTransaction::with('package:id,name', 'customer:id,name', 'bank_receipt_files');

        // Apply search filter
        if ($request->has('search') && !empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhere('transaction_id', 'LIKE', "%$search%")
                    ->orWhere('payment_gateway', 'LIKE', "%$search%")
                    ->orWhere('amount', 'LIKE', "%$search%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('package', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    });
            });
        }

        // Apply payment status filter
        if (!empty($payment_status)) {
            $query->where('payment_status', $payment_status);
        }

        // Apply payment type filter
        if (!empty($payment_type)) {
            $query->where('payment_type', $payment_type);
        }

        // Apply date range filter
        if (!empty($date_range)) {
            $dates = explode(' - ', $date_range);
            if (count($dates) == 2) {
                $start_date = Carbon::createFromFormat('m/d/Y', $dates[0])->startOfDay();
                $end_date = Carbon::createFromFormat('m/d/Y', $dates[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            }
        }

        $total = $query->count();

        $transactions = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->bank_receipt_files = $item->bank_receipt_files->map(function ($file) {
                    $file->file_name = $file->getRawOriginal('file');
                    return $file;
                });
                return $item;
            });

        $rows = [];
        $priceSymbol = HelperService::getSettingData('currency_symbol') ?? '$';

        foreach ($transactions as $transaction) {
            $tempRow = $transaction->toArray();
            $tempRow['created_at'] = $transaction->created_at->format('d-m-Y H:i:s');
            $tempRow['payment_type'] = trans(ucfirst($transaction->payment_type));
            $tempRow['price_symbol'] = $priceSymbol;

            // Add action buttons
            $tempRow['operate'] = null;

            // View receipt button for successful payments
            if ($transaction->payment_status === 'success' && has_permissions('read', 'payment')) {
                $receiptButtonClasses = ["btn", "icon", "btn-primary", "btn-sm", "rounded-pill"];
                $receiptButtonAttributes = [
                    "id" => $transaction->id,
                    "title" => trans('View Receipt'),
                    "onclick" => "window.open('" . route('payment.receipt.view', $transaction->id) . "', '_blank')"
                ];
                $tempRow['operate'] = BootstrapTableService::button('bi bi-receipt', '', $receiptButtonClasses, $receiptButtonAttributes);
            }

            // View files button if files exist
            if ($transaction->bank_receipt_files->count() > 0) {
                $viewFilesButtonClasses = ["btn", "icon", "btn-info", "btn-sm", "rounded-pill", "view-files"];
                $viewFilesButtonAttributes = [
                    "id" => $transaction->id,
                    "title" => trans('View Files'),
                    "data-toggle" => "modal",
                    "data-bs-target" => "#viewFilesModal",
                    "data-bs-toggle" => "modal"
                ];
                $viewFilesButton = BootstrapTableService::button('bi bi-file-earmark-text', '', $viewFilesButtonClasses, $viewFilesButtonAttributes);
                $tempRow['operate'] = $tempRow['operate'] ? $tempRow['operate'] . ' ' . $viewFilesButton : $viewFilesButton;
            }

            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    /**
     * View a payment receipt in PDF format
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function viewReceipt($id)
    {
        if (!has_permissions('read', 'payment')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        try {
            $payment = PaymentTransaction::with('package', 'customer')->findOrFail($id);

            // Only allow viewing receipts for successful payments
            if ($payment->payment_status !== 'success') {
                return redirect()->back()->with('error', trans('Receipt is only available for successful payments'));
            }

            $receiptService = new PaymentReceiptService();
            return $receiptService->streamPDF($payment);
        } catch (Exception $e) {
            return redirect()->back()->with('error', trans('Error generating receipt: ') . $e->getMessage());
        }
    }
}
