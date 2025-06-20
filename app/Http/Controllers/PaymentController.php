<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use App\Services\ResponseService;

class PaymentController extends Controller
{
    public function index()
    {
        if (!has_permissions('read', 'payment')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('payments.index');
    }
    public function paymentList(Request $request)
    {
        if (!has_permissions('read', 'payment')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');
        $search = $request->input('search');

        $sql = PaymentTransaction::with('package:id,name','customer:id,name')
            ->when($request->has('search') && !empty($search),function($query) use($search){
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('transaction_id', 'LIKE', "%$search%")
                    ->orWhere('payment_gateway', 'LIKE', "%$search%")
                    ->orWhere('amount', 'LIKE', "%$search%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })->orWhereHas('package', function ($q1) use ($search) {
                        $q1->where('name', 'LIKE', "%$search%");
                    });
            });

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $count = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['created_at'] = $row->created_at->format('d-m-Y H:i:s');
            $tempRow['updated_at'] = $row->updated_at->format('d-m-Y H:i:s');
            $rows[] = $tempRow;
            $count++;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
