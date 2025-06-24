<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Customer;
use App\Models\Company;
use App\Models\BankDetail;
use App\Models\Usertokens;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\InterestedUser;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'customer')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('customer.index');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!has_permissions('update', 'customer')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            // Update customer status
            $updateData = ['isActive' => $request->status];

            // Handle management_type for property owners
            if ($request->has('management_type')) {
                $validator = Validator::make($request->all(), [
                    'management_type' => 'required|in:himself,as home',
                ]);

                if ($validator->fails()) {
                    return ResponseService::validationError($validator->errors()->first());
                }

                $updateData['management_type'] = $request->management_type;
            }

            // Handle agent type (individual or company)
            if ($request->has('agent_type')) {
                $validator = Validator::make($request->all(), [
                    'agent_type' => 'required|in:individual,company',
                ]);

                if ($validator->fails()) {
                    return ResponseService::validationError($validator->errors()->first());
                }

                // If it's a company agent, handle company details
                if ($request->agent_type === 'company' && $request->has('company')) {
                    $companyValidator = Validator::make($request->company, [
                        'company_legal_name' => 'required|string|max:255',
                        'manager_name' => 'required|string|max:255',
                        'type_of_company' => 'required|string|max:255',
                        'email_address' => 'required|email|max:255',
                    ]);

                    if ($companyValidator->fails()) {
                        return ResponseService::validationError($companyValidator->errors()->first());
                    }

                    // Create or update company
                    $company = null;
                    if ($request->has('company_id') && !empty($request->company_id)) {
                        $company = Company::find($request->company_id);
                        if ($company) {
                            $company->update($request->company);
                        } else {
                            $company = Company::create($request->company);
                        }
                    } else {
                        $company = Company::create($request->company);
                    }

                    if ($company) {
                        $updateData['company_id'] = $company->id;
                    }
                }
            }

            // Handle bank details for agents (both individual and company)
            if ($request->has('bank_details')) {
                $bankValidator = Validator::make($request->bank_details, [
                    'type_of_company' => 'required|string|max:255',
                    'bank_branch' => 'required|string|max:255',
                    'bank_address' => 'required|string',
                    'country' => 'required|string|max:255',
                    'bank_account_number' => 'required|string|max:255',
                    'iban' => 'required|string|max:255',
                    'swift_code' => 'required|string|max:50',
                ]);

                if ($bankValidator->fails()) {
                    return ResponseService::validationError($bankValidator->errors()->first());
                }

                // Create or update bank details
                $bankDetail = null;
                if ($request->has('bankDetails_id') && !empty($request->bankDetails_id)) {
                    $bankDetail = BankDetail::find($request->bankDetails_id);
                    if ($bankDetail) {
                        $bankDetail->update($request->bank_details);
                    } else {
                        $bankDetail = BankDetail::create($request->bank_details);
                    }
                } else {
                    $bankDetail = BankDetail::create($request->bank_details);
                }

                if ($bankDetail) {
                    $updateData['bankDetails_id'] = $bankDetail->id;
                }
            }

            // Update the customer with all collected data
            Customer::where('id', $request->id)->update($updateData);

            // Send mail for user status
            try {
                $customerData = Customer::where('id', $request->id)->select('id', 'name', 'email', 'isActive')->first();

                if ($customerData->email) {
                    // Get Data of email type
                    $emailTypeData = HelperService::getEmailTemplatesTypes("user_status");

                    // Email Template
                    $propertyFeatureStatusTemplateData = system_setting($emailTypeData['type']);
                    $appName = env("APP_NAME") ?? "eBroker";
                    $variables = array(
                        'app_name' => $appName,
                        'user_name' => $customerData->name,
                        'status' => $customerData->isActive == 1 ? 'Activated' : 'Deactivated',
                        'email' => $customerData->email
                    );
                    if (empty($propertyFeatureStatusTemplateData)) {
                        $propertyFeatureStatusTemplateData = "Your Property :- " . $variables['propertyName'] . "'s feature status " . $variables['status'];
                    }
                    $propertyFeatureStatusTemplate = HelperService::replaceEmailVariables($propertyFeatureStatusTemplateData, $variables);

                    $data = array(
                        'email_template' => $propertyFeatureStatusTemplate,
                        'email' => $customerData->email,
                        'title' => $emailTypeData['title'],
                    );
                    HelperService::sendMail($data);
                }
            } catch (Exception $e) {
                Log::error("Something Went Wrong in Customer Status Update Mail Sending");
            }

            /** Notification */
            $fcm_ids = array();

            $customer_id = Customer::where(['id' => $request->id, 'notification' => 1])->count();
            if ($customer_id) {
                $user_token = Usertokens::where('customer_id', $request->id)->pluck('fcm_id')->toArray();
                $fcm_ids[] = $user_token;
            }

            $msg = "";
            if (!empty($fcm_ids)) {
                $msg = $request->status == 1 ? 'Activate by Adminstrator ' : 'Deactive by Adminstrator ';
                $type = $request->status == 1 ? 'account_activated' : 'account_deactivated';
                $full_msg = $request->status == 1 ? 'Your Account' . $msg : 'Please Contact to Administrator';
                $registrationIDs = $fcm_ids[0];

                $fcmMsg = array(
                    'title' =>  'Your Account' . $msg,
                    'message' => $full_msg,
                    'type' => $type,
                    'body' => 'Your Account'  . $msg,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default',
                );
                send_push_notification($registrationIDs, $fcmMsg);
            }

            ResponseService::successResponse($request->status ? "Customer Updated Successfully" : "Customer Updated Successfully");
        }
    }

    /**
     * Get customer details with related data
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getCustomerDetails($id)
    {
        if (!has_permissions('read', 'customer')) {
            return ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $customer = Customer::with(['bankDetail', 'company'])->find($id);

        if (!$customer) {
            return ResponseService::errorResponse('Customer not found');
        }

        return ResponseService::successResponse('Customer details retrieved successfully', $customer);
    }

    public function customerList(Request $request)
    {
        if (!has_permissions('read', 'customer')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'sequence');
        $order = $request->input('order', 'ASC');


        if (isset($_GET['property_id'])) {
            $interested_users =  InterestedUser::select('customer_id')->where('property_id', $_GET['property_id'])->pluck('customer_id');

            $sql = Customer::whereIn('id', $interested_users)->orderBy($sort, $order);
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('email', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('mobile', 'LIKE', "%$search%");
                });
            }
        } else {

            $sql = Customer::orderBy($sort, $order);
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where('id', 'LIKE', "%$search%")->orwhere('email', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('mobile', 'LIKE', "%$search%");
            }

            // Filter by management type if requested
            if (isset($_GET['management_type']) && !empty($_GET['management_type'])) {
                $sql->where('management_type', $_GET['management_type']);
            }

            // Filter by agent type (with or without company)
            if (isset($_GET['agent_type'])) {
                if ($_GET['agent_type'] === 'company') {
                    $sql->whereNotNull('company_id');
                } else if ($_GET['agent_type'] === 'individual') {
                    $sql->whereNull('company_id')->whereNotNull('bankDetails_id');
                }
            }
        }

        $total = $sql->count();

        if (isset($_GET['limit'])) {
            $sql->skip($offset)->take($limit);
        }

        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $count = 1;

        $operate = '';
        foreach ($res as $row) {
            $tempRow = $row->toArray();

            // Mask Details in Demo Mode
            $tempRow['mobile'] = (env('DEMO_MODE') ? (env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ($row->mobile) : '****************************') : ($row->mobile));
            $tempRow['email'] = (env('DEMO_MODE') ? (env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ($row->email) : '****************************') : ($row->email));
            $tempRow['address'] = (env('DEMO_MODE') ? (env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ($row->address) : '****************************') : ($row->address));
            $tempRow['logintype'] = $row->logintype;

            // Add management type and agent type information
            $tempRow['management_type'] = $row->management_type ?? 'Not specified';
            $tempRow['agent_type'] = $row->company_id ? 'Company Agent' : ($row->bankDetails_id ? 'Individual Agent' : 'Not an agent');

            $tempRow['edit_status_url'] = 'customerstatus';
            $tempRow['total_properties'] =  '<a href="' . url('property') . '?customer=' . $row->id . '">' . $row->total_properties . '</a>';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
            $count++;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function resetPasswordIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return redirect(route('home'))->with('error', $validator->errors()->first())->send();
        }
        try {
            $token = $request->token;
            $email = HelperService::verifyToken($token);
            if ($email) {
                return view('customer.reset-password', compact('token'));
            } else {
                ResponseService::errorRedirectResponse("", trans('Invalid Token'));
            }
        } catch (Exception $e) {
            ResponseService::errorRedirectResponse("", trans('Something Went Wrong'));
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|min:6',
            're_password' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $email = HelperService::verifyToken($request->token);
            if ($email) {
                $customerQuery = Customer::where(['email' => $email, 'logintype' => 3]);
                $customerCheck = $customerQuery->clone()->count();
                if (!$customerCheck) {
                    ResponseService::errorResponse("No User Found");
                }
                $password = Hash::make($request->password);
                $customerQuery->clone()->update(['password' => $password]);
                HelperService::expireToken($email);
                ResponseService::successResponse("Password Changed Successfully");
            } else {
                ResponseService::errorResponse("Token Expired");
            }
        } catch (Exception $e) {
            ResponseService::errorRedirectResponse("", trans('Something Went Wrong'));
        }
    }
}
