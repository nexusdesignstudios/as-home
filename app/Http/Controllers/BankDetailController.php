<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;

class BankDetailController extends Controller
{
    /**
     * Display a listing of the bank details.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'bank_details')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        $bankDetails = BankDetail::all();
        return view('bank_details.index', compact('bankDetails'));
    }

    /**
     * Store a newly created bank detail in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!has_permissions('create', 'bank_details')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'type_of_company' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_address' => 'required|string',
            'country' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'iban' => 'required|string|max:255',
            'swift_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $bankDetail = BankDetail::create($request->all());
            return ResponseService::successResponse('Bank details created successfully', $bankDetail);
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified bank detail.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!has_permissions('read', 'bank_details')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $bankDetail = BankDetail::find($id);
        if (!$bankDetail) {
            return ResponseService::errorResponse('Bank detail not found');
        }

        return ResponseService::successResponse('Bank detail retrieved successfully', $bankDetail);
    }

    /**
     * Update the specified bank detail in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!has_permissions('update', 'bank_details')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $bankDetail = BankDetail::find($id);
        if (!$bankDetail) {
            return ResponseService::errorResponse('Bank detail not found');
        }

        $validator = Validator::make($request->all(), [
            'type_of_company' => 'sometimes|required|string|max:255',
            'bank_branch' => 'sometimes|required|string|max:255',
            'bank_address' => 'sometimes|required|string',
            'country' => 'sometimes|required|string|max:255',
            'bank_account_number' => 'sometimes|required|string|max:255',
            'iban' => 'sometimes|required|string|max:255',
            'swift_code' => 'sometimes|required|string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $bankDetail->update($request->all());
            return ResponseService::successResponse('Bank detail updated successfully', $bankDetail);
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified bank detail from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!has_permissions('delete', 'bank_details')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $bankDetail = BankDetail::find($id);
        if (!$bankDetail) {
            return ResponseService::errorResponse('Bank detail not found');
        }

        try {
            $bankDetail->delete();
            return ResponseService::successResponse('Bank detail deleted successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Get bank details list for API
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getBankDetailsList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');

        $sql = BankDetail::orderBy($sort, $order);

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('type_of_company', 'LIKE', "%$search%")
                ->orWhere('bank_branch', 'LIKE', "%$search%")
                ->orWhere('bank_account_number', 'LIKE', "%$search%");
        }

        $total = $sql->count();

        if (isset($_GET['limit'])) {
            $sql->skip($offset)->take($limit);
        }

        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['operate'] = '';
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
