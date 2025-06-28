<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'companies')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        $companies = Company::all();
        return view('companies.index', compact('companies'));
    }

    /**
     * Store a newly created company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!has_permissions('create', 'companies')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'company_legal_name' => 'required|string|max:255',
            'manager_name' => 'required|string|max:255',
            'type_of_company' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_address' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $company = Company::create($request->all());
            return ResponseService::successResponse('Company created successfully', $company);
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified company.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!has_permissions('read', 'companies')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $company = Company::find($id);
        if (!$company) {
            return ResponseService::errorResponse('Company not found');
        }

        return ResponseService::successResponse('Company retrieved successfully', $company);
    }

    /**
     * Update the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!has_permissions('update', 'companies')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $company = Company::find($id);
        if (!$company) {
            return ResponseService::errorResponse('Company not found');
        }

        $validator = Validator::make($request->all(), [
            'company_legal_name' => 'sometimes|required|string|max:255',
            'manager_name' => 'sometimes|required|string|max:255',
            'type_of_company' => 'sometimes|required|string|max:255',
            'email_address' => 'sometimes|required|email|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_address' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $company->update($request->all());
            return ResponseService::successResponse('Company updated successfully', $company);
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified company from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!has_permissions('delete', 'companies')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $company = Company::find($id);
        if (!$company) {
            return ResponseService::errorResponse('Company not found');
        }

        try {
            $company->delete();
            return ResponseService::successResponse('Company deleted successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }

    /**
     * Get companies list for API
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCompaniesList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');

        $sql = Company::orderBy($sort, $order);

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('company_legal_name', 'LIKE', "%$search%")
                ->orWhere('manager_name', 'LIKE', "%$search%")
                ->orWhere('email_address', 'LIKE', "%$search%")
                ->orWhere('bank_branch', 'LIKE', "%$search%")
                ->orWhere('bank_account_number', 'LIKE', "%$search%")
                ->orWhere('swift_code', 'LIKE', "%$search%");
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
