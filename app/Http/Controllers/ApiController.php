<?php

namespace App\Http\Controllers;



use DateTime;
use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Faq;

use App\Models\User;
use App\Models\Chats;
use Razorpay\Api\Api;
use App\Models\Slider;

use App\Models\Article;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Setting;


use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\BankDetail;

use App\Models\Language;
use App\Models\Payments;
use App\Models\Projects;
use App\Models\Property;
use App\Libraries\Paypal;
use App\Models\CityImage;
use App\Models\Favourite;
use App\Models\NumberOtp;
use App\Models\parameter;

// use GuzzleHttp\Client;
use App\Models\OldPackage;
use App\Models\Usertokens;

use App\Models\SeoSettings;

use App\Models\UserPackage;
use Carbon\CarbonInterface;

use Illuminate\Support\Str;
use App\Models\ProjectPlans;
use App\Models\user_reports;
use App\Models\UserInterest;

use Illuminate\Http\Request;
use App\Models\Advertisement;
// use PayPal_Pro as GlobalPayPal_Pro;
use App\Models\Notifications;

use App\Models\InterestedUser;
use App\Models\PackageFeature;
use App\Models\PropertyImages;
use App\Models\report_reasons;
use App\Models\VerifyCustomer;
use App\Models\BankReceiptFile;
use App\Models\BlockedChatUser;
use App\Models\Contactrequests;
use App\Models\PaymentFormSubmission;
use App\Models\HomepageSection;
use App\Services\HelperService;
use App\Models\AssignParameters;
use App\Models\ProjectDocuments;
use App\Models\UserPackageLimit;
use App\Models\OutdoorFacilities;
use App\Services\ResponseService;
use App\Models\PaymentTransaction;
use App\Models\PropertiesDocument;
use App\Models\VerifyCustomerForm;
use Illuminate\Support\Facades\DB;
use App\Models\VerifyCustomerValue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Twilio\Exceptions\RestException;
use App\Models\OldUserPurchasedPackage;
use App\Models\VerifyCustomerFormValue;
use App\Services\Payment\PaymentService;
use App\Models\AssignedOutdoorFacilities;
use Illuminate\Support\Facades\Validator;
use App\Services\PDF\PaymentReceiptService;
use Twilio\Rest\Client as TwilioRestClient;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Illuminate\Support\Facades\Request as FacadesRequest;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Models\PropertyTerms;
use App\Models\HotelAddonField;
use App\Models\HotelAddonFieldValue;
use App\Models\PropertyQuestionField;
use App\Models\PropertyQuestionAnswer;
use Illuminate\Support\Facades\File;
use App\Models\PropertyHotelAddonValue;
use App\Models\AddonsPackage;
use App\Models\PropertyCertificate;

class ApiController extends Controller
{

    public function update_fcm_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()]);
        }

        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json(['error' => true, 'message' => 'Unauthorized']);
            }

            Usertokens::updateOrCreate(
                ['fcm_id' => $request->fcm_id],
                [
                    'customer_id' => $user->id,
                    'api_token' => $request->header('Authorization') ?? 'N/A'
                ]
            );

            return response()->json(['error' => false, 'message' => 'FCM Token Updated Successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    //* START :: get_system_settings   *//
    public function get_system_settings(Request $request)
    {
        $result =  Setting::select('type', 'data')->get();

        foreach ($result as $row) {


            if ($row->type == "place_api_key") {

                $publicKey = file_get_contents(base_path('public_key.pem')); // Load the public key
                $encryptedData = '';
                if (openssl_public_encrypt($row->data, $encryptedData, $publicKey)) {

                    $tempRow[$row->type] = base64_encode($encryptedData);
                }
            } else if ($row->type == 'company_logo') {

                $tempRow[$row->type] = url('/assets/images/logo/logo.png');
            } else if ($row->type == 'web_logo' || $row->type == 'web_placeholder_logo' || $row->type == 'app_home_screen' || $row->type == 'web_footer_logo' || $row->type == 'placeholder_logo' || $row->type == 'favicon_icon') {


                // Check if file exists, otherwise use default logo
                $logoPath = public_path('assets/images/logo/' . $row->data);
                if (!empty($row->data) && file_exists($logoPath)) {
                    $tempRow[$row->type] = url('/assets/images/logo/') . '/' . $row->data;
                } else {
                    // Fallback to default logo if file doesn't exist
                    $tempRow[$row->type] = url('/assets/images/logo/logo.png');
                }
            } else {
                $tempRow[$row->type] = $row->data;
            }
        }

        if (collect(Auth::guard('sanctum')->user())->isNotEmpty()) {
            $loggedInUserId = Auth::guard('sanctum')->user()->id;
            update_subscription($loggedInUserId);

            $customer_data = Customer::find($loggedInUserId);
            if ($customer_data->isActive == 0) {

                $tempRow['is_active'] = false;
            } else {
                $tempRow['is_active'] = true;
            }
            if ($row->type == "seo_settings") {

                $tempRow[$row->type] = $row->data == 1 ? true : false;
            }

            $customer = Customer::select('id', 'subscription', 'is_premium')
                ->where(function ($query) {
                    $query->where('subscription', 1)
                        ->orWhere('is_premium', 1);
                })
                ->find($loggedInUserId);



            if (($customer)) {
                $tempRow['is_premium'] = $customer->is_premium == 1 ? true : ($customer->subscription == 1 ? true : false);

                $tempRow['subscription'] = $customer->subscription == 1 ? true : false;
            } else {

                $tempRow['is_premium'] = false;
                $tempRow['subscription'] = false;
            }
        }
        $language = Language::select('code', 'name')->get();
        $user_data = User::find(1);
        $tempRow['admin_name'] = $user_data->name;
        $tempRow['admin_image'] = url('/assets/images/faces/2.jpg');
        $tempRow['demo_mode'] = env('DEMO_MODE');
        $tempRow['languages'] = $language;
        $tempRow['img_placeholder'] = url('/assets/images/placeholder.svg');


        $tempRow['min_price'] = DB::table('propertys')
            ->selectRaw('MIN(price) as min_price')
            ->value('min_price');


        $tempRow['max_price'] = DB::table('propertys')
            ->selectRaw('MAX(price) as max_price')
            ->value('max_price');

        if (!empty($result)) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $tempRow;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    //* END :: Get System Setting   *//


    //* START :: user_signup   *//
    public function user_signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:0,1,2,3',
            'auth_id' => 'required_if:type,0,1,2',
            'email' => 'required_if:type,3',
            'password' => 'required_if:type,3'
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        $type = $request->type;
        if ($type == 3) {
            $email = $request->email;
            $user = Customer::where(['email' => $email, 'logintype' => 3])->first();

            if ($user) {
                if (!Hash::check($request->password, $user->password)) {
                    ApiResponseService::validationError("Invalid Password");
                } else if ($user->is_email_verified == false) {
                    ApiResponseService::validationError("Email is not verified");
                }
            } else {
                ApiResponseService::validationError("Invalid Email");
            }

            $auth_id = $user->auth_id;
        } else {
            $auth_id = $request->auth_id;
            $user = Customer::where('auth_id', $auth_id)->where('logintype', $type)->first();
        }
        if (collect($user)->isEmpty()) {
            $saveCustomer = new Customer();
            $saveCustomer->name = isset($request->name) ? $request->name : '';
            $saveCustomer->email = isset($request->email) ? $request->email : '';
            $saveCustomer->mobile = isset($request->mobile) ? $request->mobile : null;
            $saveCustomer->slug_id = generateUniqueSlug($request->name, 5);
            $saveCustomer->logintype = isset($request->type) ? $request->type : '';
            $saveCustomer->address = isset($request->address) ? $request->address : '';
            $saveCustomer->customer_type = isset($request->customer_type) ? $request->customer_type : null;
            $saveCustomer->auth_id = isset($request->auth_id) ? $request->auth_id : '';
            $saveCustomer->about_me = isset($request->about_me) ? $request->about_me : '';
            $saveCustomer->facebook_id = isset($request->facebook_id) ? $request->facebook_id : '';
            $saveCustomer->twiiter_id = isset($request->twiiter_id) ? $request->twiiter_id : '';
            $saveCustomer->instagram_id = isset($request->instagram_id) ? $request->instagram_id : '';
            $saveCustomer->youtube_id = isset($request->youtube_id) ? $request->youtube_id : '';
            $saveCustomer->latitude = isset($request->latitude) ? $request->latitude : '';
            $saveCustomer->longitude = isset($request->longitude) ? $request->longitude : '';
            $saveCustomer->notification = 1;
            $saveCustomer->about_me = isset($request->about_me) ? $request->about_me : '';
            $saveCustomer->facebook_id = isset($request->facebook_id) ? $request->facebook_id : '';
            $saveCustomer->twiiter_id = isset($request->twiiter_id) ? $request->twiiter_id : '';
            $saveCustomer->instagram_id = isset($request->instagram_id) ? $request->instagram_id : '';
            $saveCustomer->isActive = '1';


            $destinationPath = public_path('images') . config('global.USER_IMG_PATH');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            // image upload

            if ($request->hasFile('profile')) {
                $profile = $request->file('profile');
                $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                $profile->move($destinationPath, $imageName);
                $saveCustomer->profile = $imageName;
            } else {
                $saveCustomer->profile = $request->profile;
            }

            $saveCustomer->save();
            // Create a new personal access token for the user
            $token = $saveCustomer->createToken('token-name');


            $response['error'] = false;
            $response['message'] = 'User Register Successfully';

            // Get the customer by ID first, then try to find by auth_id for consistency
            $credentials = Customer::find($saveCustomer->id);
            
            // Fallback: If not found by ID, try by auth_id
            if (!$credentials) {
                $credentials = Customer::where('auth_id', $auth_id)->where('logintype', $type)->first();
            }

            $response['token'] = $token->plainTextToken;
            $response['data'] = $credentials;

            // Check if credentials exists before accessing properties
            if ($credentials && !empty($credentials->email)) {
                Log::info('under Mail');
                $data = array(
                    'appName' => env("APP_NAME"),
                    'email' => $credentials->email
                );
                try {
                    // Get Data of email type
                    $emailTypeData = HelperService::getEmailTemplatesTypes("welcome_mail");

                    // Email Template
                    $welcomeEmailTemplateData = system_setting($emailTypeData['type']);
                    $appName = env("APP_NAME") ?? "eBroker";
                    $variables = array(
                        'app_name' => $appName,
                        'user_name' => !empty($request->name) ? $request->name : "$appName User",
                        'email' => $request->email,
                        'current_date_today' => now()->format('d M Y, h:i A'),
                    );
                    if (empty($welcomeEmailTemplateData)) {
                        $welcomeEmailTemplateData = "Welcome to $appName";
                    }
                    $welcomeEmailTemplate = HelperService::replaceEmailVariables($welcomeEmailTemplateData, $variables);

                    $data = array(
                        'email_template' => $welcomeEmailTemplate,
                        'email' => $request->email,
                        'title' => $emailTypeData['title'],
                    );
                    HelperService::sendMail($data, false, true); // Skip PDF for booking confirmations
                } catch (Exception $e) {
                    Log::info("Welcome Mail Sending Issue with error :- " . $e->getMessage());
                }
            }
        } else {
            $credentials = Customer::where('auth_id', $auth_id)->where('logintype', $type)->first();
            
            // Check if user exists
            if (!$credentials) {
                $response['error'] = true;
                $response['message'] = 'User not found or invalid credentials';
                return response()->json($response);
            }
            
            if ($credentials->isActive == 0) {
                $response['error'] = true;
                $response['message'] = 'Account Deactivated by Administrative please connect to them';
                $response['is_active'] = false;
                return response()->json($response);
            }
            $credentials->update();
            $token = $credentials->createToken('token-name');

            // Update or add FCM ID in UserToken for Current User
            if ($request->has('fcm_id') && !empty($request->fcm_id)) {
                Usertokens::updateOrCreate(
                    ['fcm_id' => $request->fcm_id],
                    ['customer_id' => $credentials->id]
                );
            }
            $response['error'] = false;
            $response['message'] = 'Login Successfully';
            $response['token'] = $token->plainTextToken;
            $response['data'] = $credentials;
        }
        return response()->json($response);
    }



    //* START :: get_slider   *//
    public function getSlider(Request $request)
    {
        $sliderData = Slider::select('id', 'type', 'image', 'web_image', 'category_id', 'propertys_id', 'show_property_details', 'link')->with(['category' => function ($query) {
            $query->select('id,category')->where('status', 1);
        }], 'property:id,title,title_image,price,propery_type as property_type')->orderBy('id', 'desc')->get()->map(function ($slider) {
            if (collect($slider->property)->isNotEmpty()) {
                $slider->property->parameters = $slider->property->parameters;
            }
            return $slider;
        });

        if (collect($sliderData)->isNotEmpty()) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $sliderData;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }

    //* END :: get_slider   *//


    //* START :: get_categories   *//
    public function get_categories(Request $request)
    {
        try {
            // Parse offset and limit, handle empty strings
            $offset = isset($request->offset) && $request->offset !== '' ? (int)$request->offset : 0;
            $limit = isset($request->limit) && $request->limit !== '' ? (int)$request->limit : 10;
            
            // Handle empty strings for latitude/longitude
            $latitude = $request->has('latitude') && $request->latitude !== '' ? $request->latitude : null;
            $longitude = $request->has('longitude') && $request->longitude !== '' ? $request->longitude : null;

            $categories = Category::select('id', 'category', 'image', 'parameter_types', 'meta_title', 'meta_description', 'meta_keywords', 'slug_id', 'property_classification')->where('status', '1');

            if ($request->has('has_property') && $request->has_property == true) {
                $categories = $categories->clone()->whereHas('properties', function ($query) use ($latitude, $longitude) {
                    $query->where(['status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude && $latitude !== '' && $longitude !== '', function ($query) use ($latitude, $longitude) {
                        $query->where('latitude', $latitude)->where('longitude', $longitude);
                    });
                })->withCount([
                    'properties' => function ($query) use ($latitude, $longitude) {
                        $query->where(['status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude && $latitude !== '' && $longitude !== '', function ($query) use ($latitude, $longitude) {
                            $query->where('latitude', $latitude)->where('longitude', $longitude);
                        });
                    }
                ]);
            }

            if (isset($request->search) && !empty($request->search)) {
                $search = $request->search;
                $categories->where('category', 'LIKE', "%$search%");
            }

            if (isset($request->id) && !empty($request->id)) {
                $id = $request->id;
                $categories->where('id', $id);
            }
            if (isset($request->slug_id) && !empty($request->slug_id)) {
                $id = $request->slug_id;
                $categories->where('slug_id', $request->slug_id);
            }

            // Filter by property classification if provided
            if (isset($request->property_classification) && !empty($request->property_classification)) {
                $categories->where('property_classification', $request->property_classification);
            }

            $total = $categories->get()->count();
            $result = $categories->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();

            // Fix: map should return the modified result
            $result = $result->map(function ($item) {
                $item['meta_image'] = $item->image;
                return $item;
            });

            if (!$result->isEmpty()) {
                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                foreach ($result as $row) {
                    try {
                        $parameterData = $row->parameters ?? collect([]);
                        if (collect($parameterData)->isNotEmpty()) {
                            $parameterData = $parameterData->map(function ($item) {
                                if (isset($item->assigned_parameter)) {
                                    unset($item->assigned_parameter);
                                }
                                return $item;
                            });
                        }
                        $row->parameter_types = $parameterData;
                    } catch (\Exception $e) {
                        \Log::warning('Error processing parameters for category', [
                            'category_id' => $row->id,
                            'error' => $e->getMessage()
                        ]);
                        $row->parameter_types = collect([]);
                    }
                }

                $response['total'] = $total;
                $response['data'] = $result;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
                $response['total'] = 0;
            }
            return response()->json($response);
        } catch (Exception $e) {
            // Log the actual error with full details
            \Log::error('get_categories failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);
            
            $response = array(
                'error' => true,
                'message' => 'Failed to fetch categories: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            );
            return response()->json($response, 500);
        }
    }
    //* END :: get_slider   *//

    //* START :: get_category_classifications   *//
    public function get_category_classifications(Request $request)
    {
        $classifications = [
            1 => 'Sell/Long Term Rent',
            2 => 'Commercial',
            3 => 'New Project',
            4 => 'Vacation Homes',
            5 => 'Hotel Booking'
        ];

        $result = [];
        foreach ($classifications as $key => $value) {
            $result[] = [
                'id' => $key,
                'name' => $value
            ];
        }

        return response()->json([
            'error' => false,
            'message' => 'Data Fetch Successfully',
            'data' => $result
        ]);
    }

    public function get_categories_by_classification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classification' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        $classification = $request->classification;
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 100; // Increased limit to get all categories

        $categories = Category::select('id', 'category', 'image', 'parameter_types', 'meta_title', 'meta_description', 'meta_keywords', 'slug_id', 'property_classification')
            ->where('status', '1');

        // Only filter by classification if it's a valid value
        if ($classification >= 1 && $classification <= 5) {
            $categories->where('property_classification', $classification);
        }

        if (isset($request->search) && !empty($request->search)) {
            $search = $request->search;
            $categories->where('category', 'LIKE', "%$search%");
        }

        $total = $categories->count();
        $result = $categories->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();

        $result->map(function ($result) {
            $result['meta_image'] = $result->image;
        });

        if (!$result->isEmpty()) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            foreach ($result as $row) {
                $parameterData = $row->parameters;
                if (collect($parameterData)->isNotEmpty()) {
                    $parameterData = $parameterData->map(function ($item) {
                        unset($item->assigned_parameter);
                        return $item;
                    });
                }
                $row->parameter_types = $parameterData;
            }

            $response['total'] = $total;
            $response['data'] = $result;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    //* END :: get_category_classifications   *//

    //* START :: change_password   *//
    public function change_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()]);
        }

        try {
            $user = Auth::user();
            if(!$user){
                 return response()->json(['error' => true, 'message' => 'User not authenticated']);
            }
            $customer = Customer::find($user->id);

            if (!$customer) {
                return response()->json(['error' => true, 'message' => 'User not found']);
            }

            // Check if user has a password set (social login users might not)
            if ($customer->password && !Hash::check($request->old_password, $customer->password)) {
                return response()->json(['error' => true, 'message' => 'Incorrect old password']);
            }

            $customer->password = Hash::make($request->new_password);
            $customer->save();

            return response()->json(['error' => false, 'message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }
    //* END :: change_password   *//

    //* START :: about_meofile   *//
    public function update_profile(Request $request)
    {
        try {
            DB::beginTransaction();
            $currentUser = Auth::user();
            $customer =  Customer::find($currentUser->id);

            if (!empty($customer)) {

                // update the Data passed in payload
                $fieldsToUpdate = $request->only([
                    'name',
                    'email',
                    'mobile',
                    'whatsappnumber',
                    'fcm_id',
                    'address',
                    'notification',
                    'about_me',
                    'facebook_id',
                    'twiiter_id',
                    'instagram_id',
                    'youtube_id',
                    'latitude',
                    'longitude',
                    'city',
                    'state',
                    'country',
                    'customer_type',
                    'management_type'
                ]);

                // Validate customer_type if provided
                if (isset($fieldsToUpdate['customer_type']) && !in_array($fieldsToUpdate['customer_type'], ['property_owner', 'agent'])) {
                    return response()->json(['error' => true, 'message' => 'Invalid customer type. Must be "property_owner" or "agent".'], 422);
                }

                // Validate management_type if customer is property_owner
                if (isset($fieldsToUpdate['customer_type']) && $fieldsToUpdate['customer_type'] === 'property_owner') {
                    if (isset($fieldsToUpdate['management_type']) && !in_array($fieldsToUpdate['management_type'], ['himself', 'as home'])) {
                        return response()->json(['error' => true, 'message' => 'Invalid management type. Must be "himself" or "as home".'], 422);
                    }
                }

                $customer->update($fieldsToUpdate);

                if ($request->has('fcm_id') && !empty($request->fcm_id)) {
                    Usertokens::updateOrCreate(
                        ['fcm_id' => $request->fcm_id],
                        ['customer_id' => $customer->id,]
                    );
                }

                // Handle company information for agents
                if (($customer->customer_type === 'agent' || $request->customer_type === 'agent') && $request->has('company')) {
                    $companyData = $request->company;

                    // Create or update company information
                    if ($customer->company_id) {
                        $company = Company::find($customer->company_id);
                        if ($company) {
                            $company->update($companyData);
                        } else {
                            $company = Company::create($companyData);
                            $customer->company_id = $company->id;
                            $customer->save();
                        }
                    } else {
                        $company = Company::create($companyData);
                        $customer->company_id = $company->id;
                        $customer->save();
                    }
                }

                // Update Profile
                if ($request->hasFile('profile')) {
                    $old_image = $customer->profile;
                    $profile = $request->file('profile');

                    // Use store_image function for consistent file handling
                    $imageName = store_image($profile, 'USER_IMG_PATH');

                    if ($imageName) {
                        $customer->profile = $imageName;

                        // Delete old image if it exists
                        if ($old_image != '') {
                            unlink_image($old_image);
                        }

                        $customer->update();
                    }
                }

                // Refresh customer with related company data
                $customer = Customer::with(['company'])->find($customer->id);

                DB::commit();
                return response()->json(['error' => false, 'data' => $customer]);
            } else {
                return response()->json(['error' => false, 'message' => "No data found!", 'data' => []]);
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => true, 'message' => 'Something Went Wrong: ' . $e->getMessage()], 500);
        }
    }

    //* END :: update_profile   *//


    //* START :: get_user_by_id   *//
    public function getUserData()
    {
        try {
            // Get LoggedIn User Data from Toke
            $userData = Auth::user();
            // Check the User Data is not Empty
            if (collect($userData)->isNotEmpty()) {
                $response['error'] = false;
                $response['data'] = $userData;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }
    //* END :: get_user_by_id   *//


    //* START :: get_property   *//
    public function get_property(Request $request)
    {
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;
        if (collect(Auth::guard('sanctum')->user())->isNotEmpty()) {
            $current_user = Auth::guard('sanctum')->user()->id;
        } else {
            $current_user = null;
        }

        // Get language preference if specified
        $language = $request->has('language') ? $request->language : null;

        // Select fields - always include both English and Arabic fields, plus employee fields
        // Include all fields that might be accessed by the helper function
        // Note: title_image_hash and is_user_verified are not columns in propertys table
        $select = [
            'id', 'slug_id', 'title', 'price', 'description', 'address', 'propery_type', 
            'title_image', 'status', 'request_status', 'total_click', 'state', 'city', 
            'country', 'latitude', 'longitude', 'added_by', 'is_premium', 'property_classification', 
            'availability_type', 'available_dates', 'corresponding_day', 'instant_booking', 
            'non_refundable', 'title_ar', 'description_ar', 'area_description_ar', 'area_description', 
            'company_employee_username', 'company_employee_email', 'company_employee_phone_number', 
            'company_employee_whatsappnumber', 'video_link', 'rentduration', 'meta_title', 
            'meta_description', 'meta_keywords', 'meta_image', 'three_d_image', 'rent_package',
            'created_at', 'category_id', 'client_address', 'cancellation_period'
        ];

        $property = Property::select($select)
            ->with('customer', 'user', 'category:id,category,image,slug_id,parameter_types', 'assignfacilities.outdoorfacilities', 'parameters', 'favourite', 'interested_users', 'certificates', 'vacationApartments')
            ->where(['status' => 1, 'request_status' => 'approved']);

        // If Property Classification is passed
        // CRITICAL: Do NOT apply property_classification filter if Studio filter is selected
        // Studio filter should only apply to regular properties (not vacation homes or hotels)
        $bedroomsValue = $request->has('bedrooms') ? (string) $request->bedrooms : null;
        $isStudio = ($bedroomsValue === '0'); // Studio filter
        
        if ($request->has('property_classification') && !empty($request->property_classification) && !$isStudio) {
            $propertyClassification = (int) $request->property_classification;
            $property = $property->where('property_classification', $propertyClassification);
            
            // Log property classification filter for vacation homes and hotel bookings
            if ($propertyClassification == 4 || $propertyClassification == 5) {
                \Log::info('🔍 Property Classification Filter Applied', [
                    'property_classification' => $propertyClassification,
                    'type' => $propertyClassification == 4 ? 'Vacation Homes' : 'Hotel Bookings',
                    'property_type' => $request->property_type ?? 'not_set',
                    'bedrooms' => $request->bedrooms ?? 'not_set',
                    'bathrooms' => $request->bathrooms ?? 'not_set',
                    'location' => [
                        'city' => $request->city ?? 'not_set',
                        'state' => $request->state ?? 'not_set',
                        'country' => $request->country ?? 'not_set',
                    ],
                    'note' => 'Filter applied for ' . ($propertyClassification == 4 ? 'vacation homes' : 'hotel bookings')
                ]);
            }
        }

        // CRITICAL FIX: Apply property_type filter BEFORE bedroom filter for vacation homes
        // This ensures whereHas subqueries respect the property_type constraint
        $property_type = $request->property_type;  //0 : Sell 1:Rent
        if (isset($property_type) && (!empty($property_type) || $property_type == 0)) {
            // Convert to integer for consistent comparison (handles both string "0"/"1" and integer 0/1)
            $property_type_int = (int) $property_type;
            $property = $property->where('propery_type', $property_type_int);
        }

        // CRITICAL FIX: Apply category_id filter BEFORE bedroom filter for vacation homes
        // This ensures whereHas subqueries respect the category_id constraint
        // Special handling for hotels: if property_classification is 5 and category_id is provided,
        // treat category_id as hotel_apartment_type_id (star rating) for filtering
        if ($request->has('category_id') && !empty($request->category_id)) {
            $categoryId = $request->category_id;
            $propertyClassification = $request->has('property_classification') ? $request->property_classification : null;
            
            // Check if this is a hotel (property_classification = 5)
            if ($propertyClassification == 5) {
                // For hotels, filter by category name containing the star rating
                if (is_numeric($categoryId) && $categoryId >= 1 && $categoryId <= 5) {
                    $starPatterns = [
                        $categoryId . ' star',
                        $categoryId . ' Star',
                        $categoryId . ' stars',
                        $categoryId . ' Stars',
                        'star ' . $categoryId,
                        'Star ' . $categoryId,
                        'stars ' . $categoryId,
                        'Stars ' . $categoryId,
                        $categoryId . 'نجمة',
                        'نجمة ' . $categoryId
                    ];
                    
                    $property = $property->whereHas('category', function($catQuery) use ($starPatterns) {
                        foreach ($starPatterns as $pattern) {
                            $catQuery->orWhere('category', 'LIKE', '%' . $pattern . '%');
                        }
                    });
                }
            } else {
                // For non-hotel properties (including vacation homes), use category_id normally
                $property = $property->where('category_id', $categoryId);
            }
        }

        $max_price = isset($request->max_price) ? $request->max_price : Property::max('price');
        $min_price = isset($request->min_price) ? $request->min_price : 0;
        $totalClicks = 0;

        // If parameter ID passed
        if ($request->has('parameter_id') && !empty($request->parameter_id)) {
            $parameterId = $request->parameter_id;
            $property = $property->whereHas('parameters', function ($q) use ($parameterId) {
                $q->where('parameter_id', $parameterId);
            });
        }

        // If bedrooms filter is passed - exact match on parameter value
        // For regular properties: check parameters table
        // For vacation homes (property_classification = 4): also check vacation_apartments table
        // Special handling: When "0" is sent (Studio), match both "0" and "studio" (case-insensitive)
        if ($request->has('bedrooms') && $request->bedrooms !== null && $request->bedrooms !== '') {
            $bedroomsValue = (string) $request->bedrooms;
            $bedroomsIntValue = (int) $bedroomsValue; // For vacation apartments comparison
            
            // Check if filtering for Studio - handle both "0" and "Studio" (case-insensitive)
            $bedroomsValueLower = strtolower(trim($bedroomsValue));
            $isStudio = ($bedroomsValue === '0' || $bedroomsValueLower === 'studio');
            
            // Debug logging for all bedroom filters (not just Studio)
            \Log::info('🔍 Bedrooms Filter Applied (Backend)', [
                'raw_bedrooms' => $request->bedrooms,
                'bedroomsValue' => $bedroomsValue,
                'bedroomsIntValue' => $bedroomsIntValue,
                'isStudio' => $isStudio,
                'property_classification' => $request->property_classification ?? 'not_set',
                'note' => 'Filter will match: assign_parameters.value = "' . $bedroomsValue . '" OR vacation_apartments.bedrooms = ' . $bedroomsIntValue
            ]);
            
            // If Studio was passed as string, normalize to "0" for consistency
            if ($isStudio && $bedroomsValueLower === 'studio') {
                $bedroomsValue = '0';
                $bedroomsIntValue = 0;
            }
            
            // Comprehensive debug logging for Studio filter
            if ($isStudio) {
                // Count vacation apartments with 0 bedrooms before applying filter
                $vacationStudioCount = Property::where('property_classification', 4)
                    ->where(['status' => 1, 'request_status' => 'approved'])
                    ->whereHas('vacationApartments', function ($aptQuery) {
                        $aptQuery->where('status', 1)->where('bedrooms', 0);
                    })
                    ->count();
                
                // Count properties with Studio in assign_parameters
                $assignParamsStudioCount = Property::where(['status' => 1, 'request_status' => 'approved'])
                    ->whereExists(function ($existsQuery) {
                        $existsQuery->select(DB::raw(1))
                            ->from('assign_parameters')
                            ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                            ->where(function ($linkQuery) {
                                $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                    ->orWhere(function ($modalQuery) {
                                        $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                            ->where(function ($typeQuery) {
                                                $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                    ->orWhere('assign_parameters.modal_type', 'property');
                                            });
                                    });
                            })
                            ->where(function ($nameQuery) {
                                $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                    ->orWhere('parameters.name', 'LIKE', '%bed%');
                            })
                            ->where(function ($valueQuery) {
                                $valueQuery->where('assign_parameters.value', '0')
                                    ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                    ->orWhere('assign_parameters.value', 'Studio')
                                    ->orWhere('assign_parameters.value', 'STUDIO');
                            });
                    })
                    ->count();
                
                \Log::info('🔍 Studio Bedrooms Filter Debug (Backend)', [
                    'raw_bedrooms' => $request->bedrooms,
                    'bedroomsValue' => $bedroomsValue,
                    'bedroomsIntValue' => $bedroomsIntValue,
                    'bedroomsValueLower' => $bedroomsValueLower,
                    'isStudio' => $isStudio,
                    'property_classification' => $request->property_classification ?? 'not_set',
                    'vacation_apartments_studio_count' => $vacationStudioCount,
                    'assign_parameters_studio_count' => $assignParamsStudioCount,
                    'note' => 'vacation_apartments_studio_count shows properties with ONLY vacation_apartments data (no assign_parameters)',
                    'request_all' => $request->only(['bedrooms', 'property_classification', 'property_type', 'category_id'])
                ]);
            }
            
            // CRITICAL FIX: Check property_classification to determine query priority
            // For vacation homes (property_classification = 4), check vacation_apartments FIRST
            // This ensures properties with only vacation_apartments data are matched correctly
            $propertyClassification = $request->has('property_classification') ? $request->property_classification : null;
            $isVacationHomes = ($propertyClassification == 4 || $propertyClassification == '4');
            
            \Log::info('🔍 Bedroom Filter Query Structure (Backend)', [
                'property_classification' => $propertyClassification,
                'isVacationHomes' => $isVacationHomes,
                'bedroomsValue' => $bedroomsValue,
                'bedroomsIntValue' => $bedroomsIntValue,
                'queryPriority' => $isVacationHomes 
                    ? 'Checking vacation_apartments FIRST, then assign_parameters' 
                    : 'Checking assign_parameters FIRST, then vacation_apartments as fallback',
                'note' => 'This fix ensures vacation homes with only vacation_apartments data are matched correctly'
            ]);
            
            // Get property_type early to use in bedroom filter query if needed
            // NOTE: category_id is now applied to main query before this, so it will be respected automatically
            $propertyType = $request->has('property_type') && ($request->property_type !== null && $request->property_type !== '') ? $request->property_type : null;
            
            $property = $property->where(function ($query) use ($bedroomsValue, $bedroomsIntValue, $isStudio, $isVacationHomes, $propertyType) {
                if ($isVacationHomes) {
                    // FIXED: For vacation homes, exclude Studio filter entirely
                    // Studio filter should only apply to regular properties
                    if ($isStudio) {
                        // For Studio filter: exclude all vacation homes
                        $query->whereRaw('1=0'); // This will exclude all vacation homes from Studio filter
                    } else {
                        // For 1,2,3 bedroom filters: include if vacation_apartments match
                        // Ignore assign_parameters for non-studio filters - just check vacation_apartments
                        $query->whereHas('vacationApartments', function($aptQuery) use ($bedroomsIntValue) {
                            $aptQuery->where('status', 1)
                                ->where('bedrooms', $bedroomsIntValue)
                                ->where('bedrooms', '!=', 0); // Exclude studio apartments
                        });
                    }
                    
                    // Debug logging for fixed vacation homes logic
                    \Log::info('🔍 Vacation Homes Bedroom Filter Logic (Backend) - FIXED', [
                        'bedroomsValue' => $bedroomsValue,
                        'isStudio' => $isStudio,
                        'logic' => 'Including properties based on vacation_apartments, respecting assign_parameters for studio filtering',
                        'note' => 'Properties with matching vacation_apartments will appear in bedroom filters, studio in assign_parameters only affects studio filter'
                    ]);
                } else {
                    // For regular properties (not vacation homes), check assign_parameters first
                    // Then also check vacation_apartments as fallback (in case property_classification wasn't set but property has vacation_apartments)
                    $query->where(function ($subQuery) use ($bedroomsValue, $isStudio) {
                        $subQuery->whereExists(function ($existsQuery) use ($bedroomsValue, $isStudio) {
                            $existsQuery->select(DB::raw(1))
                                ->from('assign_parameters')
                                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                                ->where(function ($linkQuery) {
                                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                        ->orWhere(function ($modalQuery) {
                                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                                ->where(function ($typeQuery) {
                                                    $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                        ->orWhere('assign_parameters.modal_type', 'property');
                                                });
                                        });
                                })
                                ->where(function ($nameQuery) {
                                    $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                        ->orWhere('parameters.name', 'LIKE', '%bed%');
                                })
                                // NOTE: category_id is already applied to main query, so it will be respected automatically
                                ->where(function ($valueQuery) use ($bedroomsValue, $isStudio) {
                                    $valueQuery->whereNotNull('assign_parameters.value')
                                        ->where('assign_parameters.value', '!=', '')
                                        ->where('assign_parameters.value', '!=', 'null')
                                        ->whereRaw('TRIM(assign_parameters.value) != ?', ['']);
                                    
                                    if ($isStudio) {
                                        $valueQuery->where(function ($studioQuery) {
                                            $studioQuery->where('assign_parameters.value', '0')
                                                ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                                ->orWhere('assign_parameters.value', 'Studio')
                                                ->orWhere('assign_parameters.value', 'STUDIO')
                                                ->orWhere('assign_parameters.value', '"0"')
                                                ->orWhere('assign_parameters.value', '"Studio"')
                                                ->orWhere('assign_parameters.value', '"studio"')
                                                ->orWhere('assign_parameters.value', '"STUDIO"')
                                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['0'])
                                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['Studio'])
                                                ->orWhereRaw('LOWER(TRIM(JSON_EXTRACT(assign_parameters.value, "$"))) = ?', ['studio'])
                                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['0'])
                                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['Studio']);
                                        });
                                    } else {
                                        $valueQuery->where(function ($exactQuery) use ($bedroomsValue) {
                                            $exactQuery->where('assign_parameters.value', $bedroomsValue)
                                                ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                                                ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
                                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
                                                ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
                                        });
                                    }
                                });
                        });
                    })
                    // Also check vacation_apartments as fallback (for cases where property_classification wasn't set)
                    ->orWhere(function ($vacationQuery) use ($bedroomsIntValue, $isStudio) {
                        $vacationQuery->where('property_classification', 4)
                            ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue, $isStudio) {
                                $aptQuery->where('status', 1);
                                
                                if ($isStudio) {
                                    $aptQuery->where('bedrooms', 0);
                                } else {
                                    $aptQuery->where('bedrooms', $bedroomsIntValue);
                                }
                            });
                    });
                }
            });
        }

        // If bathrooms filter is passed - exact match on parameter value
        // For regular properties: check parameters table
        // For vacation homes (property_classification = 4): also check vacation_apartments table
        if ($request->has('bathrooms') && $request->bathrooms !== null && $request->bathrooms !== '') {
            $bathroomsValue = (string) $request->bathrooms;
            $bathroomsIntValue = (int) $bathroomsValue; // For vacation apartments comparison
            
            $property = $property->where(function ($query) use ($bathroomsValue, $bathroomsIntValue) {
                // Check parameters table (for regular properties)
                // Handle both plain string values and JSON-encoded values
                // Also handle both morphMany (modal_id) and direct property_id relationships
                $query->where(function ($subQuery) use ($bathroomsValue) {
                    // Check directly via assign_parameters table
                    // This handles both property_id matches AND modal_id matches (polymorphic)
                    $subQuery->whereExists(function ($existsQuery) use ($bathroomsValue) {
                        $existsQuery->select(DB::raw(1))
                            ->from('assign_parameters')
                            ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                            ->where(function ($linkQuery) {
                                // Match by property_id OR by modal_id (polymorphic)
                                $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                    ->orWhere(function ($modalQuery) {
                                        $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                            ->where(function ($typeQuery) {
                                                $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                    ->orWhere('assign_parameters.modal_type', 'property');
                                            });
                                    });
                            })
                            ->where(function ($nameQuery) {
                                $nameQuery->where('parameters.name', 'LIKE', '%bathroom%')
                                    ->orWhere('parameters.name', 'LIKE', '%bath%');
                            })
                            ->where('assign_parameters.value', $bathroomsValue);
                    });
                })
                // OR check vacation_apartments table (for vacation homes)
                ->orWhere(function ($vacationQuery) use ($bathroomsIntValue) {
                    $vacationQuery->where('property_classification', 4)
                        ->whereHas('vacationApartments', function ($aptQuery) use ($bathroomsIntValue) {
                            $aptQuery->where('bathrooms', $bathroomsIntValue);
                        });
                });
            });
        }

        // Commercial area filter (for commercial properties - property_classification = 2)
        if ($request->has('commercial_area') && !empty($request->commercial_area)) {
            $commercialAreaValue = (string) $request->commercial_area;
            $property = $property->where(function ($query) use ($commercialAreaValue) {
                // Check parameters table for commercial area
                $query->whereExists(function ($existsQuery) use ($commercialAreaValue) {
                    $existsQuery->select(DB::raw(1))
                        ->from('assign_parameters')
                        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                        ->where(function ($linkQuery) {
                            // Match by property_id OR by modal_id (polymorphic)
                            $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                ->orWhere(function ($modalQuery) {
                                    $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                        ->where(function ($typeQuery) {
                                            $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                ->orWhere('assign_parameters.modal_type', 'property');
                                        });
                                });
                        })
                        ->where(function ($nameQuery) {
                            $nameQuery->where('parameters.name', 'LIKE', '%commercial area%')
                                ->orWhere('parameters.name', 'LIKE', '%area%');
                        })
                        ->where(function ($valueQuery) use ($commercialAreaValue) {
                            $valueQuery->where('assign_parameters.value', $commercialAreaValue)
                                ->orWhere('assign_parameters.value', '"' . $commercialAreaValue . '"')
                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$commercialAreaValue])
                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$commercialAreaValue]);
                        });
                });
            });
        }

        // Commercial Area Range Filter (Min/Max Area)
        if (($request->has('min_area') && !empty($request->min_area)) || ($request->has('max_area') && !empty($request->max_area))) {
            $minArea = $request->min_area;
            $maxArea = $request->max_area;
            
            \Log::info('🔍 Area Filter Applied', [
                'min_area' => $minArea,
                'max_area' => $maxArea,
                'request_all' => $request->all()
            ]);
            
            $property = $property->where(function ($query) use ($minArea, $maxArea) {
                // Check parameters table for area values
                $query->whereExists(function ($existsQuery) use ($minArea, $maxArea) {
                    $existsQuery->select(DB::raw(1))
                        ->from('assign_parameters')
                        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                        ->where(function ($linkQuery) {
                            // Match by property_id OR by modal_id (polymorphic)
                            $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                ->orWhere(function ($modalQuery) {
                                    $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                        ->where(function ($typeQuery) {
                                            $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                ->orWhere('assign_parameters.modal_type', 'property');
                                        });
                                });
                        })
                        ->where(function ($nameQuery) {
                            $nameQuery->where('parameters.name', 'LIKE', '%commercial area%')
                                ->orWhere('parameters.name', 'LIKE', '%area%')
                                ->orWhere('parameters.name', 'LIKE', '%size%')
                                ->orWhere('parameters.name', 'LIKE', '%sqft%')
                                ->orWhere('parameters.name', 'LIKE', '%sqm%')
                                ->orWhere('parameters.name', 'LIKE', '%square%');
                        })
                        ->where(function ($valueQuery) use ($minArea, $maxArea) {
                            // Use CAST to treat the value as a number for range comparison
                            // This handles values like "180 Sq.m" by parsing the leading number
                            if (!empty($minArea)) {
                                $valueQuery->whereRaw('CAST(assign_parameters.value AS UNSIGNED) >= ?', [$minArea]);
                            }
                            if (!empty($maxArea)) {
                                $valueQuery->whereRaw('CAST(assign_parameters.value AS UNSIGNED) <= ?', [$maxArea]);
                            }
                        });
                });
            });
        } else {
            \Log::info('🔍 Area Filter Skipped', [
                'has_min_area' => $request->has('min_area'),
                'min_area' => $request->min_area,
                'has_max_area' => $request->has('max_area'),
                'max_area' => $request->max_area,
                'request_all' => $request->all()
            ]);
        }

        // If Max Price And Min Price passed
        if (isset($request->max_price) && isset($request->min_price) && (!empty($request->max_price) || !empty($request->min_price))) {
            // For hotel properties (classification = 5), filter by minimum room price
            // For other properties, filter by main property price
            if ($request->has('property_classification') && $request->property_classification == 5) {
                // Hotel properties: filter by minimum room price
                // This matches the frontend logic that shows the lowest available room price
                $property = $property->whereHas('hotelRooms', function ($query) use ($request) {
                    // Apply price range filtering to hotel rooms
                    if (!empty($request->min_price)) {
                        $query->where('price_per_night', '>=', $request->min_price);
                    }
                    if (!empty($request->max_price)) {
                        $query->where('price_per_night', '<=', $request->max_price);
                    }
                });
            } else {
                // Regular properties: filter by main property price
                if (!empty($request->min_price) && !empty($request->max_price)) {
                    $property = $property->whereBetween('price', [$request->min_price, $request->max_price]);
                } elseif (!empty($request->min_price)) {
                    $property = $property->where('price', '>=', $request->min_price);
                } elseif (!empty($request->max_price)) {
                    $property = $property->where('price', '<=', $request->max_price);
                }
            }
        }

        // NOTE: property_type filter is now applied BEFORE bedroom filter (see above)
        // This ensures whereHas subqueries for vacation homes respect the property_type constraint
        // The duplicate check below is removed to avoid applying the filter twice

        // If Posted Since 0 or 1 is passed
        if ($request->has('posted_since') && !empty($request->posted_since)) {
            $posted_since = $request->posted_since;
            // 0 - Last Week
            if ($posted_since == 0) {
                $startDateOfWeek = Carbon::now()->subWeek()->startOfWeek();
                $endDateOfWeek = Carbon::now()->subWeek()->endOfWeek();
                $property = $property->whereBetween('created_at', [$startDateOfWeek, $endDateOfWeek]);
            }
            // 1 - Yesterday
            if ($posted_since == 1) {
                $yesterdayDate = Carbon::yesterday();
                $property =  $property->whereDate('created_at', $yesterdayDate);
            }
        }

        // NOTE: category_id filter is now applied earlier (before bedroom filter, around line 806)
        // This ensures whereHas subqueries for vacation homes respect the category_id constraint
        // The duplicate filter below has been removed to avoid applying it twice

        // If Rent Package is Passed
        if ($request->has('rent_package') && !empty($request->rent_package)) {
            $property = $property->where('rent_package', $request->rent_package);
        }

        // If Hotel Apartment Type ID is Passed (explicit parameter)
        if ($request->has('hotel_apartment_type_id') && !empty($request->hotel_apartment_type_id)) {
            $property = $property->where('hotel_apartment_type_id', $request->hotel_apartment_type_id);
        }

        // If Id is passed
        if ($request->has('id') && !empty($request->id)) {
            $property = $property->where('id', $request->id);
            HelperService::incrementTotalClick('property', $request->id);
        }

        if ($request->has('category_slug_id') && !empty($request->category_slug_id)) {
            // Get the category date on category slug id
            $category = Category::where('slug_id', $request->category_slug_id)->first();
            // if category data exists then get property on the category id
            if (collect($category)->isNotEmpty()) {
                $property = $property->where('category_id', $category->id);
            }
        }

        // If Property Slug is passed
        if ($request->has('slug_id') && !empty($request->slug_id)) {
            $property = $property->where('slug_id', $request->slug_id);
            HelperService::incrementTotalClick('property', null, $request->slug_id);
        }

        // If Country is passed
        if ($request->has('country') && !empty($request->country)) {
            $property = $property->where('country', $request->country);
        }

        // If State is passed
        if ($request->has('state') && !empty($request->state)) {
            $property = $property->where('state', $request->state);
        }

        // If City is passed
        if ($request->has('city') && !empty($request->city)) {
            // Check if cityVariations array is provided (for normalized city names with multiple spellings)
            if ($request->has('cityVariations') && is_array($request->cityVariations) && count($request->cityVariations) > 0) {
                // Search for properties matching any of the city name variations (case-insensitive)
                $property = $property->where(function($query) use ($request) {
                    foreach ($request->cityVariations as $variation) {
                        $query->orWhereRaw('LOWER(city) = LOWER(?)', [$variation]);
                    }
                });
            } else {
                // Single city name (case-insensitive to handle hurghada vs Hurghada)
                $property = $property->whereRaw('LOWER(city) = LOWER(?)', [$request->city]);
            }
        }

        // If nearby cities search is requested (free-text search with nearby detection)
        if ($request->has('isFreeTextSearch') && $request->isFreeTextSearch === true && $request->has('searchQuery')) {
            $searchQuery = $request->searchQuery;
            
            // First, try to find exact city matches (case-insensitive)
            $exactMatches = $property->whereRaw('LOWER(city) LIKE LOWER(?)', ['%' . $searchQuery . '%'])->pluck('city')->unique();
            
            if ($exactMatches->isEmpty()) {
                // If no exact matches, find nearby cities using coordinates
                $nearbyCities = $this->findNearbyCitiesByName($searchQuery);
                
                if (!empty($nearbyCities)) {
                    // Search in nearby cities
                    $property = $property->whereIn('city', $nearbyCities);
                } else {
                    // If no nearby cities found, search in all cities (broader search, case-insensitive)
                    $property = $property->whereRaw('LOWER(city) LIKE LOWER(?)', ['%' . $searchQuery . '%']);
                }
            } else {
                // Use exact matches found
                $property = $property->whereIn('city', $exactMatches);
            }
        }

        // If promoted is passed then get the properties according to advertisement's data except the advertisement's slider data
        if ($request->has('promoted') && !empty($request->promoted)) {
            $propertiesId = Advertisement::whereNot('type', 'Slider')->where('is_enable', 1)->pluck('property_id');
            $property = $property->whereIn('id', $propertiesId)->inRandomOrder();
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }

        // IF User Promoted Param Passed then show the User's Advertised data
        if ($request->has('users_promoted') && !empty($request->users_promoted)) {
            $propertiesId = Advertisement::where('customer_id', $current_user)->pluck('property_id');
            $property = $property->whereIn('id', $propertiesId);
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $property = $property->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', "%$search%")
                    ->orWhere('address', 'LIKE', "%$search%")
                    ->orWhereHas('category', function ($query1) use ($search) {
                        $query1->where('category', 'LIKE', "%$search%");
                    });
            });
        }

        // If Top Rated passed then show the property data with Order by on Total Click Descending
        if ($request->has('top_rated') && $request->top_rated == 1) {
            $property = $property->orderBy('total_click', 'DESC');
        }

        // IF Most Liked Passed then show the data according to
        if ($request->has('most_liked') && !empty($request->most_liked)) {
            $property = $property->withCount('favourite')->orderBy('favourite_count', 'DESC');
        }

        // CRITICAL: Log the query BEFORE counting to see what filters are applied
        if ($request->has('bedrooms') && $request->bedrooms !== null && $request->bedrooms !== '') {
            $bedroomsValue = (string) $request->bedrooms;
            $propertyClassification = $request->has('property_classification') ? $request->property_classification : null;
            $propertyType = $request->has('property_type') ? $request->property_type : null;
            
            \Log::info('🔍 Bedroom Filter - Query BEFORE count', [
                'bedrooms' => $bedroomsValue,
                'property_classification' => $propertyClassification,
                'property_type' => $propertyType,
                'is_vacation_homes' => ($propertyClassification == 4 || $propertyClassification == '4'),
                'sql_query' => $property->toSql(),
                'sql_bindings' => $property->getBindings(),
                'note' => 'This shows the complete query with all filters applied, including property_type'
            ]);
        }
        
        // Log filter summary for vacation homes and hotel bookings
        $propertyClassification = $request->has('property_classification') && !empty($request->property_classification) 
            ? (int) $request->property_classification 
            : null;
        if ($propertyClassification == 4 || $propertyClassification == 5) {
            \Log::info('🔍 Filter Summary - ' . ($propertyClassification == 4 ? 'Vacation Homes' : 'Hotel Bookings'), [
                'property_classification' => $propertyClassification,
                'property_type' => $request->property_type ?? 'not_set',
                'bedrooms' => $request->bedrooms ?? 'not_set',
                'bathrooms' => $request->bathrooms ?? 'not_set',
                'location' => [
                    'city' => $request->city ?? 'not_set',
                    'state' => $request->state ?? 'not_set',
                    'country' => $request->country ?? 'not_set',
                ],
                'price_range' => [
                    'min_price' => $request->min_price ?? 'not_set',
                    'max_price' => $request->max_price ?? 'not_set',
                ],
                'facilities' => $request->parameter_id ?? 'not_set',
                'check_in_date' => $request->check_in_date ?? 'not_set',
                'check_out_date' => $request->check_out_date ?? 'not_set',
                'hotel_apartment_type_id' => $request->hotel_apartment_type_id ?? 'not_set',
                'note' => 'Complete filter summary before query execution'
            ]);
        }
        
        $total = $property->count();
        
                // Log bedroom filter query results for all bedroom values (not just Studio)
                if ($request->has('bedrooms') && $request->bedrooms !== null && $request->bedrooms !== '') {
                    $bedroomsValue = (string) $request->bedrooms;
                    $bedroomsValueLower = strtolower(trim($bedroomsValue));
                    $isStudio = ($bedroomsValue === '0' || $bedroomsValueLower === 'studio');
                    $bedroomsIntValue = (int) $bedroomsValue;
                    
                    // Count properties with matching assign_parameters
                    $assignParamsCount = DB::table('assign_parameters')
                        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                        ->join('propertys', function($join) {
                            $join->on('assign_parameters.property_id', '=', 'propertys.id')
                                ->orOn(function($q) {
                                    $q->on('assign_parameters.modal_id', '=', 'propertys.id')
                                      ->where(function($typeQuery) {
                                          $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                    ->orWhere('assign_parameters.modal_type', 'property');
                                      });
                                });
                        })
                        ->where('propertys.status', 1)
                        ->where('propertys.request_status', 'approved')
                        ->where(function ($nameQuery) {
                            $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                ->orWhere('parameters.name', 'LIKE', '%bed%');
                        })
                        ->where(function ($valueQuery) use ($bedroomsValue, $isStudio) {
                            if ($isStudio) {
                                $valueQuery->where('assign_parameters.value', '0')
                                    ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                    ->orWhere('assign_parameters.value', 'Studio')
                                    ->orWhere('assign_parameters.value', 'STUDIO');
                            } else {
                                $valueQuery->where('assign_parameters.value', $bedroomsValue)
                                    ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                                    ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue]);
                            }
                        })
                        ->distinct('propertys.id')
                        ->count('propertys.id');
                    
                    // Count vacation apartments with matching bedrooms
                    $vacationAptsCount = DB::table('vacation_apartments')
                        ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
                        ->where('propertys.property_classification', 4)
                        ->where('propertys.status', 1)
                        ->where('propertys.request_status', 'approved')
                        ->where('vacation_apartments.status', 1)
                        ->where('vacation_apartments.bedrooms', $bedroomsIntValue)
                        ->distinct('propertys.id')
                        ->count('propertys.id');
                    
                    if ($isStudio) {
                        // Check how many vacation apartments with Studio exist
                        $vacationAptsWithStudio = DB::table('vacation_apartments')
                            ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
                            ->where('propertys.property_classification', 4)
                            ->where('propertys.status', 1)
                            ->where('propertys.request_status', 'approved')
                            ->where('vacation_apartments.status', 1)
                            ->where('vacation_apartments.bedrooms', 0)
                            ->select('propertys.id', 'propertys.title', 'vacation_apartments.apartment_number', 'vacation_apartments.bedrooms')
                            ->get();
                        
                        \Log::info('🔍 Studio Filter Query Results', [
                            'total_count' => $total,
                            'offset' => $offset,
                            'limit' => $limit,
                            'bedrooms_value' => $bedroomsValue,
                            'assign_parameters_count' => $assignParamsCount,
                            'vacation_apartments_count' => $vacationAptsCount,
                            'vacation_apartments_with_studio' => $vacationAptsWithStudio->count(),
                            'vacation_apartments_details' => $vacationAptsWithStudio->map(function ($apt) {
                                return [
                                    'property_id' => $apt->id,
                                    'property_title' => $apt->title,
                                    'apartment_number' => $apt->apartment_number,
                                    'bedrooms' => $apt->bedrooms
                                ];
                            })->toArray(),
                            'sql_query' => $property->toSql(),
                            'sql_bindings' => $property->getBindings()
                        ]);
                    } else {
                        // Log for non-Studio bedroom filters
                        // Also check if property_type filter might be affecting results
                        $propertyType = $request->has('property_type') ? $request->property_type : null;
                        $propertyClassification = $request->has('property_classification') ? $request->property_classification : null;
                        
                        // Count vacation apartments with matching bedrooms AND property_type
                        $vacationAptsWithPropertyType = 0;
                        if ($propertyClassification == 4 || $propertyClassification == '4') {
                            $vacationAptsQuery = DB::table('vacation_apartments')
                                ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
                                ->where('propertys.property_classification', 4)
                                ->where('propertys.status', 1)
                                ->where('propertys.request_status', 'approved')
                                ->where('vacation_apartments.status', 1)
                                ->where('vacation_apartments.bedrooms', $bedroomsIntValue);
                            
                            if ($propertyType !== null && ($propertyType !== '' || $propertyType == 0)) {
                                $vacationAptsQuery->where('propertys.propery_type', $propertyType);
                            }
                            
                            $vacationAptsWithPropertyType = $vacationAptsQuery->distinct('propertys.id')->count('propertys.id');
                        }
                        
                        \Log::info('🔍 Bedroom Filter Query Results (Non-Studio)', [
                            'total_count' => $total,
                            'offset' => $offset,
                            'limit' => $limit,
                            'bedrooms_value' => $bedroomsValue,
                            'bedrooms_int_value' => $bedroomsIntValue,
                            'property_type' => $propertyType,
                            'property_classification' => $propertyClassification,
                            'assign_parameters_count' => $assignParamsCount,
                            'vacation_apartments_count' => $vacationAptsCount,
                            'vacation_apartments_with_property_type' => $vacationAptsWithPropertyType,
                            'note' => 'Properties should match if assign_parameters.value = "' . $bedroomsValue . '" OR vacation_apartments.bedrooms = ' . $bedroomsIntValue . '. If property_type is set, vacation apartments must also match property_type.',
                            'sql_query' => $property->toSql(),
                            'sql_bindings' => $property->getBindings()
                        ]);
                    }
                }
        
        $result = $property->orderBy('id', 'DESC')->skip($offset)->take($limit)->get();
        
                // Log bedroom filter results after query execution for ALL bedroom values
                if ($request->has('bedrooms') && $request->bedrooms !== null && $request->bedrooms !== '') {
                    $bedroomsValue = (string) $request->bedrooms;
                    $bedroomsValueLower = strtolower(trim($bedroomsValue));
                    $isStudio = ($bedroomsValue === '0' || $bedroomsValueLower === 'studio');
                    $bedroomsIntValue = (int) $bedroomsValue;
                    $propertyClassification = $request->has('property_classification') ? $request->property_classification : null;
                    $propertyType = $request->has('property_type') ? $request->property_type : null;
                    
                    // Log results for all bedroom filters (not just Studio)
                    \Log::info('🔍 Bedroom Filter - Query Results', [
                        'bedrooms_value' => $bedroomsValue,
                        'bedrooms_int_value' => $bedroomsIntValue,
                        'isStudio' => $isStudio,
                        'property_classification' => $propertyClassification,
                        'property_type' => $propertyType,
                        'total_count' => $total,
                        'returned_count' => $result->count(),
                        'returned_property_ids' => $result->pluck('id')->toArray(),
                        'returned_property_titles' => $result->pluck('title')->toArray(),
                        'note' => 'If returned_count is 0 but total_count > 0, there might be a pagination or query issue'
                    ]);
                    
                    if ($isStudio) {
                        $resultIds = $result->pluck('id')->toArray();
                        $resultTitles = $result->pluck('title')->toArray();
                        
                        // Check which properties matched via vacation_apartments vs assign_parameters
                        $matchedViaVacation = [];
                        $matchedViaAssignParams = [];
                        
                        foreach ($result as $prop) {
                            // Check if property has vacation_apartments with Studio
                            $hasVacationStudio = DB::table('vacation_apartments')
                                ->where('property_id', $prop->id)
                                ->where('status', 1)
                                ->where('bedrooms', 0)
                                ->exists();
                            
                            // Check if property has assign_parameters with Studio
                            $hasAssignParamsStudio = DB::table('assign_parameters')
                                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                                ->where(function ($query) use ($prop) {
                                    $query->where('assign_parameters.property_id', $prop->id)
                                        ->orWhere(function ($q) use ($prop) {
                                            $q->where('assign_parameters.modal_id', $prop->id)
                                                ->where(function ($typeQuery) {
                                                    $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                        ->orWhere('assign_parameters.modal_type', 'property');
                                                });
                                        });
                                })
                                ->where(function ($nameQuery) {
                                    $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                        ->orWhere('parameters.name', 'LIKE', '%bed%');
                                })
                                ->where(function ($valueQuery) {
                                    $valueQuery->where('assign_parameters.value', '0')
                                        ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                        ->orWhere('assign_parameters.value', 'Studio')
                                        ->orWhere('assign_parameters.value', 'STUDIO');
                                })
                                ->exists();
                            
                            if ($hasVacationStudio) {
                                $matchedViaVacation[] = $prop->id;
                            }
                            if ($hasAssignParamsStudio) {
                                $matchedViaAssignParams[] = $prop->id;
                            }
                        }
                        
                        \Log::info('🔍 Studio Filter Results After Query', [
                            'result_count' => $result->count(),
                            'total_count' => $total,
                            'property_ids' => $resultIds,
                            'property_titles' => $resultTitles,
                            'matched_via_vacation_apartments' => $matchedViaVacation,
                            'matched_via_assign_parameters' => $matchedViaAssignParams,
                            'note' => 'Properties can match via both methods, but vacation_apartments is for properties with NO assign_parameters data'
                        ]);
                    }
                }

        if (!$result->isEmpty()) {
            $property_details  = get_property_details($result, $current_user);

            // Check that Property Details exists or not
            if (isset($property_details) && collect($property_details)->isNotEmpty()) {
                // If apartment_id is provided, add selected apartment data to property details
                if ($request->has('apartment_id') && !empty($request->apartment_id)) {
                    $apartmentId = $request->apartment_id;
                    $property = $result->first();
                    
                    // Load vacation apartments if not already loaded
                    if (!$property->relationLoaded('vacationApartments')) {
                        $property->load('vacationApartments');
                    }
                    
                    // Find the selected apartment
                    $selectedApartment = $property->vacationApartments->firstWhere('id', $apartmentId);
                    
                    if ($selectedApartment) {
                        // Add selected apartment data to property details
                        foreach ($property_details as &$detail) {
                            $detail->selected_apartment_id = $apartmentId;
                            $detail->selected_apartment = $selectedApartment;
                            // Update price to show apartment price
                            $detail->price = $selectedApartment->price_per_night;
                            // Add apartment-specific fields
                            $detail->apartment_number = $selectedApartment->apartment_number;
                            $detail->bedrooms = $selectedApartment->bedrooms;
                            $detail->bathrooms = $selectedApartment->bathrooms;
                            $detail->max_guests = $selectedApartment->max_guests;
                        }
                    }
                }
                /**
                 * Check that id or slug id passed and get the similar properties data according to param passed
                 * If both passed then priority given to id param
                 * */
                if ((isset($id) && !empty($id))) {
                    $getSimilarPropertiesQueryData = Property::where('id', '!=', $id)
                        ->select(
                            'id',
                            'slug_id',
                            'category_id',
                            'title',
                            'added_by',
                            'address',
                            'city',
                            'country',
                            'state',
                            'propery_type',
                            'price',
                            'created_at',
                            'title_image',
                            'request_status',
                            'property_classification',
                            'check_in',
                            'check_out',
                            'agent_addons',
                            'corresponding_day',
                            'title_ar',
                            'description_ar',
                            'area_description_ar',
                            'area_description',
                            'company_employee_username',
                            'company_employee_email',
                            'company_employee_phone_number',
                            'company_employee_whatsappnumber'
                        )
                        ->with('certificates')
                        ->where(function ($query) {
                            return $query->where(['status' => 1, 'request_status' => 'approved']);
                        })->orderBy('id', 'desc')->limit(10)->get();
                    $getSimilarProperties = get_property_details($getSimilarPropertiesQueryData, $current_user);
                } else if ((isset($request->slug_id) && !empty($request->slug_id))) {
                    $getSimilarPropertiesQueryData = Property::where('slug_id', '!=', $request->slug_id)
                        ->select(
                            'id',
                            'slug_id',
                            'category_id',
                            'title',
                            'added_by',
                            'address',
                            'city',
                            'country',
                            'state',
                            'propery_type',
                            'price',
                            'created_at',
                            'title_image',
                            'request_status',
                            'property_classification',
                            'check_in',
                            'check_out',
                            'agent_addons',
                            'corresponding_day',
                            'title_ar',
                            'description_ar',
                            'area_description_ar',
                            'area_description',
                            'company_employee_username',
                            'company_employee_email',
                            'company_employee_phone_number',
                            'company_employee_whatsappnumber'
                        )
                        ->with('certificates')
                        ->where(function ($query) {
                            return $query->where(['status' => 1, 'request_status' => 'approved']);
                        })->orderBy('id', 'desc')->limit(10)->get();
                    $getSimilarProperties = get_property_details($getSimilarPropertiesQueryData, $current_user);
                }
            }


            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['similar_properties'] = $getSimilarProperties ?? array();
            $response['total'] = $total;
            $response['data'] = $property_details;
        } else {

            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return ($response);
    }
    //* END :: get_property   *//



    //* START :: post_property   *//
    public function post_property(Request $request)
    {
        // Override PHP upload limits for this endpoint
        ini_set('upload_max_filesize', '100M');
        ini_set('post_max_size', '100M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '512M');

        $validator = Validator::make($request->all(), [
            'title'             => 'required',
            'title_ar'          => 'nullable|string',
            'description'       => 'required',
            'description_ar'    => 'nullable|string',
            'area_description'  => 'nullable|string',
            'area_description_ar' => 'nullable|string',
            'company_employee_username' => 'nullable|string',
            'company_employee_email' => 'nullable|email',
            'company_employee_phone_number' => 'nullable|string',
            'company_employee_whatsappnumber' => 'nullable|string',
            'category_id'       => 'required',
            'property_type'     => 'required',
            'property_classification' => 'nullable|integer|between:1,5',
            'address'           => 'required',
            'title_image'       => 'required|file|mimes:jpeg,png,jpg',
            'three_d_image'     => 'nullable|mimes:jpg,jpeg,png,gif',
            'documents.*'       => 'nullable|mimes:pdf,doc,docx,txt',
            'policy_data'       => 'mimes:pdf,doc,docx,txt',
            'weekend_commission' => 'nullable|numeric|min:0|max:100',
            'identity_proof'    => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'national_id_passport' => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'utilities_bills'   => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'power_of_attorney' => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'availability_type' => 'nullable|integer|in:1,2|required_if:property_classification,4',
            'available_dates'   => 'nullable|json|required_if:property_classification,4',
            'refund_policy'     => 'nullable|in:flexible,non-refundable,non_refundable,both',
            'corresponding_day' => 'nullable|json',
            'check_in'          => 'nullable|string',
            'check_out'         => 'nullable|string',
            'available_rooms'   => 'nullable|integer|min:0',
            'agent_addons'      => 'nullable|json',
            'instant_booking'   => 'nullable|boolean',
            'non_refundable'        => 'nullable|boolean',
            'hotel_rooms'       => 'nullable|array',
            'hotel_rooms.*.room_type_id' => 'nullable',
            'hotel_rooms.*.custom_room_type' => 'nullable|string',
            'hotel_rooms.*.max_guests' => 'nullable|integer|min:0',
            'hotel_rooms.*.room_number' => 'required_with:hotel_rooms',
            'hotel_rooms.*.price_per_night' => 'required_with:hotel_rooms|numeric|min:0',
            'hotel_rooms.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.refund_policy' => 'nullable|in:flexible,non-refundable',
            'hotel_rooms.*.availability_type' => 'nullable|integer|in:1,2',
            'hotel_rooms.*.available_dates' => 'nullable|json',
            'hotel_rooms.*.weekend_commission' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.max_guests' => 'nullable|integer|min:1',
            'hotel_rooms.*.min_guests' => 'nullable|integer|min:1',
            'hotel_apartment_type_id' => 'nullable|exists:hotel_apartment_types,id',
            'rent_package' => 'nullable|in:basic,premium',
            'addons_packages'       => 'nullable|array',
            'addons_packages.*.name' => 'required_with:addons_packages',
            'addons_packages.*.description' => 'nullable|string',
            'addons_packages.*.room_type_id' => 'nullable|exists:hotel_room_types,id',
            'addons_packages.*.status' => 'nullable|in:active,inactive',
            'addons_packages.*.price' => 'nullable|numeric|min:0',
            'addons_packages.*.addon_values' => 'nullable|array',
            'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'required|exists:hotel_addon_fields,id',
            'addons_packages.*.addon_values.*.value' => 'required',
            'addons_packages.*.addon_values.*.static_price' => 'nullable',
            'price'             => ['required_unless:property_classification,5', 'nullable', 'numeric', 'min:0', 'max:9223372036854775807', function ($attribute, $value, $fail) {
                if ($value !== null && $value >= 9223372036854775807) {
                    $fail("The Price must not exceed more than 9223372036854775807.");
                }

                return true;
            }],
            'video_link' => ['nullable', 'url', function ($attribute, $value, $fail) {
                // Regular expression to validate YouTube URLs
                $youtubePattern = '/^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/';

                if (!preg_match($youtubePattern, $value)) {
                    return $fail("The Video Link must be a valid YouTube URL.");
                }

                // Transform youtu.be short URL to full YouTube URL for validation
                if (strpos($value, 'youtu.be') !== false) {
                    $value = 'https://www.youtube.com/watch?v=' . substr(parse_url($value, PHP_URL_PATH), 1);
                }

                // Get the headers of the URL
                $headers = @get_headers($value);

                // Check if the URL is accessible
                if (!$headers || strpos($headers[0], '200') === false) {
                    return $fail("The Video Link must be accessible.");
                }
            }],
            'certificates'      => 'nullable|array',
            'certificates.*.title' => 'required_with:certificates',
            'certificates.*.description' => 'nullable|string',
            'certificates.*.file' => 'required_with:certificates|file|mimes:jpeg,png,jpg,pdf,doc,docx',
            'revenue_user_name' => 'nullable|string',
            'revenue_phone_number' => 'nullable|string',
            'revenue_email' => 'nullable|email',
            'reservation_user_name' => 'nullable|string',
            'reservation_phone_number' => 'nullable|string',
            'reservation_email' => 'nullable|email',
            'hotel_vat' => 'nullable|string',
            'hotelAvailableRooms' => 'nullable|integer|min:0',
            'cancellation_period' => 'nullable|string|in:7_days,same_day_6pm',
        ], [], [
            'documents.*' => 'document :position',
            'addons_packages.*.name' => 'package name :position',
            'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'package addon field :position',
            'addons_packages.*.addon_values.*.value' => 'package addon value :position',
            'certificates.*.file' => 'certificate file :position',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            DB::beginTransaction();
            
            // Check if package limit check is required
            // Hotel (5) and Vacation Homes (4) do not require package limits
            $classification = $request->property_classification;
            if ($classification != 4 && $classification != 5) {
                HelperService::updatePackageLimit('property_list');
            }
            
            $loggedInUserId = Auth::user()->id;

            $slugData = (isset($request->slug_id) && !empty($request->slug_id)) ? $request->slug_id : $request->title;
            $saveProperty = new Property();
            $saveProperty->category_id = $request->category_id;
            $saveProperty->slug_id = generateUniqueSlug($slugData, 1);
            $saveProperty->title = $request->title;
            $saveProperty->title_ar = (isset($request->title_ar) && !empty($request->title_ar)) ? $request->title_ar : null;
            $saveProperty->description = $request->description;
            $saveProperty->description_ar = (isset($request->description_ar) && !empty($request->description_ar)) ? $request->description_ar : null;
            $saveProperty->area_description = (isset($request->area_description)) ? $request->area_description : null;
            $saveProperty->area_description_ar = (isset($request->area_description_ar) && !empty($request->area_description_ar)) ? $request->area_description_ar : null;
            $saveProperty->company_employee_username = (isset($request->company_employee_username)) ? $request->company_employee_username : null;
            $saveProperty->company_employee_email = (isset($request->company_employee_email)) ? $request->company_employee_email : null;
            $saveProperty->company_employee_phone_number = (isset($request->company_employee_phone_number)) ? $request->company_employee_phone_number : null;
            $saveProperty->company_employee_whatsappnumber = (isset($request->company_employee_whatsappnumber)) ? $request->company_employee_whatsappnumber : null;
            $saveProperty->address = $request->address;
            $saveProperty->client_address = (isset($request->client_address)) ? $request->client_address : '';
            $saveProperty->propery_type = $request->property_type;
            $saveProperty->price =  (isset($request->price)) ? $request->price : "";
            $saveProperty->country = (isset($request->country)) ? $request->country : '';
            $saveProperty->state = (isset($request->state)) ? $request->state : '';
            $saveProperty->city = (isset($request->city)) ? $request->city : '';
            $saveProperty->latitude = (isset($request->latitude)) ? $request->latitude : '';
            $saveProperty->longitude = (isset($request->longitude)) ? $request->longitude : '';
            $saveProperty->rentduration = (isset($request->rentduration)) ? $request->rentduration : '';
            $saveProperty->meta_title = (isset($request->meta_title)) ? $request->meta_title : '';
            $saveProperty->meta_description = (isset($request->meta_description)) ? $request->meta_description : '';
            $saveProperty->meta_keywords = (isset($request->meta_keywords)) ? $request->meta_keywords : '';
            $saveProperty->added_by = $loggedInUserId;
            $saveProperty->video_link = (isset($request->video_link)) ? $request->video_link : "";
            $saveProperty->package_id = $request->package_id;
            $saveProperty->post_type = 1;
            $saveProperty->property_classification = (isset($request->property_classification)) ? $request->property_classification : null;
            $saveProperty->rent_package = (isset($request->rent_package)) ? $request->rent_package : null;
            $saveProperty->weekend_commission = (isset($request->weekend_commission)) ? $request->weekend_commission : null;
            $saveProperty->corresponding_day = (isset($request->corresponding_day)) ? $request->corresponding_day : null;
            $saveProperty->check_in = (isset($request->check_in)) ? $request->check_in : null;
            $saveProperty->check_out = (isset($request->check_out)) ? $request->check_out : null;
            $saveProperty->agent_addons = (isset($request->agent_addons)) ? $request->agent_addons : null;
            $saveProperty->available_rooms = (isset($request->available_rooms)) ? $request->available_rooms : null;
            $saveProperty->revenue_user_name = (isset($request->revenue_user_name)) ? $request->revenue_user_name : null;
            $saveProperty->revenue_phone_number = (isset($request->revenue_phone_number)) ? $request->revenue_phone_number : null;
            $saveProperty->revenue_email = (isset($request->revenue_email)) ? $request->revenue_email : null;
            $saveProperty->reservation_user_name = (isset($request->reservation_user_name)) ? $request->reservation_user_name : null;
            $saveProperty->reservation_phone_number = (isset($request->reservation_phone_number)) ? $request->reservation_phone_number : null;
            $saveProperty->reservation_email = (isset($request->reservation_email)) ? $request->reservation_email : null;
            $saveProperty->instant_booking = (isset($request->instant_booking)) ? $request->instant_booking : null;
            $saveProperty->non_refundable = (isset($request->non_refundable)) ? $request->non_refundable : null;

            // Set vacation home specific fields if property classification is vacation_homes (4)
            if (isset($request->property_classification) && $request->property_classification == 4) {
                $saveProperty->availability_type = $request->availability_type;
                // Use has() or input() for FormData - more reliable than isset() for FormData fields
                if ($request->has('available_dates') || isset($request->available_dates)) {
                    // Parse available_dates if it's a JSON string (from FormData)
                    $availableDates = $request->input('available_dates') ?? $request->available_dates ?? [];
                    if (is_string($availableDates)) {
                        $availableDates = json_decode($availableDates, true) ?? [];
                    }
                    // Always set available_dates for vacation homes, even if empty array
                    $saveProperty->available_dates = $availableDates;
                }
            }

            // Set hotel specific fields if property classification is hotel (5)
            if (isset($request->property_classification) && $request->property_classification == 5) {
                $saveProperty->refund_policy = $request->refund_policy;
                $saveProperty->hotel_apartment_type_id = $request->hotel_apartment_type_id;
                $saveProperty->check_in = $request->check_in;
                $saveProperty->check_out = $request->check_out;
                $saveProperty->agent_addons = $request->agent_addons;
                if (isset($request->hotel_vat)) {
                    $saveProperty->hotel_vat = $request->hotel_vat;
                }
                if (isset($request->hotelAvailableRooms)) {
                    $saveProperty->hotel_available_rooms = $request->hotelAvailableRooms;
                }
                if (isset($request->cancellation_period)) {
                    $saveProperty->cancellation_period = $request->cancellation_period;
                }
            }

            $autoApproveStatus = $this->getAutoApproveStatus($loggedInUserId);
            if ($autoApproveStatus) {
                $saveProperty->request_status = 'approved';
            } else {
                $saveProperty->request_status = 'pending';
            }
            $saveProperty->status = 1;

            //Title Image
            if ($request->hasFile('title_image')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_TITLE_IMG_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('title_image');
                $imageName = microtime(true) . "." . $file->getClientOriginalExtension();
                $titleImageName = handleFileUpload($request, 'title_image', $destinationPath, $imageName);
                $saveProperty->title_image = $titleImageName;
            } else {
                $saveProperty->title_image  = '';
            }

            // Meta Image
            if ($request->hasFile('meta_image')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_SEO_IMG_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('meta_image');
                $imageName = microtime(true) . "." . $file->getClientOriginalExtension();
                $metaImageName = handleFileUpload($request, 'meta_image', $destinationPath, $imageName);
                $saveProperty->meta_image = $metaImageName;
            }

            // three_d_image
            if ($request->hasFile('three_d_image')) {
                $destinationPath = public_path('images') . config('global.3D_IMG_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('three_d_image');
                $imageName = microtime(true) . "." . $file->getClientOriginalExtension();
                $three_dImage = handleFileUpload($request, 'three_d_image', $destinationPath, $imageName);
                $saveProperty->three_d_image = $three_dImage;
            } else {
                $saveProperty->three_d_image  = '';
            }

            // Policy Data
            if ($request->hasFile('policy_data')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('policy_data');
                $imageName = microtime(true) . "." . $file->getClientOriginalExtension();
                $policyDataName = handleFileUpload($request, 'policy_data', $destinationPath, $imageName);
                $saveProperty->policy_data = $policyDataName;
            }

            // Identity Proof
            if ($request->hasFile('identity_proof')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_IDENTITY_PROOF_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('identity_proof');
                $imageName = microtime(true) . "." . $file->getClientOriginalExtension();
                $identityProofName = handleFileUpload($request, 'identity_proof', $destinationPath, $imageName);
                $saveProperty->identity_proof = $identityProofName;
            }

            // National ID/Passport
            if ($request->hasFile('national_id_passport')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_NATIONAL_ID_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('national_id_passport');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $nationalIdName = handleFileUpload($request, 'national_id_passport', $destinationPath, $fileName);
                $saveProperty->national_id_passport = $nationalIdName;
            }

            // Utilities Bills
            if ($request->hasFile('utilities_bills')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_UTILITIES_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('utilities_bills');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $utilitiesBillsName = handleFileUpload($request, 'utilities_bills', $destinationPath, $fileName);
                $saveProperty->utilities_bills = $utilitiesBillsName;
            }

            // Power of Attorney
            if ($request->hasFile('power_of_attorney')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_POA_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('power_of_attorney');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $poaName = handleFileUpload($request, 'power_of_attorney', $destinationPath, $fileName);
                $saveProperty->power_of_attorney = $poaName;
            }

            // Alternative ID
            if ($request->hasFile('alternative_id')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_ALTERNATIVE_ID_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('alternative_id');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $alternativeIdName = handleFileUpload($request, 'alternative_id', $destinationPath, $fileName);
                $saveProperty->alternative_id = $alternativeIdName;
            }

            // Ownership Contract (handle both ownership_contract and policy_data)
            if ($request->hasFile('ownership_contract')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_OWNERSHIP_CONTRACT_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('ownership_contract');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $ownershipContractName = handleFileUpload($request, 'ownership_contract', $destinationPath, $fileName);
                $saveProperty->ownership_contract = $ownershipContractName;
            } elseif ($request->hasFile('policy_data')) {
                // Fallback: if ownership_contract not provided, use policy_data
                $destinationPath = public_path('images') . config('global.PROPERTY_OWNERSHIP_CONTRACT_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('policy_data');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $ownershipContractName = handleFileUpload($request, 'policy_data', $destinationPath, $fileName);
                $saveProperty->ownership_contract = $ownershipContractName;
            }

            // Fact Sheet (for hotels)
            if ($request->hasFile('fact_sheet')) {
                $destinationPath = public_path('images') . config('global.PROPERTY_FACT_SHEET_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('fact_sheet');
                $fileName = microtime(true) . "." . $file->getClientOriginalExtension();
                $factSheetName = handleFileUpload($request, 'fact_sheet', $destinationPath, $fileName);
                $saveProperty->fact_sheet = $factSheetName;
            }

            $saveProperty->is_premium = isset($request->is_premium) ? ($request->is_premium == "true" ? 1 : 0) : 0;
            $saveProperty->save();

            $destinationPathForParam = public_path('images') . config('global.PARAMETER_IMAGE_PATH');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            if ($request->facilities) {
                foreach ($request->facilities as $key => $value) {
                    $facilities = new AssignedOutdoorFacilities();
                    $facilities->facility_id = $value['facility_id'];
                    $facilities->property_id = $saveProperty->id;
                    $facilities->distance = $value['distance'];
                    $facilities->save();
                }
            }
            if ($request->parameters) {
                foreach ($request->parameters as $key => $parameter) {
                    if (isset($parameter['value']) && !empty($parameter['value'])) {
                        $AssignParameters = new AssignParameters();
                        $AssignParameters->modal()->associate($saveProperty);
                        $AssignParameters->parameter_id = $parameter['parameter_id'];
                        if ($request->hasFile('parameters.' . $key . '.value')) {
                            $profile = $request->file('parameters.' . $key . '.value');
                            $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                            $profile->move($destinationPathForParam, $imageName);
                            $AssignParameters->value = $imageName;
                        } else if (filter_var($parameter['value'], FILTER_VALIDATE_URL)) {
                            $ch = curl_init($parameter['value']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $fileContents = curl_exec($ch);
                            curl_close($ch);
                            $filename = microtime(true) . basename($parameter['value']);
                            file_put_contents($destinationPathForParam . '/' . $filename, $fileContents);
                            $AssignParameters->value = $filename;
                        } else {
                            $AssignParameters->value = $parameter['value'];
                        }
                        $AssignParameters->save();
                    }
                }
            }

            /// START :: UPLOAD GALLERY IMAGE
            $FolderPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH');
            if (!is_dir($FolderPath)) {
                mkdir($FolderPath, 0777, true);
            }
            $destinationPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $saveProperty->id;
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            if ($request->hasfile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    $uploadedFileName = store_image($file, 'PROPERTY_GALLERY_IMG_PATH', $saveProperty->id);
                    $gallary_image = new PropertyImages();
                    $gallary_image->image = $uploadedFileName;
                    $gallary_image->propertys_id = $saveProperty->id;
                    $gallary_image->save();
                }
            }
            /// END :: UPLOAD GALLERY IMAGE


            /// START :: UPLOAD DOCUMENTS
            $FolderPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH');
            if (!is_dir($FolderPath)) {
                mkdir($FolderPath, 0777, true);
            }
            $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . "/" . $saveProperty->id;
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            if ($request->hasfile('documents')) {
                $documentsData = array();
                foreach ($request->file('documents') as $file) {
                    $type = $file->extension();
                    $uploadedFileName = store_image($file, 'PROPERTY_DOCUMENT_PATH', $saveProperty->id);
                    $documentsData[] = array(
                        'property_id' => $saveProperty->id,
                        'name' => $uploadedFileName,
                        'type' => $type
                    );
                }

                if (collect($documentsData)->isNotEmpty()) {
                    PropertiesDocument::insert($documentsData);
                }
            }
            /// END :: UPLOAD DOCUMENTS

            // START :: ADD CITY DATA
            if (isset($request->city) && !empty($request->city)) {
                CityImage::updateOrCreate(array('city' => $request->city));
            }
            // END :: ADD CITY DATA

            // START :: ADD HOTEL ROOMS
            if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->hotel_rooms) && !empty($request->hotel_rooms)) {
                try {
                    // Process hotel rooms
                    foreach ($request->hotel_rooms as $index => $room) {
                        try {
                            // Make sure both room_type_id and room_type have the same value
                            $roomTypeId = $room['room_type_id'] ?? null;
                            $customRoomType = $room['custom_room_type'] ?? null;

                            // Validate required fields
                            if ((!isset($roomTypeId) && !isset($customRoomType)) || !isset($room['price_per_night'])) {
                                \Log::error('Missing required fields for new room', [
                                    'room_data' => $room,
                                    'missing_fields' => [
                                        'room_type_id_or_custom' => !isset($roomTypeId) && !isset($customRoomType),
                                        'price_per_night' => !isset($room['price_per_night'])
                                    ]
                                ]);
                                continue;
                            }

                            $hotelRoom = HotelRoom::create([
                                'property_id' => $saveProperty->id,
                                'room_type_id' => $roomTypeId,
                                'custom_room_type' => $customRoomType,
                                'room_number' => $room['room_number'] ?? "0",
                                'price_per_night' => (float)$room['price_per_night'],
                                'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                'availability_type' => $room['availability_type'] ?? null,
                                'available_dates' => $room['available_dates'] ?? null,
                                'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : null,
                                'description' => $room['description'] ?? null,
                                'status' => $room['status'] ?? 1,
                                'max_guests' => isset($room['max_guests']) ? (int)$room['max_guests'] : null,
                                'min_guests' => isset($room['min_guests']) ? (int)$room['min_guests'] : null,
                                'instant_booking' => isset($room['instant_booking']) ? (bool)$room['instant_booking'] : true
                            ]);
                        } catch (\Exception $roomEx) {
                            throw $roomEx;
                        }
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            // END :: ADD HOTEL ROOMS

            // START :: ADD VACATION APARTMENTS
            if (isset($request->property_classification) && $request->property_classification == 4 && isset($request->vacation_apartments) && !empty($request->vacation_apartments)) {
                try {
                    // Process vacation apartments
                    foreach ($request->vacation_apartments as $index => $apartment) {
                        try {
                            $vacationApartment = \App\Models\VacationApartment::create([
                                'property_id' => $saveProperty->id,
                                'apartment_number' => $apartment['apartment_number'],
                                'price_per_night' => (float)$apartment['price_per_night'],
                                'discount_percentage' => isset($apartment['discount_percentage']) ? (float)$apartment['discount_percentage'] : 0,
                                'availability_type' => $apartment['availability_type'] ?? null,
                                'available_dates' => $apartment['available_dates'] ?? null,
                                'description' => $apartment['description'] ?? null,
                                'status' => $apartment['status'] ?? 1,
                                'max_guests' => isset($apartment['max_guests']) ? (int)$apartment['max_guests'] : null,
                                'bedrooms' => isset($apartment['bedrooms']) ? (int)$apartment['bedrooms'] : null,
                                'bathrooms' => isset($apartment['bathrooms']) ? (int)$apartment['bathrooms'] : null,
                                'quantity' => isset($apartment['quantity']) ? (int)$apartment['quantity'] : 1,
                            ]);
                        } catch (\Exception $apartmentEx) {
                            throw $apartmentEx;
                        }
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            // END :: ADD VACATION APARTMENTS

            // START :: ADD ADDONS PACKAGES
            // Check if property classification is 5 (hotel) either from request or saved property
            $isHotelProperty = (isset($request->property_classification) && $request->property_classification == 5) ||
                              ($saveProperty->getRawOriginal('property_classification') == 5);
            if ($isHotelProperty && isset($request->addons_packages) && !empty($request->addons_packages)) {
                try {
                    // Create destination path for hotel addon files
                    $addonFolderPath = public_path('images') . config('global.HOTEL_ADDON_PATH');
                    if (!is_dir($addonFolderPath)) {
                        mkdir($addonFolderPath, 0777, true);
                    }

                    // Process each package
                    foreach ($request->addons_packages as $packageIndex => $package) {
                        // Create the package
                        $addonsPackage = new AddonsPackage();
                        $addonsPackage->name = $package['name'];
                        $addonsPackage->room_type_id = $package['room_type_id'] ?? null;
                        $addonsPackage->description = $package['description'] ?? null;
                        $addonsPackage->property_id = $saveProperty->id;
                        $addonsPackage->status = $package['status'] ?? 'active';
                        $addonsPackage->price = isset($package['price']) ? $package['price'] : null;
                        $addonsPackage->save();

                        // Process addon values for this package
                        if (isset($package['addon_values']) && !empty($package['addon_values'])) {
                            foreach ($package['addon_values'] as $addonIndex => $addon) {
                                // Get the addon field to check its type
                                $addonField = HotelAddonField::where('id', $addon['hotel_addon_field_id'])->where('status', 'active')->first();

                                if (!$addonField) {
                                    continue; // Skip inactive or non-existent fields
                                }

                                $value = $addon['value'];

                                // Handle file uploads
                                if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                    $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                    $uploadedFileName = store_image($file, 'HOTEL_ADDON_PATH');
                                    $value = $uploadedFileName;
                                }
                                // Handle checkbox values (convert array to JSON)
                                else if ($addonField->field_type == 'checkbox' && is_array($value)) {
                                    $value = json_encode($value);
                                }
                                // Handle radio and dropdown values (validate against available options)
                                else if (in_array($addonField->field_type, ['radio', 'dropdown'])) {
                                    $validValue = HotelAddonFieldValue::where('hotel_addon_field_id', $addon['hotel_addon_field_id'])
                                        ->where('value', $value)
                                        ->exists();

                                    if (!$validValue) {
                                        continue; // Skip invalid values
                                    }
                                }

                                // Save the addon value with user-provided price fields
                                PropertyHotelAddonValue::create([
                                    'property_id' => $saveProperty->id,
                                    'hotel_addon_field_id' => $addon['hotel_addon_field_id'],
                                    'value' => $value,
                                    'static_price' => (isset($addon['static_price']) && is_numeric($addon['static_price'])) ? $addon['static_price'] : null,
                                    'multiply_price' => (isset($addon['multiply_price']) && is_numeric($addon['multiply_price'])) ? $addon['multiply_price'] : 1,
                                    'package_id' => $addonsPackage->id // Link to the package
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            // END :: ADD ADDONS PACKAGES

            // START :: ADD CERTIFICATES
            if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->certificates) && !empty($request->certificates)) {
                try {
                    // Create destination path for certificate files
                    $certificateFolderPath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH');
                    if (!is_dir($certificateFolderPath)) {
                        mkdir($certificateFolderPath, 0777, true);
                    }

                    // Process each certificate (use index-based file keys to match FormData)
                    foreach ($request->certificates as $certificateIndex => $certificate) {
                        $propertyCertificate = new PropertyCertificate();
                        $propertyCertificate->title = $certificate['title'];
                        $propertyCertificate->description = $certificate['description'] ?? null;
                        $propertyCertificate->property_id = $saveProperty->id;

                        // Handle file uploads via index-based key
                        if ($request->hasFile('certificates.' . $certificateIndex . '.file')) {
                            $file = $request->file('certificates.' . $certificateIndex . '.file');
                            $uploadedFileName = store_image($file, 'PROPERTY_CERTIFICATE_PATH');
                            $propertyCertificate->file = $uploadedFileName;
                        }

                        $propertyCertificate->save();
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            // END :: ADD CERTIFICATES

            $result = Property::with([
                'customer',
                'category:id,category,image,parameter_types',
                'assignfacilities.outdoorfacilities',
                'favourite',
                'parameters',
                'interested_users',
                'propertyImages',
                'propertiesDocuments',
                'hotelRooms.roomType',
                'addons_packages.addon_values.hotel_addon_field',
                'certificates',
                'vacationApartments'
            ])->where('id', $saveProperty->id)->get();
            $property_details = get_property_details($result);

            DB::commit();
            
            // Send contract emails after property creation
            try {
                // Reload property with customer relationship for email sending
                $propertyData = Property::where('id', $saveProperty->id)
                    ->select('id', 'title', 'request_status', 'added_by', 'city', 'state', 'country', 'rent_package', 'propery_type', 'property_classification')
                    ->with('customer:id,name,email,management_type,address')
                    ->first();
                
                if ($propertyData && !empty($propertyData->customer->email)) {
                    $propertyType = $propertyData->getRawOriginal('propery_type');
                    
                    // Case 1: Sell Property (propery_type == 0)
                    if ($propertyType == 0) {
                        $this->sendContractEmail($propertyData, "list_property_sell_contract");
                    }
                    // Case 2 & 3: Rent Property (propery_type == 1)
                    elseif ($propertyType == 1) {
                        // Check if rent_package is set
                        if (isset($propertyData->rent_package) && !empty($propertyData->rent_package)) {
                            // Case 2: Rent with Basic Package
                            if ($propertyData->rent_package == 'basic') {
                                $this->sendContractEmail($propertyData, "basic_package_renting");
                            }
                            // Case 3: Rent with Premium Package
                            elseif ($propertyData->rent_package == 'premium') {
                                $this->sendContractEmail($propertyData, "premium_package_renting");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the property creation
                Log::error("Failed to send contract email after property creation: " . $e->getMessage(), [
                    'property_id' => $saveProperty->id ?? null,
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            $response['error'] = false;
            $response['message'] = 'Property Post Successfully';
            $response['data'] = $property_details;
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            );
            return response()->json($response, 500);
        }
        return response()->json($response);
    }

    /**
     * Send contract email to property owner
     * 
     * @param \App\Models\Property $propertyData
     * @param string $contractType
     * @return void
     */
    private function sendContractEmail($propertyData, $contractType)
    {
        try {
            // Get Data of email type
            $contractEmailTypeData = HelperService::getEmailTemplatesTypes($contractType);

            // Email Template
            $contractTemplateData = system_setting($contractEmailTypeData['type']);
            $appName = env("APP_NAME") ?? "eBroker";

            // Get current date for contract
            $currentDate = now();
            $agreementYear = $currentDate->format('d F Y');
            $contractDate = $currentDate->format('F d, Y');

            // Generate LE ID (you can modify this logic as needed)
            $leId = 'LE-' . $propertyData->id; // This can be made dynamic if needed

            $variables = array(
                'app_name' => $appName,
                'partner_name' => $propertyData->customer->name,
                'partner_address' => $propertyData->customer->address ?? 'Address not provided',
                'agreement_year' => $agreementYear,
                'le_id' => $leId,
                'contract_date' => $contractDate,
            );

            if (empty($contractTemplateData)) {
                $contractTemplateData = "Your Partner Agreement with {app_name}";
            }
            $contractTemplate = HelperService::replaceEmailVariables($contractTemplateData, $variables);

            // For selling_or_renting_contract, list_property_sell_contract, and list_property_rent_contract, create email body with welcoming message
            // PDF will contain only the contract template (without welcoming message)
            if (in_array($contractType, ['selling_or_renting_contract', 'list_property_sell_contract', 'list_property_rent_contract', 'basic_package_renting', 'premium_package_renting'])) {
                // Email body content (shown in email only, not in PDF)
                $emailBodyContent = '<div style="font-family: Arial, sans-serif; line-height: 1.8; color: #333; padding: 30px; max-width: 600px; margin: 0 auto;">';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 20px;">Dear {partner_name},</p>';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 20px;">Thank you for choosing {app_name} to list your property.</p>';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 20px;">Please find the {app_name} Property Listing Contract attached as a PDF.</p>';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 20px;">Kindly review the document at your convenience. If you have any questions or need clarification, we are always happy to assist.</p>';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 10px;">Warm regards,</p>';
                $emailBodyContent .= '<p style="font-size: 16px; margin-bottom: 5px; font-weight: bold;">{app_name} Team</p>';
                $emailBodyContent .= '<p style="font-size: 14px; margin-bottom: 0;"><a href="https://www.ashome-eg.com" style="color: #007bff; text-decoration: none;">www.ashome-eg.com</a></p>';
                $emailBodyContent .= '</div>';
                
                // Replace variables in email body
                $emailBodyContent = HelperService::replaceEmailVariables($emailBodyContent, $variables);
                
                // Store original contract template for PDF (without welcoming message)
                $pdfTemplate = $contractTemplate;
                
                // Use email body content for email display
                $contractTemplate = $emailBodyContent;
            }

            // Update title for contract types
            $emailTitle = $contractEmailTypeData['title'];
            if (in_array($contractType, ['selling_or_renting_contract', 'list_property_sell_contract', 'list_property_rent_contract'])) {
                $emailTitle = 'Your As-home Property Listing Contract';
            }

            $contractData = array(
                'email_template' => $contractTemplate,
                'email' => $propertyData->customer->email,
                'title' => $emailTitle,
            );
            
            // For contract types, pass separate PDF template (contract only, no welcoming message)
            // This ensures the FULL contract template is included in the PDF attachment
            // The PDF will contain the complete contract content without any character limits
            if (in_array($contractType, ['selling_or_renting_contract', 'list_property_sell_contract', 'list_property_rent_contract', 'basic_package_renting', 'premium_package_renting']) && isset($pdfTemplate)) {
                $contractData['pdf_template'] = $pdfTemplate;
            }
            
            // Send email with PDF attachment containing the full contract
            // The PDF generation is configured to handle unlimited content length
            HelperService::sendMail($contractData);
        } catch (Exception $e) {
            Log::error("Something Went Wrong in Contract Email Sending for type {$contractType}: " . $e->getMessage());
        }
    }

    //* END :: post_property   *//
    //* START :: update_post_property   *//
    /// This api use for update and delete  property
    public function update_post_property(Request $request)
    {
        // Override PHP upload limits for this endpoint
        ini_set('upload_max_filesize', '100M');
        ini_set('post_max_size', '100M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '512M');

        $validator = Validator::make($request->all(), [
            'id'                    => 'required|exists:propertys,id',
            'action_type'           => 'required',
            'title'                 => 'nullable',
            'title_ar'              => 'nullable|string',
            'description'           => 'nullable',
            'description_ar'        => 'nullable|string',
            'area_description'      => 'nullable|string',
            'area_description_ar'   => 'nullable|string',
            'company_employee_username' => 'nullable|string',
            'company_employee_email' => 'nullable|email',
            'company_employee_phone_number' => 'nullable|string',
            'company_employee_whatsappnumber' => 'nullable|string',
            'category_id'           => 'nullable',
            'slug_id'               => 'nullable',
            'property_type'         => 'nullable',
            'address'               => 'nullable',
            'client_address'        => 'nullable',
            'country'               => 'nullable',
            'state'                 => 'nullable',
            'city'                  => 'nullable',
            'latitude'              => 'nullable',
            'longitude'             => 'nullable',
            'rentduration'          => 'nullable',
            'meta_title'            => 'nullable',
            'meta_description'      => 'nullable',
            'meta_keywords'         => 'nullable',
            'is_premium'            => 'nullable',
            'title_image'           => 'nullable|file|mimes:jpeg,png,jpg',
            'three_d_image'         => 'nullable|mimes:jpg,jpeg,png,gif',
            'remove_three_d_image'  => 'nullable|in:0,1',
            'documents.*'           => 'nullable|mimes:pdf,doc,docx,txt',
            'policy_data'           => 'nullable|mimes:pdf,doc,docx,txt',
            'weekend_commission'    => 'nullable|numeric|min:0|max:100',
            'identity_proof'        => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'national_id_passport'  => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'alternative_id'        => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'utilities_bills'       => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'power_of_attorney'     => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'ownership_contract'    => 'nullable|file|max:10240', // Accept all file types, max 10MB
            'property_classification' => 'nullable|integer|between:1,5',
            'availability_type' => 'nullable|integer|in:1,2|required_if:property_classification,4',
            'available_dates'   => 'nullable|json|required_if:property_classification,4',
            'refund_policy'     => 'nullable|in:flexible,non-refundable',
            'corresponding_day' => 'nullable|json',
            'check_in'          => 'nullable|string',
            'check_out'         => 'nullable|string',
            'agent_addons'      => 'nullable|json',
            'available_rooms'   => 'nullable|integer|min:0',
            'instant_booking'   => 'nullable|boolean',
            'non_refundable'        => 'nullable|boolean',
            'hotel_rooms'       => 'nullable|array',
            'hotel_rooms.*.id'  => 'nullable|exists:hotel_rooms,id',
            'hotel_rooms.*.room_type_id' => 'nullable',
            'hotel_rooms.*.custom_room_type' => 'nullable|string',
            'hotel_rooms.*.room_number' => 'required_with:hotel_rooms',
            'hotel_rooms.*.price_per_night' => 'required_with:hotel_rooms|numeric|min:0',
            'hotel_rooms.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.max_guests' => 'nullable|integer|min:0',
            'hotel_rooms.*.min_guests' => 'nullable|integer|min:1',
            'hotel_rooms.*.refund_policy' => 'nullable|in:flexible,non-refundable,non_refundable,both',
            'hotel_rooms.*.availability_type' => 'nullable|integer|in:1,2',
            'hotel_rooms.*.available_dates' => 'nullable|json',
            'hotel_rooms.*.weekend_commission' => 'nullable|numeric|min:0|max:100',
            'hotel_rooms.*.available_rooms' => 'nullable|integer|min:0',
            'hotel_rooms.*.base_guests' => 'nullable|integer|min:1',
            'hotel_rooms.*.guest_pricing_rules' => 'nullable',
            'hotel_apartment_type_id' => 'nullable|exists:hotel_apartment_types,id',
            'rent_package' => 'nullable|in:basic,premium',
            'cancellation_period' => 'nullable|string|in:3,5,7,14,same_day_6pm',
            'addons_packages'       => 'nullable|array',
            'addons_packages.*.id' => 'nullable|exists:addons_packages,id',
            'addons_packages.*.name' => 'required_with:addons_packages',
            'addons_packages.*.description' => 'nullable|string',
            'addons_packages.*.room_type_id' => 'nullable|exists:hotel_room_types,id',
            'addons_packages.*.status' => 'nullable|in:active,inactive',
            'addons_packages.*.price' => 'nullable|numeric|min:0',
            'addons_packages.*.addon_values' => 'required_with:addons_packages|array',
            'addons_packages.*.addon_values.*.id' => 'nullable|exists:property_hotel_addon_values,id',
            'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'required|exists:hotel_addon_fields,id',
            'addons_packages.*.addon_values.*.value' => 'required',
            'addons_packages.*.addon_values.*.static_price' => 'nullable',
            'deleted_room_ids'      => 'nullable|array',
            'deleted_room_ids.*'    => 'exists:hotel_rooms,id',
            'deleted_package_ids'   => 'nullable|array',
            'deleted_package_ids.*' => 'exists:addons_packages,id',
            'deleted_certificate_ids' => 'nullable|array',
            'deleted_certificate_ids.*' => 'exists:property_certificates,id',
            'price'                 => ['nullable', 'numeric', 'min:0', 'max:9223372036854775807', function ($attribute, $value, $fail) {
                if ($value !== null && $value >= 9223372036854775807) {
                    $fail("The Price must not exceed more than 9223372036854775807.");
                }
                return true;
            }],
            'video_link' => ['nullable', 'url', function ($attribute, $value, $fail) {
                // Regular expression to validate YouTube URLs
                $youtubePattern = '/^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/';

                if (!preg_match($youtubePattern, $value)) {
                    return $fail("The Video Link must be a valid YouTube URL.");
                }

                // Transform youtu.be short URL to full YouTube URL for validation
                if (strpos($value, 'youtu.be') !== false) {
                    $value = 'https://www.youtube.com/watch?v=' . substr(parse_url($value, PHP_URL_PATH), 1);
                }

                // Get the headers of the URL
                $headers = @get_headers($value);

                // Check if the URL is accessible
                if (!$headers || strpos($headers[0], '200') === false) {
                    return $fail("The Video Link must be accessible.");
                }
            }],
            'certificates'      => 'nullable|array',
            'certificates.*.id' => 'nullable|exists:property_certificates,id',
            'certificates.*.title' => 'required_with:certificates',
            'certificates.*.description' => 'nullable|string',
            'certificates.*.file' => 'required_with:certificates|file|mimes:jpeg,png,jpg,pdf,doc,docx',
            'revenue_user_name' => 'nullable|string',
            'revenue_phone_number' => 'nullable|string',
            'revenue_email' => 'nullable|email',
            'reservation_user_name' => 'nullable|string',
            'reservation_phone_number' => 'nullable|string',
            'reservation_email' => 'nullable|email',
            'hotel_vat' => 'nullable|string',
            'hotelAvailableRooms' => 'nullable|integer|min:0',
        ], [], [
            'documents.*' => 'document :position',
            'addons_packages.*.name' => 'package name :position',
            'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'package addon field :position',
            'addons_packages.*.addon_values.*.value' => 'package addon value :position',
            'certificates.*.file' => 'certificate file :position',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            DB::beginTransaction();
            $current_user = Auth::user()->id;
            $id = $request->id;
            $action_type = $request->action_type;
            $response = null; // Will be set in the code paths below
            if ($request->slug_id) {
                $property = Property::where('added_by', $current_user)->where('slug_id', $request->slug_id)->first();
                if (!$property) {
                    $property = Property::where('added_by', $current_user)->find($id);
                }
            } else {
                $property = Property::where('added_by', $current_user)->find($id);
            }
            if (($property)) {
                // 0: Update 1: Delete
                if ($action_type == 0) {

                    $destinationPath = public_path('images') . config('global.PROPERTY_TITLE_IMG_PATH');
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }

                    if (isset($request->category_id)) {
                        $property->category_id = $request->category_id;
                    }

                    if (isset($request->title)) {
                        $property->title = $request->title;
                        $slugData = (isset($request->slug_id) && !empty($request->slug_id)) ? $request->slug_id : $request->title;
                        $property->slug_id = generateUniqueSlug($slugData, 1, null, $id);
                    }

                    // Save title_ar (Arabic title)
                    if (isset($request->title_ar) || $request->has('title_ar')) {
                        $property->title_ar = $request->title_ar ?? null;
                    }

                    if (isset($request->slug_id) && !empty($request->slug_id)) {
                        $property->slug_id = generateUniqueSlug($request->slug_id, 1, null, $id);
                    }

                    if (isset($request->description)) {
                        $property->description = $request->description;
                    }

                    // Save description_ar (Arabic description)
                    if (isset($request->description_ar) || $request->has('description_ar')) {
                        $property->description_ar = $request->description_ar ?? null;
                    }

                    if (isset($request->area_description)) {
                        $property->area_description = $request->area_description;
                    }

                    // Save area_description_ar (Arabic area description)
                    if (isset($request->area_description_ar) || $request->has('area_description_ar')) {
                        $property->area_description_ar = $request->area_description_ar ?? null;
                    }

                    if (isset($request->company_employee_username)) {
                        $property->company_employee_username = $request->company_employee_username;
                    }

                    if (isset($request->company_employee_email)) {
                        $property->company_employee_email = $request->company_employee_email;
                    }

                    if (isset($request->company_employee_phone_number)) {
                        $property->company_employee_phone_number = $request->company_employee_phone_number;
                    }

                    if (isset($request->company_employee_whatsappnumber)) {
                        $property->company_employee_whatsappnumber = $request->company_employee_whatsappnumber;
                    }

                    if (isset($request->instant_booking)) {
                        $property->instant_booking = $request->instant_booking;
                    }

                    if (isset($request->non_refundable)) {
                        $property->non_refundable = $request->non_refundable;
                    }

                    if (isset($request->address)) {
                        $property->address = $request->address;
                    }

                    if (isset($request->client_address)) {
                        $property->client_address = $request->client_address;
                    }

                    if (isset($request->property_type)) {
                        $property->propery_type = $request->property_type;
                    }

                    if (isset($request->price)) {
                        $property->price = $request->price;
                    }
                    if (isset($request->country)) {
                        $property->country = $request->country;
                    }
                    if (isset($request->state)) {
                        $property->state = $request->state;
                    }
                    if (isset($request->city)) {
                        $property->city = $request->city;
                    }
                    if (isset($request->status)) {
                        $property->status = $request->status;
                    }
                    if (isset($request->latitude)) {
                        $property->latitude = $request->latitude;
                    }
                    if (isset($request->longitude)) {
                        $property->longitude = $request->longitude;
                    }
                    if (isset($request->rentduration)) {
                        $property->rentduration = $request->rentduration;
                    }

                    if (isset($request->video_link)) {
                        $property->video_link = $request->video_link;
                    }

                    if (isset($request->property_classification)) {
                        $property->property_classification = $request->property_classification;
                    }

                    if (isset($request->rent_package)) {
                        $property->rent_package = $request->rent_package;
                    }

                    if (isset($request->weekend_commission)) {
                        $property->weekend_commission = $request->weekend_commission;
                    }

                    if (isset($request->corresponding_day)) {
                        $property->corresponding_day = $request->corresponding_day;
                    }

                    if (isset($request->check_in)) {
                        $property->check_in = $request->check_in;
                    }

                    if (isset($request->check_out)) {
                        $property->check_out = $request->check_out;
                    }

                    if (isset($request->agent_addons)) {
                        $property->agent_addons = $request->agent_addons;
                    }

                    if (isset($request->available_rooms)) {
                        $property->available_rooms = $request->available_rooms;
                    }

                    if (isset($request->revenue_user_name)) {
                        $property->revenue_user_name = $request->revenue_user_name;
                    }

                    if (isset($request->revenue_phone_number)) {
                        $property->revenue_phone_number = $request->revenue_phone_number;
                    }

                    if (isset($request->revenue_email)) {
                        $property->revenue_email = $request->revenue_email;
                    }

                    if (isset($request->reservation_user_name)) {
                        $property->reservation_user_name = $request->reservation_user_name;
                    }

                    if (isset($request->reservation_phone_number)) {
                        $property->reservation_phone_number = $request->reservation_phone_number;
                    }

                    if (isset($request->reservation_email)) {
                        $property->reservation_email = $request->reservation_email;
                    }

                    if (isset($request->package_id)) {
                        $property->package_id = $request->package_id;
                    }

                    // Set vacation home specific fields if property classification is vacation_homes (4)
                    $propertyClassification = isset($request->property_classification) ? $request->property_classification : $property->property_classification;

                    if ($propertyClassification == 4) {
                        if (isset($request->availability_type)) {
                            $property->availability_type = $request->availability_type;
                        }
                        // Use has() or input() for FormData - more reliable than isset() for FormData fields
                        if ($request->has('available_dates') || isset($request->available_dates)) {
                            // Parse available_dates if it's a JSON string (from FormData)
                            $availableDates = $request->input('available_dates') ?? $request->available_dates ?? [];
                            if (is_string($availableDates)) {
                                $availableDates = json_decode($availableDates, true) ?? [];
                            }
                            // Always set available_dates for vacation homes, even if empty array
                            $property->available_dates = $availableDates;
                        }
                    }

                    // Save hotel_vat - always save if provided, regardless of property classification
                    // This ensures hotel_vat is saved even if property_classification is not in the request
                    // Check both isset and has() to handle FormData properly
                    if (isset($request->hotel_vat) || $request->has('hotel_vat')) {
                        $property->hotel_vat = $request->input('hotel_vat');
                    }

                    // Set hotel specific fields if property classification is hotel (5)
                    if ($propertyClassification == 5) {
                        if (isset($request->refund_policy)) {
                            $property->refund_policy = $request->refund_policy;
                        }
                        if (isset($request->hotel_apartment_type_id)) {
                            $property->hotel_apartment_type_id = $request->hotel_apartment_type_id;
                        }
                        if (isset($request->check_in)) {
                            $property->check_in = $request->check_in;
                        }
                        if (isset($request->check_out)) {
                            $property->check_out = $request->check_out;
                        }
                        if (isset($request->agent_addons)) {
                            $property->agent_addons = $request->agent_addons;
                        }
                        if (isset($request->hotelAvailableRooms)) {
                            $property->hotel_available_rooms = $request->hotelAvailableRooms;
                        }
                        if (isset($request->cancellation_period)) {
                            $property->cancellation_period = $request->cancellation_period;
                        }
                    }

                    $property->meta_title = $request->meta_title ?? null;
                    $property->meta_description = $request->meta_description ?? null;
                    $property->meta_keywords = $request->meta_keywords ?? null;
                    $property->is_premium = !empty($request->is_premium) && $request->is_premium == "true" ? 1 : 0;

                    // Note: request_status is handled later by selective approval logic (line 2430+)
                    // - If auto-approve ON → Set to 'approved' (line 2613-2615)
                    // - If auto-approve OFF and approval-required fields changed → Create edit request (line 2504)
                    // - If auto-approve OFF and only non-approval fields changed → Set to 'approved' (line 2580-2581)

                    if ($request->hasFile('title_image')) {
                        $imageName = microtime(true) . "." . $request->file('title_image')->getClientOriginalExtension();
                        $titleImageName = handleFileUpload($request, 'title_image', $destinationPath, $imageName, $property->title_image);
                        if ($titleImageName) {
                            $property->title_image = $titleImageName;
                        }
                    }

                    // if ($request->hasFile('meta_image')) {
                    //     if (!empty($property->meta_image)) {
                    //         $url = $property->meta_image;
                    //         $relativePath = parse_url($url, PHP_URL_PATH);
                    //         if (file_exists(public_path()  . $relativePath)) {
                    //             unlink(public_path()  . $relativePath);
                    //         }
                    //     }
                    //     $destinationPath = public_path('images') . config('global.PROPERTY_SEO_IMG_PATH');
                    //     if (!is_dir($destinationPath)) {
                    //         mkdir($destinationPath, 0777, true);
                    //     }
                    //     $profile = $request->file('meta_image');
                    //     $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                    //     $profile->move($destinationPath, $imageName);
                    //     $property->meta_image = $imageName;
                    // } else {
                    //     if (!empty($property->meta_image)) {
                    //         $url = $property->meta_image;
                    //         $relativePath = parse_url($url, PHP_URL_PATH);
                    //         if (file_exists(public_path()  . $relativePath)) {
                    //             unlink(public_path()  . $relativePath);
                    //         }
                    //     }
                    //     $property->meta_image = null;
                    // }



                    if ($request->hasFile('meta_image')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_SEO_IMG_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $imageName = microtime(true) . "." . $request->file('meta_image')->getClientOriginalExtension();
                        $metaImageName = handleFileUpload($request, 'meta_image', $destinationPath, $imageName, $property->meta_image);
                        if ($metaImageName) {
                            $property->meta_image = $metaImageName;
                        }
                    } else if ($request->has('meta_image') && empty($request->meta_image)) {
                        // Handle case where meta_image is being removed
                        if (!empty($property->meta_image)) {
                            $url = $property->meta_image;
                            $relativePath = parse_url($url, PHP_URL_PATH);
                            if (file_exists(public_path()  . $relativePath)) {
                                unlink(public_path()  . $relativePath);
                            }
                        }
                        $property->meta_image = null;
                    }

                    if ($request->has('remove_three_d_image') && $request->remove_three_d_image == 1) {
                        $threeDImage = $property->getRawOriginal('three_d_image');
                        if (!empty($threeDImage)) {
                            if (file_exists(public_path('images') . config('global.3D_IMG_PATH') .  $threeDImage)) {
                                unlink(public_path('images') . config('global.3D_IMG_PATH') . $threeDImage);
                            }
                        }
                        $property->three_d_image = null;
                    }

                    if ($request->hasFile('three_d_image')) {
                        $destinationPath1 = public_path('images') . config('global.3D_IMG_PATH');
                        if (!is_dir($destinationPath1)) {
                            mkdir($destinationPath1, 0777, true);
                        }
                        $imageName = microtime(true) . "." . $request->file('three_d_image')->getClientOriginalExtension();
                        $threeDImageName = handleFileUpload($request, 'three_d_image', $destinationPath1, $imageName, $property->three_d_image);
                        if ($threeDImageName) {
                            $property->three_d_image = $threeDImageName;
                        }
                    }

                    // Handle policy_data file
                    if ($request->hasFile('policy_data')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('policy_data')->getClientOriginalExtension();
                        $policyDataName = handleFileUpload($request, 'policy_data', $destinationPath, $fileName, $property->getRawOriginal('policy_data'));
                        if ($policyDataName) {
                            $property->policy_data = $policyDataName;
                        }
                    }

                    // Handle identity_proof file
                    if ($request->hasFile('identity_proof')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_IDENTITY_PROOF_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('identity_proof')->getClientOriginalExtension();
                        $identityProofName = handleFileUpload($request, 'identity_proof', $destinationPath, $fileName, $property->getRawOriginal('identity_proof'));
                        if ($identityProofName) {
                            $property->identity_proof = $identityProofName;
                        }
                    }

                    // Handle national_id_passport file
                    if ($request->hasFile('national_id_passport')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_NATIONAL_ID_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('national_id_passport')->getClientOriginalExtension();
                        $nationalIdName = handleFileUpload($request, 'national_id_passport', $destinationPath, $fileName, $property->getRawOriginal('national_id_passport'));
                        if ($nationalIdName) {
                            $property->national_id_passport = $nationalIdName;
                        }
                    }

                    // Handle alternative_id file
                    if ($request->hasFile('alternative_id')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_ALTERNATIVE_ID_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('alternative_id')->getClientOriginalExtension();
                        $alternativeIdName = handleFileUpload($request, 'alternative_id', $destinationPath, $fileName, $property->getRawOriginal('alternative_id'));
                        if ($alternativeIdName) {
                            $property->alternative_id = $alternativeIdName;
                        }
                    }

                    // Handle utilities_bills file
                    if ($request->hasFile('utilities_bills')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_UTILITIES_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('utilities_bills')->getClientOriginalExtension();
                        $utilitiesBillsName = handleFileUpload($request, 'utilities_bills', $destinationPath, $fileName, $property->getRawOriginal('utilities_bills'));
                        if ($utilitiesBillsName) {
                            $property->utilities_bills = $utilitiesBillsName;
                        }
                    }

                    // Handle power_of_attorney file
                    if ($request->hasFile('power_of_attorney')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_POA_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('power_of_attorney')->getClientOriginalExtension();
                        $poaName = handleFileUpload($request, 'power_of_attorney', $destinationPath, $fileName, $property->getRawOriginal('power_of_attorney'));
                        if ($poaName) {
                            $property->power_of_attorney = $poaName;
                        }
                    }

                    // Handle ownership_contract file (handle both ownership_contract and policy_data)
                    if ($request->hasFile('ownership_contract')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_OWNERSHIP_CONTRACT_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('ownership_contract')->getClientOriginalExtension();
                        $ownershipContractName = handleFileUpload($request, 'ownership_contract', $destinationPath, $fileName, $property->getRawOriginal('ownership_contract'));
                        if ($ownershipContractName) {
                            $property->ownership_contract = $ownershipContractName;
                        }
                    } elseif ($request->hasFile('policy_data')) {
                        // Fallback: if ownership_contract not provided, use policy_data
                        $destinationPath = public_path('images') . config('global.PROPERTY_OWNERSHIP_CONTRACT_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('policy_data')->getClientOriginalExtension();
                        $ownershipContractName = handleFileUpload($request, 'policy_data', $destinationPath, $fileName, $property->getRawOriginal('ownership_contract'));
                        if ($ownershipContractName) {
                            $property->ownership_contract = $ownershipContractName;
                        }
                    }

                    // Handle fact_sheet file
                    if ($request->hasFile('fact_sheet')) {
                        $destinationPath = public_path('images') . config('global.PROPERTY_FACT_SHEET_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        $fileName = microtime(true) . "." . $request->file('fact_sheet')->getClientOriginalExtension();
                        $factSheetName = handleFileUpload($request, 'fact_sheet', $destinationPath, $fileName, $property->getRawOriginal('fact_sheet'));
                        if ($factSheetName) {
                            $property->fact_sheet = $factSheetName;
                        }
                    }

                    if ($request->parameters) {
                        $destinationPathforparam = public_path('images') . config('global.PARAMETER_IMAGE_PATH');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        foreach ($request->parameters as $key => $parameter) {
                            $AssignParameters = AssignParameters::where('modal_id', $property->id)->where('parameter_id', $parameter['parameter_id'])->pluck('id');
                            if (count($AssignParameters)) {
                                $update_data = AssignParameters::find($AssignParameters[0]);
                                if ($request->hasFile('parameters.' . $key . '.value')) {
                                    $profile = $request->file('parameters.' . $key . '.value');
                                    $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                                    $profile->move($destinationPathforparam, $imageName);
                                    $update_data->value = $imageName;
                                } else if (filter_var($parameter['value'], FILTER_VALIDATE_URL)) {
                                    $ch = curl_init($parameter['value']);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    $fileContents = curl_exec($ch);
                                    curl_close($ch);
                                    $filename = microtime(true) . basename($parameter['value']);
                                    file_put_contents($destinationPathforparam . '/' . $filename, $fileContents);
                                    $update_data->value = $filename;
                                } else {
                                    $update_data->value = $parameter['value'];
                                }
                                $update_data->save();
                            } else {
                                $AssignParameters = new AssignParameters();
                                $AssignParameters->modal()->associate($property);
                                $AssignParameters->parameter_id = $parameter['parameter_id'];
                                if ($request->hasFile('parameters.' . $key . '.value')) {
                                    $profile = $request->file('parameters.' . $key . '.value');
                                    $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                                    $profile->move($destinationPathforparam, $imageName);
                                    $AssignParameters->value = $imageName;
                                } else if (filter_var($parameter['value'], FILTER_VALIDATE_URL)) {
                                    $ch = curl_init($parameter['value']);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    $fileContents = curl_exec($ch);
                                    curl_close($ch);
                                    $filename = microtime(true) . basename($parameter['value']);
                                    file_put_contents($destinationPathforparam . '/' . $filename, $fileContents);
                                    $AssignParameters->value = $filename;
                                } else {
                                    $AssignParameters->value = $parameter['value'];
                                }
                                $AssignParameters->save();
                            }
                        }
                    }

                    // Store facilities data for later processing (after approval check)
                    $facilitiesData = $request->facilities ?? null;
                    $prop_id = $request->id ?? null;
                    if (!$prop_id && $request->slug_id) {
                        $prop = Property::where('slug_id', $request->slug_id)->first();
                        $prop_id = $prop ? $prop->id : null;
                    }

                    // Check for pending edit request first
                    $pendingRequest = \App\Models\PropertyEditRequest::where('property_id', $property->id)
                        ->where('status', 'pending')
                        ->first();
                    
                    if ($pendingRequest) {
                        DB::rollBack();
                        return response()->json([
                            'error' => true,
                            'message' => 'This property has a pending edit request. Please wait for approval before making additional changes.',
                        ], 400);
                    }

                    // START :: UPDATE VACATION APARTMENTS (Moved before owner check to ensure it runs for owner edits)
                    // Parse property_classification to integer (FormData sends as string)
                    $propertyClassification = isset($request->property_classification) 
                        ? (int)$request->property_classification 
                        : ($property->property_classification ?? null);
                    
                    // Capture original vacation apartments BEFORE update (for edit request)
                    $originalVacationApartments = [];
                    // Initialize updatedVacationApartments array to store updated data
                    $updatedVacationApartments = [];

                    if ($propertyClassification == 4) {
                        $originalVacationApartments = \App\Models\VacationApartment::where('property_id', $property->id)
                            ->get()
                            ->map(function($apt) {
                                return [
                                    'id' => $apt->id,
                                    'apartment_number' => $apt->apartment_number,
                                    'quantity' => $apt->quantity,
                                    'price_per_night' => $apt->price_per_night,
                                    'discount_percentage' => $apt->discount_percentage,
                                    'max_guests' => $apt->max_guests,
                                    'bedrooms' => $apt->bedrooms,
                                    'bathrooms' => $apt->bathrooms,
                                    'status' => $apt->status,
                                    'availability_type' => $apt->availability_type,
                                    'available_dates' => $apt->available_dates,
                                    'description' => $apt->description,
                                ];
                            })
                            ->toArray();
                    }
                    
                    \Log::info('Checking vacation apartments update (before owner check)', [
                        'property_classification' => $propertyClassification,
                        'action_type' => $action_type,
                        'property_id' => $property->id ?? null,
                        'original_apartments_count' => count($originalVacationApartments)
                    ]);
                    
                    if ($propertyClassification == 4) {
                        // Use input() to properly parse nested FormData arrays
                        $vacationApartments = $request->input('vacation_apartments');
                        
                        // Add debug logging
                        \Log::info('Vacation apartments update started (before owner check)', [
                            'property_id' => $property->id,
                            'property_classification' => $propertyClassification,
                            'vacation_apartments_received' => $vacationApartments !== null ? 'yes' : 'no',
                            'vacation_apartments_is_array' => is_array($vacationApartments),
                            'vacation_apartments_count' => is_array($vacationApartments) ? count($vacationApartments) : 0
                        ]);
                        
                        // If vacation_apartments is explicitly set (even if empty array), process it
                        if ($vacationApartments !== null) {
                            // Get all existing apartment IDs for this property
                            $existingApartmentIds = \App\Models\VacationApartment::where('property_id', $property->id)
                                ->pluck('id')
                                ->toArray();
                            
                            // Track which apartments are being updated/created
                            $processedApartmentIds = [];
                            
                            // Only process apartments if the array is not empty
                            if (!empty($vacationApartments) && is_array($vacationApartments)) {
                                \Log::info('Processing vacation apartments (before owner check)', [
                                    'total_apartments' => count($vacationApartments),
                                    'property_id' => $property->id
                                ]);

                                try {
                                    foreach ($vacationApartments as $index => $apartment) {
                                        // Parse available_dates if it's a JSON string
                                        $availableDates = $apartment['available_dates'] ?? null;
                                        if (is_string($availableDates)) {
                                            $availableDates = json_decode($availableDates, true);
                                            if (json_last_error() !== JSON_ERROR_NONE) {
                                                \Log::warning('Failed to parse available_dates JSON', [
                                                    'apartment_index' => $index,
                                                    'available_dates_string' => $apartment['available_dates'] ?? null,
                                                    'json_error' => json_last_error_msg()
                                                ]);
                                                $availableDates = [];
                                            }
                                        }
                                        
                                        // Check if this is an existing apartment (has id and id exists for this property)
                                        if (isset($apartment['id']) && !empty($apartment['id']) && $apartment['id'] !== null && $apartment['id'] !== '') {
                                            $apartmentId = (int)$apartment['id'];
                                            $vacationApartment = \App\Models\VacationApartment::find($apartmentId);
                                            
                                            // Only update if the apartment exists and belongs to this property
                                            if ($vacationApartment && $vacationApartment->property_id == $property->id) {
                                                if ($availableDates === null) {
                                                    $availableDates = $vacationApartment->available_dates;
                                                }

                                                $vacationApartment->apartment_number = $apartment['apartment_number'] ?? $vacationApartment->apartment_number;
                                                $vacationApartment->price_per_night = isset($apartment['price_per_night']) ? (float)$apartment['price_per_night'] : $vacationApartment->price_per_night;
                                                $vacationApartment->discount_percentage = isset($apartment['discount_percentage']) ? (float)$apartment['discount_percentage'] : $vacationApartment->discount_percentage;
                                                $vacationApartment->description = $apartment['description'] ?? $vacationApartment->description;
                                                $vacationApartment->status = isset($apartment['status']) ? (bool)$apartment['status'] : $vacationApartment->status;
                                                $vacationApartment->availability_type = isset($apartment['availability_type']) ? (int)$apartment['availability_type'] : $vacationApartment->getRawOriginal('availability_type');
                                                $vacationApartment->available_dates = $availableDates;
                                                $vacationApartment->max_guests = isset($apartment['max_guests']) && $apartment['max_guests'] !== '' ? (int)$apartment['max_guests'] : $vacationApartment->max_guests;
                                                $vacationApartment->bedrooms = isset($apartment['bedrooms']) && $apartment['bedrooms'] !== '' ? (int)$apartment['bedrooms'] : $vacationApartment->bedrooms;
                                                $vacationApartment->bathrooms = isset($apartment['bathrooms']) && $apartment['bathrooms'] !== '' ? (int)$apartment['bathrooms'] : $vacationApartment->bathrooms;
                                                
                                                // Parse quantity - ensure it's at least 1
                                                $oldQuantity = $vacationApartment->quantity;
                                                $quantity = isset($apartment['quantity']) ? (int)$apartment['quantity'] : $vacationApartment->quantity;
                                                
                                                \Log::info('Updating apartment quantity (owner edit)', [
                                                    'apartment_id' => $apartmentId,
                                                    'property_id' => $property->id,
                                                    'old_quantity' => $oldQuantity,
                                                    'new_quantity' => $quantity,
                                                    'quantity_from_request' => $apartment['quantity'] ?? 'not_set',
                                                    'quantity_type' => gettype($apartment['quantity'] ?? null),
                                                    'is_owner_edit' => true
                                                ]);
                                                
                                                if ($quantity < 1) {
                                                    throw new \Exception("Apartment quantity must be at least 1 for apartment ID: {$apartmentId}");
                                                }
                                                
                                                // Update quantity
                                                $vacationApartment->quantity = $quantity;
                                                
                                                // Save to database (for owner edits, vacation apartments are saved directly)
                                                $saved = $vacationApartment->save();
                                                
                                                // Verify the save was successful
                                                $vacationApartment->refresh(); // Reload from database
                                                
                                                \Log::info('Apartment quantity update result (owner edit)', [
                                                    'apartment_id' => $apartmentId,
                                                    'save_result' => $saved,
                                                    'quantity_after_save' => $vacationApartment->quantity,
                                                    'quantity_expected' => $quantity,
                                                    'save_successful' => ($vacationApartment->quantity == $quantity)
                                                ]);
                                                
                                                if ($vacationApartment->quantity != $quantity) {
                                                    \Log::error('Quantity was not saved correctly (owner edit)', [
                                                        'apartment_id' => $apartmentId,
                                                        'expected' => $quantity,
                                                        'actual' => $vacationApartment->quantity
                                                    ]);
                                                    throw new \Exception("Failed to save quantity for apartment ID: {$apartmentId}. Expected: {$quantity}, Got: {$vacationApartment->quantity}");
                                                }
                                                
                                                $processedApartmentIds[] = $apartmentId;
                                            } else {
                                                \Log::warning('Apartment not found or does not belong to property', [
                                                    'apartment_id' => $apartmentId,
                                                    'property_id' => $property->id,
                                                    'apartment_exists' => $vacationApartment ? 'yes' : 'no',
                                                    'apartment_property_id' => $vacationApartment ? $vacationApartment->property_id : null
                                                ]);
                                            }
                                        } else {
                                            // Create new apartment
                                            if ($availableDates === null) {
                                                $availableDates = [];
                                            }

                                            // Validate required fields for new apartment
                                            if (empty($apartment['apartment_number'])) {
                                                throw new \Exception("Apartment number is required for new apartment at index: {$index}");
                                            }
                                            
                                            $quantity = isset($apartment['quantity']) ? (int)$apartment['quantity'] : 1;
                                            if ($quantity < 1) {
                                                throw new \Exception("Apartment quantity must be at least 1 for new apartment at index: {$index}");
                                            }

                                            $newApartment = \App\Models\VacationApartment::create([
                                                'property_id' => $property->id,
                                                'apartment_number' => $apartment['apartment_number'] ?? '',
                                                'price_per_night' => (float)($apartment['price_per_night'] ?? 0),
                                                'discount_percentage' => isset($apartment['discount_percentage']) ? (float)$apartment['discount_percentage'] : 0,
                                                'availability_type' => isset($apartment['availability_type']) ? (int)$apartment['availability_type'] : 1,
                                                'available_dates' => $availableDates,
                                                'description' => $apartment['description'] ?? null,
                                                'status' => isset($apartment['status']) ? (bool)$apartment['status'] : 1,
                                                'max_guests' => isset($apartment['max_guests']) && $apartment['max_guests'] !== '' ? (int)$apartment['max_guests'] : null,
                                                'bedrooms' => isset($apartment['bedrooms']) && $apartment['bedrooms'] !== '' ? (int)$apartment['bedrooms'] : null,
                                                'bathrooms' => isset($apartment['bathrooms']) && $apartment['bathrooms'] !== '' ? (int)$apartment['bathrooms'] : null,
                                                'quantity' => $quantity,
                                            ]);
                                            
                                            $processedApartmentIds[] = $newApartment->id;
                                        }
                                    }
                                } catch (\Exception $apartmentEx) {
                                    \Log::error('Error processing vacation apartment (before owner check)', [
                                        'error' => $apartmentEx->getMessage(),
                                        'file' => $apartmentEx->getFile(),
                                        'line' => $apartmentEx->getLine(),
                                        'trace' => $apartmentEx->getTraceAsString(),
                                        'apartment_index' => $index ?? 'unknown',
                                        'apartment_data' => $apartment ?? null,
                                        'property_id' => $property->id
                                    ]);
                                    throw $apartmentEx; // Re-throw to be caught by outer catch
                                }
                            }
                            
                            // Delete any apartments that were not included in the request
                            $apartmentsToDelete = array_diff($existingApartmentIds, $processedApartmentIds);
                            if (!empty($apartmentsToDelete)) {
                                \Log::info('Deleting vacation apartments not in request (before owner check)', [
                                    'apartment_ids' => $apartmentsToDelete,
                                    'property_id' => $property->id
                                ]);
                                \App\Models\VacationApartment::where('property_id', $property->id)
                                    ->whereIn('id', $apartmentsToDelete)
                                    ->delete();
                            }
                        } else {
                            // If vacation_apartments is explicitly null, delete all existing apartments
                            \App\Models\VacationApartment::where('property_id', $property->id)->delete();
                        }
                        
                        // Populate updatedVacationApartments for use in edit request
                        $updatedVacationApartments = \App\Models\VacationApartment::where('property_id', $property->id)->get()->toArray();
                    }
                    // END :: UPDATE VACATION APARTMENTS (Before owner check)

                    // Check if this is an owner edit (not admin)
                    // 0 means admin, non-zero means owner/customer
                    $isOwnerEdit = $property->added_by != 0;
                    
                    // Check auto-approve setting for edited listings
                    $autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
                    
                    // If auto-approve is ON, bypass all approval checks and save directly
                    // If auto-approve is OFF, use selective approval (only approval-required fields need approval)
                    if ($isOwnerEdit && !$autoApproveEdited) {
                        // Auto-approve is OFF - check if approval-required fields have changed
                        // Define approval-required fields
                        $approvalRequiredFields = [
                            'title', 'title_ar',
                            'description', 'description_ar',
                            'area_description', 'area_description_ar',
                            'title_image', 'gallery_images', 'three_d_image', 'og_images',
                            'hotel_rooms' // Only description field within rooms
                        ];
                        
                        // Check if approval-required fields have changed
                        $hasApprovalRequiredChanges = $this->hasApprovalRequiredChanges(
                            $property, 
                            $request, 
                            $approvalRequiredFields,
                            $propertyClassification
                        );
                        
                        // Only require approval if approval-required fields changed
                        if ($hasApprovalRequiredChanges) {
                        // Owner edits require admin approval when auto-approve is OFF - save as pending request
                        // Get the current state of the property (with all modifications)
                        $editedData = $property->getAttributes();
                        
                        // Remove timestamps and IDs from edited data
                        unset($editedData['created_at'], $editedData['updated_at'], $editedData['id']);
                        
                        // Include vacation apartments in edited data if property is vacation home
                        if ($propertyClassification == 4 && !empty($updatedVacationApartments)) {
                            $editedData['vacation_apartments'] = $updatedVacationApartments;
                            \Log::info('Including vacation apartments in edited_data', [
                                'property_id' => $property->id,
                                'apartments_count' => count($updatedVacationApartments),
                                'quantities' => collect($updatedVacationApartments)->pluck('quantity', 'id')->toArray()
                            ]);
                        }
                        
                        // Get original property data (before modifications - we need to reload from DB)
                        // But first, save current property state temporarily
                        $tempPropertyData = $property->getAttributes();
                        
                        // Reload property from database to get original state
                        $property->refresh();
                        $originalData = $property->getAttributes();
                        unset($originalData['created_at'], $originalData['updated_at'], $originalData['id']);
                        
                        // Restore the modified property data for the response
                        foreach ($tempPropertyData as $key => $value) {
                            $property->$key = $value;
                        }
                        
                        // Include original vacation apartments in original data
                        if ($propertyClassification == 4 && !empty($originalVacationApartments)) {
                            $originalData['vacation_apartments'] = $originalVacationApartments;
                            \Log::info('Including original vacation apartments in original_data', [
                                'property_id' => $property->id,
                                'apartments_count' => count($originalVacationApartments),
                                'quantities' => collect($originalVacationApartments)->pluck('quantity', 'id')->toArray()
                            ]);
                        }
                        
                        // Include facilities in edited data if provided (so they're applied when approved)
                        if ($facilitiesData && is_array($facilitiesData)) {
                            $editedData['facilities'] = $facilitiesData;
                            \Log::info('Including facilities in edited_data for approval', [
                                'property_id' => $property->id,
                                'facilities_count' => count($facilitiesData)
                            ]);
                        }
                        
                        // Use PropertyEditRequestService to save the edit request
                        $editRequestService = new \App\Services\PropertyEditRequestService();
                        $editRequest = $editRequestService->saveEditRequest($property, $editedData, $current_user, $originalData);
                        
                        // Note: Vacation apartments are saved directly (before this check)
                        // Gallery images, documents, and other related data changes
                        // are NOT processed here because the property itself is not being updated.
                        // These will be handled when the admin approves the edit request.
                        
                        // Reload property with updated vacation apartments to return fresh data
                        // (Vacation apartments were already saved before the owner check)
                        $update_property = Property::with([
                            'customer',
                            'category:id,category,image',
                            'assignfacilities.outdoorfacilities',
                            'favourite',
                            'parameters',
                            'interested_users',
                            'propertyImages',
                            'propertiesDocuments',
                            'hotelRooms.roomType',
                            'addons_packages.addon_values.hotel_addon_field',
                            'certificates',
                            'vacationApartments'
                        ])->where('id', $request->id)->get();
                        
                        // Verify vacation apartments were updated
                        $vacationApartments = \App\Models\VacationApartment::where('property_id', $request->id)->get();
                        \Log::info('Vacation apartments after owner edit (before response)', [
                            'property_id' => $request->id,
                            'apartments_count' => $vacationApartments->count(),
                            'apartment_quantities' => $vacationApartments->pluck('quantity', 'id')->toArray()
                        ]);
                        
                        $property_details = get_property_details($update_property, $current_user, true);
                        
                        // Log vacation apartments in response
                        if (isset($property_details) && is_array($property_details) && !empty($property_details)) {
                            $firstProperty = $property_details[0] ?? null;
                            if ($firstProperty && isset($firstProperty['vacation_apartments'])) {
                                \Log::info('Vacation apartments in owner edit response', [
                                    'property_id' => $request->id,
                                    'apartments_count' => count($firstProperty['vacation_apartments'] ?? []),
                                    'apartment_quantities' => collect($firstProperty['vacation_apartments'] ?? [])->pluck('quantity', 'id')->toArray()
                                ]);
                            }
                        }
                        
                        DB::commit();
                        
                        // Return property_details directly (same structure as admin edit) so frontend can use it
                        return response()->json([
                            'error' => false,
                            'message' => 'Property edit request submitted successfully. Changes will be applied after admin approval.',
                            'data' => $property_details,  // Return directly, not nested
                            'edit_request' => [
                                'edit_request_id' => $editRequest->id,
                                'status' => 'pending_approval'
                            ]
                        ]);
                        } else {
                            // Auto-approve is OFF but only non-approval fields changed - save directly
                            // Save facilities immediately since they don't require approval
                            if ($facilitiesData && is_array($facilitiesData) && $prop_id) {
                                AssignedOutdoorFacilities::where('property_id', $prop_id)->delete();
                                foreach ($facilitiesData as $key => $value) {
                                    $facilities = new AssignedOutdoorFacilities();
                                    $facilities->facility_id = $value['facility_id'];
                                    $facilities->property_id = $prop_id;
                                    $facilities->distance = $value['distance'];
                                    $facilities->save();
                                }
                                \Log::info('Facilities saved directly (no approval required)', [
                                    'property_id' => $prop_id,
                                    'facilities_count' => count($facilitiesData)
                                ]);
                            }
                            // Ensure property status is approved when saving directly (non-approval fields only)
                            if ($property->request_status !== 'approved') {
                                $property->request_status = 'approved';
                            }
                            $property->save();
                            \Log::info('Property saved directly (only non-approval fields changed)', [
                                'property_id' => $property->id,
                                'request_status' => 'approved'
                            ]);
                        }
                    } else {
                        // Admin edit OR owner edit with auto-approve enabled - save directly (no approval needed)
                        // When auto-approve is ON, ALL edits bypass approval regardless of which fields changed
                        // Save the property with all updates including Arabic fields and hotel_vat
                        
                        // Save facilities immediately since no approval is needed
                        if ($facilitiesData && is_array($facilitiesData) && $prop_id) {
                            AssignedOutdoorFacilities::where('property_id', $prop_id)->delete();
                            foreach ($facilitiesData as $key => $value) {
                                $facilities = new AssignedOutdoorFacilities();
                                $facilities->facility_id = $value['facility_id'];
                                $facilities->property_id = $prop_id;
                                $facilities->distance = $value['distance'];
                                $facilities->save();
                            }
                            \Log::info('Facilities saved directly (auto-approve ON or admin edit)', [
                                'property_id' => $prop_id,
                                'facilities_count' => count($facilitiesData),
                                'auto_approve' => $autoApproveEdited,
                                'is_admin' => !$isOwnerEdit
                            ]);
                        }
                        
                        // Set request_status to approved for:
                        // 1. Admin edits (always approved)
                        // 2. Owner edits with auto-approve ON (bypass approval)
                        if (!$isOwnerEdit) {
                            // Admin edit - always approved
                            $property->request_status = 'approved';
                            \Log::info('Admin edit - property approved', [
                                'property_id' => $property->id
                            ]);
                        } else if ($autoApproveEdited) {
                            // Owner edit with auto-approve ON - bypass approval
                            $property->request_status = 'approved';
                            \Log::info('Owner edit auto-approved (bypassing edit request)', [
                                'property_id' => $property->id,
                                'auto_approve_setting' => true
                            ]);
                        }
                        
                        $property->save();
                    }
                    $update_property = Property::with([
                        'customer',
                        'category:id,category,image',
                        'assignfacilities.outdoorfacilities',
                        'favourite',
                        'parameters',
                        'interested_users',
                        'propertyImages',
                        'propertiesDocuments',
                        'hotelRooms.roomType',
                        'addons_packages.addon_values.hotel_addon_field',
                        'certificates',
                        'vacationApartments'
                    ])->where('id', $request->id)->get();

                    /// START :: UPLOAD GALLERY IMAGE
                    $FolderPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH');
                    if (!is_dir($FolderPath)) {
                        mkdir($FolderPath, 0777, true);
                    }
                    $destinationPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $property->id;
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }
                    if ($request->remove_gallery_images) {
                        foreach ($request->remove_gallery_images as $key => $value) {
                            $gallary_images = PropertyImages::find($value);
                            if (file_exists(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $gallary_images->propertys_id . '/' . $gallary_images->image)) {
                                unlink(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $gallary_images->propertys_id . '/' . $gallary_images->image);
                            }
                            $gallary_images->delete();
                        }
                    }
                    if ($request->hasfile('gallery_images')) {
                        foreach ($request->file('gallery_images') as $file) {
                            $name = microtime(true) . '.' . $file->extension();
                            $file->move($destinationPath, $name);
                            PropertyImages::create([
                                'image' => $name,
                                'propertys_id' => $property->id,
                            ]);
                        }
                    }
                    /// END :: UPLOAD GALLERY IMAGE



                    /// START :: UPLOAD DOCUMENTS
                    if ($request->remove_documents) {
                        foreach ($request->remove_documents as $key => $value) {
                            $document = PropertiesDocument::find($value);
                            if (file_exists(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $document->propertys_id . '/' . $document->name)) {
                                unlink(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $document->propertys_id . '/' . $document->name);
                            }
                            $document->delete();
                        }
                    }

                    $FolderPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH');
                    if (!is_dir($FolderPath)) {
                        mkdir($FolderPath, 0777, true);
                    }
                    $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . "/" . $property->id;
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }
                    if ($request->hasfile('documents')) {
                        $documentsData = array();
                        foreach ($request->file('documents') as $file) {
                            // $name = time() . rand(1, 100) . '.' . $file->extension();
                            // $type = $file->extension();
                            // $file->move($destinationPath, $name);

                            $type = $file->extension();
                            $name = microtime(true) . '.' . $type;
                            $file->move($destinationPath, $name);

                            $documentsData[] = array(
                                'property_id' => $property->id,
                                'name' => $name,
                                'type' => $type
                            );
                        }

                        if (collect($documentsData)->isNotEmpty()) {
                            PropertiesDocument::insert($documentsData);
                        }
                    }
                    /// END :: UPLOAD DOCUMENTS

                    // START :: ADD CITY DATA
                    if (isset($request->city) && !empty($request->city)) {
                        CityImage::updateOrCreate(array('city' => $request->city));
                    }
                    // END :: ADD CITY DATA



                    // START :: UPDATE HOTEL ROOMS
                    if (isset($request->property_classification) && $request->property_classification == 5) {
                        // Update hotel specific fields
                        if (isset($request->refund_policy)) {
                            $property->refund_policy = $request->refund_policy;
                        }

                        // Update hotel apartment type
                        if (isset($request->hotel_apartment_type_id)) {
                            $property->hotel_apartment_type_id = $request->hotel_apartment_type_id;
                        }

                        // Handle hotel rooms
                        if (isset($request->hotel_rooms) && !empty($request->hotel_rooms)) {
                            \Log::info('Processing hotel rooms', [
                                'total_rooms' => count($request->hotel_rooms),
                                'property_id' => $property->id,
                                'all_rooms_data' => $request->hotel_rooms
                            ]);
                            
                            // Process added/updated rooms
                            foreach ($request->hotel_rooms as $index => $room) {
                                \Log::info('Processing room', [
                                    'index' => $index,
                                    'room_id' => $room['id'] ?? 'new',
                                    'room_type_id' => $room['room_type_id'] ?? 'missing',
                                    'has_id' => isset($room['id']) && !empty($room['id']) && $room['id'] !== null,
                                    'room_data' => $room
                                ]);
                                
                                if (isset($room['id']) && !empty($room['id']) && $room['id'] !== null) {
                                    // Update existing room
                                    $hotelRoom = HotelRoom::find($room['id']);
                                    if ($hotelRoom && $hotelRoom->property_id == $property->id) {
                                        // Parse available_dates if it's a JSON string
                                        $availableDates = $room['available_dates'] ?? $hotelRoom->available_dates;
                                        if (is_string($availableDates)) {
                                            $availableDates = json_decode($availableDates, true) ?? $hotelRoom->available_dates;
                                        }
                                        
                                        $hotelRoom->room_type_id = $room['room_type_id'];
                                        $hotelRoom->custom_room_type = $room['custom_room_type'] ?? null;
                                        $hotelRoom->room_number = $room['room_number'];
                                        $hotelRoom->price_per_night = (float)$room['price_per_night'];
                                        $hotelRoom->discount_percentage = isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : $hotelRoom->discount_percentage;
                                        $hotelRoom->nonrefundable_percentage = isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : $hotelRoom->nonrefundable_percentage;
                                        $hotelRoom->refund_policy = $room['refund_policy'] ?? $hotelRoom->refund_policy;
                                        $hotelRoom->availability_type = isset($room['availability_type']) ? (int)$room['availability_type'] : $hotelRoom->availability_type;
                                        $hotelRoom->available_dates = $availableDates;
                                        $hotelRoom->weekend_commission = isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : $hotelRoom->weekend_commission;
                                        $hotelRoom->description = $room['description'] ?? $hotelRoom->description;
                                        $hotelRoom->status = isset($room['status']) ? (bool)$room['status'] : $hotelRoom->status;
                                        $hotelRoom->max_guests = isset($room['max_guests']) ? (int)$room['max_guests'] : $hotelRoom->max_guests;
                                        $hotelRoom->min_guests = isset($room['min_guests']) ? (int)$room['min_guests'] : $hotelRoom->min_guests;
                                        if (isset($room['base_guests'])) {
                                            $hotelRoom->base_guests = (int)$room['base_guests'];
                                        }
                                        if (array_key_exists('guest_pricing_rules', $room) || array_key_exists('guestPricingRules', $room)) {
                                            $rawRules = $room['guest_pricing_rules'] ?? $room['guestPricingRules'] ?? null;
                                            if ($rawRules === '' || $rawRules === null) {
                                                $hotelRoom->guest_pricing_rules = [];
                                            } else {
                                                if (is_string($rawRules)) {
                                                    $decodedRules = json_decode($rawRules, true);
                                                    if (json_last_error() === JSON_ERROR_NONE) {
                                                        $rawRules = $decodedRules;
                                                    }
                                                }
                                                $hotelRoom->guest_pricing_rules = $rawRules;
                                            }
                                        }
                                        if (isset($room['available_rooms'])) {
                                            $hotelRoom->available_rooms = (int)$room['available_rooms'];
                                        }
                                        if (isset($room['instant_booking'])) {
                                            $hotelRoom->instant_booking = (bool)$room['instant_booking'];
                                        }
                                        $hotelRoom->save();
                                    }
                                } else {
                                    // Create new room
                                    \Log::info('Creating new room', [
                                        'room_data' => $room,
                                        'property_id' => $property->id
                                    ]);
                                    
                                    // Validate required fields for new room
                                    if ((!isset($room['room_type_id']) && !isset($room['custom_room_type'])) || !isset($room['price_per_night'])) {
                                        \Log::error('Missing required fields for new room', [
                                            'room_data' => $room,
                                            'missing_fields' => [
                                                'room_type_id_or_custom' => !isset($room['room_type_id']) && !isset($room['custom_room_type']),
                                                'price_per_night' => !isset($room['price_per_night'])
                                            ]
                                        ]);
                                        continue; // Skip this room if required fields are missing
                                    }
                                    
                                    try {
                                        // Parse available_dates if it's a JSON string
                                        $availableDates = $room['available_dates'] ?? [];
                                        if (is_string($availableDates)) {
                                            $availableDates = json_decode($availableDates, true) ?? [];
                                        }
                                        $guestPricingRules = $room['guest_pricing_rules'] ?? $room['guestPricingRules'] ?? [];
                                        if (is_string($guestPricingRules)) {
                                            $guestPricingRules = json_decode($guestPricingRules, true) ?? [];
                                        }
                                        
                                        $newRoom = HotelRoom::create([
                                            'property_id' => $property->id,
                                            'room_type_id' => $room['room_type_id'] ?? null,
                                            'custom_room_type' => $room['custom_room_type'] ?? null,
                                            'room_number' => $room['room_number'] ?? "0",
                                            'price_per_night' => (float)$room['price_per_night'],
                                            'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                            'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                            'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                            'availability_type' => isset($room['availability_type']) ? (int)$room['availability_type'] : 1,
                                            'available_dates' => $availableDates,
                                            'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : 0,
                                            'description' => $room['description'] ?? "",
                                            'status' => isset($room['status']) ? (bool)$room['status'] : true,
                                            'max_guests' => isset($room['max_guests']) ? (int)$room['max_guests'] : 1,
                                            'min_guests' => isset($room['min_guests']) ? (int)$room['min_guests'] : 1,
                                            'base_guests' => isset($room['base_guests']) ? (int)$room['base_guests'] : 2,
                                            'guest_pricing_rules' => $guestPricingRules,
                                            'available_rooms' => isset($room['available_rooms']) ? (int)$room['available_rooms'] : 1,
                                            'instant_booking' => isset($room['instant_booking']) ? (bool)$room['instant_booking'] : true
                                        ]);
                                        \Log::info('New hotel room created successfully', [
                                            'room_id' => $newRoom->id, 
                                            'property_id' => $property->id,
                                            'room_type_id' => $newRoom->room_type_id,
                                            'price_per_night' => $newRoom->price_per_night
                                        ]);
                                    } catch (\Exception $e) {
                                        \Log::error('Failed to create new hotel room', [
                                            'error' => $e->getMessage(),
                                            'room_data' => $room,
                                            'property_id' => $property->id,
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }
                            }

                            // Process deleted rooms
                            if (isset($request->deleted_room_ids) && !empty($request->deleted_room_ids)) {
                                foreach ($request->deleted_room_ids as $roomId) {
                                    $roomToDelete = HotelRoom::where('id', $roomId)
                                        ->where('property_id', $property->id)
                                        ->first();

                                    if ($roomToDelete) {
                                        $roomToDelete->delete();
                                    }
                                }
                            }
                            
                            \Log::info('Hotel rooms processing completed', [
                                'total_processed' => count($request->hotel_rooms),
                                'property_id' => $property->id
                            ]);
                        }
                    }
                    // END :: UPDATE HOTEL ROOMS

                    // NOTE: Vacation apartments update has been moved BEFORE the owner check (line ~2470)
                    // to ensure quantity updates are saved for owner edits as well

                    // START :: UPDATE HOTEL ADDON VALUES AND PACKAGES
                    // Always process addon packages if they are provided in the request
                    // Create destination path for hotel addon files
                    $addonFolderPath = public_path('images') . config('global.HOTEL_ADDON_PATH');
                    if (!is_dir($addonFolderPath)) {
                        mkdir($addonFolderPath, 0777, true);
                    }

                    // We only use packages now - individual addon values are not supported

                    if (isset($request->addons_packages) && !empty($request->addons_packages)) {
                            // If no package IDs are provided, treat this as a full replacement:
                            // delete all existing packages (and their addon values) and recreate from request
                            $providedPackageIds = collect($request->addons_packages)
                                ->pluck('id')
                                ->filter()
                                ->values();

                            if ($providedPackageIds->isEmpty()) {
                                $existingPackages = AddonsPackage::where('property_id', $property->id)->get();
                                foreach ($existingPackages as $existingPackage) {
                                    PropertyHotelAddonValue::where('package_id', $existingPackage->id)->delete();
                                    $existingPackage->delete();
                                }
                            }

                            foreach ($request->addons_packages as $packageIndex => $package) {
                                // Check if this is an update or new package
                                if (isset($package['id']) && !empty($package['id'])) {
                                    // Update existing package
                                    $addonsPackage = AddonsPackage::find($package['id']);
                                    if ($addonsPackage && $addonsPackage->property_id == $property->id) {
                                        $addonsPackage->name = $package['name'];
                                        $addonsPackage->room_type_id = $package['room_type_id'] ?? $addonsPackage->room_type_id;
                                        $addonsPackage->description = $package['description'] ?? $addonsPackage->description;
                                        $addonsPackage->status = $package['status'] ?? $addonsPackage->status;
                                        $addonsPackage->price = isset($package['price']) ? $package['price'] : $addonsPackage->price;
                                        $addonsPackage->save();

                                        // Handle addon values for this package
                                        if (isset($package['addon_values']) && !empty($package['addon_values'])) {
                                            foreach ($package['addon_values'] as $addonIndex => $addon) {
                                                // Check if this is an update or new addon value
                                                if (isset($addon['id']) && !empty($addon['id'])) {
                                                    // Update existing addon value
                                                    $addonValue = PropertyHotelAddonValue::find($addon['id']);
                                                    if ($addonValue && $addonValue->property_id == $property->id && $addonValue->package_id == $addonsPackage->id) {
                                                        // Get the addon field to check its type
                                                        $addonField = HotelAddonField::where('id', $addon['hotel_addon_field_id'])->where('status', 'active')->first();
                                                        if (!$addonField) continue;

                                                        $value = $addon['value'];

                                                        // Handle file uploads
                                                        if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                                            // Delete old file if exists
                                                            if (!empty($addonValue->value) && file_exists($addonFolderPath . '/' . $addonValue->value)) {
                                                                unlink($addonFolderPath . '/' . $addonValue->value);
                                                            }

                                                            $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                                            $fileName = microtime(true) . '.' . $file->extension();
                                                            $file->move($addonFolderPath, $fileName);
                                                            $value = $fileName;
                                                        }
                                                        // Handle checkbox values
                                                        else if ($addonField->field_type == 'checkbox' && is_array($value)) {
                                                            $value = json_encode($value);
                                                        }

                                                        // Update the addon value
                                                        $addonValue->value = $value;
                                                        $addonValue->static_price = isset($addon['static_price']) ? $addon['static_price'] : null;
                                                        $addonValue->multiply_price = isset($addon['multiply_price']) ? $addon['multiply_price'] : 1;
                                                        $addonValue->save();
                                                    }
                                                } else {
                                                    // Create new addon value
                                                    $addonField = HotelAddonField::where('id', $addon['hotel_addon_field_id'])->where('status', 'active')->first();
                                                    if (!$addonField) continue;

                                                    $value = $addon['value'];

                                                    // Handle file uploads
                                                    if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                                        $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                                        $fileName = microtime(true) . '.' . $file->extension();
                                                        $file->move($addonFolderPath, $fileName);
                                                        $value = $fileName;
                                                    }
                                                    // Handle checkbox values
                                                    else if ($addonField->field_type == 'checkbox' && is_array($value)) {
                                                        $value = json_encode($value);
                                                    }

                                                    // Create the addon value
                                                    PropertyHotelAddonValue::create([
                                                        'property_id' => $property->id,
                                                        'hotel_addon_field_id' => $addon['hotel_addon_field_id'],
                                                        'value' => $value,
                                                        'static_price' => (isset($addon['static_price']) && is_numeric($addon['static_price'])) ? $addon['static_price'] : null,
                                                        'multiply_price' => (isset($addon['multiply_price']) && is_numeric($addon['multiply_price'])) ? $addon['multiply_price'] : 1,
                                                        'package_id' => $addonsPackage->id
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    // Create new package
                                    $addonsPackage = new AddonsPackage();
                                    $addonsPackage->name = $package['name'];
                                    $addonsPackage->room_type_id = $package['room_type_id'] ?? null;
                                    $addonsPackage->description = $package['description'] ?? null;
                                    $addonsPackage->property_id = $property->id;
                                    $addonsPackage->status = $package['status'] ?? 'active';
                                    $addonsPackage->price = isset($package['price']) ? $package['price'] : null;
                                    $addonsPackage->save();

                                    // Handle addon values for this package
                                    if (isset($package['addon_values']) && !empty($package['addon_values'])) {
                                        foreach ($package['addon_values'] as $addonIndex => $addon) {
                                            $addonField = HotelAddonField::where('id', $addon['hotel_addon_field_id'])->where('status', 'active')->first();
                                            if (!$addonField) continue;

                                            $value = $addon['value'];

                                            // Handle file uploads
                                            if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                                $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                                $fileName = microtime(true) . '.' . $file->extension();
                                                $file->move($addonFolderPath, $fileName);
                                                $value = $fileName;
                                            }
                                            // Handle checkbox values
                                            else if ($addonField->field_type == 'checkbox' && is_array($value)) {
                                                $value = json_encode($value);
                                            }

                                            // Create the addon value
                                            PropertyHotelAddonValue::create([
                                                'property_id' => $property->id,
                                                'hotel_addon_field_id' => $addon['hotel_addon_field_id'],
                                                'value' => $value,
                                                'static_price' => (isset($addon['static_price']) && is_numeric($addon['static_price'])) ? $addon['static_price'] : null,
                                                'multiply_price' => (isset($addon['multiply_price']) && is_numeric($addon['multiply_price'])) ? $addon['multiply_price'] : 1,
                                                'package_id' => $addonsPackage->id
                                            ]);
                                        }
                                    }
                                }
                            }
                    }

                    // Handle deleted packages even if no new packages were sent
                    if (isset($request->deleted_package_ids) && !empty($request->deleted_package_ids)) {
                        foreach ($request->deleted_package_ids as $packageId) {
                            $packageToDelete = AddonsPackage::where('id', $packageId)
                                ->where('property_id', $property->id)
                                ->first();

                            if ($packageToDelete) {
                                // Delete associated addon values first
                                PropertyHotelAddonValue::where('package_id', $packageToDelete->id)->delete();

                                // Then delete the package
                                $packageToDelete->delete();
                            }
                        }
                    }
                    // END :: UPDATE HOTEL ADDON VALUES AND PACKAGES

                    // START :: ADD CERTIFICATES
                    if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->certificates) && !empty($request->certificates)) {
                        try {
                            // Create destination path for certificate files
                            $certificateFolderPath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH');
                            if (!is_dir($certificateFolderPath)) {
                                mkdir($certificateFolderPath, 0777, true);
                            }

                            // Process each certificate
                            foreach ($request->certificates as $certificate) {
                                // Create the certificate
                                $propertyCertificate = new PropertyCertificate();
                                $propertyCertificate->title = $certificate['title'];
                                $propertyCertificate->description = $certificate['description'] ?? null;
                                $propertyCertificate->property_id = $property->id;

                                // Handle file uploads
                                if ($request->hasFile('certificates.' . $certificate['title'] . '.file')) {
                                    $file = $request->file('certificates.' . $certificate['title'] . '.file');
                                    $fileName = microtime(true) . '.' . $file->extension();
                                    $file->move($certificateFolderPath, $fileName);
                                    $propertyCertificate->file = $fileName;
                                }

                                $propertyCertificate->save();
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                    // END :: ADD CERTIFICATES

                    // START :: UPDATE CERTIFICATES
                    if (isset($request->property_classification) && $request->property_classification == 5) {
                        // Delete existing certificates if requested
                        if (isset($request->deleted_certificate_ids) && !empty($request->deleted_certificate_ids)) {
                            foreach ($request->deleted_certificate_ids as $certificateId) {
                                $certificateToDelete = PropertyCertificate::where('id', $certificateId)
                                    ->where('property_id', $property->id)
                                    ->first();

                                if ($certificateToDelete) {
                                    // Delete file if exists
                                    $certificateFile = $certificateToDelete->getRawOriginal('file');
                                    if (!empty($certificateFile)) {
                                        $filePath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH') . $certificateFile;
                                        if (file_exists($filePath)) {
                                            unlink($filePath);
                                        }
                                    }
                                    $certificateToDelete->delete();
                                }
                            }
                        }

                        // Update or create certificates
                        if (isset($request->certificates) && !empty($request->certificates)) {
                            // Create destination path for certificate files
                            $certificateFolderPath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH');
                            if (!is_dir($certificateFolderPath)) {
                                mkdir($certificateFolderPath, 0777, true);
                            }

                            foreach ($request->certificates as $certificateIndex => $certificate) {
                                // Check if this is an update or new certificate
                                if (isset($certificate['id']) && !empty($certificate['id'])) {
                                    // Update existing certificate
                                    $existingCertificate = PropertyCertificate::find($certificate['id']);
                                    if ($existingCertificate && $existingCertificate->property_id == $property->id) {
                                        $existingCertificate->title = $certificate['title'];
                                        $existingCertificate->description = $certificate['description'] ?? $existingCertificate->description;

                                        // Handle file uploads
                                        if ($request->hasFile('certificates.' . $certificateIndex . '.file')) {
                                            // Delete old file if exists
                                            $oldFile = $existingCertificate->getRawOriginal('file');
                                            if (!empty($oldFile)) {
                                                $filePath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH') . $oldFile;
                                                if (file_exists($filePath)) {
                                                    unlink($filePath);
                                                }
                                            }

                                            $file = $request->file('certificates.' . $certificateIndex . '.file');
                                            $fileName = microtime(true) . '.' . $file->extension();
                                            $file->move($certificateFolderPath, $fileName);
                                            $existingCertificate->file = $fileName;
                                        }

                                        $existingCertificate->save();
                                    }
                                } else {
                                    // Create new certificate
                                    $propertyCertificate = new PropertyCertificate();
                                    $propertyCertificate->title = $certificate['title'];
                                    $propertyCertificate->description = $certificate['description'] ?? null;
                                    $propertyCertificate->property_id = $property->id;

                                    // Handle file uploads
                                    if ($request->hasFile('certificates.' . $certificateIndex . '.file')) {
                                        $file = $request->file('certificates.' . $certificateIndex . '.file');
                                        $fileName = microtime(true) . '.' . $file->extension();
                                        $file->move($certificateFolderPath, $fileName);
                                        $propertyCertificate->file = $fileName;
                                    }

                                    $propertyCertificate->save();
                                }
                            }
                        }
                    }
                    // END :: UPDATE CERTIFICATES

                    // Reload property with all relationships after all updates (especially vacation apartments)
                    $update_property = Property::with([
                        'customer',
                        'category:id,category,image',
                        'assignfacilities.outdoorfacilities',
                        'favourite',
                        'parameters',
                        'interested_users',
                        'propertyImages',
                        'propertiesDocuments',
                        'hotelRooms.roomType',
                        'addons_packages.addon_values.hotel_addon_field',
                        'certificates',
                        'vacationApartments'
                    ])->where('id', $request->id)->get();

                    $current_user = Auth::user()->id;
                    
                    // Verify vacation apartments quantities before getting property details
                    $vacationApartments = \App\Models\VacationApartment::where('property_id', $request->id)->get();
                    \Log::info('Vacation apartments from database after update', [
                        'property_id' => $request->id,
                        'apartments_count' => $vacationApartments->count(),
                        'apartment_quantities' => $vacationApartments->pluck('quantity', 'id')->toArray()
                    ]);
                    
                    $property_details = get_property_details($update_property, $current_user, true);
                    
                    // Verify vacation apartments quantities are correct in response
                    if (isset($property_details) && is_array($property_details) && !empty($property_details)) {
                        $propertyData = is_array($property_details[0] ?? null) ? $property_details[0] : $property_details;
                        if (isset($propertyData['vacation_apartments'])) {
                            \Log::info('Vacation apartments in response', [
                                'property_id' => $request->id,
                                'apartments_count' => count($propertyData['vacation_apartments'] ?? []),
                                'apartment_quantities' => collect($propertyData['vacation_apartments'] ?? [])->pluck('quantity', 'id')->toArray()
                            ]);
                        }
                    }
                    
                    \Log::info('Setting success response after vacation apartments update', [
                        'property_id' => $request->id,
                        'action_type' => $action_type,
                        'has_property_details' => !empty($property_details)
                    ]);
                    
                    $response = [
                        'error' => false,
                        'message' => 'Property Update Successfully',
                        'data' => $property_details
                    ];
                    
                    \Log::info('Response set successfully', [
                        'response_error' => $response['error'] ?? 'not_set',
                        'response_message' => $response['message'] ?? 'not_set'
                    ]);
                } elseif ($action_type == 1) {
                    if ($property->delete()) {
                        $response = [
                            'error' => false,
                            'message' => 'Delete Successfully'
                        ];
                    } else {
                        $response = [
                            'error' => true,
                            'message' => 'something wrong'
                        ];
                    }
                } else {
                    // action_type is neither 0 nor 1
                    $response = [
                        'error' => true,
                        'message' => 'Invalid action_type. Must be 0 (update) or 1 (delete)',
                        'data' => null
                    ];
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No Data Found',
                    'data' => null
                ];
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            
            // Log the actual error with full details for debugging
            \Log::error('Property update failed in update_post_property', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'property_id' => $request->input('id'),
                'property_classification' => $request->input('property_classification'),
                'user_id' => Auth::id(),
                'vacation_apartments_count' => is_array($request->input('vacation_apartments')) 
                    ? count($request->input('vacation_apartments')) 
                    : 'not_array'
            ]);
            
            $response = array(
                'error' => true,
                'message' => 'Update failed: ' . $e->getMessage() // Return actual error message for debugging
            );
            return response()->json($response, 500);
        }

        // Ensure response is always set before returning
        if ($response === null || !isset($response['error'])) {
            \Log::error('Response not properly set in update_post_property', [
                'action_type' => $action_type ?? 'not_set',
                'property_id' => $request->input('id'),
                'response_value' => $response,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            $response = [
                'error' => true,
                'message' => 'Unexpected error: Response not set properly',
                'data' => null
            ];
        }

        return response()->json($response);
    }
    //* END :: update_post_property   *//


    //* START :: remove_post_images   *//
    public function remove_post_images(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if (!$validator->fails()) {
            $id = $request->id;
            $getImage = PropertyImages::where('id', $id)->first();
            $image = $getImage->image;
            $propertys_id =  $getImage->propertys_id;

            if (PropertyImages::where('id', $id)->delete()) {
                if (file_exists(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $propertys_id . "/" . $image)) {
                    unlink(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $propertys_id . "/" . $image);
                }
                $response['error'] = false;
            } else {
                $response['error'] = true;
            }

            $countImage = PropertyImages::where('propertys_id', $propertys_id)->get();
            if ($countImage->count() == 0) {
                rmdir(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $propertys_id);
            }

            $response['error'] = false;
            $response['message'] = 'Property Post Succssfully';
        } else {
            $response['error'] = true;
            $response['message'] = "Please fill all data and Submit";
        }

        return response()->json($response);
    }
    //* END :: remove_post_images   *//

    //* START :: set_property_inquiry   *//




    //* START :: get_notification_list   *//
    public function get_notification_list(Request $request)
    {
        $loggedInUserId = Auth::user()->id;
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;

        $notificationQuery = Notifications::where("customers_id", $loggedInUserId)
            ->orWhere('send_type', '1')
            ->with('property:id,title_image')
            ->select('id', 'title', 'message', 'image', 'type', 'send_type', 'customers_id', 'propertys_id', 'created_at')
            ->orderBy('id', 'DESC');

        $result = $notificationQuery->clone()
            ->skip($offset)
            ->take($limit)
            ->get();

        $total = $notificationQuery->count();

        if (!$result->isEmpty()) {
            $result = $result->map(function ($notification) {
                $notification->created = $notification->created_at->diffForHumans();
                $notification->notification_image = !empty($notification->image) ? $notification->image : (!empty($notification->propertys_id) && !empty($notification->property) ? $notification->property->title_image : "");
                unset($notification->image);
                return $notification;
            });

            $response = [
                'error' => false,
                'total' => $total,
                'data' => $result->toArray(),
            ];
        } else {
            $response = [
                'error' => false,
                'message' => 'No data found!',
                'data' => [],
            ];
        }

        return response()->json($response);
    }
    //* END :: get_notification_list   *//


    //* START :: delete_user   *//
    public function delete_user(Request $request)
    {
        try {
            DB::beginTransaction();
            $loggedInUserId = Auth::user()->id;
            $customer = Customer::find($loggedInUserId);
            if (collect($customer)->isNotEmpty()) {
                $customer->delete();
            }
            DB::commit();
            $response['error'] = false;
            $response['message'] = 'Delete Successfully';
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }
    //* END :: delete_user   *//
    public function bearerToken($request)
    {
        $header = $request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }
    //*START :: add favoutite *//
    public function add_favourite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'property_id' => 'required',


        ]);

        if (!$validator->fails()) {
            //add favourite
            $current_user = Auth::user()->id;
            if ($request->type == 1) {


                $fav_prop = Favourite::where('user_id', $current_user)->where('property_id', $request->property_id)->get();

                if (count($fav_prop) > 0) {
                    $response['error'] = false;
                    $response['message'] = "Property already add to favourite";
                    return response()->json($response);
                }
                $favourite = new Favourite();
                $favourite->user_id = $current_user;
                $favourite->property_id = $request->property_id;
                $favourite->save();
                $response['error'] = false;
                $response['message'] = "Property add to Favourite add successfully";
            }
            //delete favourite
            if ($request->type == 0) {
                Favourite::where('property_id', $request->property_id)->where('user_id', $current_user)->delete();

                $response['error'] = false;
                $response['message'] = "Property remove from Favourite  successfully";
            }
        } else {
            $response['error'] = true;
            $response['message'] = $validator->errors()->first();
        }


        return response()->json($response);
    }

    public function get_articles(Request $request)
    {
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;
        $article = Article::with('category:id,category,slug_id')->select('id', 'slug_id', 'image', 'title', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'category_id', 'created_at');

        if (isset($request->category_id)) {
            $category_id = $request->category_id;
            if ($category_id == 0) {
                $article = $article->clone()->where('category_id', '');
            } else {

                $article = $article->clone()->where('category_id', $category_id);
            }
        }

        if (isset($request->id)) {
            $similarArticles = $article->clone()->where('id', '!=', $request->id)->get();
            $article = $article->clone()->where('id', $request->id);
        } else if (isset($request->slug_id)) {
            $category = Category::where('slug_id', $request->slug_id)->first();
            if ($category) {
                $article = $article->clone()->where('category_id', $category->id);
            } else {
                $similarArticles = $article->clone()->where('slug_id', '!=', $request->slug_id)->get();
                $article = $article->clone()->where('slug_id', $request->slug_id);
            }
        }


        $total = $article->clone()->get()->count();
        $result = $article->clone()->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();
        if (!$result->isEmpty()) {
            $result = $result->toArray();

            foreach ($result as &$item) {
                $item['meta_image'] = $item['image'];
                $item['created_at'] = Carbon::parse($item['created_at'])->diffForHumans();
            }

            $response['data'] = $result;
            $response['similar_articles'] = $similarArticles ?? array();
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['total'] = $total;
            $response['data'] = $result;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['total'] = $total;
            $response['data'] = [];
        }
        return response()->json($response);
    }



    public function store_advertisement(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'feature_for' => 'required|in:property,project',
            'property_id' => 'nullable|required_if:feature_for,property',
            'project_id' => 'nullable|required_if:feature_for,project',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $current_user = Auth::user()->id;
            $advertisementQuery = Advertisement::whereIn('status', [0, 1]);
            if ($request->feature_for == 'property') {
                $packageData = HelperService::updatePackageLimit('property_feature', true);
                $checkAdvertisement = $advertisementQuery->clone()->where('property_id', $request->property_id)->count();
                if (collect($packageData)->isEmpty()) {
                    ApiResponseService::validationError("Package not found");
                }
            } else {
                // Project features are no longer tied to subscription plans - anyone can feature projects
                $packageData = null;
                $checkAdvertisement = $advertisementQuery->clone()->where('project_id', $request->project_id)->count();
            }
            if (!empty($checkAdvertisement)) {
                ApiResponseService::validationError("Advertisement Already Exists");
            }
            $advertisementData = new Advertisement();
            $advertisementData->for = $request->feature_for;
            $advertisementData->start_date = Carbon::now();
            if (isset($request->end_date)) {
                $advertisementData->end_date = $request->end_date;
            } else {
                // For projects, use a default duration (e.g., 30 days) since they're not tied to packages
                // For properties, use package duration
                if ($request->feature_for == 'property' && $packageData) {
                    $advertisementData->end_date = Carbon::now()->addHours($packageData->duration);
                } else {
                    // Default 30 days for project features (not tied to subscription)
                    $advertisementData->end_date = Carbon::now()->addDays(30);
                }
            }
            // Only set package_id for properties (projects are not tied to packages)
            if ($request->feature_for == 'property' && $packageData) {
                $advertisementData->package_id = $packageData->id;
            } else {
                $advertisementData->package_id = null;
            }
            $advertisementData->type = 'HomeScreen';
            if ($request->feature_for == 'property') {
                $advertisementData->property_id = $request->property_id;
            } else {
                $advertisementData->project_id = $request->project_id;
            }
            $advertisementData->customer_id = $current_user;
            $advertisementData->is_enable = false;

            // Check the auto approve and verified user status and make advertisement auto approved or pending and is enable true or false
            $autoApproveStatus = $this->getAutoApproveStatus($current_user);
            if ($autoApproveStatus) {
                $advertisementData->status = 0;
                $advertisementData->is_enable = true;
            } else {
                $advertisementData->status = 1;
                $advertisementData->is_enable = false;
            }
            $advertisementData->save();

            DB::commit();
            ApiResponseService::successResponse("Advertisement add successfully");
        } catch (\Throwable $th) {
            DB::rollback();
            ApiResponseService::errorResponse();
        }
    }

    public function get_advertisement(Request $request)
    {

        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;

        $date = date('Y-m-d');

        $adv = Advertisement::select('id', 'image', 'category_id', 'property_id', 'type', 'customer_id', 'is_enable', 'status')->with('customer:id,name')->where('end_date', '>', $date);
        if (isset($request->customer_id)) {
            $adv->where('customer_id', $request->customer_id);
        }
        $total = $adv->get()->count();
        $result = $adv->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();
        if (!$result->isEmpty()) {
            foreach ($adv as $row) {
                if (filter_var($row->image, FILTER_VALIDATE_URL) === false) {
                    $row->image = ($row->image != '') ? url('') . config('global.IMG_PATH') . config('global.ADVERTISEMENT_IMAGE_PATH') . $row->image : '';
                } else {
                    $row->image = $row->image;
                }
            }
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $result;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }


        return response()->json($response);
    }
    public function get_package(Request $request)
    {
        if ($request->platform == "ios") {
            $packages = OldPackage::where('status', 1)
                ->where('ios_product_id', '!=', '')
                ->orderBy('price', 'ASC')
                ->get();
        } else {
            $packages = Package::where('status', 1)
                ->orderBy('price', 'ASC')
                ->get();
        }

        $packages->transform(function ($item) use ($request) {
            if (collect(Auth::guard('sanctum')->user())->isNotEmpty()) {
                $currentDate = Carbon::now()->format("Y-m-d");

                $loggedInUserId = Auth::guard('sanctum')->user()->id;
                $user_package = OldUserPurchasedPackage::where('modal_id', $loggedInUserId)->where(function ($query) use ($currentDate) {
                    $query->whereDate('start_date', '<=', $currentDate)
                        ->whereDate('end_date', '>=', $currentDate);
                });

                if ($request->type == 'property') {
                    $user_package->where('prop_status', 1);
                } else if ($request->type == 'advertisement') {
                    $user_package->where('adv_status', 1);
                }

                $user_package = $user_package->where('package_id', $item->id)->first();


                if (!empty($user_package)) {
                    $startDate = new DateTime(Carbon::now());
                    $endDate = new DateTime($user_package->end_date);

                    // Calculate the difference between two dates
                    $interval = $startDate->diff($endDate);

                    // Get the difference in days
                    $diffInDays = $interval->days;

                    $item['is_active'] = 1;
                    $item['type'] = $item->type === "premium_user" ? "premium_user" : "product_listing";

                    if (!($item->type === "premium_user")) {
                        $item['used_limit_for_property'] = $user_package->used_limit_for_property;
                        $item['used_limit_for_advertisement'] = $user_package->used_limit_for_advertisement;
                        $item['property_status'] = $user_package->prop_status;
                        $item['advertisement_status'] = $user_package->adv_status;
                    }

                    $item['start_date'] = $user_package->start_date;
                    $item['end_date'] = $user_package->end_date;
                    $item['remaining_days'] = $diffInDays;
                } else {
                    $item['is_active'] = 0;
                }
            }

            if (!($item->type === "premium_user")) {
                $item['advertisement_limit'] = $item->advertisement_limit == '' ? "unlimited" : ($item->advertisement_limit == 0 ? "not_available" : $item->advertisement_limit);
                $item['property_limit'] = $item->property_limit == '' ? "unlimited" : ($item->property_limit == 0 ? "not_available" : $item->property_limit);
            } else {
                unset($item['property_limit']);
                unset($item['advertisement_limit']);
            }


            return $item;
        });

        // Sort the packages based on is_active flag (active packages first)
        $packages = $packages->sortByDesc('is_active');

        $response = [
            'error' => false,
            'message' => 'Data Fetch Successfully',
            'data' => $packages->values()->all(), // Reset the keys after sorting
        ];

        return response()->json($response);
    }
    public function user_purchase_package(Request $request)
    {

        $start_date =  Carbon::now();
        $validator = Validator::make($request->all(), [
            'package_id' => 'required',
        ]);

        if (!$validator->fails()) {
            $loggedInUserId = Auth::user()->id;
            if (isset($request->flag)) {
                $user_exists = OldUserPurchasedPackage::where('modal_id', $loggedInUserId)->get();
                if ($user_exists) {
                    OldUserPurchasedPackage::where('modal_id', $loggedInUserId)->delete();
                }
            }

            $package = Package::find($request->package_id);
            $user = Customer::find($loggedInUserId);
            $data_exists = OldUserPurchasedPackage::where('modal_id', $loggedInUserId)->get();
            if (count($data_exists) == 0 && $package) {
                $user_package = new OldUserPurchasedPackage();
                $user_package->modal()->associate($user);
                $user_package->package_id = $request->package_id;
                $user_package->start_date = $start_date;
                $user_package->end_date = $package->duratio != 0 ? Carbon::now()->addDays($package->duration) : NULL;
                $user_package->save();

                $user->subscription = 1;
                $user->update();

                $response['error'] = false;
                $response['message'] = "purchased package  add successfully";
            } else {
                $response['error'] = true;
                $response['message'] = "data already exists or package not found or add flag for add new package";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Please fill all data and Submit";
        }
        return response()->json($response);
    }
    public function get_favourite_property(Request $request)
    {

        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 25;

        $current_user = Auth::user()->id;

        $favourite = Favourite::where('user_id', $current_user)->select('property_id')->get();
        $arr = array();
        foreach ($favourite as $p) {
            $arr[] =  $p->property_id;
        }

        $property_details = Property::whereIn('id', $arr)->with('category:id,category,image,parameter_types')->with('assignfacilities.outdoorfacilities')->with('parameters');
        $result = $property_details->clone()->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();

        $total = $property_details->clone()->count();

        if (!$result->isEmpty()) {

            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] =  get_property_details($result, $current_user, true);
            $response['total'] = $total;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function delete_advertisement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);

        if (!$validator->fails()) {
            $adv = Advertisement::find($request->id);
            if (!$adv) {
                $response['error'] = false;
                $response['message'] = "Data not found";
            } else {

                $adv->delete();
                $response['error'] = false;
                $response['message'] = "Advertisement Deleted successfully";
            }
        } else {
            $response['error'] = true;
            $response['message'] = $validator->errors()->first();
        }
        return response()->json($response);
    }
    public function interested_users(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required',
            'type' => 'required'


        ]);
        if (!$validator->fails()) {
            $current_user = Auth::user()->id;

            $interested_user = InterestedUser::where('customer_id', $current_user)->where('property_id', $request->property_id);

            if ($request->type == 1) {

                if (count($interested_user->get()) > 0) {
                    $response['error'] = false;
                    $response['message'] = "already added to interested users ";
                } else {
                    $interested_user = new InterestedUser();
                    $interested_user->property_id = $request->property_id;
                    $interested_user->customer_id = $current_user;
                    $interested_user->save();
                    $response['error'] = false;
                    $response['message'] = "Interested Users added successfully";
                }
            }
            if ($request->type == 0) {

                if (count($interested_user->get()) == 0) {
                    $response['error'] = false;
                    $response['message'] = "No data found to delete";
                } else {
                    $interested_user->delete();

                    $response['error'] = false;
                    $response['message'] = "Interested Users removed  successfully";
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = $validator->errors()->first();
        }
        return response()->json($response);
    }

    public function user_interested_property(Request $request)
    {

        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 25;

        $current_user = Auth::user()->id;


        $favourite = InterestedUser::where('customer_id', $current_user)->select('property_id')->get();
        $arr = array();
        foreach ($favourite as $p) {
            $arr[] =  $p->property_id;
        }
        $property_details = Property::whereIn('id', $arr)->with('category:id,category,parameter_types')->with('parameters');
        $result = $property_details->orderBy('id', 'ASC')->skip($offset)->take($limit)->get();


        $total = $result->count();

        if (!$result->isEmpty()) {
            foreach ($property_details as $row) {
                if (filter_var($row->image, FILTER_VALIDATE_URL) === false) {
                    $row->image = ($row->image != '') ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_TITLE_IMG_PATH') . $row->image : '';
                } else {
                    $row->image = $row->image;
                }
            }
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $result;
            $response['total'] = $total;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function get_languages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required',
        ]);

        if (!$validator->fails()) {
            $language = Language::where('code', $request->language_code)->first();

            if ($language) {
                if ($request->web_language_file) {
                    $json_file_path = public_path('web_languages/' . $request->language_code . '.json');
                } else {
                    $json_file_path = public_path('languages/' . $request->language_code . '.json');
                }

                if (file_exists($json_file_path)) {
                    $json_string = file_get_contents($json_file_path);
                    $json_data = json_decode($json_string);

                    if ($json_data !== null) {
                        $language->file_name = $json_data;
                        $response['error'] = false;
                        $response['message'] = "Data Fetch Successfully";
                        $response['data'] = $language;
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Invalid JSON format in the language file";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Language file not found";
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Language not found";
            }
        } else {
            $response['error'] = true;
            $response['message'] = $validator->errors()->first();
        }

        return response()->json($response);
    }
    public function getPaymentTransactionDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_type' => 'nullable|string|in:online payment,bank transfer,free'
            ]);
            if ($validator->fails()) {
                ApiResponseService::validationError($validator->errors()->first());
            }
            // Get offset and limit
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;
            // Get logged in user id
            $loggedInUserId = Auth::user()->id;

            // Get payment query
            $paymentQuery = PaymentTransaction::where('user_id', $loggedInUserId)
                // Filter by payment type if provided
                ->when($request->payment_type, function ($query) use ($request) {
                    $query->where('payment_type', $request->payment_type);
                });
            // Get total count of filtered results
            $total = $paymentQuery->clone()->count();
            // Get paginated results
            $result = $paymentQuery->with('package:id,name,price')->orderBy('created_at', 'DESC')->skip($offset)->take($limit)->get();

            if (count($result)) {
                ApiResponseService::successResponse("Data Fetch Successfully", $result, array('total' => $total));
            } else {
                ApiResponseService::errorResponse("No data found!");
            }
        } catch (Exception $e) {
            ApiResponseService::errorResponse($e->getMessage());
        }
    }



    public function paypal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();

            $currentUser = Auth::user();
            $package = Package::where(['id' => $request->package_id, 'status' => 1])->first();
            if (collect($package)->isEmpty()) {
                ApiResponseService::validationError("Packge not found");
            }

            //Add Payment Data to Payment Transactions Table
            $paymentTransactionData = PaymentTransaction::create([
                'user_id'         => $currentUser->id,
                'package_id'      => $package->id,
                'amount'          => $package->price,
                'payment_gateway' => "Paypal",
                'payment_status'  => 'pending',
                'order_id'        => null,
                'payment_type'    => 'online payment'
            ]);
            $returnURL = url('api/app_payment_status?error=false&payment_transaction_id=' . $paymentTransactionData->id);
            $cancelURL = url('api/app_payment_status?error=true&payment_transaction_id=' . $paymentTransactionData->id);
            $notifyURL = url('webhook/paypal');

            $paypal = new Paypal();
            // Get current user ID from the session
            $paypal->add_field('return', $returnURL);
            $paypal->add_field('cancel_return', $cancelURL);
            $paypal->add_field('notify_url', $notifyURL);
            $custom_data = $paymentTransactionData->id;

            // // Add fields to paypal form
            $paypal->add_field('item_name', "package");
            $paypal->add_field('custom_id', json_encode($custom_data));
            $paypal->add_field('custom', ($custom_data));
            $paypal->add_field('amount', $request->amount);

            DB::commit();
            // Render paypal form
            $paypal->paypal_auto_form();
        } catch (Exception $e) {
            DB::rollback();
            ApiResponseService::errorResponse();
        }
    }
    public function app_payment_status(Request $request)
    {
        $paypalInfo = $request->all();
        $paymentTransactionId = $request->payment_transaction_id;
        // Get Web URL
        $webURL = system_setting('web_url') ?? null;
        if (isset($paypalInfo) && !empty($paypalInfo) && isset($paypalInfo['payment_status']) && !empty($paypalInfo['payment_status'])) {
            if ($paypalInfo['payment_status'] == "Completed") {
                $webWithStatusURL = $webURL . '/payment/success';
                $response['error'] = false;
                $response['message'] = "Your Purchase Package Activate Within 10 Minutes ";
                $response['data'] = $paypalInfo['txn_id'];
            } elseif ($paypalInfo['payment_status'] == "Authorized") {
                $webWithStatusURL = $webURL . '/payment/success';
                $response['error'] = false;
                $response['message'] = "Your payment has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture Ads wil be credited automatically.";
                $response['data'] = $paypalInfo;
            } else {
                PaymentTransaction::where('id', $paymentTransactionId)->update(['payment_status' => 'failed']);
                $webWithStatusURL = $webURL . '/payment/fail';
                $response['error'] = true;
                $response['message'] = "Payment Cancelled / Declined ";
                $response['data'] = !empty($paypalInfo) ? $paypalInfo : "";
            }
        } else {
            PaymentTransaction::where('id', $paymentTransactionId)->update(['payment_status' => 'failed']);
            $webWithStatusURL = $webURL . '/payment/fail';
            $response['error'] = true;
            $response['message'] = "Payment Cancelled / Declined ";
        }

        if ($webURL) {
            echo "<html>
            <body>
            Redirecting...!
            </body>
            <script>
                window.location.replace('" . $webWithStatusURL . "');
            </script>
            </html>";
        } else {
            echo "<html>
            <body>
            Redirecting...!
            </body>
            <script>
                console.log('No web url added');
            </script>
            </html>";
        }
        // return (response()->json($response));
    }
    public function get_payment_settings(Request $request)
    {
        $payment_settings = Setting::select('type', 'data')->whereIn('type', ['paypal_business_id', 'sandbox_mode', 'paypal_gateway', 'razor_key', 'razor_secret', 'razorpay_gateway', 'paystack_public_key', 'paystack_secret_key', 'paystack_currency', 'paystack_gateway', 'flutterwave_status', 'paymob_gateway', 'bank_transfer_status'])->get();

        if (count($payment_settings)) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $payment_settings;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return (response()->json($response));
    }
    public function send_message(Request $request)
    {
        Log::info("DEBUG: send_message called with request: " . json_encode($request->all()));
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'property_id' => 'required',
            'file' => 'nullable|mimes:png,jpg,jpeg,pdf,doc,docx',
            'audio' => 'nullable|mimes:mpeg,m4a,mp3,mp4,webm'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        $customer = Customer::select('id', 'name', 'profile')->with(['usertokens' => function ($q) {
            $q->select('fcm_id', 'id', 'customer_id');
        }])->find($request->receiver_id);
        if (collect($customer)->isNotEmpty()) {
            $senderBlockedReciever = BlockedChatUser::where(['by_user_id' => $request->sender_id, 'user_id' => $request->receiver_id])->count();
            if ($senderBlockedReciever) {
                ApiResponseService::validationError("You have blocked user");
            }
            $recieverBlockedSender = BlockedChatUser::where(['by_user_id' => $request->receiver_id, 'user_id' => $request->sender_id])->count();
            if ($recieverBlockedSender) {
                ApiResponseService::validationError("You are blocked by user");
            }
        } else {
            $senderBlockedReciever = BlockedChatUser::where(['by_user_id' => $request->sender_id, 'admin' => 1])->count();
            if ($senderBlockedReciever) {
                ApiResponseService::validationError("You have blocked admin");
            }
            $recieverBlockedSender = BlockedChatUser::where(['by_admin' => 1, 'user_id' => $request->sender_id])->count();
            if ($recieverBlockedSender) {
                ApiResponseService::validationError("You are blocked by admin");
            }
        }

        $fcm_id = array();
        
        // Generate conversation ID for this chat
        $conversationId = Chats::getOrCreateConversationId(
            $request->sender_id,
            $request->receiver_id,
            $request->property_id
        );
        
        $chat = new Chats();
        $chat->fill([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'property_id' => $request->property_id,
            'conversation_id' => $conversationId,
            'message' => $request->message,
            'approval_status' => isset($request->approval_status) ? $request->approval_status : 'pending'
        ]);

        // S3 storage is now handled by store_image function
        // Files upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileFilename = store_image($file, 'CHAT_FILE');
            $chat->setAttribute('file', $fileFilename);
        } else {
            $chat->setAttribute('file', '');
        }

        // S3 storage is now handled by store_image function
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $audioFilename = store_image($file, 'CHAT_FILE', 'chat_audio');
            $chat->setAttribute('audio', $audioFilename);
        } else {
            $chat->setAttribute('audio', '');
        }
        $chat->save();

        // Get property data first to avoid duplicate queries
        $Property = Property::find($request->property_id);

        if ($customer) {
            foreach ($customer->usertokens as $usertokens) {
                array_push($fcm_id, $usertokens->fcm_id);
            }
            $username = $customer->name;
        } else {
            // Get the specific admin user who owns the property
            if ($Property && $Property->added_by) {
                $admin_user = User::select('fcm_id', 'name')->find($Property->added_by);
                if ($admin_user) {
                    array_push($fcm_id, $admin_user->fcm_id);
                    $username = $admin_user->name;
                } else {
                    $username = "Admin";
                }
            } else {
                $username = "Admin";
            }
        }
        $senderUser = Customer::select('fcm_id', 'name', 'profile')->find($request->sender_id);
        if ($senderUser) {
            $profile = $senderUser->profile;
        } else {
            $profile = "";
        }






        $chat_message_type = "";

        if (!empty($request->file('audio'))) {
            $chat_message_type = "audio";
        } else if (!empty($request->file('file')) && $request->message == "") {
            $chat_message_type = "file";
        } else if (!empty($request->file('file')) && $request->message != "") {
            $chat_message_type = "file_and_text";
        } else if (empty($request->file('file')) && $request->message != "" && empty($request->file('audio'))) {
            $chat_message_type = "text";
        } else {
            $chat_message_type = "text";
        }


        // Get UnRead Messages Count for this specific conversation
        $unreadMessagesCount = Chats::where(['conversation_id' => $conversationId, 'receiver_id' => $request->sender_id, 'is_read' => false])->count();


        $senderName = $senderUser ? $senderUser->name : "Admin";
        $fcmMsg = array(
            'title' => "New message sent from {$senderName}",
            'message' => $request->message,
            'type' => 'chat',
            'body' => $request->message,
            'sender_id' => $request->sender_id,
            'sender_name' => $senderUser->name ?? 'User',
            'sender_profile' => $senderUser->profile ?? '',
            'receiver_id' => $request->receiver_id,
            'file' => $chat->file,
            'username' => $username,
            'user_profile' => $profile,
            'audio' => $chat->audio,
            'date' => $chat->created_at->diffForHumans(now(), CarbonInterface::DIFF_RELATIVE_AUTO, true),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'sound' => 'default',
            'time_ago' => $chat->created_at->diffForHumans(now(), CarbonInterface::DIFF_RELATIVE_AUTO, true),
            'property_id' => (string)$Property->id,
            'property_title_image' => $Property->title_image,
            'property_title' => $Property->title,
            'chat_message_type' => $chat_message_type,
            'created_at' => Carbon::parse($chat->created_at)->toIso8601ZuluString(),
            'unread_messages_count' => (string)$unreadMessagesCount
        );

        // Save Notification to Database
        Notifications::create([
            'title' => "New message sent from {$senderName}",
            'message' => !empty($request->message) ? $request->message : ($chat_message_type == 'audio' ? 'Audio message' : 'File sent'),
            'type' => 1, // 1 seems to be generic/standard type based on other logic
            'send_type' => 0, // Specific user
            'customers_id' => $request->receiver_id,
            'propertys_id' => $request->property_id,
            'image' => '' 
        ]);

        $send = send_push_notification($fcm_id, $fcmMsg);
        $response['error'] = false;
        $response['message'] = "Data Store Successfully";
        $response['id'] = $chat->id;
        // $response['data'] = $send;
        return (response()->json($response));
    }
    public function get_messages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required',
            'user_id' => 'required'

        ]);
        if (!$validator->fails()) {
            $currentUser = Auth::user();
            $userId = $request->user_id;

            // Generate conversation ID for this specific conversation
            $conversationId = Chats::getOrCreateConversationId(
                $currentUser->id,
                $userId,
                $request->property_id
            );

            // update is_read to true for the current user in this specific conversation
            Chats::where(['conversation_id' => $conversationId, 'receiver_id' => $currentUser->id, 'is_read' => false])
                ->update(['is_read' => true]);

            $perPage = $request->per_page ? $request->per_page : 15; // Number of results to display per page
            $page = $request->page ?? 1; // Get the current page from the query string, or default to 1
            
            // Get messages only for this specific conversation
            $chat = Chats::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            // You can then pass the $chat object to your view to display the paginated results.




            $chat_message_type = "";
            if ($chat) {


                $chat->map(function ($chat) use ($chat_message_type, $currentUser) {
                    if (!empty($chat->audio)) {
                        $chat_message_type = "audio";
                    } else if (!empty($chat->file) && $chat->message == "") {
                        $chat_message_type = "file";
                    } else if (!empty($chat->file) && $chat->message != "") {
                        $chat_message_type = "file_and_text";
                    } else if (empty($chat->file) && !empty($chat->message) && empty($chat->audio)) {
                        $chat_message_type = "text";
                    } else {
                        $chat_message_type = "text";
                    }
                    $chat['chat_message_type'] = $chat_message_type;
                    $chat['user_profile'] = $currentUser->profile;
                    $chat['time_ago'] = $chat->created_at->diffForHumans();
                    $chat['approval_status'] = $chat->approval_status;
                });

                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                $response['total_page'] = $chat->lastPage();
                $response['data'] = $chat;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Please fill all data and Submit";
        }
        return response()->json($response);
    }

    public function get_chats(Request $request)
    {
        $current_user = Auth::user()->id;
        $perPage = $request->per_page ? $request->per_page : 15; // Number of results to display per page
        $page = $request->page ?? 1;

        $adminData = User::where('type', 0)->select('id', 'name', 'profile')->first();

        $chat = Chats::with(['sender', 'receiver'])->with('property')
            ->select(
                'id',
                'sender_id',
                'receiver_id',
                'property_id',
                'conversation_id',
                'created_at',
                'approval_status',
                DB::raw('LEAST(sender_id, receiver_id) as user1_id'),
                DB::raw('GREATEST(sender_id, receiver_id) as user2_id'),
                DB::raw('COUNT(CASE WHEN receiver_id = ' . $current_user . ' AND is_read = 0 THEN 1 END) AS unread_count')
            )
            ->where(function ($query) use ($current_user) {
                $query->where('sender_id', $current_user)
                    ->orWhere('receiver_id', $current_user);
            })
            ->orderBy('id', 'desc')
            ->groupBy('conversation_id')
            ->paginate($perPage, ['*'], 'page', $page);

        if (!$chat->isEmpty()) {

            $rows = array();

            $count = 1;

            $response['total_page'] = $chat->lastPage();

            foreach ($chat as $key => $row) {
                $tempRow = array();
                $tempRow['property_id'] = $row->property_id;
                $tempRow['title'] = $row->property->title;
                $tempRow['title_image'] = $row->property->title_image;
                $tempRow['property_reference_id'] = $row->property->slug_id;
                $tempRow['date'] = $row->created_at;
                $tempRow['property_id'] = $row->property_id;
                $tempRow['unread_count'] = $row->unread_count;
                $tempRow['approval_status'] = $row->approval_status;
                if (!$row->receiver || !$row->sender) {
                    $user = Customer::where('id', $row->sender_id)->orWhere('id', $row->receiver_id)->select('id')->first();

                    $isBlockedByMe = false;
                    $isBlockedByUser = false;

                    $blockedByMe = BlockedChatUser::where('by_user_id', $user->id)
                        ->where('admin', 1)
                        ->exists();

                    $blockedByAdmin = BlockedChatUser::where('by_admin', 1)
                        ->where('user_id', $user->id)
                        ->exists();
                    $tempRow['is_blocked_by_me'] = $blockedByMe;
                    $tempRow['is_blocked_by_user'] = $blockedByAdmin;


                    $tempRow['user_id'] = 0;
                    $tempRow['name'] = "Admin";
                    $tempRow['profile'] = !empty($adminData->getRawOriginal('profile')) ? $adminData->profile : url('assets/images/faces/2.jpg');

                    // $tempRow['fcm_id'] = $row->receiver->fcm_id;
                } else {

                    $isBlockedByMe = false;
                    $isBlockedByUser = false;
                    if ($row->sender->id == $current_user) {
                        $isBlockedByMe = BlockedChatUser::where('by_user_id', $current_user)
                            ->where('user_id', $row->receiver->id)
                            ->exists();

                        $isBlockedByUser = BlockedChatUser::where('by_user_id', $row->receiver->id)
                            ->where('user_id', $current_user)
                            ->exists();

                        $tempRow['is_blocked_by_me'] = $isBlockedByMe;
                        $tempRow['is_blocked_by_user'] = $isBlockedByUser;

                        $tempRow['user_id'] = $row->receiver->id;
                        $tempRow['name'] = $row->receiver->name;
                        $tempRow['profile'] = $row->receiver->profile;
                        $tempRow['fcm_id'] = $row->receiver->fcm_id;
                    }
                    if ($row->receiver->id == $current_user) {

                        $isBlockedByMe = BlockedChatUser::where('by_user_id', $current_user)
                            ->where('user_id', $row->sender->id)
                            ->exists();

                        $isBlockedByUser = BlockedChatUser::where('by_user_id', $row->sender->id)
                            ->where('user_id', $current_user)
                            ->exists();

                        $tempRow['is_blocked_by_me'] = $isBlockedByMe;
                        $tempRow['is_blocked_by_user'] = $isBlockedByUser;

                        $tempRow['user_id'] = $row->sender->id;
                        $tempRow['name'] = $row->sender->name;
                        $tempRow['profile'] = $row->sender->profile;
                        $tempRow['fcm_id'] = $row->sender->fcm_id;
                    }
                }
                $rows[] = $tempRow;
                $count++;
            }


            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";
            $response['data'] = $rows;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function get_nearby_properties(Request $request)
    {
        $latitude = $request->has('latitude') ? $request->latitude : null;
        $longitude = $request->has('longitude') ? $request->longitude : null;
        $radius = $request->has('radius') ? $request->radius : null;

        // Create reusable property mapper function
        $propertyMapper = function ($propertyData) {
            $propertyData->promoted = $propertyData->is_promoted;
            $propertyData->property_type = $propertyData->propery_type;
            $propertyData->parameters = $propertyData->parameters;
            $propertyData->is_premium = $propertyData->is_premium == 1;
            return $propertyData;
        };

        // Base property query that will be reused
        $propertyQuery = Property::select(
            'id',
            'slug_id',
            'category_id',
            'city',
            'state',
            'country',
            'price',
            'propery_type',
            'title',
            'title_ar',
            'title_image',
            'is_premium',
            'address',
            'rentduration',
            'latitude',
            'longitude'
        )
            ->with('category:id,slug_id,image,category')
            ->where(['status' => 1, 'request_status' => 'approved'])
            ->whereIn('propery_type', [0, 1]);

        if ($latitude && $longitude) {
            if ($radius) {
                // Create a query with Haversine formula
                $propertyQuery->selectRaw("
                    (6371 * acos(cos(radians($latitude))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians($longitude))
                    + sin(radians($latitude))
                    * sin(radians(latitude)))) AS distance")
                    ->where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->having('distance', '<', $radius);
            } else {
                $$propertyQuery->where(['latitude' => $latitude, 'longitude' => $longitude]);
            }
        }


        // Check the city and state params and query the params according to it
        if (isset($request->city) || isset($request->state)) {
            $propertyQuery->where(function ($query) use ($request) {
                $query->where('state', 'LIKE', "%{$request->state}%")
                    ->orWhere('city', 'LIKE', "%{$request->city}%");
            });
        }

        // Check the type params and query the params according to it
        if (isset($request->type)) {
            $propertyQuery->where('propery_type', $request->type);
        }

        // Get Final Data
        $propertiesData = $propertyQuery->get()->map($propertyMapper);

        // Pass data as json
        if (collect($propertiesData)->isNotEmpty()) {
            $response['error'] = false;
            $response['data'] = $propertiesData;
        } else {
            $response['error'] = false;
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function update_property_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:2,3',
            'property_id' => 'required|exists:propertys,id'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            $property = Property::find($request->property_id);

            if ($property->getRawOriginal('propery_type') == 0 && $request->status != 2) {
                ApiResponseService::validationError("You can only change sell property to sold");
            } else if ($property->getRawOriginal('propery_type') == 1 && $request->status != 3) {
                ApiResponseService::validationError("You can only change rent property to rented");
            } else if ($property->getRawOriginal('propery_type') != 0 && $property->getRawOriginal('propery_type') != 1) {
                ApiResponseService::validationError("You can only change status of sell and rent properties");
            }
            $property->propery_type = $request->status;
            $property->save();
            $response['error'] = false;
            $response['message'] = "Data updated Successfully";
            return response()->json($response);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }
    public function getCitiesData(Request $request)
    {
        try {
            // Get Offset and Limit from payload request
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;
            $city_arr = array();
            $citiesQuery = CityImage::where('status', 1)->withCount(['property' => function ($query) {
                $query->whereIn('propery_type', [0, 1])->where(['status' => 1, 'request_status' => 'approved']);
            }])->having('property_count', '>', 0);
            $totalData = $citiesQuery->clone()->count();
            $citiesData = $citiesQuery->clone()->orderBy('property_count', 'DESC')->skip($offset)->take($limit)->get();
            foreach ($citiesData as $city) {
                if (!empty($city->getRawOriginal('image'))) {
                    // Always use stored image URL when available; do not gate by local file existence
                    array_push($city_arr, ['City' => $city->city, 'Count' => $city->property_count, 'image' => $city->image]);
                    continue;
                }
                $resultArray = $this->getUnsplashData($city);
                array_push($city_arr, $resultArray);
            }
            return ApiResponseService::successResponseReturn("Data Fetched Successfully", $city_arr, array('total' => $totalData));
        } catch (Exception $e) {
            ApiResponseService::logErrorResponse($e, 'getCitiesData failed');
            ApiResponseService::errorResponse();
        }
    }

    public function get_facilities(Request $request)
    {
        $facilities = OutdoorFacilities::query();

        // if (isset($request->search) && !empty($request->search)) {
        //     $search = $request->search;
        //     $facilities->where('category', 'LIKE', "%$search%");
        // }

        if (isset($request->id) && !empty($request->id)) {
            $id = $request->id;
            $facilities->where('id', '=', $id);
        }
        $total = $facilities->clone()->count();
        $result = $facilities->clone()->get();


        if (!$result->isEmpty()) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";

            $response['total'] = $total;
            $response['data'] = $result;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function get_report_reasons(Request $request)
    {
        $report_reason = report_reasons::query();

        if (isset($request->id) && !empty($request->id)) {
            $id = $request->id;
            $report_reason->where('id', '=', $id);
        }
        $result = $report_reason->clone()->get();

        $total = $report_reason->clone()->count();

        if (!$result->isEmpty()) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";

            $response['total'] = $total;
            $response['data'] = $result;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function add_reports(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason_id' => 'required',
            'property_id' => 'required',



        ]);
        $current_user = Auth::user()->id;
        if (!$validator->fails()) {
            $report_count = user_reports::where('property_id', $request->property_id)->where('customer_id', $current_user)->get();
            if (!count($report_count)) {
                $report_reason = new user_reports();
                $report_reason->reason_id = $request->reason_id ? $request->reason_id : 0;
                $report_reason->property_id = $request->property_id;
                $report_reason->customer_id = $current_user;
                $report_reason->other_message = $request->other_message ? $request->other_message : '';



                $report_reason->save();


                $response['error'] = false;
                $response['message'] = "Report Submited Successfully";
            } else {
                $response['error'] = false;
                $response['message'] = "Already Reported";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Please fill all data and Submit";
        }
        return response()->json($response);
    }
    public function delete_chat_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            // Get Customer IDs

            // Get FCM IDs
            $fcmId = Usertokens::select('fcm_id')->where('customer_id', $request->receiver_id)->pluck('fcm_id')->toArray();

            if (isset($request->message_id)) {
                $chat = Chats::find($request->message_id);
                if ($chat) {
                    if (!empty($fcmId)) {
                        $registrationIDs = array_filter($fcmId);
                        $fcmMsg = array(
                            'title' => "Delete Chat Message",
                            'message' => "Message Deleted Successfully",
                            "image" => '',
                            'type' => 'delete_message',
                            'message_id' => $request->message_id,
                            'body' => 'Message Deleted Successfully',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'sound' => 'default',

                        );
                        send_push_notification($registrationIDs, $fcmMsg, 1);
                    }
                    $chat->delete();
                    ApiResponseService::successResponse("Message Deleted Successfully");
                } else {
                    ApiResponseService::validationError("No data found");
                }
            } else if (isset($request->sender_id) && isset($request->receiver_id) && isset($request->property_id)) {

                $user_chat = Chats::where('property_id', $request->property_id)
                    ->where(function ($query) use ($request) {
                        $query->where('sender_id', $request->sender_id)
                            ->orWhere('receiver_id', $request->receiver_id);
                    })
                    ->orWhere(function ($query) use ($request) {
                        $query->where('sender_id', $request->receiver_id)
                            ->orWhere('receiver_id', $request->sender_id);
                    });
                if (count($user_chat->get())) {

                    $user_chat->delete();
                    ApiResponseService::successResponse("chat deleted successfully");
                } else {
                    ApiResponseService::validationError("No data found");
                }
            } else {
                ApiResponseService::validationError("No data found");
            }
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }
    public function get_user_recommendation(Request $request)
    {
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;
        $current_user = Auth::user()->id;


        $user_interest = UserInterest::where('user_id', $current_user)->first();
        if (collect($user_interest)->isNotEmpty()) {

            $property = Property::with('customer')->with('user')->with('category:id,category,image,parameter_types')->with('assignfacilities.outdoorfacilities')->with('favourite')->with('parameters')->with('interested_users')->where(['status' => 1, 'request_status' => 'approved']);


            $property_type = $request->property_type;
            if ($user_interest->category_ids != '') {

                $category_ids = explode(',', $user_interest->category_ids);

                $property = $property->whereIn('category_id', $category_ids);
            }

            if ($user_interest->price_range != '') {

                $max_price = explode(',', $user_interest->price_range)[1];

                $min_price = explode(',', $user_interest->price_range)[0];

                if (isset($max_price) && isset($min_price)) {
                    $min_price = floatval($min_price);
                    $max_price = floatval($max_price);

                    $property = $property->where(function ($query) use ($min_price, $max_price) {
                        $query->whereRaw("CAST(price AS DECIMAL(10, 2)) >= ?", [$min_price])
                            ->whereRaw("CAST(price AS DECIMAL(10, 2)) <= ?", [$max_price]);
                    });
                }
            }


            if ($user_interest->city != '') {
                $city = $user_interest->city;
                $property = $property->where('city', $city);
            }
            if ($user_interest->property_type != '') {
                $property_type = explode(',',  $user_interest->property_type);
            }
            if ($user_interest->outdoor_facilitiy_ids != '') {


                $outdoor_facilitiy_ids = explode(',', $user_interest->outdoor_facilitiy_ids);
                $property = $property->whereHas('assignfacilities.outdoorfacilities', function ($q) use ($outdoor_facilitiy_ids) {
                    $q->whereIn('id', $outdoor_facilitiy_ids);
                });
            }



            if (isset($property_type)) {
                if (count($property_type) == 2) {
                    $property_type = $property->where(function ($query) use ($property_type) {
                        $query->where('propery_type', $property_type[0])->orWhere('propery_type', $property_type[1]);
                    });
                } else {
                    if (isset($property_type[0])  &&  $property_type[0] == 0) {

                        $property = $property->where('propery_type', $property_type[0]);
                    }
                    if (isset($property_type[0])  &&  $property_type[0] == 1) {

                        $property = $property->where('propery_type', $property_type[0]);
                    }
                }
            }



            $total = $property->get()->count();

            $result = $property->skip($offset)->take($limit)->get();
            $property_details = get_property_details($result, $current_user, true);

            if (!empty($result)) {
                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                $response['total'] = $total;
                $response['data'] = $property_details;
            } else {

                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return ($response);
    }
    public function contct_us(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if (!$validator->fails()) {

            $contactrequest = new Contactrequests();
            $contactrequest->first_name = $request->first_name;
            $contactrequest->last_name = $request->last_name;
            $contactrequest->email = $request->email;
            $contactrequest->subject = $request->subject;
            $contactrequest->message = $request->message;
            $contactrequest->save();

            // Send inquiry email to Ayman using template
            try {
                $emailTypeData = HelperService::getEmailTemplatesTypes('inquiry_form');
                $templateData = system_setting('inquiry_form_mail_template');
                $variables = array(
                    'app_name' => env('APP_NAME') ?? 'eBroker',
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'subject' => $request->subject,
                    'message' => $request->message,
                );
                if (empty($templateData)) {
                    $templateData = 'New Inquiry from {first_name} {last_name} ({email}) with subject "{subject}". Message: {message}';
                }
                // Add Action Required note for non-instant bookings
                if ($reservation->requires_approval) {
                    $templateData .= "\n\n⏳ **Action Required**: This is a non-instant booking. Please review and approve or reject this request in your dashboard.";
                }

                $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

                $data = array(
                    'email_template' => $emailTemplate,
                    'email' => 'Ayman.yehia@As-home-group.com',
                    'title' => $emailTypeData['title'],
                );
                HelperService::sendMail($data);
            } catch (Exception $e) {
                // Do not fail the API if email fails; log instead
                Log::error('Failed to send inquiry email: ' . $e->getMessage());
            }
            $response['error'] = false;
            $response['message'] = "Contact Request Send successfully";
        } else {


            $response['error'] = true;
            $response['message'] =  $validator->errors()->first();
        }
        return response()->json($response);
    }

    public function delete_property(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:propertys,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            DB::beginTransaction();
            Property::findOrFail($request->id)->delete();
            DB::commit();
            $response['error'] = false;
            $response['message'] =  'Delete Successfully';
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }
    public function assign_package(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
            'product_id' => 'required_if:in_app,true',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            $loggedInUserId = Auth::user()->id;

            if ($request->in_app == 'true' || $request->in_app === true) {
                $package = Package::where('ios_product_id', $request->product_id)->first();
            } else {
                $package = Package::where('id', $request->package_id)->first();
                if ($package->package_type == 'paid') {
                    ApiResponseService::validationError("Package is paid cannot assign directly");
                }
            }
            if (collect($package)->isNotEmpty()) {
                DB::beginTransaction();
                // Assign Package to user
                $userPackage = UserPackage::create([
                    'package_id'  => $package->id,
                    'user_id'     => $loggedInUserId,
                    'start_date'  => Carbon::now(),
                    'end_date'    => $package->package_type == "unlimited" ? null : Carbon::now()->addHours($package->duration),
                ]);

                // Create Payment Transaction
                PaymentTransaction::create([
                    'user_id' => $loggedInUserId,
                    'package_id' => $package->id,
                    'amount' => 0,
                    'payment_gateway' => null,
                    'payment_type' => 'free',
                    'payment_status' => 'success',
                    'order_id' => Str::uuid(),
                    'transaction_id' => Str::uuid()
                ]);

                // Assign limited count feature to user with limits
                $packageFeatures = PackageFeature::where(['package_id' => $package->id, 'limit_type' => 'limited'])->get();
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
                DB::commit();
                ApiResponseService::successResponse("Package Purchased Successfully");
            } else {
                ApiResponseService::validationError("Package Not Found");
            }
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function get_app_settings(Request $request)
    {
        $result =  Setting::select('type', 'data')->whereIn('type', ['app_home_screen', 'placeholder_logo', 'light_tertiary', 'light_secondary', 'light_primary', 'dark_tertiary', 'dark_secondary', 'dark_primary'])->get();


        $tempRow = [];

        if (($request->user_id) != "") {
            update_subscription($request->user_id);

            $customer_data = Customer::find($request->user_id);
            if ($customer_data) {
                if ($customer_data->isActive == 0) {

                    $tempRow['is_active'] = false;
                } else {
                    $tempRow['is_active'] = true;
                }
            }
        }



        foreach ($result as $row) {
            $tempRow[$row->type] = $row->data;

            if ($row->type == 'app_home_screen' || $row->type == "placeholder_logo") {

                // Check if file exists, otherwise use default logo
                $logoPath = public_path('assets/images/logo/' . $row->data);
                if (!empty($row->data) && file_exists($logoPath)) {
                    $tempRow[$row->type] = url('/assets/images/logo/') . '/' . $row->data;
                } else {
                    // Fallback to default logo if file doesn't exist
                    $tempRow[$row->type] = url('/assets/images/logo/logo.png');
                }
            }
        }

        $response['error'] = false;
        $response['data'] = $tempRow;
        return response()->json($response);
    }
    public function get_seo_settings(Request $request)
    {
        $offset = isset($request->offset) ? $request->offset : 0;
        $limit = isset($request->limit) ? $request->limit : 10;

        $seo_settings = SeoSettings::select('id', 'page', 'image', 'title', 'description', 'keywords');


        if (isset($request->page) && !empty($request->page)) {

            $seo_settings->where('page', 'LIKE', "%$request->page%");
        } else {
            $seo_settings->where('page', 'LIKE', "%homepage%");
        }

        $total = $seo_settings->count();
        $result = $seo_settings->skip($offset)->take($limit)->get();


        // $seo_settingsWithCount = Category::withCount('properties')->get();
        $rows = array();
        $count = 0;
        if (!$result->isEmpty()) {

            foreach ($result as $key => $row) {
                $tempRow['id'] = $row->id;
                $tempRow['page'] = $row->page;
                $tempRow['meta_image'] = $row->image;

                if ($row->page == "properties-city") {
                    $tempRow['meta_title'] = "[Your City]'s Finest:" . $row->title;
                    $tempRow['meta_description'] = "Discover the charm of living near [Your City]." . $row->description;
                } else {

                    $tempRow['meta_title'] = $row->title;
                    $tempRow['meta_description'] = $row->description;
                }
                $tempRow['meta_keywords'] = $row->keywords;

                $rows[] = $tempRow;

                $count++;
            }
        }


        if (!$result->isEmpty()) {
            $response['error'] = false;
            $response['message'] = "Data Fetch Successfully";


            $response['total'] = $total;
            $response['data'] = $rows;
        } else {
            $response['error'] = false;
            $response['message'] = "No data found!";
            $response['data'] = [];
        }
        return response()->json($response);
    }
    public function getInterestedUsers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required_without:slug_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ]);
            }

            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;

            if (isset($request->slug_id)) {
                $property = Property::where('slug_id', $request->slug_id)->first();
                $property_id = $property->id;
            } else {
                $property_id = $request->property_id;
            }

            $interestedUserQuery = InterestedUser::has('customer')->with('customer:id,name,profile,email,mobile')->where('property_id', $property_id);
            $totalData = $interestedUserQuery->clone()->count();
            $interestedData = $interestedUserQuery->take($limit)->skip($offset)->get();
            if (collect($interestedData)->isNotEmpty()) {
                $data = $interestedData->pluck('customer');
                ApiResponseService::successResponse("Data Fetched Successfully", $data, ['total' => $totalData]);
            } else {
                ApiResponseService::validationError("No Data Found");
            }
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }
    public function post_project(Request $request)
    {
        if ($request->has('id')) {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'release_date' => 'nullable|date',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'title'         => 'required',
                'description'   => 'required',
                'image'         => 'required|file|mimes:jpeg,png,jpg',
                'category_id'   => 'required',
                'city'          => 'required',
                'state'         => 'required',
                'country'       => 'required',
                'release_date'  => 'nullable|date',
                'video_link' => ['nullable', 'url', function ($attribute, $value, $fail) {
                    // Regular expression to validate YouTube URLs
                    $youtubePattern = '/^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/';

                    if (!preg_match($youtubePattern, $value)) {
                        return $fail("The Video Link must be a valid YouTube URL.");
                    }

                    // Transform youtu.be short URL to full YouTube URL for validation
                    if (strpos($value, 'youtu.be') !== false) {
                        $value = 'https://www.youtube.com/watch?v=' . substr(parse_url($value, PHP_URL_PATH), 1);
                    }

                    // Get the headers of the URL
                    $headers = @get_headers($value);

                    // Check if the URL is accessible
                    if (!$headers || strpos($headers[0], '200') === false) {
                        return $fail("The Video Link must be accessible.");
                    }
                }]
            ]);
        }
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            DB::beginTransaction();
            if (!$request->id) {
                HelperService::updatePackageLimit('project_list');
            }
            $slugData = (isset($request->slug_id) && !empty($request->slug_id)) ? $request->slug_id : $request->title;

            $currentUser = Auth::user()->id;
            if (!(isset($request->id))) {
                $project = new Projects();

                // Set status to inactive (0) by default - will be activated only when admin approves
                $project->status = 0;
                
                // Set request_status to pending - waiting for admin approval
                $project->request_status = 'pending';
            } else {
                $project = Projects::where('added_by', $currentUser)->find($request->id);
                if (!$project) {
                    $response['error'] = false;
                    $response['message'] = 'Project Not Found ';
                }
                if (HelperService::getSettingData('auto_approve_edited_listings') == 0) {
                    $project->request_status = 'pending';
                }
            }

            if ($request->category_id) {
                $project->category_id = $request->category_id;
            }
            if ($request->description) {
                $project->description = $request->description;
            }
            // Save description_ar - convert empty string to null
            if ($request->has('description_ar')) {
                $project->description_ar = !empty(trim($request->description_ar)) ? $request->description_ar : null;
            }
            if ($request->area_description) {
                $project->area_description = $request->area_description;
            }
            // Save area_description_ar - convert empty string to null
            if ($request->has('area_description_ar')) {
                $project->area_description_ar = !empty(trim($request->area_description_ar)) ? $request->area_description_ar : null;
            }
            if ($request->location) {
                $project->location = $request->location;
            }
            if ($request->meta_title) {
                $project->meta_title = $request->meta_title;
            }
            if ($request->meta_description) {
                $project->meta_description = $request->meta_description;
            }
            if ($request->meta_keywords) {
                $project->meta_keywords = $request->meta_keywords;
            }
            $project->added_by = $currentUser;
            if ($request->country) {
                $project->country = $request->country;
            }
            if ($request->state) {
                $project->state = $request->state;
            }
            if ($request->city) {
                $project->city = $request->city;
            }
            if ($request->latitude) {
                $project->latitude = $request->latitude;
            }
            if ($request->longitude) {
                $project->longitude = $request->longitude;
            }
            if ($request->video_link) {
                $project->video_link = $request->video_link;
            }
            if ($request->type) {
                $project->type = $request->type;
            }
            if ($request->release_date) {
                $project->release_date = $request->release_date;
            }
            // Only save project details if they have values (not null/empty)
            if ($request->has('bedroom') && $request->bedroom !== null && $request->bedroom !== '') {
                $project->bedroom = $request->bedroom;
            }
            if ($request->has('bathroom') && $request->bathroom !== null && $request->bathroom !== '') {
                $project->bathroom = $request->bathroom;
            }
            if ($request->has('garage') && $request->garage !== null && $request->garage !== '') {
                $project->garage = $request->garage;
            }
            if ($request->has('year_built') && $request->year_built !== null && $request->year_built !== '') {
                $project->year_built = $request->year_built;
            }
            if ($request->has('lot_size') && $request->lot_size !== null && $request->lot_size !== '') {
                $project->lot_size = $request->lot_size;
            }
            if ($request->id) {
                if ($project->title !== $request->title) {
                    $title = !empty($request->title) ? $request->title : $project->title;
                    $project->title = $title;
                } else {
                    $title = $request->title;
                    $project->title = $title;
                }
                $project->slug_id = generateUniqueSlug($slugData, 4, null, $request->id);
                if ($request->hasFile('image')) {
                    $project->image = store_image($request->file('image'), 'PROJECT_TITLE_IMG_PATH');
                }

                if ($request->has('meta_image')) {
                    if ($request->meta_image != $project->meta_image) {
                        if (!empty($request->meta_image && $request->hasFile('meta_image'))) {
                            if (!empty($project->meta_image)) {
                                $url = $project->meta_image;
                                $relativePath = parse_url($url, PHP_URL_PATH);
                                if (file_exists(public_path()  . $relativePath)) {
                                    unlink(public_path()  . $relativePath);
                                }
                            }
                            $destinationPath = public_path('images') . config('global.PROJECT_SEO_IMG_PATH');
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            $profile = $request->file('meta_image');
                            $imageName = microtime(true) . "." . $profile->getClientOriginalExtension();
                            $profile->move($destinationPath, $imageName);
                            $project->meta_image = $imageName;
                        } else {
                            if (!empty($project->meta_image)) {
                                $url = $project->meta_image;
                                $relativePath = parse_url($url, PHP_URL_PATH);
                                if (file_exists(public_path()  . $relativePath)) {
                                    unlink(public_path()  . $relativePath);
                                }
                            }
                            $project->meta_image = null;
                        }
                    }
                }
                // if ($request->hasFile('meta_image')) {
                //     if ($project->meta_image) {
                //         unlink_image($project->meta_image);
                //     }
                //     $project->meta_image = store_image($request->file('meta_image'), 'PROJECT_SEO_IMG_PATH');
                // }else{
                //     if ($request->has('image')){
                //         if(!empty($request->meta_image)) {
                //             $url = $project->meta_image;
                //             $relativePath = parse_url($url, PHP_URL_PATH);
                //             if (file_exists(public_path()  . $relativePath)) {
                //                 unlink(public_path()  . $relativePath);
                //             }
                //         }
                //         $project->meta_image = null;
                //     }
                // }
            } else {
                $project->title = $request->title;
                // Save title_ar - convert empty string to null
                if ($request->has('title_ar')) {
                    $project->title_ar = !empty(trim($request->title_ar)) ? $request->title_ar : null;
                }
                $project->image = $request->hasFile('image') ? store_image($request->file('image'), 'PROJECT_TITLE_IMG_PATH') : '';
                $project->meta_image = $request->hasFile('meta_image') ? store_image($request->file('meta_image'), 'PROJECT_SEO_IMG_PATH') : '';
                $title = $request->title;
                $project->slug_id = generateUniqueSlug($slugData, 4);
            }
            
            // Save title_ar for update case - convert empty string to null
            if ($request->id && $request->has('title_ar')) {
                $project->title_ar = !empty(trim($request->title_ar)) ? $request->title_ar : null;
            }

            $project->save();

            if ($request->remove_gallery_images) {
                $remove_gallery_images = explode(',', $request->remove_gallery_images);
                foreach ($remove_gallery_images as $key => $value) {
                    $gallary_images = ProjectDocuments::find($value);
                    unlink_image($gallary_images->name);
                    $gallary_images->delete();
                }
            }

            if ($request->remove_documents) {
                $remove_documents = explode(',', $request->remove_documents);
                foreach ($remove_documents as $key => $value) {
                    $gallary_images = ProjectDocuments::find($value);
                    unlink_image($gallary_images->name);
                    $gallary_images->delete();
                }
            }

            if ($request->hasfile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    $gallary_image = new ProjectDocuments();
                    $gallary_image->name = store_image($file, 'PROJECT_DOCUMENT_PATH');
                    $gallary_image->project_id = $project->id;
                    $gallary_image->type = 'image';
                    $gallary_image->save();
                }
            }

            if ($request->hasfile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $project_documents = new ProjectDocuments();
                    $project_documents->name = store_image($file, 'PROJECT_DOCUMENT_PATH');
                    $project_documents->project_id = $project->id;
                    $project_documents->type = 'doc';
                    $project_documents->save();
                }
            }

            if ($request->plans) {
                foreach ($request->plans as $key => $plan) {
                    if (isset($plan['id']) && $plan['id'] != '') {
                        $project_plans =  ProjectPlans::find($plan['id']);
                    } else {
                        $project_plans = new ProjectPlans();
                    }
                    if (isset($plan['document'])) {
                        $project_plans->document = store_image($plan['document'], 'PROJECT_DOCUMENT_PATH');
                    }
                    $project_plans->title = $plan['title'];
                    $project_plans->project_id = $project->id;
                    $project_plans->save();
                }
            }


            if ($request->remove_plans) {
                $remove_plans = explode(',', $request->remove_plans);
                foreach ($remove_plans as $key => $value) {
                    $project_plans = ProjectPlans::find($value);
                    unlink_image($project_plans->document);
                    $project_plans->delete();
                }
            }
            $result = Projects::with('customer')->with('gallary_images')->with('documents')->with('plans')->with('category:id,category,image')->where('id', $project->id)->get();

            DB::commit();
            $response['error'] = false;
            $response['message'] = isset($request->id) ? 'Project Updated Successfully' : 'Project Post Succssfully';
            $response['data'] = $result;
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }
    public function delete_project(Request $request)
    {
        $current_user = Auth::user()->id;

        $validator = Validator::make($request->all(), [

            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        $project = Projects::where('added_by', $current_user)->with('gallary_images')->with('documents')->with('plans')->find($request->id);

        if ($project) {
            foreach ($project->gallary_images as $row) {
                if ($project->title_image != '') {
                    unlink_image($row->title_image);
                }
                $gallary_image = ProjectDocuments::find($row->id);
                if ($gallary_image) {
                    if ($row->name != '') {

                        unlink_image($row->name);
                    }
                }
            }

            foreach ($project->documents as $row) {

                $project_documents = ProjectDocuments::find($row->id);
                if ($project_documents) {
                    if ($row->name != '') {

                        unlink_image($row->name);
                    }
                    $project_documents->delete();
                }
            }
            foreach ($project->plans as $row) {

                $project_plans = ProjectPlans::find($row->id);
                if ($project_plans) {
                    if ($row->name != '') {

                        unlink_image($row->document);
                    }
                    $project_plans->delete();
                }
            }
            $project->delete();
            $response['error'] = false;
            $response['message'] =  'Project Delete Successfully';
        } else {
            $response['error'] = true;
            $response['message'] = 'Data not found';
        }
        return response()->json($response);
    }

    public function getUserPersonalisedInterest(Request $request)
    {
        try {
            // Get Current User's ID From Token
            $loggedInUserId = Auth::user()->id;
            $data = array();

            // Get User Interest Data on the basis of current User
            $userInterest = UserInterest::where('user_id', $loggedInUserId)->first();
            if (collect($userInterest)->isNotEmpty()) {
                // Get Data
                $categoriesIds = !empty($userInterest->category_ids) ? explode(',', $userInterest->category_ids) : '';
                $priceRange = $userInterest->property_type != null ? explode(',', $userInterest->price_range) : '';
                $propertyType = $userInterest->property_type == 0 || $userInterest->property_type == 1 ? explode(',', $userInterest->property_type) : '';
                $outdoorFacilitiesIds = !empty($userInterest->outdoor_facilitiy_ids) ? explode(',', $userInterest->outdoor_facilitiy_ids) : '';
                $city = !empty($userInterest->city) ?  $userInterest->city : '';
                // Custom Data Array
                $data = array(
                    'user_id'               => $loggedInUserId,
                    'category_ids'          => $categoriesIds,
                    'price_range'           => $priceRange,
                    'property_type'         => $propertyType,
                    'outdoor_facilitiy_ids' => $outdoorFacilitiesIds,
                    'city'                  => $city,
                );
            }
            $response = array(
                'error' => false,
                'data' => $data,
                'message' => 'Data fetched Successfully'
            );


            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    public function storeUserPersonalisedInterest(Request $request)
    {
        try {
            DB::beginTransaction();
            // Get Current User's ID From Token
            $loggedInUserId = Auth::user()->id;

            // Get User Interest
            $userInterest = UserInterest::where('user_id', $loggedInUserId)->first();

            // If data Exists then update or else insert new data
            if (collect($userInterest)->isNotEmpty()) {
                $response['error'] = false;
                $response['message'] = "Data Updated Successfully";
            } else {
                $userInterest = new UserInterest();
                $response['error'] = false;
                $response['message'] = "Data Store Successfully";
            }

            // Change the values
            $userInterest->user_id = $loggedInUserId;
            $userInterest->category_ids = (isset($request->category_ids) && !empty($request->category_ids)) ? $request->category_ids : "";
            $userInterest->outdoor_facilitiy_ids = (isset($request->outdoor_facilitiy_ids) && !empty($request->outdoor_facilitiy_ids)) ? $request->outdoor_facilitiy_ids : null;
            $userInterest->price_range = (isset($request->price_range) && !empty($request->price_range)) ? $request->price_range : "";
            $userInterest->city = (isset($request->city) && !empty($request->city)) ? $request->city : "";
            $userInterest->property_type = isset($request->property_type) && ($request->property_type == 0 || $request->property_type == 1) ? $request->property_type : "0,1";
            $userInterest->save();

            DB::commit();

            // Get Datas
            $categoriesIds = !empty($userInterest->category_ids) ? explode(',', $userInterest->category_ids) : '';
            $priceRange = !empty($userInterest->price_range) ? explode(',', $userInterest->price_range) : '';
            $propertyType = explode(',', $userInterest->property_type);
            $outdoorFacilitiesIds = !empty($userInterest->outdoor_facilitiy_ids) ? explode(',', $userInterest->outdoor_facilitiy_ids) : '';
            $city = !empty($userInterest->city) ?  $userInterest->city : '';

            // Custom Data Array
            $data = array(
                'user_id'               => $userInterest->user_id,
                'category_ids'          => $categoriesIds,
                'price_range'           => $priceRange,
                'property_type'         => $propertyType,
                'outdoor_facilitiy_ids' => $outdoorFacilitiesIds,
                'city'                  => $city,
            );
            $response['data'] = $data;

            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    public function deleteUserPersonalisedInterest(Request $request)
    {
        try {
            DB::beginTransaction();
            // Get Current User From Token
            $loggedInUserId = Auth::user()->id;

            // Get User Interest
            UserInterest::where('user_id', $loggedInUserId)->delete();
            DB::commit();
            $response = array(
                'error' => false,
                'message' => 'Data Deleted Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    public function removeAllPackages(Request $request)
    {
        try {
            DB::beginTransaction();

            $loggedInUserId = Auth::user()->id;

            // Cannot directly delete the payment transaction and user package because it has foreign key constraint
            $paymentTransaction = PaymentTransaction::where('user_id', $loggedInUserId)->get();
            foreach ($paymentTransaction as $transaction) {
                $transaction->bank_receipt_files()->delete();
                $transaction->delete();
            }
            $userPackage = UserPackage::where('user_id', $loggedInUserId)->get();
            foreach ($userPackage as $package) {
                $package->delete();
            }

            DB::commit();
            $response = array(
                'error' => false,
                'message' => 'Data Deleted Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }


    public function getAddedProperties(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_type' => 'nullable|in:0,1,2,3',
                'is_promoted' => 'nullable|in:1',
                'slug_id' => 'nullable|string',
                'property_classification' => 'nullable|integer|in:1,2,3,4,5',
                'search' => 'nullable|string',
                'search_type' => 'nullable|in:property_name,reference_id',
                'offset' => 'nullable|integer|min:0',
                'limit' => 'nullable|integer|min:1|max:1000'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            
            // Get Offset and Limit from payload request
            $offset = isset($request->offset) && $request->offset !== '' ? (int)$request->offset : 0;
            $limit = isset($request->limit) && $request->limit !== '' ? (int)$request->limit : 10;

            // Get Logged In User data
            $loggedInUserData = Auth::user();
            if (!$loggedInUserData) {
                return response()->json([
                    'error' => true,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }
            
            // Get Current Logged In User ID
            $loggedInUserID = $loggedInUserData->id;

            // when is_promoted is passed then show only property who has been featured (advertised)
            // Handle empty string as false
            $isPromoted = $request->has('is_promoted') && $request->is_promoted !== '' && $request->is_promoted != '0' && (int)$request->is_promoted == 1;
            
            if ($isPromoted) {
                // Create Advertisement Query which has Property Data
                $advertisementQuery = Advertisement::whereHas('property', function ($query) use ($loggedInUserID) {
                    $query->where(['post_type' => 1, 'added_by' => $loggedInUserID]);
                })->with('property:id,category_id,slug_id,title,propery_type,city,state,country,price,title_image', 'property.category:id,category,image');

                // Get Total Advertisement Data
                $advertisementTotal = $advertisementQuery->clone()->count();

                // Get Advertisement Data with custom Data
                $advertisementData = $advertisementQuery->clone()->skip($offset)->take($limit)->orderBy('id', 'DESC')->get()->map(function ($advertisement) {
                    if (collect($advertisement->property)->isNotEmpty()) {
                        $otherData = array();
                        $otherData['id'] = $advertisement->property->id;
                        $otherData['slug_id'] = $advertisement->property->slug_id;
                        $otherData['property_type'] = $advertisement->property->propery_type;
                        $otherData['title'] = $advertisement->property->title;
                        $otherData['city'] = $advertisement->property->city;
                        $otherData['state'] = $advertisement->property->state;
                        $otherData['country'] = $advertisement->property->country;
                        $otherData['price'] = $advertisement->property->price;
                        $otherData['title_image'] = $advertisement->property->title_image;
                        $otherData['advertisement_id'] = $advertisement->id;
                        $otherData['advertisement_status'] = $advertisement->status;
                        $otherData['advertisement_type'] = $advertisement->type;
                        $otherData['category'] = $advertisement->property->category;
                        unset($advertisement); // remove the original data
                        return $otherData; // return custom created data
                    }
                });
                $response = array(
                    'error' => false,
                    'data' => $advertisementData,
                    'total' => $advertisementTotal,
                    'message' => 'Data fetched Successfully'
                );
            } else {
                // Check the property's post is done by customer and added by logged in user
                $propertyQuery = Property::where(['post_type' => 1, 'added_by' => $loggedInUserID])
                    // When property type is passed in payload show data according property type that is sell or rent
                    ->when($request->filled('property_type'), function ($query) use ($request) {
                        return $query->where('propery_type', $request->property_type);
                    })
                    ->when($request->has('property_classification') && $request->property_classification !== '' && $request->property_classification !== null, function ($query) use ($request) {
                        return $query->where('property_classification', (int)$request->property_classification);
                    })
                    ->when($request->filled('id'), function ($query) use ($request) {
                        return $query->where('id', $request->id);
                    })
                    ->when($request->filled('slug_id'), function ($query) use ($request) {
                        return $query->where('slug_id', $request->slug_id);
                    })
                    ->when($request->filled('search') && $request->filled('search_type'), function ($query) use ($request) {
                        $search = $request->search;
                        $searchType = $request->search_type;
                        
                        if ($searchType === 'reference_id') {
                            // Search by reference ID (property ID)
                            return $query->where('id', 'LIKE', "%{$search}%");
                        } else {
                            // Search by property name
                            return $query->where(function($q) use ($search) {
                                $q->where('title', 'LIKE', "%{$search}%")
                                  ->orWhere('title_ar', 'LIKE', "%{$search}%")
                                  ->orWhere('address', 'LIKE', "%{$search}%");
                            });
                        }
                    })
                    ->when($request->filled('status'), function ($query) use ($request) {
                        // IF Status is passed and status has active (1) or deactive (0) or both
                        $statusData = explode(',', $request->status);
                        return $query->whereIn('status', $statusData)->where('request_status', 'approved');
                    })
                    ->when($request->filled('request_status'), function ($query) use ($request) {
                        // IF Request Status is passed and status has approved or rejected or pending or all
                        $requestAccessData = explode(',', $request->request_status);
                        return $query->whereIn('request_status', $requestAccessData);
                    })

                    // Pass the Property Data with Category and Advertisement Relation Data
                    ->with([
                        'category', 
                        'advertisement', 
                        'interested_users:id,property_id,customer_id', 
                        'interested_users.customer:id,name,profile',
                        'vacationApartments' => function($query) {
                            $query->orderBy('apartment_number');
                        },
                        'hotel_rooms' => function($query) {
                            $query->select('id', 'property_id', 'room_type_id', 'custom_room_type', 'room_number', 'price_per_night', 'discount_percentage', 'nonrefundable_percentage', 'refund_policy', 'description', 'status', 'availability_type', 'available_dates', 'weekend_commission', 'min_guests', 'max_guests', 'base_guests', 'guest_pricing_rules', 'available_rooms');
                        }
                    ]);

                // Get Total Views by Sum of total click of each property
                $totalViews = $propertyQuery->sum('total_click');

                // Get total properties
                $totalProperties = $propertyQuery->count();

                // Get the property data with extra data and changes :- is_premium, post_created and promoted
                $propertyData = $propertyQuery->skip($offset)->take($limit)->orderBy('id', 'DESC')->get()->map(function ($property) use ($loggedInUserData) {
                    // Add lastest Reject reason when request status is rejected
                    $property->reject_reason = (object)array();
                    if ($property->request_status == 'rejected' && method_exists($property, 'reject_reason')) {
                        try {
                            $property->reject_reason = $property->reject_reason()->latest()->first();
                        } catch (\Exception $e) {
                            $property->reject_reason = (object)array();
                        }
                    }
                    $property->is_premium = $property->is_premium == 1 ? true : false;
                    $property->property_type = $property->propery_type;
                    $property->post_created = $property->created_at->diffForHumans();
                    $property->promoted = $property->is_promoted;
                    $property->parameters = $property->parameters;
                    $property->assign_facilities = $property->assign_facilities;
                    $property->is_feature_available = $property->is_feature_available;
                    
                    // Include vacation apartments if property classification is 4
                    if ($property->property_classification == 4) {
                        $property->vacation_apartments = $property->vacationApartments ?? [];
                    } else {
                        $property->vacation_apartments = [];
                    }

                    // Interested Users
                    $interestedUsers = $property->interested_users ?? collect([]);
                    unset($property->interested_users);
                    $property->interested_users = $interestedUsers->map(function ($interestedUser) {
                        if (!$interestedUser || !$interestedUser->customer) {
                            return null;
                        }
                        return $interestedUser->customer;
                    })->filter();

                    // Add User's Details
                    $property->customer_name = $loggedInUserData->name;
                    $property->email = $loggedInUserData->email;
                    $property->mobile = $loggedInUserData->mobile;
                    $property->profile = $loggedInUserData->profile;
                    return $property;
                });

                $response = array(
                    'error' => false,
                    'data' => $propertyData,
                    'total' => $totalProperties,
                    'total_views' => $totalViews,
                    'message' => 'Data fetched Successfully'
                );

                if ($request->has('id')) {
                    $getSimilarPropertiesQueryData = Property::where(['post_type' => 1, 'added_by' => $loggedInUserID])->where('id', '!=', $request->id)->select('id', 'slug_id', 'category_id', 'title', 'added_by', 'address', 'city', 'country', 'state', 'propery_type', 'price', 'created_at', 'title_image', 'title_ar', 'description_ar', 'area_description_ar', 'area_description', 'company_employee_username', 'company_employee_email', 'company_employee_phone_number', 'company_employee_whatsappnumber')->orderBy('id', 'desc')->limit(10)->get();

                    $getSimilarProperties = get_property_details($getSimilarPropertiesQueryData, $loggedInUserData);
                } else if ($request->has('slug_id')) {
                    $getSimilarPropertiesQueryData = Property::where(['post_type' => 1, 'added_by' => $loggedInUserID])->where('slug_id', '!=', $request->slug_id)->select('id', 'slug_id', 'category_id', 'title', 'added_by', 'address', 'city', 'country', 'state', 'propery_type', 'price', 'created_at', 'title_image', 'instant_booking', 'non_refundable', 'title_ar', 'description_ar', 'area_description_ar', 'area_description', 'company_employee_username', 'company_employee_email', 'company_employee_phone_number', 'company_employee_whatsappnumber')->orderBy('id', 'desc')->limit(10)->get();
                    $getSimilarProperties = get_property_details($getSimilarPropertiesQueryData, $loggedInUserData);
                } else {
                    $getSimilarProperties = array();
                }
                if ($getSimilarProperties) {
                    $response['similiar_properties'] = $getSimilarProperties;
                }
            }
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }


    /**
     * Homepage Data API
     * Params :- None
     */
    public function homepageData(Request $request)
    {
        try {
            // Handle empty strings - convert to null
            $latitude = $request->has('latitude') && $request->latitude !== '' ? $request->latitude : null;
            $longitude = $request->has('longitude') && $request->longitude !== '' ? $request->longitude : null;
            $radius = $request->has('radius') && $request->radius !== '' ? $request->radius : null;
            
            // Validate latitude/longitude are numeric if provided
            if ($latitude !== null && (!is_numeric($latitude) || $latitude == 0)) {
                $latitude = null;
            }
            if ($longitude !== null && (!is_numeric($longitude) || $longitude == 0)) {
                $longitude = null;
            }
            if ($radius !== null && (!is_numeric($radius) || $radius <= 0)) {
                $radius = null;
            }
            
            $homepageLocationDataAvailable = false;

            // Create reusable property mapper function
            $propertyMapper = function ($propertyData) {
                $propertyData->promoted = $propertyData->is_promoted;
                $propertyData->property_type = $propertyData->propery_type;
                $propertyData->parameters = $propertyData->parameters;
                $propertyData->is_premium = $propertyData->is_premium == 1;
                return $propertyData;
            };

            // Base property query that will be reused
            $propertyBaseQuery = Property::select(
                'id',
                'slug_id',
                'category_id',
                'city',
                'state',
                'country',
                'price',
                'propery_type',
                'title',
                'title_ar',
                'title_image',
                'is_premium',
                'address',
                'rentduration',
                'latitude',
                'longitude'
            )
                ->with('category:id,slug_id,image,category')
                ->where(['status' => 1, 'request_status' => 'approved'])
                ->whereIn('propery_type', [0, 1]);
            if ($latitude && $longitude) {
                if ($radius) {
                    // Create a query with Haversine formula
                    $latlongBasedQuery = $propertyBaseQuery->clone()
                        ->selectRaw("
                            (6371 * acos(cos(radians($latitude))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians($longitude))
                            + sin(radians($latitude))
                            * sin(radians(latitude)))) AS distance")
                        ->where('latitude', '!=', 0)
                        ->where('longitude', '!=', 0)
                        ->having('distance', '<', $radius);
                } else {
                    $latlongBasedQuery = $propertyBaseQuery->clone()->where(['latitude' => $latitude, 'longitude' => $longitude]);
                }

                $count = $latlongBasedQuery->clone()->count();
                if ($count > 0) {
                    $homepageLocationDataAvailable = true;
                    $locationBasedPropertyQuery = $latlongBasedQuery;
                } else {
                    $locationBasedPropertyQuery = $propertyBaseQuery;
                    $homepageLocationDataAvailable = false;
                }
            } else {
                $locationBasedPropertyQuery = $propertyBaseQuery;
                $homepageLocationDataAvailable = false;
            }

            // Base projects query that will be reused
            $projectsBaseQuery = Projects::select(
                'id',
                'slug_id',
                'city',
                'state',
                'country',
                'title',
                'type',
                'image',
                'location',
                'category_id',
                'added_by',
                'latitude',
                'longitude'
            )
                ->where(['request_status' => 'approved', 'status' => 1])
                ->with([
                    'category:id,slug_id,image,category',
                    'gallary_images:id,project_id,name',
                    'customer:id,name,profile,email,mobile'
                ]);
            if ($latitude && $longitude) {
                $latlongBasedQuery = $projectsBaseQuery->clone()->where(['latitude' => $latitude, 'longitude' => $longitude]);
                $count = $latlongBasedQuery->clone()->count();
                if ($count > 0 || $homepageLocationDataAvailable == true) {
                    $homepageLocationDataAvailable = true;
                    $projectsBaseQuery = $latlongBasedQuery;
                } else {
                    $projectsBaseQuery = $projectsBaseQuery;
                    $homepageLocationDataAvailable = false;
                }
            } else {
                $count = $projectsBaseQuery->clone()->count();
                $homepageLocationDataAvailable = false;
            }

            // Get homepage sections
            $sections = $this->getHomepageSections($latitude, $longitude, $propertyBaseQuery, $propertyMapper, $projectsBaseQuery, $homepageLocationDataAvailable, $locationBasedPropertyQuery);

            // Add slider data (not controlled by homepage sections)
            $slidersData = Slider::select('id', 'type', 'image', 'web_image', 'category_id', 'propertys_id', 'show_property_details', 'link')
                ->with([
                    'category' => function ($query) {
                        $query->where('status', 1)->select('id', 'slug_id', 'category');
                    },
                    'property' => function ($query) {
                        $query->whereIn('propery_type', [0, 1])
                            ->where(['status' => 1, 'request_status' => 'approved'])
                            ->select('id', 'slug_id', 'title', 'title_image', 'price', 'propery_type');
                    }
                ])
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($slider) {
                    $slider->slider_type = $slider->getRawOriginal('type');

                    // Filter out entries that don't meet our criteria
                    if ($slider->getRawOriginal('type') == 2 && empty($slider->category)) {
                        return null;
                    }

                    if ($slider->getRawOriginal('type') == 3) {
                        if (empty($slider->property)) {
                            return null;
                        }
                        $slider->property->parameters = $slider->property->parameters;
                    }

                    return $slider;
                })
                ->filter()
                ->values();

            $data = [
                'sections' => $sections,
                'slider_section' => $slidersData,
                'homepage_location_data_available' => $homepageLocationDataAvailable == true ? true : false,
            ];
            return ApiResponseService::successResponseReturn("Data fetched Successfully", $data);
        } catch (Exception $e) {
            // Log the actual error with full details
            \Log::error('homepageData failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'radius' => $request->input('radius')
            ]);
            
            ApiResponseService::logErrorResponse($e, $e->getMessage(), 'Failed to fetch homepage data: ' . $e->getMessage());
            
            // Return JSON response instead of using errorResponse() which calls exit()
            return response()->json([
                'error' => true,
                'message' => 'Failed to fetch homepage data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Agent List API
     * Params :- limit and offset
     */
    public function getAgentList(Request $request)
    {
        try {
            // Get Offset and Limit from payload request
            $offset = isset($request->offset) ? $request->offset : 0;

            // if there is limit in request then have to do less by one so that to manage total data count with admin
            $limit = isset($request->limit) && !empty($request->limit) ? ($request->limit - 1) : 10;

            $latitude = $request->has('latitude') ? $request->latitude : null;
            $longitude = $request->has('longitude') ? $request->longitude : null;


            if (!empty($request->limit)) {
                $agentsListQuery = Customer::select('id', 'name', 'email', 'profile', 'slug_id')->where(function ($query) {
                    $query->where('isActive', 1);
                })
                    ->where(function ($query) use ($latitude, $longitude) {
                        $query->whereHas('projects', function ($query) use ($latitude, $longitude) {
                            $query->where('status', 1)->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                                $query->where('latitude', $latitude)->where('longitude', $longitude);
                            });
                        })->orWhereHas('property', function ($query) use ($latitude, $longitude) {
                            $query->where(['status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                                $query->where('latitude', $latitude)->where('longitude', $longitude);
                            });
                        });
                    })
                    ->withCount([
                        'projects' => function ($query) use ($latitude, $longitude) {
                            $query->where('status', 1)->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                                $query->where('latitude', $latitude)->where('longitude', $longitude);
                            });
                        },
                        'property' => function ($query) use ($latitude, $longitude) {
                            $query->where(['status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                                $query->where('latitude', $latitude)->where('longitude', $longitude);
                            });
                        }
                    ]);

                $agentListCount = $agentsListQuery->clone()->count();

                $agentListData = $agentsListQuery->clone()
                    ->get()
                    ->map(function ($customer) {
                        $customer->is_verified = $customer->is_user_verified;
                        $customer->total_count = $customer->projects_count + $customer->property_count;
                        $customer->is_admin = false;
                        return $customer;
                    })
                    ->filter(function ($customer) {
                        return $customer->projects_count > 0 || $customer->property_count > 0;
                    })
                    ->sortByDesc(function ($customer) {
                        return [$customer->is_verified, $customer->total_count];
                    })
                    ->skip($offset)
                    ->take($limit)
                    ->values(); // This line resets the array keys




                // Get admin List

                $adminEmail = system_setting('company_email');
                $adminData = array();
                $adminPropertiesCount = Property::where(['added_by' => 0, 'status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                    $query->where('latitude', $latitude)->where('longitude', $longitude);
                })->count();
                $adminProjectsCount = Projects::where(['is_admin_listing' => 1, 'status' => 1, 'request_status' => 'approved'])->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                    $query->where('latitude', $latitude)->where('longitude', $longitude);
                })->count();
                $totalCount = $adminPropertiesCount + $adminProjectsCount;

                $adminData = User::where('type', 0)->select('id', 'name', 'profile')->first();

                $adminQuery = User::where('type', 0)->select('id', 'slug_id')->first();
                if ($adminQuery && ($adminPropertiesCount > 0 || $adminProjectsCount > 0)) {
                    $adminData = array(
                        'id' => $adminQuery->id,
                        'name' => 'Admin',
                        'slug_id' => $adminQuery->slug_id,
                        'email' => !empty($adminEmail) ? $adminEmail : "",
                        'property_count' => $adminPropertiesCount,
                        'projects_count' => $adminProjectsCount,
                        'total_count' => $totalCount,
                        'is_verified' => true,
                        'profile' => !empty($adminData->getRawOriginal('profile')) ? $adminData->profile : url('assets/images/faces/2.jpg'),
                        'is_admin' => true
                    );
                    if ($offset == 0) {
                        $agentListData->prepend((object) $adminData);
                    }
                }
            }
            $response = array(
                'error' => false,
                'total' => $agentListCount ?? 0,
                'data' => $agentListData ?? array(),
                'message' => 'Data fetched Successfully'
            );

            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    /**
     * Agent Properties API
     * Params :- id or slug_id, limit, offset and is_project
     */
    public function getAgentProperties(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug_id' => 'required_without_all:id,is_admin',
            'is_projects' => 'nullable|in:1',
            'is_admin' => 'nullable|in:1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            $response = array(
                'package_available' => false,
                'feature_available' => false,
                'limit_available' => false,
            );
            // Get Limit Status of premium properties feature
            if (Auth::guard('sanctum')) {
                $response = HelperService::checkPackageLimit('premium_properties', true);
            }
            // Get Offset and Limit from payload request
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;
            $isAdminListing = false;

            if ($request->has('is_admin') && $request->is_admin == 1) {
                $addedBy = 0;
                $isAdminListing = true;
                $adminEmail = system_setting('company_email');
                $adminCompanyTel1 = system_setting('company_tel1');
                $customerData = array();
                $adminPropertiesCount = Property::where(['added_by' => 0, 'status' => 1, 'request_status' => 'approved'])->count();
                $adminProjectsCount = Projects::where(['is_admin_listing' => 1, 'status' => 1])->count();
                $totalCount = $adminPropertiesCount + $adminProjectsCount;

                $adminData = User::where('type', 0)->select('id', 'name', 'profile')->first();

                $adminQuery = User::where('type', 0)->select('id', 'slug_id')->first();
                if ($adminQuery) {
                    $customerData = array(
                        'id' => $adminQuery->id,
                        'name' => 'Admin',
                        'slug_id' => $adminQuery->slug_id,
                        'email' => !empty($adminEmail) ? $adminEmail : "",
                        'mobile' => !empty($adminCompanyTel1) ? $adminCompanyTel1 : "",
                        'property_count' => $adminPropertiesCount,
                        'projects_count' => $adminProjectsCount,
                        'total_count' => $totalCount,
                        'is_verify' => true,
                        'profile' => !empty($adminData->getRawOriginal('profile')) ? $adminData->profile : url('assets/images/faces/2.jpg')
                    );
                }
            } else {
                // Customer Query
                $customerQuery = Customer::select('id', 'slug_id', 'name', 'profile', 'mobile', 'email', 'address', 'city', 'country', 'state', 'facebook_id', 'twiiter_id as twitter_id', 'youtube_id', 'instagram_id', 'about_me', 'latitude', 'longitude')->where(function ($query) {
                    $query->where('isActive', 1);
                })->withCount(['projects' => function ($query) {
                    $query->where('status', 1);
                }, 'property' => function ($query) use ($response) {
                    if ($response['package_available'] == true && $response['feature_available'] == true) {
                        $query->where(['status' => 1, 'request_status' => 'approved']);
                    } else {
                        $query->where(['status' => 1, 'request_status' => 'approved', 'is_premium' => 0]);
                    }
                }]);
                // Check if id exists or slug id on the basis of get agent id
                if ($request->has('id') && !empty($request->id)) {
                    $addedBy = $request->id;
                    // Get Customer Data
                    $customerData = $customerQuery->clone()->where('id', $request->id)->first();
                    $addedBy = !empty($customerData) ? $customerData->id : "";
                } else if ($request->has('slug_id')) {
                    // Get Customer Data
                    $customerData = $customerQuery->clone()->where('slug_id', $request->slug_id)->first();
                    $addedBy = !empty($customerData) ? $customerData->id : "";
                }
                // Add Is User Verified Status in Customer Data
                !empty($customerData) ? $customerData->is_verify = $customerData->is_user_verified : "";
            }

            // if there is agent id then only get properties of it
            if (!empty($addedBy) || $addedBy == 0) {

                if (($request->has('is_projects') && !empty($request->is_projects) && $request->is_projects == 1)) {
                    $response = HelperService::checkPackageLimit('project_access', true);
                    $projectQuery = Projects::select('id', 'slug_id', 'city', 'state', 'country', 'title', 'type', 'image', 'location', 'category_id', 'added_by');
                    if ($isAdminListing == true) {
                        $projectQuery = $projectQuery->clone()->where(['status' => 1, 'is_admin_listing' => 1]);
                    } else {
                        $projectQuery = $projectQuery->clone()->where(['status' => 1, 'request_status' => 'approved', 'added_by' => $addedBy]);
                    }
                    $totalProjects = $projectQuery->clone()->count();
                    $totalData = $totalProjects;
                    if ($response['package_available'] == true && $response['feature_available'] == true) {
                        $projectData = $projectQuery->clone()->with('gallary_images', 'category:id,slug_id,image,category')->skip($offset)->take($limit)->get();
                    }
                } else {
                    // Create a proeprty query
                    $propertiesQuery = Property::select('id', 'slug_id', 'city', 'state', 'category_id', 'country', 'price', 'propery_type', 'title', 'title_ar', 'title_image', 'is_premium', 'address', 'added_by')
                        ->where(['status' => 1, 'request_status' => 'approved', 'added_by' => $addedBy]);

                    // Count total properties
                    $totalProperties = $propertiesQuery->clone()
                        ->when(($response['feature_available'] == false), function ($query) {
                            $query->where('is_premium', 0);
                        })->count();

                    // Count premium properties without the condition
                    $premiumPropertiesCount = Property::where(['status' => 1, 'request_status' => 'approved', 'added_by' => $addedBy, 'is_premium' => 1])->count();

                    // Get Propertis Data
                    $propertiesData = $propertiesQuery->clone()
                        ->when(($response['feature_available'] == false), function ($query) {
                            $query->where('is_premium', 0);
                        })
                        ->with('category:id,slug_id,image,category')
                        ->orderBy('is_premium', 'DESC')->skip($offset)->take($limit)->get()->map(function ($property) {
                            $property->property_type = $property->propery_type;
                            $property->parameters = $property->parameters;
                            $property->promoted = $property->is_promoted;
                            unset($property->propery_type);
                            return $property;
                        });
                    $totalData = $totalProperties;
                }
            }

            $response = array(
                'error'                     => false,
                'total'                     => $totalData ?? 0,
                'data' => array(
                    'customer_data'             => $customerData ?? array(),
                    'properties_data'           => $propertiesData ?? array(),
                    'projects_data'             => $projectData ?? array(),
                    'premium_properties_count'  => $premiumPropertiesCount ?? 0,
                    'package_available'         => $response['package_available'],
                    'feature_available'         => $response['feature_available'],

                ),
                'message' => 'Data fetched Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }


    public function getWebSettings(Request $request)
    {
        try {
            // Types for web requirement only
            $types = array('company_name', 'currency_symbol', 'default_language', 'number_with_suffix', 'web_maintenance_mode', 'company_tel', 'company_tel2', 'system_version', 'web_favicon', 'web_logo', 'web_footer_logo', 'web_placeholder_logo', 'company_email', 'latitude', 'longitude', 'company_address', 'system_color', 'svg_clr', 'iframe_link', 'facebook_id', 'instagram_id', 'twitter_id', 'youtube_id', 'playstore_id', 'sell_background', 'appstore_id', 'category_background', 'web_maintenance_mod', 'seo_settings', 'company_tel1', 'place_api_key', 'paystack_public_key', 'sell_web_color', 'sell_web_background_color', 'rent_web_color', 'rent_web_background_color', 'about_us', 'terms_conditions', 'privacy_policy', 'number_with_otp_login', 'social_login', 'distance_option', 'otp_service_provider', 'text_property_submission', 'auto_approve', 'verification_required_for_user', 'allow_cookies', 'currency_code', 'bank_details', 'schema_for_deeplink', 'min_radius_range', 'max_radius_range', 'google_analytics_id');

            // Query the Types to Settings Table to get its data
            $result =  Setting::select('type', 'data')->whereIn('type', $types)->get();

            // Check the result data is not empty
            if (collect($result)->isNotEmpty()) {
                $settingsData = array();

                // Loop on the result data
                foreach ($result as $row) {
                    // Change data according to conditions
                    if ($row->type == 'company_logo') {
                        // Add logo image with its url
                        $settingsData[$row->type] = url('/assets/images/logo/logo.png');
                    } else if ($row->type == 'seo_settings') {
                        // Change Value to Bool
                        $settingsData[$row->type] = $row->data == 1 ? true : false;
                    } else if ($row->type == 'allow_cookies') {
                        // Change Value to Bool
                        $settingsData[$row->type] = $row->data == 1 ? true : false;
                    } else if ($row->type == 'verification_required_for_user') {
                        // Change Value to Bool
                        $settingsData[$row->type] = $row->data == 1 ? true : false;
                    } else if ($row->type == 'web_favicon' || $row->type == 'web_logo' || $row->type == 'web_placeholder_logo' || $row->type == 'web_footer_logo') {
                        // Add Full URL to the specified type
                        // Check if file exists, otherwise use default logo
                        $logoPath = public_path('assets/images/logo/' . $row->data);
                        if (!empty($row->data) && file_exists($logoPath)) {
                            $settingsData[$row->type] = url('/assets/images/logo/') . '/' . $row->data;
                        } else {
                            // Fallback to default logo if file doesn't exist
                            $settingsData[$row->type] = url('/assets/images/logo/logo.png');
                        }
                    } else if ($row->type == 'place_api_key') {
                        // Add Full URL to the specified type
                        $publicKeyPath = base_path('public_key.pem');
                        if (file_exists($publicKeyPath)) {
                            try {
                                $publicKey = file_get_contents($publicKeyPath);
                                $encryptedData = '';
                                if (openssl_public_encrypt($row->data, $encryptedData, $publicKey)) {
                                    $settingsData[$row->type] = base64_encode($encryptedData);
                                } else {
                                    $settingsData[$row->type] = "";
                                }
                            } catch (\Exception $e) {
                                \Log::warning('Failed to encrypt place_api_key', ['error' => $e->getMessage()]);
                                $settingsData[$row->type] = "";
                            }
                        } else {
                            \Log::warning('public_key.pem file not found');
                            $settingsData[$row->type] = "";
                        }
                    } else if ($row->type == 'currency_code') {
                        // Change Value to Bool
                        $settingsData['selected_currency_data'] = HelperService::getCurrencyData($row->data);
                    } else if ($row->type == 'bank_details') {
                        // Change Value to Bool
                        $settingsData['bank_details'] = json_decode($row->data, true);
                    } else {
                        // add the data as it is in array
                        $settingsData[$row->type] = $row->data;
                    }
                }

                // Get admin user - type = 0 means admin, fallback to first user or default
                try {
                    // Users table has 'type' column: 0 = Admin, 1 = Users
                    $user_data = User::where('type', 0)->first() ?? User::first();
                    if ($user_data) {
                        $settingsData['admin_name'] = $user_data->name ?? 'Admin';
                        $settingsData['admin_image'] = !empty($user_data->profile) 
                            ? url('/') . config('global.IMG_PATH') . config('global.ADMIN_PROFILE_IMG_PATH') . $user_data->profile
                            : url('/assets/images/faces/2.jpg');
                    } else {
                        $settingsData['admin_name'] = 'Admin';
                        $settingsData['admin_image'] = url('/assets/images/faces/2.jpg');
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to get admin user', ['error' => $e->getMessage()]);
                    $settingsData['admin_name'] = 'Admin';
                    $settingsData['admin_image'] = url('/assets/images/faces/2.jpg');
                }
                $settingsData['demo_mode'] = env('DEMO_MODE');
                $settingsData['img_placeholder'] = url('/assets/images/placeholder.svg');

                // if Token is passed of current user.
                if (collect(Auth::guard('sanctum')->user())->isNotEmpty()) {
                    $loggedInUserId = Auth::guard('sanctum')->user()->id;
                    update_subscription($loggedInUserId);

                    $checkVerifiedStatus = VerifyCustomer::where('user_id', $loggedInUserId)->first();
                    if (!empty($checkVerifiedStatus)) {
                        $settingsData['verification_status'] = $checkVerifiedStatus->status;
                    } else {
                        $settingsData['verification_status'] = 'initial';
                    }

                    $customerDataQuery = Customer::select('id', 'subscription', 'is_premium', 'isActive');
                    $customerData = $customerDataQuery->clone()->find($loggedInUserId);

                    // Check Active of current User
                    if (collect($customerData)->isNotEmpty()) {
                        $settingsData['is_active'] = $customerData->isActive == 1 ? true : false;
                    } else {
                        $settingsData['is_active'] = false;
                    }

                    // Check the subscription
                    if (collect($customerData)->isNotEmpty()) {
                        $settingsData['is_premium'] = $customerData->is_premium == 1 ? true : ($customerData->subscription == 1 ? true : false);
                        $settingsData['subscription'] = $customerData->subscription == 1 ? true : false;
                    } else {
                        $settingsData['is_premium'] = false;
                        $settingsData['subscription'] = false;
                    }
                }


                // Check the min_price and max_price
                $settingsData['min_price'] = DB::table('propertys')->selectRaw('MIN(price) as min_price')->value('min_price');
                $settingsData['max_price'] = DB::table('propertys')->selectRaw('MAX(price) as max_price')->value('max_price');

                // Check the features available
                $settingsData['features_available'] = array(
                    'premium_properties' => HelperService::checkPackageLimit('premium_properties', true)['feature_available'],
                    'project_access' => HelperService::checkPackageLimit('project_access', true)['feature_available'],
                );

                // Get Languages Data
                $language = Language::select('id', 'code', 'name')->get();
                $settingsData['languages'] = $language;

                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                $response['data'] = $settingsData;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }


    public function getAppSettings(Request $request)
    {
        try {
            $types = array('company_name', 'currency_symbol', 'ios_version', 'default_language', 'force_update', 'android_version', 'number_with_suffix', 'maintenance_mode', 'company_tel1', 'company_tel2', 'company_email', 'company_address', 'place_api_key', 'svg_clr', 'playstore_id', 'sell_background', 'appstore_id', 'show_admob_ads', 'android_banner_ad_id', 'ios_banner_ad_id', 'android_interstitial_ad_id', 'ios_interstitial_ad_id', 'android_native_ad_id', 'ios_native_ad_id', 'demo_mode', 'min_price', 'max_price', 'privacy_policy', 'terms_conditions', 'about_us', 'number_with_otp_login', 'social_login', 'distance_option', 'otp_service_provider', 'app_home_screen', 'placeholder_logo', 'light_tertiary', 'light_secondary', 'light_primary', 'dark_tertiary', 'dark_secondary', 'dark_primary', 'text_property_submission', 'auto_approve', 'verification_required_for_user', 'currency_code', 'bank_details', 'schema_for_deeplink', 'min_radius_range', 'max_radius_range', 'latitude', 'longitude');

            // Query the Types to Settings Table to get its data
            $result =  Setting::select('type', 'data')->whereIn('type', $types)->get();

            // Check the result data is not empty
            if (collect($result)->isNotEmpty()) {
                $settingsData = array();

                // Loop on the result data
                foreach ($result as $row) {
                    if ($row->type == "place_api_key") {
                        $publicKey = file_get_contents(base_path('public_key.pem')); // Load the public key
                        $encryptedData = '';
                        if (openssl_public_encrypt($row->data, $encryptedData, $publicKey)) {
                            $settingsData[$row->type] = base64_encode($encryptedData);
                        }
                    } else if ($row->type == 'default_language') {
                        // Add Code in Data
                        $settingsData[$row->type] = $row->data;

                        // Add Default language's name
                        $languageData = Language::where('code', $row->data)->first();
                        if (collect($languageData)->isNotEmpty()) {
                            $settingsData['default_language_name'] = $languageData->name;
                            $settingsData['default_language_rtl'] = $languageData->rtl == 1 ? 1 : 0;
                        } else {
                            $settingsData['default_language_name'] = "";
                            $settingsData['default_language_rtl'] = 0;
                        }
                    } else if ($row->type == 'app_home_screen' || $row->type == "placeholder_logo") {
                        // Check if file exists, otherwise use default logo
                        $logoPath = public_path('assets/images/logo/' . $row->data);
                        if (!empty($row->data) && file_exists($logoPath)) {
                            $settingsData[$row->type] = url('/assets/images/logo/') . '/' . $row->data;
                        } else {
                            // Fallback to default logo if file doesn't exist
                            $settingsData[$row->type] = url('/assets/images/logo/logo.png');
                        }
                    } else if ($row->type == 'verification_required_for_user') {
                        // Change Value to Bool
                        $settingsData[$row->type] = $row->data == 1 ? true : false;
                    } else if ($row->type == 'currency_code') {
                        // Change Value to Bool
                        $settingsData['selected_currency_data'] = HelperService::getCurrencyData($row->data);
                    } else if ($row->type == 'bank_details') {
                        // Change Value to Bool
                        $settingsData['bank_details'] = json_decode($row->data, true);
                    } else {
                        // add the data as it is in array
                        $settingsData[$row->type] = $row->data;
                    }
                }

                $settingsData['demo_mode'] = env('DEMO_MODE');
                // if Token is passed of current user.
                if (collect(Auth::guard('sanctum')->user())->isNotEmpty()) {
                    $loggedInUserId = Auth::guard('sanctum')->user()->id;
                    update_subscription($loggedInUserId);


                    $checkVerifiedStatus = VerifyCustomer::where('user_id', $loggedInUserId)->first();
                    if (!empty($checkVerifiedStatus)) {
                        $settingsData['verification_status'] = $checkVerifiedStatus->status;
                    } else {
                        $settingsData['verification_status'] = 'initial';
                    }

                    $customerDataQuery = Customer::select('id', 'subscription', 'is_premium', 'isActive');
                    $customerData = $customerDataQuery->clone()->find($loggedInUserId);

                    // Check Active of current User
                    if (collect($customerData)->isNotEmpty()) {
                        $settingsData['is_active'] = $customerData->isActive == 1 ? true : false;
                    } else {
                        $settingsData['is_active'] = false;
                    }

                    // Check the subscription
                    if (collect($customerData)->isNotEmpty()) {
                        $settingsData['is_premium'] = $customerData->is_premium == 1 ? true : ($customerData->subscription == 1 ? true : false);
                        $settingsData['subscription'] = $customerData->subscription == 1 ? true : false;
                    } else {
                        $settingsData['is_premium'] = false;
                        $settingsData['subscription'] = false;
                    }
                }

                // Check the min_price and max_price
                $settingsData['min_price'] = DB::table('propertys')->selectRaw('MIN(price) as min_price')->value('min_price');
                $settingsData['max_price'] = DB::table('propertys')->selectRaw('MAX(price) as max_price')->value('max_price');

                // Check the features available
                $settingsData['features_available'] = array(
                    'premium_properties' => HelperService::checkPackageLimit('premium_properties', true)['feature_available'],
                    'project_access' => HelperService::checkPackageLimit('project_access', true)['feature_available'],
                );

                // Get Languages Data
                $language = Language::select('id', 'code', 'name')->get();
                $settingsData['languages'] = $language;

                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                $response['data'] = $settingsData;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    public function getLanguagesData()
    {
        try {
            $languageData = Language::select('id', 'code', 'name')->get();
            if (collect($languageData)->isNotEmpty()) {
                $response['error'] = false;
                $response['message'] = "Data Fetch Successfully";
                $response['data'] = $languageData;
            } else {
                $response['error'] = false;
                $response['message'] = "No data found!";
                $response['data'] = [];
            }
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    /**
     * Faq API
     * Params :- Limit and offset
     */
    public function getFaqData(Request $request)
    {
        try {
            // Get Offset and Limit from payload request
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;

            $faqsQuery = Faq::where('status', 1);
            $totalData = $faqsQuery->clone()->count();
            $faqsData = $faqsQuery->clone()->select('id', 'question', 'answer')->orderBy('id', 'DESC')->skip($offset)->take($limit)->get();
            $response = array(
                'error' => false,
                'total' => $totalData ?? 0,
                'data' => $faqsData,
                'message' => 'Data fetched Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    /**
     * beforeLogout API
     */
    public function beforeLogout(Request $request)
    {
        try {
            if ($request->has('fcm_id')) {
                Usertokens::where(['fcm_id' => $request->fcm_id, 'customer_id' => $request->user()->id])->delete();
            }
            $response = array(
                'error' => false,
                'message' => 'Data Processed Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => 'Something Went Wrong'
            );
            return response()->json($response, 500);
        }
    }

    public function getOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required_without:email|nullable',
            'email' => 'required_without:number|email|nullable|exists:customers,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            $otpRecordDB = NumberOtp::query();
            if ($request->has('number') && !empty($request->number)) {
                $requestNumber = $request->number; // Get data from Request
                $trimmedNumber = ltrim($requestNumber, '+'); // remove + from starting if exists
                $toNumber = "+" . (string)$trimmedNumber; // Add + starting of number

                // Initialize empty array
                $dbData = array();

                // make an array of types for database query and get data from settings table
                $twilioCredentialsTypes = array('twilio_account_sid', 'twilio_auth_token', 'twilio_my_phone_number');
                $twilioCredentialsDB = Setting::select('type', 'data')->whereIn('type', $twilioCredentialsTypes)->get();

                // Loop the db result in such a way that type becomes key of array and data becomes its value in new array
                foreach ($twilioCredentialsDB as $value) {
                    $dbData[$value->type] = $value->data;
                }

                // Get Twilio credentials
                $sid = $dbData['twilio_account_sid'];
                $token = $dbData['twilio_auth_token'];
                $fromNumber = $dbData['twilio_my_phone_number'];

                // Instance Created of Twilio client with Twilio SID and token
                $client = new TwilioRestClient($sid, $token);

                // Validate phone number using Twilio Lookup API
                try {
                    $client->lookups->v1->phoneNumbers($toNumber)->fetch();
                } catch (RestException $e) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid phone number.',
                    ]);
                }
                // Check if OTP already exists and is still valid
                $existingOtp = $otpRecordDB->clone()->where('number', $toNumber)->first();
            } else if ($request->has('email') && !empty($request->email)) {
                $toEmail = $request->email;
                // Check if OTP already exists and is still valid
                $existingOtp = $otpRecordDB->clone()->where('email', $toEmail)->first();
            } else {
                ApiResponseService::errorResponse();
            }

            if ($existingOtp && now()->isBefore($existingOtp->expire_at)) {
                // OTP is still valid
                $otp = $existingOtp->otp;
            } else {
                // Generate a new OTP
                $otp = rand(123456, 999999);
                $expireAt = now()->addMinutes(10); // Set OTP expiry time

                if ($request->has('number') && !empty($request->number)) {
                    // Update or create OTP entry in the database
                    NumberOtp::updateOrCreate(
                        ['number' => $toNumber],
                        ['otp' => $otp, 'expire_at' => $expireAt]
                    );

                    // Use the Client to make requests to the Twilio REST API
                    $client->messages->create(
                        // The number you'd like to send the message to
                        $toNumber,
                        [
                            // A Twilio phone number you purchased at https://console.twilio.com
                            'from' => $fromNumber,
                            // The body of the text message you'd like to send
                            'body' => "Here is the OTP: " . $otp . ". It expires in 3 minutes."
                        ]
                    );
                    /** Note :- While using Trial accounts cannot send messages to unverified numbers, or purchase a Twilio number to send messages to unverified numbers.*/
                } else if ($request->has('email') && !empty($request->email)) {
                    // Update or create OTP entry in the database
                    NumberOtp::updateOrCreate(
                        ['email' => $toEmail],
                        ['otp' => $otp, 'expire_at' => $expireAt]
                    );
                } else {
                    ApiResponseService::errorResponse();
                }
            }


            if ($request->has('email') && !empty($request->email)) {
                try {
                    // Get Data of email type
                    $emailTypeData = HelperService::getEmailTemplatesTypes("verify_mail");

                    // Email Template
                    $verifyEmailTemplateData = system_setting("verify_mail_template");
                    $variables = array(
                        'app_name' => env("APP_NAME") ?? "eBroker",
                        'otp' => $otp
                    );
                    if (empty($verifyEmailTemplateData)) {
                        $verifyEmailTemplateData = "Your OTP is :- $otp";
                    }
                    $verifyEmailTemplate = HelperService::replaceEmailVariables($verifyEmailTemplateData, $variables);

                    $data = array(
                        'email_template' => $verifyEmailTemplate,
                        'email' => $toEmail,
                        'title' => $emailTypeData['title'],
                    );

                    HelperService::sendMail($data);
                } catch (Exception $e) {
                    if (Str::contains($e->getMessage(), [
                        'Failed',
                        'Mail',
                        'Mailer',
                        'MailManager',
                        "Connection could not be established"
                    ])) {
                        ApiResponseService::validationError("There is issue with mail configuration, kindly contact admin regarding this");
                    } else {
                        ApiResponseService::errorResponse();
                    }
                }
            }
            // Return success response
            return response()->json([
                'error' => false,
                'message' => 'OTP sent successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required_without:email|nullable',
            'email' => 'required_without:number|nullable',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $otpRecordDB = NumberOtp::query();
            if ($request->has('number') && !empty($request->number)) {
                $requestNumber = $request->number; // Get data from Request
                $trimmedNumber = ltrim($requestNumber, '+'); // remove + from starting if exists
                $toNumber = "+" . (string)$trimmedNumber; // Add + starting of number

                // Fetch the OTP record from the database
                $otpRecord = $otpRecordDB->clone()->where('number', $toNumber)->first();
            } else if ($request->has('email') && !empty($request->email)) {
                $toEmail = $request->email;
                // Fetch the OTP record from the database
                $otpRecord = $otpRecordDB->clone()->where('email', $toEmail)->first();
            } else {
                ApiResponseService::errorResponse();
            }
            $userOtp = $request->otp;

            if (!$otpRecord) {
                return response()->json([
                    'error' => true,
                    'message' => 'OTP not found.',
                ]);
            }

            // Check if the OTP is valid and not expired
            if ($otpRecord->otp == $userOtp && now()->isBefore($otpRecord->expire_at)) {

                if ($request->has('number') && !empty($request->number)) {
                    // Check the number and login type exists in user table
                    $user = Customer::where('mobile', $trimmedNumber)->where('logintype', 1)->first();
                } else if ($request->has('email') && !empty($request->email)) {
                    // Check the email and login type exists in user table
                    $user = Customer::where('email', $toEmail)->where('logintype', 3)->first();
                } else {
                    ApiResponseService::errorResponse();
                }

                if (collect($user)->isNotEmpty()) {
                    $authId = $user->auth_id;
                } else {
                    // Generate a unique identifier
                    $authId = Str::uuid()->toString();
                }
                if ($request->has('email') && !empty($request->email)) {
                    // Check the email and login type exists in user table
                    $user->is_email_verified = true;
                    $user->save();
                }

                return response()->json([
                    'error' => false,
                    'message' => 'OTP verified successfully!',
                    'auth_id' => $authId
                ]);
            } else if ($otpRecord->otp != $userOtp) {
                ApiResponseService::validationError("Invalid OTP.");
            } else if (now()->isAfter($otpRecord->expire_at)) {
                ApiResponseService::validationError("OTP expired.");
            } else {
                ApiResponseService::errorResponse();
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getPropertyList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'offset'                        => 'nullable|numeric',
                'limit'                         => 'nullable|numeric',
                'get_all_premium_properties'    => 'nullable|in:1',
                'check_in_date'                 => 'nullable|date',
                'check_out_date'                => 'nullable|date',
                'search'                        => 'nullable|string|max:255',
            ]);
            
            // Custom validation for dates if both are provided
            if ($request->has('check_in_date') && $request->has('check_out_date') && 
                !empty($request->check_in_date) && !empty($request->check_out_date)) {
                try {
                    // Get application timezone or use UTC as fallback
                    $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
                    
                    // Parse dates as date-only values (no time component) in the application timezone
                    // This ensures consistent comparison regardless of server timezone
                    $checkIn = \Carbon\Carbon::createFromFormat('Y-m-d', $request->check_in_date, $appTimezone)->startOfDay();
                    $checkOut = \Carbon\Carbon::createFromFormat('Y-m-d', $request->check_out_date, $appTimezone)->startOfDay();
                    $today = \Carbon\Carbon::today($appTimezone)->startOfDay();
                    
                    // Check if check-in is not in the past (compare date strings to avoid timezone issues)
                    // Use format comparison to ensure we're comparing dates only, not datetime
                    if ($checkIn->format('Y-m-d') < $today->format('Y-m-d')) {
                        ApiResponseService::validationError('Check-in date cannot be in the past');
                    }
                    
                    // Check if check-out is after check-in
                    if ($checkOut->format('Y-m-d') <= $checkIn->format('Y-m-d')) {
                        ApiResponseService::validationError('Check-out date must be after check-in date');
                    }
                } catch (\Exception $e) {
                    // Log the error for debugging
                    \Illuminate\Support\Facades\Log::error('Date validation error in getPropertyList', [
                        'check_in_date' => $request->check_in_date,
                        'check_out_date' => $request->check_out_date,
                        'error' => $e->getMessage()
                    ]);
                    ApiResponseService::validationError('Invalid date format');
                }
            }
            
            if ($validator->fails()) {
                ApiResponseService::validationError($validator->errors()->first());
            }

            // Get Offset and Limit from payload request
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;

            // Create a property query with basic filters
            $propertyQuery = Property::whereIn('propery_type', [0, 1])->where(function ($query) {
                return $query->where(['status' => 1, 'request_status' => 'approved']);
            });

            // Property Classification should be applied first
            if ($request->has('property_classification') && !empty($request->property_classification)) {
                $propertyQuery = $propertyQuery->clone()->where('property_classification', $request->property_classification);

                // If hotel apartment type is passed and property classification is hotel (5)
                if ($request->property_classification == 5 && $request->has('hotel_apartment_type_id') && !empty($request->hotel_apartment_type_id)) {
                    $propertyQuery = $propertyQuery->clone()->where('hotel_apartment_type_id', $request->hotel_apartment_type_id);
                }
            }

            // If Property Type Passed (after property classification)
            $property_type = $request->property_type;  //0 : Sell 1:Rent
            if (isset($property_type) && (!empty($property_type) || $property_type == 0)) {
                $propertyQuery = $propertyQuery->clone()->where('propery_type', $property_type);
            }

            // If Category Id is Passed
            if ($request->has('category_id') && !empty($request->category_id)) {
                $propertyQuery = $propertyQuery->clone()->where('category_id', $request->category_id);
            }

            // If Rent Package is Passed
            if ($request->has('rent_package') && !empty($request->rent_package)) {
                $propertyQuery = $propertyQuery->clone()->where('rent_package', $request->rent_package);
            }

            // If parameter id passed
            if ($request->has('parameter_id') && !empty($request->parameter_id)) {
                $parametersId = explode(",", $request->parameter_id);
                $propertyQuery = $propertyQuery->clone()->whereHas('assignParameter', function ($query) use ($parametersId) {
                    $query->whereIn('parameter_id', $parametersId)->whereNotNull('value');
                });
            }

            // If bedrooms filter is passed - exact match on parameter value
            // For regular properties: check parameters table
            // For vacation homes (property_classification = 4): also check vacation_apartments table
            // Special handling: When "0" is sent (Studio), match both "0" and "studio" (case-insensitive)
            if ($request->has('bedrooms') && $request->bedrooms !== null && $request->bedrooms !== '') {
                $bedroomsValue = (string) $request->bedrooms;
                $bedroomsIntValue = (int) $bedroomsValue; // For vacation apartments comparison
                $isStudio = ($bedroomsValue === '0'); // Check if filtering for Studio
                
                $propertyQuery = $propertyQuery->clone()->where(function ($query) use ($bedroomsValue, $bedroomsIntValue, $isStudio) {
                    // For Studio filter: only check regular properties (exclude vacation homes)
                    if ($isStudio) {
                        $query->where('property_classification', '!=', 4)
                            ->whereExists(function ($existsQuery) use ($bedroomsValue) {
                                $existsQuery->select(DB::raw(1))
                                    ->from('assign_parameters')
                                    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                                    ->where(function ($linkQuery) {
                                        // Match by property_id OR by modal_id (polymorphic)
                                        // Handle both 'App\Models\Property' and 'property' (lowercase) modal types
                                        $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                            ->orWhere(function ($modalQuery) {
                                                $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                                    ->where(function ($typeQuery) {
                                                        $typeQuery->where('assign_parameters.modal_type', 'App\Models\Property')
                                                            ->orWhere('assign_parameters.modal_type', 'property');
                                                    });
                                            });
                                    })
                                    ->where(function ($nameQuery) {
                                        $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                            ->orWhere('parameters.name', 'LIKE', '%bed%');
                                    })
                                    ->where(function ($valueQuery) {
                                        // For Studio (0), match both "0" and "studio" (case-insensitive)
                                        $valueQuery->whereNotNull('assign_parameters.value')
                                            ->where('assign_parameters.value', '!=', '')
                                            ->where('assign_parameters.value', '!=', 'null')
                                            ->whereRaw('TRIM(assign_parameters.value) != ?', [''])
                                            ->where(function ($studioQuery) {
                                                $studioQuery->where('assign_parameters.value', '0')
                                                    ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                                    ->orWhere('assign_parameters.value', 'Studio')
                                                    ->orWhere('assign_parameters.value', 'STUDIO');
                                            });
                                    });
                            });
                    } else {
                        // For non-Studio filters: prioritize vacation_apartments over assign_parameters for vacation homes
                        $query->where(function ($mainQuery) use ($bedroomsIntValue) {
                            // For vacation homes with vacation_apartments, use vacation_apartments data
                            $mainQuery->where(function ($vacationQuery) use ($bedroomsIntValue) {
                                $vacationQuery->where('property_classification', 4)
                                    ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
                                        $aptQuery->where('bedrooms', $bedroomsIntValue);
                                    });
                            })
                            // For regular properties OR vacation homes without vacation_apartments, use assign_parameters
                            ->orWhere(function ($regularQuery) use ($bedroomsIntValue) {
                                $regularQuery->where(function ($classificationQuery) {
                                    // Either not a vacation home, OR vacation home without vacation_apartments
                                    $classificationQuery->where('property_classification', '!=', 4)
                                        ->orWhereDoesntHave('vacationApartments');
                                })
                                ->whereExists(function ($existsQuery) use ($bedroomsIntValue) {
                                    $existsQuery->select(DB::raw(1))
                                        ->from('assign_parameters')
                                        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                                        ->where(function ($linkQuery) {
                                            // Match by property_id OR by modal_id (polymorphic)
                                            // Handle both 'App\Models\Property' and 'property' (lowercase) modal types
                                            $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                                ->orWhere(function ($modalQuery) {
                                                    $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                                        ->where(function ($typeQuery) {
                                                            $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                                ->orWhere('assign_parameters.modal_type', 'property');
                                                        });
                                                });
                                        })
                                        ->where(function ($nameQuery) {
                                            $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                                ->orWhere('parameters.name', 'LIKE', '%bed%');
                                        })
                                        ->where(function ($valueQuery) use ($bedroomsIntValue) {
                                            // Exact match with JSON support
                                            $bedroomsValue = (string) $bedroomsIntValue;
                                            $valueQuery->whereNotNull('assign_parameters.value')
                                                ->where('assign_parameters.value', '!=', '')
                                                ->where('assign_parameters.value', '!=', 'null')
                                                ->whereRaw('TRIM(assign_parameters.value) != ?', [''])
                                                ->where(function ($exactQuery) use ($bedroomsValue) {
                                                    $exactQuery->where('assign_parameters.value', $bedroomsValue)
                                                        ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                                                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                                                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue]);
                                                });
                                        });
                                });
                            });
                        });
                    }
                });
            }

            // If bathrooms filter is passed - exact match on parameter value
            // For regular properties: check parameters table
            // For vacation homes (property_classification = 4): also check vacation_apartments table
            if ($request->has('bathrooms') && $request->bathrooms !== null && $request->bathrooms !== '') {
                $bathroomsValue = (string) $request->bathrooms;
                $bathroomsIntValue = (int) $bathroomsValue; // For vacation apartments comparison
                
                $propertyQuery = $propertyQuery->clone()->where(function ($query) use ($bathroomsValue, $bathroomsIntValue) {
                    // Check parameters table (for regular properties)
                    // Handle both plain string values and JSON-encoded values
                    // Also handle both morphMany (modal_id) and direct property_id relationships
                    $query->where(function ($subQuery) use ($bathroomsValue) {
                        // Check directly via assign_parameters table
                        // This handles both property_id matches AND modal_id matches (polymorphic)
                        $subQuery->whereExists(function ($existsQuery) use ($bathroomsValue) {
                            $existsQuery->select(DB::raw(1))
                                ->from('assign_parameters')
                                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                                ->where(function ($linkQuery) {
                                    // Match by property_id OR by modal_id (polymorphic)
                                    // Handle both 'App\Models\Property' and 'property' (lowercase) modal types
                                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                        ->orWhere(function ($modalQuery) {
                                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                                ->where(function ($typeQuery) {
                                                    $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                        ->orWhere('assign_parameters.modal_type', 'property');
                                                });
                                        });
                                })
                                ->where(function ($nameQuery) {
                                    $nameQuery->where('parameters.name', 'LIKE', '%bathroom%')
                                        ->orWhere('parameters.name', 'LIKE', '%bath%');
                                })
                                ->where(function ($valueQuery) use ($bathroomsValue) {
                                    $valueQuery->where('assign_parameters.value', $bathroomsValue)
                                        ->orWhere('assign_parameters.value', '"' . $bathroomsValue . '"')
                                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bathroomsValue])
                                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bathroomsValue]);
                                });
                        });
                    })
                    // OR check vacation_apartments table (for vacation homes)
                    ->orWhere(function ($vacationQuery) use ($bathroomsIntValue) {
                        $vacationQuery->where('property_classification', 4)
                            ->whereHas('vacationApartments', function ($aptQuery) use ($bathroomsIntValue) {
                                $aptQuery->where('bathrooms', $bathroomsIntValue);
                            });
                    });
                });
            }

            // If Category Slug is Passed
            if ($request->has('category_slug_id') && !empty($request->category_slug_id)) {
                $categorySlugId = $request->category_slug_id;
                $propertyQuery = $propertyQuery->clone()->whereHas('category', function ($query) use ($categorySlugId) {
                    $query->where('slug_id', $categorySlugId);
                });
            }

            // If Country is passed
            if ($request->has('country') && !empty($request->country)) {
                $propertyQuery = $propertyQuery->clone()->where('country', $request->country);
            }

            // If State is passed
            if ($request->has('state') && !empty($request->state)) {
                $propertyQuery = $propertyQuery->clone()->where('state', $request->state);
            }

            // If City is passed
            if ($request->has('city') && !empty($request->city)) {
                $propertyQuery = $propertyQuery->clone()->where('city', $request->city);
            }

            // If Min Area and Max Area are passed (Backend Filtering)
            if (($request->has('min_area') && !empty($request->min_area)) || ($request->has('max_area') && !empty($request->max_area))) {
                $minArea = $request->has('min_area') && is_numeric($request->min_area) ? (float)$request->min_area : 0;
                $maxArea = $request->has('max_area') && is_numeric($request->max_area) ? (float)$request->max_area : 999999999;

                $propertyQuery = $propertyQuery->clone()->whereHas('assignParameter', function ($query) use ($minArea, $maxArea) {
                    $query->whereHas('parameter', function ($q) {
                        $q->where('name', 'LIKE', '%size%')
                          ->orWhere('name', 'LIKE', '%area%')
                          ->orWhere('name', 'LIKE', '%sqm%')
                          ->orWhere('name', 'LIKE', '%square%');
                    })
                    ->where(function($valQuery) use ($minArea, $maxArea) {
                        // Handle comma-separated values and ensure numeric comparison
                        $valQuery->whereRaw('CAST(REPLACE(value, ",", "") AS DECIMAL(10,2)) >= ?', [$minArea])
                                 ->whereRaw('CAST(REPLACE(value, ",", "") AS DECIMAL(10,2)) <= ?', [$maxArea]);
                    });
                });
            }

            // If Max Price And Min Price passed
            if ($request->has('min_price') && !empty($request->min_price) && isset($request->max_price) && !empty($request->max_price)) {
                $minPrice = $request->min_price;
                $maxPrice = $request->max_price;
                
                // Special handling for hotels (property_classification = 5)
                if ($request->has('property_classification') && $request->property_classification == 5) {
                    // Use a more efficient subquery approach - check if ANY room is in the price range
                    $propertyQuery = $propertyQuery->clone()->whereHas('hotelRooms', function ($query) use ($minPrice, $maxPrice) {
                        $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
                        // Note: Not filtering by status to show all available rooms
                    });
                } else {
                    $propertyQuery = $propertyQuery->clone()->whereBetween('price', [$minPrice, $maxPrice]);
                }
            } elseif ($request->has('min_price') && !empty($request->min_price)) {
                $minPrice = $request->min_price;
                
                // Special handling for hotels (property_classification = 5)
                if ($request->has('property_classification') && $request->property_classification == 5) {
                    // Use a more efficient subquery approach
                    $propertyQuery = $propertyQuery->clone()->whereHas('hotelRooms', function ($query) use ($minPrice) {
                        $query->where('price_per_night', '>=', $minPrice);
                        // Note: Not filtering by status to show all available rooms
                    });
                } else {
                    $propertyQuery = $propertyQuery->clone()->where('price', '>=', $minPrice);
                }
            } elseif (isset($request->max_price) && !empty($request->max_price)) {
                $maxPrice = $request->max_price;
                
                // Special handling for hotels (property_classification = 5)
                if ($request->has('property_classification') && $request->property_classification == 5) {
                    // Use a more efficient subquery approach
                    $propertyQuery = $propertyQuery->clone()->whereHas('hotelRooms', function ($query) use ($maxPrice) {
                        $query->where('price_per_night', '<=', $maxPrice);
                        // Note: Not filtering by status to show all available rooms
                    });
                } else {
                    $propertyQuery = $propertyQuery->clone()->where('price', '<=', $maxPrice);
                }
            }

            // If Posted Since 0 or 1 is passed
            if ($request->has('posted_since')) {
                $posted_since = $request->posted_since;

                // 0 - Last Week (from today back to the same day last week)
                if ($posted_since == 0) {
                    $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();
                    $today = Carbon::now()->endOfDay();
                    $propertyQuery = $propertyQuery->clone()->whereBetween('created_at', [$oneWeekAgo, $today]);
                }
                // 1 - Yesterday
                if ($posted_since == 1) {
                    $yesterdayDate = Carbon::yesterday();
                    $propertyQuery =  $propertyQuery->clone()->whereDate('created_at', $yesterdayDate);
                }
            }

            // Search the property
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $isHotelSearch = $request->has('property_classification') && $request->property_classification == 5;
                $hasHotelNameColumn = false;
                
                // Check if hotel_name column exists (only check once, outside query closure)
                if ($isHotelSearch) {
                    try {
                        $hasHotelNameColumn = Schema::hasColumn('propertys', 'hotel_name');
                    } catch (\Exception $e) {
                        // Column doesn't exist, skip hotel_name search
                        $hasHotelNameColumn = false;
                    }
                }
                
                // Normalize search: remove spaces from database fields to match frontend sanitization
                // This allows "5starhotel" to match "5 star hotel" in the database
                $propertyQuery = $propertyQuery->clone()->where(function ($query) use ($search, $isHotelSearch, $hasHotelNameColumn) {
                    // Search title with spaces removed (normalized)
                    $query->whereRaw("REPLACE(REPLACE(title, ' ', ''), '-', '') LIKE ?", ["%" . str_replace([' ', '-'], '', $search) . "%"])
                        // Search address with spaces removed (normalized)
                        ->orWhereRaw("REPLACE(REPLACE(address, ' ', ''), '-', '') LIKE ?", ["%" . str_replace([' ', '-'], '', $search) . "%"]);
                    
                    // Only search hotel_name if it's a hotel search and column exists
                    if ($isHotelSearch && $hasHotelNameColumn) {
                        $query->orWhereRaw("REPLACE(REPLACE(hotel_name, ' ', ''), '-', '') LIKE ?", ["%" . str_replace([' ', '-'], '', $search) . "%"]);
                    }
                    
                    // Search category with spaces removed (normalized)
                    $query->orWhereHas('category', function ($query1) use ($search) {
                        $query1->whereRaw("REPLACE(REPLACE(category, ' ', ''), '-', '') LIKE ?", ["%" . str_replace([' ', '-'], '', $search) . "%"]);
                    });
                });
            }

            // IF Promoted Passed then show the data according to
            if ($request->has('promoted') && $request->promoted == 1) {
                $propertyQuery = $propertyQuery->clone()->whereHas('advertisement', function ($query) {
                    $query->where(['status' => 0, 'is_enable' => 1]);
                });
            }

            // If get_all_premium_properties is passed then show the data according to
            if ($request->has('get_all_premium_properties') && $request->get_all_premium_properties == 1) {
                $propertyQuery = $propertyQuery->clone()->where('is_premium', 1);
            }

            // If check-in and check-out dates are provided, filter by availability
            if (
                $request->has('check_in_date') && $request->has('check_out_date') &&
                !empty($request->check_in_date) && !empty($request->check_out_date)
            ) {

                $checkInDate = $request->check_in_date;
                $checkOutDate = $request->check_out_date;

                // Check if apartment_id column exists in reservations table (for backward compatibility)
                $hasApartmentIdColumn = Schema::hasColumn('reservations', 'apartment_id');

                $propertyQuery = $propertyQuery->clone()->where(function ($query) use ($checkInDate, $checkOutDate, $hasApartmentIdColumn) {
                    // Filter vacation homes (property_classification = 4)
                    $query->where(function ($vacationQuery) use ($checkInDate, $checkOutDate, $hasApartmentIdColumn) {
                        $vacationQuery->where('property_classification', 4)
                            // IMPORTANT: Property must have at least one apartment
                            ->whereHas('vacationApartments', function ($apartmentQuery) use ($checkInDate, $checkOutDate, $hasApartmentIdColumn) {
                                // Check availability at apartment level - more flexible approach
                                $apartmentQuery->where(function ($aptQuery) use ($checkInDate, $checkOutDate) {
                                    // Option 1: Apartment has its own available_dates JSON that covers the search dates
                                    $aptQuery->where(function ($hasOwnDates) use ($checkInDate, $checkOutDate) {
                                        $hasOwnDates->whereRaw("JSON_VALID(available_dates)")
                                            ->whereRaw("
                                                EXISTS (
                                                    SELECT 1
                                                    FROM JSON_TABLE(
                                                        available_dates,
                                                        '$[*]' COLUMNS (
                                                            from_date DATE PATH '$.from',
                                                            to_date DATE PATH '$.to',
                                                            type VARCHAR(20) PATH '$.type'
                                                        )
                                                    ) AS jt
                                                    WHERE (jt.type IS NULL OR jt.type != 'reserved')
                                                    AND jt.from_date <= ?
                                                    AND jt.to_date >= ?
                                                )
                                            ", [$checkInDate, $checkOutDate]);
                                    })
                                    // Option 2: Apartment doesn't have own available_dates - check property-level available_dates
                                    ->orWhere(function ($inheritsFromProperty) use ($checkInDate, $checkOutDate) {
                                        $inheritsFromProperty->whereRaw("(available_dates IS NULL OR available_dates = '[]' OR JSON_VALID(available_dates) = 0)")
                                            ->whereHas('property', function ($propQuery) use ($checkInDate, $checkOutDate) {
                                                // Property has available_dates that cover the search dates
                                                $propQuery->whereRaw("JSON_VALID(available_dates)")
                                                    ->whereRaw("
                                                        EXISTS (
                                                            SELECT 1
                                                            FROM JSON_TABLE(
                                                                available_dates,
                                                                '$[*]' COLUMNS (
                                                                    from_date DATE PATH '$.from',
                                                                    to_date DATE PATH '$.to',
                                                                    type VARCHAR(20) PATH '$.type'
                                                                )
                                                            ) AS jt
                                                            WHERE (jt.type IS NULL OR jt.type != 'reserved')
                                                            AND jt.from_date <= ?
                                                            AND jt.to_date >= ?
                                                        )
                                                    ", [$checkInDate, $checkOutDate]);
                                            });
                                    })
                                    // Option 3: Neither apartment nor property has available_dates - assume always available (unless reserved)
                                    ->orWhere(function ($noDatesSet) {
                                        $noDatesSet->whereRaw("(available_dates IS NULL OR available_dates = '[]' OR JSON_VALID(available_dates) = 0)")
                                            ->whereDoesntHave('property', function ($propQuery) {
                                                $propQuery->whereRaw("JSON_VALID(available_dates)");
                                            });
                                    });
                                })
                                // Check that apartment has available units (not fully booked by reservations)
                                // IMPORTANT: Checkout day is available, so use check_out_date > checkInDate (not >=)
                                ->where(function ($availabilityCheck) use ($checkInDate, $checkOutDate, $hasApartmentIdColumn) {
                                    if ($hasApartmentIdColumn) {
                                        // MODERN APPROACH: Use apartment_id column if it exists
                                        $availabilityCheck->whereRaw("
                                            (
                                                COALESCE(quantity, 1) > COALESCE((
                                                    SELECT SUM(COALESCE(apartment_quantity, 1))
                                                    FROM reservations
                                                    WHERE reservations.reservable_type = 'App\\\\Models\\\\Property'
                                                    AND reservations.reservable_id = vacation_apartments.property_id
                                                    AND reservations.status = 'confirmed'
                                                    AND reservations.apartment_id = vacation_apartments.id
                                                    AND (
                                                        (reservations.check_in_date < ? AND reservations.check_out_date > ?)
                                                        OR (reservations.check_in_date >= ? AND reservations.check_out_date <= ?)
                                                    )
                                                ), 0)
                                            )
                                        ", [
                                            $checkOutDate, $checkInDate,  // First overlap: checkout > checkIn (checkout day available)
                                            $checkInDate, $checkOutDate   // Second overlap: reservation within search dates
                                        ]);
                                    } else {
                                        // FALLBACK APPROACH: Parse special_requests for backward compatibility
                                        $availabilityCheck->whereRaw("
                                            (
                                                COALESCE(quantity, 1) > COALESCE((
                                                    SELECT SUM(
                                                        CASE 
                                                            WHEN reservations.special_requests LIKE CONCAT('%Apartment ID:', vacation_apartments.id, '%') THEN
                                                                GREATEST(
                                                                    CAST(
                                                                        SUBSTRING_INDEX(
                                                                            SUBSTRING_INDEX(
                                                                                SUBSTRING_INDEX(
                                                                                    reservations.special_requests,
                                                                                    CONCAT('Apartment ID:', vacation_apartments.id),
                                                                                    -1
                                                                                ),
                                                                                'Quantity:',
                                                                                -1
                                                                            ),
                                                                            ',',
                                                                            1
                                                                        )
                                                                        AS UNSIGNED
                                                                    ),
                                                                    1
                                                                )
                                                            ELSE 1
                                                        END
                                                    )
                                                    FROM reservations
                                                    WHERE reservations.reservable_type = 'App\\\\Models\\\\Property'
                                                    AND reservations.reservable_id = vacation_apartments.property_id
                                                    AND reservations.status = 'confirmed'
                                                    AND (
                                                        reservations.special_requests LIKE CONCAT('%Apartment ID:', vacation_apartments.id, '%')
                                                        OR (reservations.special_requests IS NULL AND reservations.reservable_id = vacation_apartments.property_id)
                                                    )
                                                    AND (
                                                        (reservations.check_in_date < ? AND reservations.check_out_date > ?)
                                                        OR (reservations.check_in_date >= ? AND reservations.check_out_date <= ?)
                                                    )
                                                ), 0)
                                            )
                                        ", [
                                            $checkOutDate, $checkInDate,  // First overlap: checkout > checkIn (checkout day available)
                                            $checkInDate, $checkOutDate   // Second overlap: reservation within search dates
                                        ]);
                                    }
                                });
                            });
                    })
                        // OR filter hotels (property_classification = 5) with available rooms
                        ->orWhere(function ($hotelQuery) use ($checkInDate, $checkOutDate) {
                            $hotelQuery->where('property_classification', 5)
                                ->whereHas('hotelRooms', function ($roomQuery) use ($checkInDate, $checkOutDate) {
                                    // Only check active rooms (status = 1)
                                    $roomQuery->where('status', 1)
                                        ->where(function ($availabilityQuery) use ($checkInDate, $checkOutDate) {
                                            // Option 1: Rooms WITH available_dates that cover the search dates
                                            $availabilityQuery->whereRaw("JSON_VALID(available_dates)")
                                            ->where(function ($dateQuery) use ($checkInDate, $checkOutDate) {
                                                // Handle availability_type = 1 (available_days) or NULL (defaults to available_days)
                                                // For available_days: search date range must be completely within an available date range
                                                $dateQuery->where(function ($availableDaysQuery) use ($checkInDate, $checkOutDate) {
                                                    $availableDaysQuery->where(function ($typeQuery) {
                                                        // availability_type = 1 means available_days, NULL also means available_days (default)
                                                        $typeQuery->whereNull('availability_type')
                                                            ->orWhere('availability_type', 1);
                                                    })
                                                        ->whereRaw("
                                                    EXISTS (
                                                        SELECT 1
                                                        FROM JSON_TABLE(
                                                            available_dates,
                                                            '$[*]' COLUMNS (
                                                                from_date DATE PATH '$.from',
                                                                to_date DATE PATH '$.to',
                                                                type VARCHAR(20) PATH '$.type'
                                                            )
                                                        ) AS jt
                                                        WHERE (jt.type IS NULL OR jt.type != 'reserved')
                                                        AND jt.from_date <= ?
                                                        AND jt.to_date >= ?
                                                    )
                                                ", [$checkInDate, $checkOutDate]);
                                                })
                                                    // Handle availability_type = 2 (busy_days)
                                                    // For busy_days: search date range must NOT overlap with any busy date range
                                                    ->orWhere(function ($busyDaysQuery) use ($checkInDate, $checkOutDate) {
                                                        $busyDaysQuery->where('availability_type', 2)
                                                            ->whereRaw("
                                                        NOT EXISTS (
                                                            SELECT 1
                                                            FROM JSON_TABLE(
                                                                available_dates,
                                                                '$[*]' COLUMNS (
                                                                    from_date DATE PATH '$.from',
                                                                    to_date DATE PATH '$.to'
                                                                )
                                                            ) AS jt
                                                            WHERE jt.from_date <= ?
                                                            AND jt.to_date >= ?
                                                        )
                                                    ", [$checkOutDate, $checkInDate]);
                                                    });
                                            })
                                        // Option 2: Rooms WITHOUT available_dates (null/empty/invalid JSON) - treat as always available
                                        ->orWhere(function ($noDatesQuery) {
                                            $noDatesQuery->whereRaw("(available_dates IS NULL OR available_dates = '' OR available_dates = '[]' OR JSON_VALID(available_dates) = 0)");
                                        });
                                    })
                                        // Also check that room is not already reserved for these dates
                                        ->whereDoesntHave('reservations', function ($reservationQuery) use ($checkInDate, $checkOutDate) {
                                            $reservationQuery->where('status', 'confirmed')
                                                ->where(function ($dateOverlapQuery) use ($checkInDate, $checkOutDate) {
                                                    $dateOverlapQuery->where(function ($query) use ($checkInDate, $checkOutDate) {
                                                        $query->where('check_in_date', '<=', $checkInDate)
                                                            ->where('check_out_date', '>', $checkInDate);
                                                    })
                                                        ->orWhere(function ($query) use ($checkInDate, $checkOutDate) {
                                                            $query->where('check_in_date', '<', $checkOutDate)
                                                                ->where('check_out_date', '>=', $checkOutDate);
                                                        })
                                                        ->orWhere(function ($query) use ($checkInDate, $checkOutDate) {
                                                            $query->where('check_in_date', '>=', $checkInDate)
                                                                ->where('check_out_date', '<=', $checkOutDate);
                                                        });
                                                });
                                        });
                                });
                        })
                        // OR allow other property classifications to pass through
                        ->orWhereNotIn('property_classification', [4, 5]);
                });
            }

            // Save base query for total count calculation (before ordering and pagination)
            $baseQueryForCount = $propertyQuery->clone();

            // Get total properties
            $totalProperties = $baseQueryForCount->count();

            // Sort By Parameter
            if ($request->has('sort_by') && !empty($request->sort_by)) {
                $sortBy = $request->sort_by;
                if ($sortBy == 'price_asc') {
                    // Force numeric sorting for price
                    $propertyQuery = $propertyQuery->clone()->orderByRaw('CAST(price AS DECIMAL(15,2)) ASC');
                } else if ($sortBy == 'price_desc') {
                    // Force numeric sorting for price
                    $propertyQuery = $propertyQuery->clone()->orderByRaw('CAST(price AS DECIMAL(15,2)) DESC');
                } else if ($sortBy == 'newest') {
                    $propertyQuery = $propertyQuery->clone()->orderBy('created_at', 'DESC');
                } else if ($sortBy == 'oldest') {
                    $propertyQuery = $propertyQuery->clone()->orderBy('created_at', 'ASC');
                } else if ($sortBy == 'featured') {
                    // Simplify featured sorting to avoid heavy joins and potential DB issues,
                    // especially for hotel (5) and vacation home (4) classifications.
                    // Sort by 'is_promoted' flag first, then recent properties.
                    $propertyQuery = $propertyQuery->clone()
                        ->orderByRaw('(SELECT COUNT(*) FROM advertisements WHERE advertisements.property_id = propertys.id AND advertisements.status = 0 AND advertisements.is_enable = 1) DESC')
                        ->orderBy('created_at', 'DESC');
                }
            }
            // If Most Viewed Passed then show the property data with Order by on Total Click Descending
            else if ($request->has('most_viewed') && $request->most_viewed == 1) {
                $propertyQuery = $propertyQuery->clone()->orderBy('total_click', 'DESC');
            }
            // If Most Liked Passed then show the property data with Order by on Total Click Descending
            else if ($request->has('most_liked') && $request->most_liked == 1) {
                $propertyQuery = $propertyQuery->clone()->orderBy('favourite_count', 'DESC');
            } else {
                // If No Most Viewed or Most Liked Passed then show the property data with Order by on Id Descending
                $propertyQuery = $propertyQuery->clone()->orderBy('id', 'DESC');
            }

            // Get properties list data
            $propertiesData = $propertyQuery->clone()
                ->with([
                    'category:id,category,image,slug_id,parameter_types', 
                    'vacationApartments' => function($query) {
                        // Only load active vacation apartments (status = 1)
                        $query->where('status', 1);
                    }, 
                    'assignParameter.parameter'
                ])
                ->select('id', 'slug_id', 'propery_type', 'title_image', 'category_id', 'title', 'price', 'city', 'state', 'country', 'rentduration', 'added_by', 'is_premium', 'property_classification', 'rent_package', 'latitude', 'longitude', 'total_click')
                ->withCount('favourite');

            // Latitude and Longitude
            if ($request->has('latitude') && !empty($request->latitude) && $request->has('longitude') && !empty($request->longitude)) {
                if ($request->has('radius') && !empty($request->radius)) {

                    // Get the distance from the latitude and longitude
                    $propertiesData = $propertiesData->selectRaw("
                            (6371 * acos(cos(radians($request->latitude))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians($request->longitude))
                            + sin(radians($request->latitude))
                            * sin(radians(latitude)))) AS distance")
                        ->where('latitude', '!=', 0)
                        ->where('longitude', '!=', 0)
                        ->having('distance', '<', $request->radius);
                } else {
                    $propertiesData = $propertiesData->where('latitude', $request->latitude)->where('longitude', $request->longitude);
                }
            }


            $propertiesData = $propertiesData->skip($offset)
                ->take($limit)
                ->get();
            
            // Expand vacation homes with apartments into separate listing items
            $expandedProperties = collect();
            foreach ($propertiesData as $property) {
                // Check if this is a vacation home (classification 4) with apartments
                // Use relationLoaded to check if relationship was eager loaded, otherwise use the accessor
                $vacationApartments = null;
                if ($property->relationLoaded('vacationApartments')) {
                    $vacationApartments = $property->getRelation('vacationApartments');
                } else {
                    // Fallback to accessor if relationship wasn't loaded
                    $vacationApartments = $property->vacationApartments;
                }
                
                // Check property classification - might be string or int, use raw value
                $classification = $property->getRawOriginal('property_classification') ?? $property->property_classification;
                $isVacationHome = ($classification == 4 || $classification === 4 || (int)$classification === 4);
                
                if ($isVacationHome && 
                    $vacationApartments && 
                    $vacationApartments->count() > 0) {
                    // Create a separate listing item for each apartment
                    foreach ($vacationApartments as $apartment) {
                        $apartmentProperty = clone $property;
                        // Keep original property ID and slug_id for navigation
                        $apartmentProperty->apartment_id = $apartment->id;
                        $apartmentProperty->parent_property_id = $property->id;
                        $apartmentProperty->title = $property->title . ' - ' . $apartment->apartment_number;
                        $apartmentProperty->price = $apartment->price_per_night;
                        $apartmentProperty->apartment_number = $apartment->apartment_number;
                        $apartmentProperty->bedrooms = $apartment->bedrooms;
                        $apartmentProperty->bathrooms = $apartment->bathrooms;
                        $apartmentProperty->max_guests = $apartment->max_guests;
                        $apartmentProperty->is_apartment = true;
                        $apartmentProperty->selected_apartment = $apartment; // Include full apartment data
                        $apartmentProperty->promoted = $property->is_promoted;
                        $apartmentProperty->is_premium = $property->is_premium == 1 ? true : false;
                        $apartmentProperty->property_type = $property->propery_type;
                        // Get property type from parameters if available (for display purposes)
                        $propertyTypeParam = null;
                        if ($property->parameters && is_array($property->parameters)) {
                          $typeParam = collect($property->parameters)->firstWhere('name', 'Property Type');
                          if ($typeParam && isset($typeParam->value)) {
                            $propertyTypeParam = $typeParam->value;
                          }
                        }
                        $apartmentProperty->unit_type = $propertyTypeParam; // Store property type for frontend display
                        $apartmentProperty->assign_facilities = $property->assign_facilities;
                        // Unset relationship to force accessor usage for correct ordering
                        $property->unsetRelation('parameters');
                        $apartmentProperty->parameters = $property->parameters; // This will now use the accessor
                        $apartmentProperty->property_classification = $property->property_classification;
                        $apartmentProperty->rent_package = $property->rent_package;
                        unset($apartmentProperty->propery_type);
                        unset($apartmentProperty->vacationApartments);
                        $expandedProperties->push($apartmentProperty);
                    }
                } else {
                    // Regular property (no apartments or not a vacation home)
                    $property->promoted = $property->is_promoted;
                    $property->is_premium = $property->is_premium == 1 ? true : false;
                    $property->property_type = $property->propery_type;
                    $property->assign_facilities = $property->assign_facilities;
                    // Unset relationship to force accessor usage for correct ordering
                    $property->unsetRelation('parameters');
                    $property->parameters = $property->parameters; // This will now use the accessor
                    $property->property_classification = $property->property_classification;
                    $property->rent_package = $property->rent_package;
                    $property->is_apartment = false;
                    unset($property->propery_type);
                    unset($property->vacationApartments);
                    $expandedProperties->push($property);
                }
            }

            // Sort properties based on the promoted attribute
            if ($request->has('sort_by') && !empty($request->sort_by) && $request->sort_by != 'featured') {
                $sortBy = $request->sort_by;
                if ($sortBy == 'price_asc') {
                    $propertiesData = $expandedProperties->sortBy(function ($property) {
                        return (float)$property->price;
                    })->values();
                } else if ($sortBy == 'price_desc') {
                    $propertiesData = $expandedProperties->sortByDesc(function ($property) {
                        return (float)$property->price;
                    })->values();
                } else if ($sortBy == 'newest') {
                    $propertiesData = $expandedProperties->sortByDesc('created_at')->values();
                } else if ($sortBy == 'oldest') {
                    $propertiesData = $expandedProperties->sortBy('created_at')->values();
                } else {
                    $propertiesData = $expandedProperties->values();
                }
            } else {
                // Default or Featured: Sort by promoted first
                $propertiesData = $expandedProperties->sortByDesc(function ($property) {
                    return $property->promoted;
                })->values();
            }

            $propertiesData = $propertiesData->filter();

            // Adjust total count - count vacation homes with apartments before expansion
            // Use the base query (before ordering/pagination) to count all vacation homes matching filters
            $vacationHomesQuery = $baseQueryForCount->clone()
                ->where('property_classification', 4)
                ->with([
                    'vacationApartments' => function($query) {
                        // Only count active vacation apartments (status = 1)
                        $query->where('status', 1);
                    }
                ])
                ->get();
            
            $additionalListings = 0;
            foreach ($vacationHomesQuery as $property) {
                if ($property->vacationApartments && $property->vacationApartments->count() > 0) {
                    $additionalListings += $property->vacationApartments->count() - 1; // -1 because original property is replaced
                }
            }
            $adjustedTotal = $totalProperties + $additionalListings;

            $response = array(
                'error' => false,
                'total' => $adjustedTotal,
                'data' => $propertiesData,
                'message' => 'Data fetched Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Search hotels with arrival and departure dates
     * Filters hotels based on available_dates in hotel_rooms table
     */
    public function searchHotelsWithDates(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'offset' => 'nullable|numeric',
                'limit' => 'nullable|numeric',
                'check_in_date' => 'required|date',
                'check_out_date' => 'required|date',
                'number_of_guests' => 'nullable|integer|min:1',
                'category_id' => 'nullable|numeric',
                'category_slug_id' => 'nullable|string',
                'country' => 'nullable|string',
                'state' => 'nullable|string',
                'city' => 'nullable|string',
                'min_price' => 'nullable|numeric',
                'max_price' => 'nullable|numeric',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get application timezone or use UTC as fallback
            $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
            
            // Parse dates
            $checkInDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->check_in_date, $appTimezone)->startOfDay();
            $checkOutDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->check_out_date, $appTimezone)->startOfDay();
            $today = \Carbon\Carbon::today($appTimezone)->startOfDay();
            
            // Validate dates
            if ($checkInDate->format('Y-m-d') < $today->format('Y-m-d')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Check-in date cannot be in the past'
                ], 422);
            }
            
            if ($checkOutDate->format('Y-m-d') <= $checkInDate->format('Y-m-d')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Check-out date must be after check-in date'
                ], 422);
            }

            // Pagination
            $offset = $request->has('offset') && !empty($request->offset) ? (int)$request->offset : 0;
            $limit = $request->has('limit') && !empty($request->limit) ? (int)$request->limit : 10;

            $checkInStr = $checkInDate->format('Y-m-d');
            $checkOutStr = $checkOutDate->format('Y-m-d');
            $checkOutExclusiveStr = $checkOutDate->copy()->subDay()->format('Y-m-d');
            
            \Log::info('Hotel Search - Querying available_dates_hotel_rooms:', [
                'check_in' => $checkInStr,
                'check_out' => $checkOutStr,
                'condition' => "from_date <= $checkInStr AND to_date >= $checkOutExclusiveStr"
            ]);
            
            // NOTE:
            // We intentionally do NOT prefilter by available_dates_hotel_rooms here.
            // That table stores explicit availability periods, but the frontend booking calendar
            // treats gaps between configured periods as available. Prefiltering would incorrectly
            // exclude hotels like "Amazing 4 Star Hotel" when availability is represented as
            // multiple adjacent periods.
            $propertyQuery = Property::query()
                ->where('property_classification', 5)
                ->where('request_status', 'approved')
                ->where('status', 1);
            
            // Filter properties to ensure they have at least one available room (not reserved)
            $propertyQuery = $propertyQuery->whereHas('hotelRooms', function ($roomQuery) use ($checkInDate, $checkOutDate, $checkInStr, $checkOutStr, $checkOutExclusiveStr) {
                $roomQuery->where('status', 1) // Only active rooms
                    ->where(function ($availabilityQuery) use ($checkInStr, $checkOutExclusiveStr) {
                        // availability_type = 1 (available_days) or NULL
                        // Match frontend behavior: gaps between availability ranges are treated as available.
                        // Therefore, check that the requested period is inside the overall envelope
                        // [MIN(from), MAX(to)] of non-reserved ranges.
                        $availabilityQuery->whereRaw("JSON_VALID(available_dates)")
                            ->where(function ($dateQuery) use ($checkInStr, $checkOutExclusiveStr) {
                                $dateQuery
                                    ->where(function ($typeQuery) {
                                        $typeQuery->whereNull('availability_type')
                                            ->orWhere('availability_type', 1);
                                    })
                                    ->whereRaw("
                                        (
                                            SELECT MIN(jt.from_date)
                                            FROM JSON_TABLE(
                                                available_dates,
                                                '$[*]' COLUMNS (
                                                    from_date DATE PATH '$.from',
                                                    type VARCHAR(20) PATH '$.type'
                                                )
                                            ) AS jt
                                            WHERE (jt.type IS NULL OR jt.type != 'reserved')
                                        ) <= ?
                                    ", [$checkInStr])
                                    ->whereRaw("
                                        (
                                            SELECT MAX(jt.to_date)
                                            FROM JSON_TABLE(
                                                available_dates,
                                                '$[*]' COLUMNS (
                                                    to_date DATE PATH '$.to',
                                                    type VARCHAR(20) PATH '$.type'
                                                )
                                            ) AS jt
                                            WHERE (jt.type IS NULL OR jt.type != 'reserved')
                                        ) >= ?
                                    ", [$checkOutExclusiveStr]);
                            })
                            // availability_type = 2 (busy_days)
                            ->orWhere(function ($busyDaysQuery) use ($checkInStr, $checkOutExclusiveStr) {
                                $busyDaysQuery->where('availability_type', 2)
                                    ->whereRaw("
                                        NOT EXISTS (
                                            SELECT 1
                                            FROM JSON_TABLE(
                                                available_dates,
                                                '$[*]' COLUMNS (
                                                    from_date DATE PATH '$.from',
                                                    to_date DATE PATH '$.to'
                                                )
                                            ) AS jt
                                            WHERE jt.from_date <= ?
                                            AND jt.to_date >= ?
                                        )
                                    ", [$checkOutExclusiveStr, $checkInStr]);
                            });
                    })
                    // OR room has no available_dates (treat as always available)
                    ->orWhere(function ($noDatesQuery) {
                        $noDatesQuery->whereRaw("(available_dates IS NULL OR available_dates = '' OR available_dates = '[]' OR JSON_VALID(available_dates) = 0)");
                    })
                    // Room is not reserved for these dates
                    ->whereDoesntHave('reservations', function ($reservationQuery) use ($checkInDate, $checkOutDate) {
                        $reservationQuery->where('status', 'confirmed')
                            ->where(function ($dateOverlapQuery) use ($checkInDate, $checkOutDate) {
                                $dateOverlapQuery->where(function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where('check_in_date', '<=', $checkInDate)
                                        ->where('check_out_date', '>', $checkInDate);
                                })
                                ->orWhere(function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where('check_in_date', '<', $checkOutDate)
                                        ->where('check_out_date', '>=', $checkOutDate);
                                })
                                ->orWhere(function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where('check_in_date', '>=', $checkInDate)
                                        ->where('check_out_date', '<=', $checkOutDate);
                                });
                            });
                    });
            });
            
            // Debug: Log base query count
            $baseCount = $propertyQuery->count();
            \Log::info('Hotel Search Debug - Properties after filtering: ' . $baseCount);

            // Apply additional filters
            if ($request->has('category_id') && !empty($request->category_id)) {
                $propertyQuery->where('category_id', $request->category_id);
            }

            if ($request->has('category_slug_id') && !empty($request->category_slug_id)) {
                $propertyQuery->whereHas('category', function ($query) use ($request) {
                    $query->where('slug_id', $request->category_slug_id);
                });
            }

            if ($request->has('country') && !empty($request->country)) {
                $propertyQuery->where('country', $request->country);
            }

            if ($request->has('state') && !empty($request->state)) {
                $propertyQuery->where('state', $request->state);
            }

            if ($request->has('city') && !empty($request->city)) {
                // Check if cityVariations array is provided (for normalized city names with multiple spellings)
                if ($request->has('cityVariations') && is_array($request->cityVariations) && count($request->cityVariations) > 0) {
                    // Search for properties matching any of the city name variations (case-insensitive)
                    $propertyQuery->where(function($query) use ($request) {
                        foreach ($request->cityVariations as $variation) {
                            $query->orWhereRaw('LOWER(city) = LOWER(?)', [$variation]);
                        }
                    });
                } else {
                    // Single city name (case-insensitive to handle hurghada vs Hurghada)
                    $propertyQuery->whereRaw('LOWER(city) = LOWER(?)', [$request->city]);
                }
            }

            if ($request->has('min_price') && !empty($request->min_price)) {
                $propertyQuery->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && !empty($request->max_price)) {
                $propertyQuery->where('price', '<=', $request->max_price);
            }

            // Get total count
            $totalProperties = $propertyQuery->count();
            
            // Debug: Log final count and SQL query
            \Log::info('Hotel Search Debug - Final count: ' . $totalProperties);
            \Log::info('Hotel Search Debug - SQL: ' . $propertyQuery->toSql());
            \Log::info('Hotel Search Debug - Bindings: ' . json_encode($propertyQuery->getBindings()));

            // Order by ID descending
            $propertyQuery->orderBy('id', 'DESC');

            // Get properties with relationships
            $propertiesData = $propertyQuery
                ->with([
                    'category:id,category,image,slug_id,parameter_types',
                    'hotelRooms' => function($query) {
                        $query->where('status', 1)
                            ->with('roomType');
                    },
                    'assignParameter.parameter'
                ])
                ->select('id', 'slug_id', 'propery_type', 'title_image', 'category_id', 'title', 'price', 'city', 'state', 'country', 'rentduration', 'added_by', 'is_premium', 'property_classification', 'rent_package', 'latitude', 'longitude', 'total_click')
                ->withCount('favourite');

            // Latitude and Longitude
            if ($request->has('latitude') && !empty($request->latitude) && $request->has('longitude') && !empty($request->longitude)) {
                if ($request->has('radius') && !empty($request->radius)) {
                    $propertiesData = $propertiesData->selectRaw("
                            (6371 * acos(cos(radians($request->latitude))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians($request->longitude))
                            + sin(radians($request->latitude))
                            * sin(radians(latitude)))) AS distance")
                        ->where('latitude', '!=', 0)
                        ->where('longitude', '!=', 0)
                        ->having('distance', '<', $request->radius);
                } else {
                    $propertiesData = $propertiesData->where('latitude', $request->latitude)->where('longitude', $request->longitude);
                }
            }

            $propertiesData = $propertiesData->skip($offset)
                ->take($limit)
                ->get();

            // Format properties similar to getPropertyList
            $checkInDateObj = $checkInDate;
            $checkOutDateObj = $checkOutDate;
            $guests = $request->number_of_guests ?? 1;

            $formattedProperties = $propertiesData->map(function ($property) use ($checkInDateObj, $checkOutDateObj, $guests) {
                $property->promoted = $property->is_promoted;
                $property->is_premium = $property->is_premium == 1 ? true : false;
                $property->property_type = $property->propery_type;
                $property->assign_facilities = $property->assign_facilities;
                $property->is_apartment = false;
                unset($property->propery_type);

                // Calculate dynamic prices for hotel rooms
                if ($property->hotelRooms && $property->hotelRooms->count() > 0) {
                    foreach ($property->hotelRooms as $room) {
                        $totalPrice = 0;
                        $currentDate = $checkInDateObj->copy();
                        $endDate = $checkOutDateObj->copy();
                        $days = $currentDate->diffInDays($endDate);
                        
                        $roomAvailableDates = $room->available_dates ?? [];

                        while ($currentDate->lt($endDate)) {
                            $dateStr = $currentDate->format('Y-m-d');
                            $dailyBasePrice = $room->price_per_night;

                            // Check specific date price
                            if (is_array($roomAvailableDates)) {
                                foreach ($roomAvailableDates as $range) {
                                    if (isset($range['from'], $range['to']) && 
                                        $dateStr >= $range['from'] && 
                                        $dateStr <= $range['to']) {
                                        if (isset($range['price'])) {
                                            $dailyBasePrice = (float)$range['price'];
                                        }
                                        break;
                                    }
                                }
                            }

                            // Apply guest pricing
                            if (method_exists($room, 'calculatePrice')) {
                                $dailyFinalPrice = $room->calculatePrice($guests, $dailyBasePrice);
                            } else {
                                $dailyFinalPrice = $dailyBasePrice;
                            }
                            
                            $totalPrice += $dailyFinalPrice;
                            $currentDate->addDay();
                        }

                        // Update room price information
                        $room->total_price = $totalPrice;
                        // Update price_per_night to reflect the average daily rate for this specific search
                        $room->price_per_night = $days > 0 ? round($totalPrice / $days, 2) : $totalPrice;
                        $room->calculated_price = $totalPrice;
                    }
                }

                return $property;
            });

            $response = array(
                'error' => false,
                'total' => $totalProperties,
                'data' => $formattedProperties,
                'message' => 'Data fetched Successfully'
            );
            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint to check hotel room availability for a given date range
     * This helps debug availability queries
     */
    public function testHotelRoomAvailability(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_date' => 'required|date',
                'to_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fromDate = $request->from_date;
            $toDate = $request->to_date;

            // Get all available dates that cover the search period
            $availableDates = DB::table('available_dates_hotel_rooms')
                ->where('from_date', '<=', $fromDate)
                ->where('to_date', '>=', $toDate)
                ->where(function ($query) {
                    $query->where('type', '!=', 'reserved')
                        ->orWhereNull('type');
                })
                ->get();

            // Get hotel rooms with their properties
            $roomIds = $availableDates->pluck('hotel_room_id')->unique();
            
            $rooms = HotelRoom::whereIn('id', $roomIds)
                ->where('status', 1)
                ->with([
                    'property' => function($query) {
                        $query->select('id', 'title', 'slug_id', 'property_classification', 'status', 'request_status', 'city', 'state', 'country');
                    },
                    'roomType' => function($query) {
                        $query->select('id', 'name');
                    },
                    'availableDates' => function($query) use ($fromDate, $toDate) {
                        $query->where('from_date', '<=', $fromDate)
                            ->where('to_date', '>=', $toDate)
                            ->where(function ($q) {
                                $q->where('type', '!=', 'reserved')
                                    ->orWhereNull('type');
                            });
                    }
                ])
                ->get();

            // Filter to only include rooms from approved hotels
            $filteredRooms = $rooms->filter(function($room) {
                return $room->property && 
                       $room->property->property_classification == 5 &&
                       $room->property->status == 1 &&
                       $room->property->request_status == 'approved';
            });

            // Format response
            $result = [
                'search_period' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
                'total_available_dates_records' => $availableDates->count(),
                'total_rooms_found' => $filteredRooms->count(),
                'rooms' => $filteredRooms->map(function($room) use ($fromDate, $toDate) {
                    return [
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'price_per_night' => $room->price_per_night,
                        'status' => $room->status,
                        'property' => [
                            'id' => $room->property->id,
                            'title' => $room->property->title,
                            'slug_id' => $room->property->slug_id,
                            'property_classification' => $room->property->property_classification,
                            'status' => $room->property->status,
                            'request_status' => $room->property->request_status,
                            'city' => $room->property->city,
                            'state' => $room->property->state,
                            'country' => $room->property->country,
                        ],
                        'room_type' => $room->roomType ? [
                            'id' => $room->roomType->id,
                            'name' => $room->roomType->name,
                        ] : null,
                        'matching_available_dates' => $room->availableDates->map(function($date) {
                            return [
                                'id' => $date->id,
                                'from_date' => $date->from_date,
                                'to_date' => $date->to_date,
                                'price' => $date->price,
                                'type' => $date->type,
                                'nonrefundable_percentage' => $date->nonrefundable_percentage,
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json([
                'error' => false,
                'message' => 'Test completed successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getAgentVerificationFormFields(Request $request)
    {
        $data = VerifyCustomerForm::where('status', 'active')->with('form_fields_values:id,verify_customer_form_id,value')->select('id', 'name', 'field_type')->get();

        if (collect($data)->isNotEmpty()) {
            ApiResponseService::successResponse("Data Fetched Successfully", $data, array(), 200);
        } else {
            ApiResponseService::successResponse("No data found!");
        }
    }

    public function getAgentVerificationFormValues(Request $request)
    {
        $data = VerifyCustomer::where('user_id', Auth::user()->id)->with(['user' => function ($query) {
            $query->select('id', 'name', 'profile')->withCount(['property', 'projects']);
        }])->with(['verify_customer_values' => function ($query) {
            $query->with('verify_form:id,name,field_type', 'verify_form.form_fields_values:id,verify_customer_form_id,value')->select('id', 'verify_customer_id', 'verify_customer_form_id', 'value');
        }])->first();

        if (collect($data)->isNotEmpty()) {
            ApiResponseService::successResponse("Data Fetched Successfully", $data, array(), 200);
        } else {
            ApiResponseService::successResponse("No data found!");
        }
    }

    public function applyAgentVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_fields'           => 'required|array',
            'form_fields.*.id'      => 'required|exists:verify_customer_forms,id',
            'form_fields.*.value'   => 'required',
        ], [
            'form_fields.*.id'      => ':positionth Form Field id is not valid',
            'form_fields.*.value'   => ':positionth Form Field Value is not valid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            DB::beginTransaction();

            // If Payload is empty then show Payload is empty
            if (empty($request->form_fields)) {
                ApiResponseService::validationError("Payload is empty");
            }

            // Update the status of Customer (User) to pending
            $verifyCustomer = VerifyCustomer::updateOrCreate(['user_id' => Auth::user()->id], ['status' => 'pending']);
            $addCustomerValues = array();

            // Loop on request data of form_fields
            foreach ($request->form_fields as $key => $form_fields) {
                if (isset($form_fields['value']) && !empty($form_fields['value'])) {
                    // Check the Value is File upload or not
                    if ($request->hasFile('form_fields.' . $key . '.value')) {
                        $file = $request->file('form_fields.' . $key . '.value'); // Get Request File
                        $allowedImageExtensions = ['jpg', 'jpeg', 'png']; // Allowed Images Extensions
                        $allowedDocumentExtensions = ['doc', 'docx', 'pdf', 'txt']; // Allowed Documentation Extensions
                        $extension = $file->getClientOriginalExtension(); // Get Extension
                        // Check the extension and verify with allowed images or documents extensions
                        if (in_array($extension, $allowedImageExtensions) || in_array($extension, $allowedDocumentExtensions)) {
                            // Get Old form value
                            $oldFormValue = VerifyCustomerValue::where(['verify_customer_id' => $verifyCustomer->id, 'verify_customer_form_id' => $form_fields['id']])->with('verify_form:id,field_type')->first();
                            if (!empty($oldFormValue)) {
                                unlink_image($oldFormValue->value);
                            }
                            // Upload the new file
                            $destinationPath = public_path('images') . config('global.AGENT_VERIFICATION_DOC_PATH');
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            $imageName = microtime(true) . "." . $extension;
                            $file->move($destinationPath, $imageName);
                            $value = $imageName;
                        } else {
                            ApiResponseService::validationError("Invalid file type. Allowed types are: jpg, jpeg, png, doc, docx, pdf, txt");
                        }
                    } else {
                        // Check the value other than File Upload
                        $formFieldQueryData = VerifyCustomerForm::where('id', $form_fields['id'])->first();
                        if ($formFieldQueryData->field_type == 'radio' || $formFieldQueryData->field_type == 'dropdown') {
                            // IF Field Type is Radio or Dropdown, then check its value with database stored options
                            $checkValueExists = VerifyCustomerFormValue::where(['verify_customer_form_id' => $form_fields['id'], 'value' => $form_fields['value']])->first();
                            if (collect($checkValueExists)->isEmpty()) {
                                ApiResponseService::validationError("No Form Value Found");
                            }
                            $value = $form_fields['value'];
                        } else if ($formFieldQueryData->field_type == 'checkbox') {
                            // IF Field Type is Checkbox
                            $submittedValue = explode(',', $form_fields['value']); // Explode the Comma Separated Values
                            // Loop on the values and check its value with database stored options
                            foreach ($submittedValue as $key => $value) {
                                $checkValueExists = VerifyCustomerFormValue::where(['verify_customer_form_id' => $form_fields['id'], 'value' => $value])->first();
                                if (collect($checkValueExists)->isEmpty()) {
                                    ApiResponseService::validationError("No Form Value Found");
                                }
                            }
                            // Convert the value into json encode
                            $value = json_encode($form_fields['value']);
                        } else {
                            // Get Value as it is for other field types
                            $value = $form_fields['value'];
                        }
                    }
                    // Create an array to upsert data
                    $addCustomerValues[] = array(
                        'verify_customer_id'        => $verifyCustomer->id,
                        'verify_customer_form_id'   => $form_fields['id'],
                        'value'                     => $value,
                        'created_at'                => now(),
                        'updated_at'                => now()
                    );
                }
            }

            // If array is not empty then update or create in bulk
            if (!empty($addCustomerValues)) {
                VerifyCustomerValue::upsert($addCustomerValues, ['verify_customer_id', 'verify_customer_form_id'], ['value']);
            }


            // Send Notification to Admin
            $fcm_id = array();
            $user_data = User::select('fcm_id', 'name')->get();
            foreach ($user_data as $user) {
                array_push($fcm_id, $user->fcm_id);
            }

            if (!empty($fcm_id)) {
                $registrationIDs = $fcm_id;
                $fcmMsg = array(
                    'title' => 'Agent Verification Form Submitted',
                    'message' => 'Agent Verification Form Submitted',
                    'type' => 'agent_verification',
                    'body' => 'Agent Verification Form Submitted',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default'
                );
                send_push_notification($registrationIDs, $fcmMsg);
            }

            // Commit the changes and return response
            DB::commit();
            ApiResponseService::successResponse("Data Submitted Successfully");
        } catch (Exception $e) {
            DB::rollback();
            ApiResponseService::logErrorResponse($e, $e->getMessage(), 'Something Went Wrong');
        }
    }

    public function calculateMortgageCalculator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'down_payment' => 'nullable|lt:loan_amount',
            'show_all_details' => 'nullable|in:1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            $loanAmount = $request->loan_amount; // Loan amount
            $downPayment = $request->down_payment; // Down payment
            $interestRate = $request->interest_rate; // Annual interest rate in percentage
            $loanTermYear = $request->loan_term_years; // Loan term in years
            $showAllDetails = 0;
            if ($request->show_all_details == 1) {
                if (Auth::guard('sanctum')->check()) {
                    $packageLimit = HelperService::checkPackageLimit('mortgage_calculator_detail');
                    if ($packageLimit == true) {
                        $showAllDetails = 1;
                    }
                }
            }

            $schedule = $this->mortgageCalculation($loanAmount, $downPayment, $interestRate, $loanTermYear, $showAllDetails);
            ApiResponseService::successResponse('Data Fetched Successfully', $schedule, [], 200);
        } catch (Exception $e) {
            ApiResponseService::logErrorResponse($e, $e->getMessage(), 'Something Went Wrong');
        }
    }
    public function getProjectDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug_id' => 'required_without:id',
            'get_similar' => 'nullable|in:1'
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            // Projects are always previewable for all users (signed in and unsigned) regardless of subscription
            // Package check removed to allow preview access for everyone
            $getSimilarProjects = array();
            // Select core fields that are guaranteed to exist in all database versions
            // Optional fields (ownership_type, admin fields, company fields) are excluded to prevent SQL errors
            // These can be added back if migrations are confirmed to be run on production
            $project = Projects::select(
                'id', 'slug_id', 'title', 'title_ar', 'description', 'description_ar', 
                'area_description', 'area_description_ar', 'location', 'city', 'state', 
                'country', 'latitude', 'longitude', 'video_link', 'type', 'image', 
                'meta_title', 'meta_description', 'meta_keywords', 'meta_image',
                'status', 'request_status', 'total_click', 'category_id', 'added_by',
                'bedroom', 'bathroom', 'garage', 'year_built', 'lot_size', 
                'is_admin_listing', 'created_at', 'updated_at'
            )
                ->with('customer:id,name,profile,email,mobile,whatsappnumber,address,slug_id')
                ->with('gallary_images')
                ->with('documents')
                ->with('plans')
                ->with('category:id,category,image')
                ->where(function ($query) {
                    $query->where(['request_status' => 'approved', 'status' => 1]);
                });

            if ($request->get_similar == 1) {
                if ($request->has('id') && !empty($request->id)) {
                    $getSimilarProjects = $project->clone()->where('id', '!=', $request->id)->select('id', 'slug_id', 'city', 'state', 'country', 'title', 'title_ar', 'type', 'image', 'location', 'category_id', 'added_by', 'request_status')->get();
                } else if ($request->has('slug_id') && !empty($request->slug_id)) {
                    $getSimilarProjects = $project->clone()->where('slug_id', '!=', $request->slug_id)->select('id', 'slug_id', 'city', 'state', 'country', 'title', 'title_ar', 'type', 'image', 'location', 'category_id', 'added_by', 'request_status')->get();
                }
            }

            if ($request->id) {
                $project = $project->clone()->where('id', $request->id);
                HelperService::incrementTotalClick('project', $request->id);
            }

            if ($request->slug_id) {
                $project = $project->clone()->where('slug_id', $request->slug_id);
                HelperService::incrementTotalClick('project', null, $request->slug_id);
            }

            $total = $project->clone()->count();
            $data = $project->first();

            // Check if project exists
            if (empty($data)) {
                return ApiResponseService::errorResponse("Project not found", null, 404);
            }

            if (!empty($data) && isset($data->is_admin_listing) && $data->is_admin_listing == 1) {
                $adminCompanyTel1 = system_setting('company_tel1');
                $adminEmail = system_setting('company_email');
                $adminAddress = system_setting('company_address');
                $adminData = User::where('type', 0)->select('id', 'name', 'profile', 'slug_id')->first();

                // Only create custom customer if admin data exists
                if (!empty($adminData)) {
                    // Create modified customer data
                    $customCustomer = [
                        'id' => $adminData->id,
                        'name' => $adminData->name,
                        'slug_id' => $adminData->slug_id,
                        'profile' => !empty($adminData->getRawOriginal('profile')) ? $adminData->profile : url('assets/images/faces/2.jpg'),
                        'mobile' => !empty($adminCompanyTel1) ? $adminCompanyTel1 : "",
                        'email' => !empty($adminEmail) ? $adminEmail : "",
                        'address' => !empty($adminAddress) ? $adminAddress : "",
                    ];

                    // Force Laravel to include the modified customer data
                    $data->setRelation('customer', (object) $customCustomer);
                    $data->customer = (object) $customCustomer;
                }
            }

            // Include similar projects in response if requested
            $responseData = $data;
            if ($request->get_similar == 1) {
                return ApiResponseService::successResponseReturn(
                    "Data Fetch Successfully",
                    $responseData,
                    array('similar_projects' => !empty($getSimilarProjects) ? $getSimilarProjects : [])
                );
            }

            return ApiResponseService::successResponseReturn(
                "Data Fetch Successfully",
                $responseData,
            );
        } catch (Exception $e) {
            ApiResponseService::logErrorResponse($e, $e->getMessage(), 'Something Went Wrong');
            ApiResponseService::errorResponse('Something Went Wrong', null, null, $e);
        }
    }

    public function getAddedProjects(Request $request)
    {
        try {
            $user = Auth::user();


            // Base query for selected columns
            $projectsQuery = Projects::where('added_by', $user->id)
                ->with('category:id,slug_id,image,category', 'gallary_images', 'customer:id,name,profile,email,mobile');

            // Check if either id or slug_id is provided
            if ($request->filled('id') || $request->filled('slug_id')) {
                $specificProjectsQuery = $projectsQuery->clone()
                    ->where(function ($query) use ($request) {
                        $query->when($request->filled('id'), function ($query) use ($request) {
                            return $query->where('id', $request->id);
                        })
                            ->when($request->filled('slug_id'), function ($query) use ($request) {
                                return $query->orWhere('slug_id', $request->slug_id);
                            });
                    });
                $data = $specificProjectsQuery->clone()->first();
                if (collect($data)->isNotEmpty()) {
                    $data = $data->toArray();
                    $data['created_at'] = Carbon::parse($data['created_at'])->diffForHumans();
                }
                // Get Similar Projects
                if ($request->has('id')) {
                    $getSimilarProjects = $projectsQuery->clone()->where('id', '!=', $request->id)->get()->map(function ($project) {
                        $array = $project->toArray();
                        $array['created_at'] = Carbon::parse($project->created_at)->diffForHumans();
                        return $array;
                    });
                } else if ($request->has('slug_id')) {
                    $getSimilarProjects = $projectsQuery->clone()->where('slug_id', '!=', $request->slug_id)->get()->map(function ($project) {
                        $projectArray = $project->toArray(); // detach from Eloquent model
                        $projectArray['created_at'] = Carbon::parse($project->created_at)->diffForHumans();
                        return $projectArray;
                    });
                }

                ApiResponseService::successResponse("Data Fetched Successfully", $data, array('similar_projects' => $getSimilarProjects));
            } else {

                $offset = isset($request->offset) ? $request->offset : 0;
                $limit = isset($request->limit) ? $request->limit : 10;

                // If neither id nor slug_id is provided, use the base query for selected columns
                $projectsQuery = $projectsQuery->clone()
                    ->when($request->filled('request_status'), function ($query) use ($request) {
                        // IF Request Status is passed and status has approved or rejected or pending or all
                        $requestAccessData = explode(',', $request->request_status);
                        return $query->whereIn('request_status', $requestAccessData);
                    })->select('id', 'slug_id', 'city', 'state', 'country', 'title', 'title_ar', 'type', 'image', 'location', 'status', 'category_id', 'added_by', 'created_at', 'request_status', 'description', 'description_ar', 'area_description', 'area_description_ar', 'bedroom', 'bathroom', 'garage', 'year_built', 'lot_size')
                    ->orderBy('created_at', 'DESC');
                // Get Total
                $total = $projectsQuery->clone()->count();

                // Calculate total clicks for all user projects (handle null values)
                try {
                    $totalClicks = Projects::where('added_by', $user->id)->sum('total_click') ?? 0;
                } catch (Exception $e) {
                    $totalClicks = 0;
                }

                // Get Data
                $data = $projectsQuery->clone()->take($limit)->skip($offset)->get()->map(function ($project) {
                    $project->reject_reason = (object)array();
                    if ($project->request_status == 'rejected') {
                        $project->reject_reason = $project->reject_reason()->select('id', 'project_id', 'reason', 'created_at')->latest()->first();
                    }
                    $createdAt = $project->created_at;
                    $projectArray = $project->toArray();
                    $projectArray['created_at'] = $createdAt ? $createdAt->diffForHumans() : '';
                    return $projectArray;
                });
                return ApiResponseService::successResponseReturn("Data Fetched Successfully", $data, array('total' => $total, 'total_clicks' => $totalClicks));
            }
        } catch (Exception $e) {
            ApiResponseService::errorResponse('Something Went Wrong', null, null, $e);
        }
    }

    public function getProjects(Request $request)
    {
        try {
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;
            $latitude = $request->has('latitude') ? $request->latitude : null;
            $longitude = $request->has('longitude') ? $request->longitude : null;
            $radius = $request->has('radius') ? $request->radius : null;

            // Query
            $projectsQuery = Projects::where(['request_status' => 'approved', 'status' => 1])
                ->with('category:id,slug_id,image,category', 'gallary_images', 'customer:id,name,profile,email,mobile,slug_id')
                ->select('id', 'slug_id', 'city', 'state', 'country', 'title', 'title_ar', 'type', 'image', 'status', 'location', 'category_id', 'added_by', 'is_admin_listing', 'request_status', 'description', 'description_ar', 'area_description', 'area_description_ar', 'bedroom', 'bathroom', 'garage', 'year_built', 'lot_size')
                ->when($latitude && $longitude, function ($query) use ($latitude, $longitude, $radius) {
                    if ($radius && !empty($radius)) {
                        $query->selectRaw("
                                    (6371 * acos(cos(radians($latitude))
                                    * cos(radians(latitude))
                                    * cos(radians(longitude) - radians($longitude))
                                    + sin(radians($latitude))
                                    * sin(radians(latitude)))) AS distance")
                            ->where('latitude', '!=', 0)
                            ->where('longitude', '!=', 0)
                            ->having('distance', '<', $radius);
                    } else {
                        $query->where(['latitude' => $latitude, 'longitude' => $longitude]);
                    }
                })
                ->when($request->filled('get_featured') && $request->get_featured == 1, function ($query) use ($request) {
                    return $query->whereHas('advertisement', function ($query) {
                        $query->where('for', 'project')->where('status', 0)->where('is_enable', 1);
                    });
                });

            $postedSince = $request->posted_since;
            if (isset($postedSince)) {
                // 0: last_week   1: yesterday
                if ($postedSince == 0) {
                    $projectsQuery = $projectsQuery->clone()->whereBetween(
                        'created_at',
                        [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]
                    );
                }
                if ($postedSince == 1) {
                    $projectsQuery =  $projectsQuery->clone()->whereDate('created_at', Carbon::yesterday());
                }
            }

            // Get Total
            $total = $projectsQuery->clone()->count();

            // Get Admin Company Details
            $adminCompanyTel1 = system_setting('company_tel1');
            $adminEmail = system_setting('company_email');
            $adminUser = User::where('id', 1)->select('id', 'slug_id')->first();

            // Get Data
            $data = $projectsQuery->clone()->take($limit)->skip($offset)->get()->map(function ($project) use ($adminCompanyTel1, $adminEmail, $adminUser) {
                // Check if listing is by admin then add admin details in customer
                if ($project->is_admin_listing == true) {
                    unset($project->customer);
                    $project->customer = array(
                        'name' => "Admin",
                        'email' => $adminEmail,
                        'mobile' => $adminCompanyTel1,
                        'slug_id' => $adminUser->slug_id
                    );
                }
                return $project;
            });
            return ApiResponseService::successResponseReturn("Data Fetched Successfully", $data, array('total' => $total));
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function flutterwave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required'
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $packageId = $request->package_id;
            $package = Package::where(['id' => $packageId, 'status' => 1])->first(); // Get price data from the database
            if (collect($package)->isEmpty()) {
                ApiResponseService::validationError("Package Not Found");
            }

            $currentUser = Auth::user();
            $currentUserName = $currentUser->name ?? null;
            $currentUserEmail = $currentUser->email ?? null;
            $currentUserNumber = $currentUser->mobile ?? null;

            $currencySymbol = HelperService::getSettingData('flutterwave_currency');

            $reference = Flutterwave::generateReference(); //This generates a payment reference

            //Add Payment Data to Payment Transactions Table
            $paymentTransactionData = PaymentTransaction::create([
                'user_id'         => $currentUser->id,
                'package_id'      => $package->id,
                'amount'          => $package->price,
                'payment_gateway' => "Flutterwave",
                'payment_status'  => 'pending',
                'order_id'        => $reference,
                'payment_type'    => 'online payment'
            ]);

            // Enter the details of the payment
            $data = [
                'payment_options' => 'card,banktransfer',
                'amount' => $package->price,
                'email' => $currentUserEmail,
                'tx_ref' => $reference,
                'currency' => $currencySymbol,
                'redirect_url' => URL::to('api/flutterwave-payment-status'),
                'customer' => [
                    'email' => $currentUserEmail,
                    "phone_number" => $currentUserNumber,
                    "name" => $currentUserName
                ],
                "meta" => [
                    "payment_transaction_id" => $paymentTransactionData->id,
                ]
            ];

            $payment = Flutterwave::initializePayment($data);

            if (empty($payment) || $payment['status'] !== 'success') {
                ApiResponseService::validationError("Payment Failed");
            } else {
                DB::commit();
                ApiResponseService::successResponse("Data Fetched Successfully", $payment);
            }
        } catch (Exception $e) {
            DB::rollBack();
            ApiResponseService::errorResponse();
        }
    }

    public function flutterwavePaymentStatus(Request $request)
    {
        $flutterwavePaymentInfo = $request->all();
        // Get Web URL
        $webURL = system_setting('web_url') ?? null;
        if (isset($flutterwavePaymentInfo) && !empty($flutterwavePaymentInfo) && isset($flutterwavePaymentInfo['status']) && !empty($flutterwavePaymentInfo['status'])) {
            if ($flutterwavePaymentInfo['status'] == "successful") {
                $webWithStatusURL = $webURL . '/payment/success';
                $response['error'] = false;
                $response['message'] = "Your Purchase Package Activate Within 10 Minutes ";
                $response['data'] = $flutterwavePaymentInfo;
            } else {
                $trxRef = $flutterwavePaymentInfo['tx_ref'];
                PaymentTransaction::where('order_id', $trxRef)->update(['payment_status' => 'failed']);
                $webWithStatusURL = $webURL . '/payment/fail';
                $response['error'] = true;
                $response['message'] = "Payment Cancelled / Declined ";
                $response['data'] = !empty($flutterwavePaymentInfo) ? $flutterwavePaymentInfo : "";
            }
        } else {
            $webWithStatusURL = $webURL . '/payment/fail';
            $response['error'] = true;
            $response['message'] = "Payment Cancelled / Declined ";
        }

        if ($webURL) {
            echo "<html>
            <body>
            Redirecting...!
            </body>
            <script>
                window.location.replace('" . $webWithStatusURL . "');
            </script>
            </html>";
        } else {
            echo "<html>
            <body>
            Redirecting...!
            </body>
            <script>
                console.log('No web url added');
            </script>
            </html>";
        }
        // return (response()->json($response));
    }

    public function blockChatUser(Request $request)
    {
        $userId = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'to_user_id' => [
                'required_without:to_admin',
                'exists:customers,id',
                function ($attribute, $value, $fail) use ($userId) {
                    if ($value == $userId) {
                        $fail('You cannot block yourself.');
                    }
                }
            ],
            'to_admin' => 'required_without:to_user_id|in:1',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            $blockUserData = array(
                'by_user_id' => $userId,
                'reason' => $request->reason ?? null
            );
            if ($request->has('to_user_id') && !empty($request->to_user_id)) {
                $ifExtryExists = BlockedChatUser::where(['by_user_id' => $userId, 'user_id' => $request->to_user_id])->count();
                if ($ifExtryExists) {
                    ApiResponseService::validationError("User Already Blocked");
                }
                $blockUserData['user_id'] = $request->to_user_id;
            } else if ($request->has('to_admin') && $request->to_admin == 1) {
                $ifExtryExists = BlockedChatUser::where(['by_user_id' => $userId, 'admin' => 1])->count();
                if ($ifExtryExists) {
                    ApiResponseService::validationError("Admin Already Blocked");
                }
                $blockUserData['admin'] = 1;
            } else {
                ApiResponseService::errorResponse("Something Went Wrong in API");
            }

            BlockedChatUser::create($blockUserData);
            ApiResponseService::successResponse("User Blocked Successfully");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function unBlockChatUser(Request $request)
    {
        $userId = Auth::user()->id;
        $validator = Validator::make($request->all(), [
            'to_user_id' => [
                'required_without:to_admin',
                'exists:customers,id',
                function ($attribute, $value, $fail) use ($userId) {
                    if ($value == $userId) {
                        $fail('You cannot unblock yourself.');
                    }
                }
            ],
            'to_admin' => 'required_without:to_user_id|in:1',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            if ($request->has('to_user_id') && !empty($request->to_user_id)) {
                $blockedUserQuery = BlockedChatUser::where(['by_user_id' => $userId, 'user_id' => $request->to_user_id]);
                $ifExtryExists = $blockedUserQuery->clone()->count();
                if (!$ifExtryExists) {
                    ApiResponseService::validationError("No Blocked User Found");
                }
                $blockedUserQuery->delete();
            } else if ($request->has('to_admin') && $request->to_admin == 1) {
                $blockedUserQuery = BlockedChatUser::where(['by_user_id' => $userId, 'user_id' => $request->to_user_id]);
                $ifExtryExists = $blockedUserQuery->count();
                if (!$ifExtryExists) {
                    ApiResponseService::validationError("No Blocked User Found");
                }
                $blockedUserQuery->delete();
            } else {
                ApiResponseService::validationError("Something Went Wrong in API");
            }
            ApiResponseService::successResponse("User Unblocked Successfully");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getFacilitiesForFilter(Request $request)
    {
        try {
            $categoryId = $request->has('category_id') && !empty($request->category_id) ? $request->category_id : null;
            
            if ($categoryId) {
                // Get parameters ordered by category
                $category = Category::find($categoryId);
                if ($category && !empty($category->parameter_types)) {
                    $parameterIds = explode(',', $category->parameter_types);
                    // Remove empty values and convert to integers
                    $parameterIds = array_filter(array_map('intval', $parameterIds));
                    
                    if (!empty($parameterIds)) {
                        $parameters = parameter::whereIn('id', $parameterIds)->get();
                        // Sort by the order in category's parameter_types
                        $parameters = $parameters->sortBy(function ($item) use ($parameterIds) {
                            return array_search($item->id, $parameterIds);
                        })->values();
                    } else {
                        $parameters = parameter::get();
                    }
                } else {
                    $parameters = parameter::get();
                }
            } else {
                // Return all parameters if no category_id provided
                $parameters = parameter::get();
            }
            
            // Filter parameters based on commercial properties
            if ($request->has('property_classification') && $request->property_classification == 2) {
                // Hide bedroom and bathroom filters for commercial properties
                $parameters = $parameters->filter(function($parameter) {
                    $name = strtolower($parameter->name);
                    return $name !== 'bedrooms' && $name !== 'bathrooms';
                });
                
                // Add area filter for commercial properties
                $areaParameter = new \stdClass();
                $areaParameter->id = 999999;
                $areaParameter->name = 'Area';
                $areaParameter->type_of_parameter = 'number';
                $areaParameter->unit = 'sqm';
                $areaParameter->description = 'Area in square meters';
                $areaParameter->created_at = now();
                $areaParameter->updated_at = now();
                
                // Add area parameter to the collection
                $parameters->push($areaParameter);
            }
            
            ApiResponseService::successResponse("Data Fetched Successfully", $parameters);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getPrivacyPolicy()
    {
        try {
            $privacyPolicy = system_setting("privacy_policy");
            ApiResponseService::successResponse("Data Fetched Successfully", !empty($privacyPolicy) ? $privacyPolicy : "");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getTermsAndConditions()
    {
        try {
            $termsAndConditions = system_setting("terms_conditions");
            ApiResponseService::successResponse("Data Fetched Successfully", !empty($termsAndConditions) ? $termsAndConditions : "");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function userRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
            're_password' => 'required|same:password',
            'mobile' => 'nullable',
            'customer_type' => 'nullable|in:property_owner,agent',
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $customerExists = Customer::where(['email' => $request->email, 'logintype' => 3])->count();
            if ($customerExists) {
                ApiResponseService::validationError("User Already Exists");
            }

            $customerData = $request->except('pasword', 're_password');
            $customerData = array_merge($customerData, array(
                'password' => Hash::make($request->password),
                'auth_id' => Str::uuid()->toString(),
                'slug_id' => generateUniqueSlug($request->name, 5),
                'notification' => 1,
                'isActive' => 1,
                'logintype' => 3,
                'mobile' => $request->has('mobile') && !empty($request->mobile) ? $request->mobile : null,
                'customer_type' => $request->has('customer_type') ? $request->customer_type : null,
            ));

            // Validate management_type if customer is property_owner
            if (isset($customerData['customer_type']) && $customerData['customer_type'] === 'property_owner') {
                if (isset($customerData['management_type']) && !in_array($customerData['management_type'], ['himself', 'as home'])) {
                    ApiResponseService::validationError("Invalid management type. Must be 'himself' or 'as home'.");
                }
            }

            Customer::create($customerData);


            // Check if OTP already exists and is still valid
            $existingOtp = NumberOtp::where('email', $customerData['email'])->first();

            if ($existingOtp && now()->isBefore($existingOtp->expire_at)) {
                // OTP is still valid
                $otp = $existingOtp->otp;
            } else {
                // Generate a new OTP
                $otp = rand(123456, 999999);
                $expireAt = now()->addMinutes(10); // Set OTP expiry time

                // Update or create OTP entry in the database
                NumberOtp::updateOrCreate(
                    ['email' => $customerData['email']],
                    ['otp' => $otp, 'expire_at' => $expireAt]
                );
            }

            /** Register Mail */
            // Get Data of email type
            $emailTypeData = HelperService::getEmailTemplatesTypes("welcome_mail");

            // Email Template
            $welcomeEmailTemplateData = system_setting($emailTypeData['type']);
            $appName = env("APP_NAME") ?? "eBroker";
            $variables = array(
                'app_name' => $appName,
                'user_name' => !empty($request->name) ? $request->name : "$appName User",
                'email' => $request->email,
            );
            if (empty($welcomeEmailTemplateData)) {
                $welcomeEmailTemplateData = "Welcome to $appName";
            }
            $welcomeEmailTemplate = HelperService::replaceEmailVariables($welcomeEmailTemplateData, $variables);

            $data = array(
                'email_template' => $welcomeEmailTemplate,
                'email' => $request->email,
                'title' => $emailTypeData['title'],
            );
            HelperService::sendMail($data, true);

            /** Send OTP mail for verification */
            // Get Data of email type
            $emailTypeData = HelperService::getEmailTemplatesTypes("verify_mail");

            // Email Template
            $propertyFeatureStatusTemplateData = system_setting($emailTypeData['type']);
            $appName = env("APP_NAME") ?? "eBroker";
            $variables = array(
                'app_name' => $appName,
                'otp' => $otp
            );
            if (empty($propertyFeatureStatusTemplateData)) {
                $propertyFeatureStatusTemplateData = "Your OTP :- " . $otp;
            }
            $propertyFeatureStatusTemplate = HelperService::replaceEmailVariables($propertyFeatureStatusTemplateData, $variables);

            $data = array(
                'email_template' => $propertyFeatureStatusTemplate,
                'email' => $request->email,
                'title' => $emailTypeData['title'],
            );
            HelperService::sendMail($data);
            DB::commit();
            ApiResponseService::successResponse('User Registered Successfully');
        } catch (Exception $e) {
            DB::rollback();
            if (Str::contains($e->getMessage(), [
                'Failed',
                'Mail',
                'Mailer',
                'MailManager',
                "Connection could not be established"
            ])) {
                ApiResponseService::validationError("There is issue with mail configuration, kindly contact admin regarding this");
            } else {
                ApiResponseService::errorResponse();
            }
        }
    }

    public function changePropertyStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:propertys,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            // Get Query Data of property based on property id
            $propertyQueryData = Property::find($request->property_id);
            if ($propertyQueryData->request_status != 'approved') {
                ApiResponseService::validationError("Property is not approved");
            }
            // update user status
            $propertyQueryData->status = $request->status == 1 ? 1 : 0;
            $propertyQueryData->save();
            ApiResponseService::successResponse("Data Updated Successfully");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            $isUserExists = Customer::where(['email' => $request->email, 'logintype' => 3])->count();
            if ($isUserExists) {
                $token = HelperService::generateToken();
                HelperService::storeToken($request->email, $token);

                // Use FRONTEND_URL if available, otherwise fallback to APP_URL
                // Ideally, FRONTEND_URL should be set in .env to point to the Next.js app (e.g. http://localhost:3000)
                $rootUrl = env("FRONTEND_URL");
                if (empty($rootUrl)) {
                    $rootUrl = env("APP_URL") ?? FacadesRequest::root();
                }
                
                $trimmedUrl = rtrim($rootUrl, '/'); // remove / from end if exists (fixed from ltrim)
                $link = $trimmedUrl . "/reset-password?token=" . $token;
                
                $data = array(
                    'email' => $request->email,
                    'link' => $link
                );

                // Get Data of email type
                $emailTypeData = HelperService::getEmailTemplatesTypes("reset_password");

                // Email Template
                $verifyEmailTemplateData = system_setting("password_reset_mail_template");
                $variables = array(
                    'app_name' => env("APP_NAME") ?? "eBroker",
                    'email' => $request->email,
                    'link' => $link
                );
                if (empty($verifyEmailTemplateData)) {
                    $verifyEmailTemplateData = "Your reset password link is :- $link";
                }
                $verifyEmailTemplate = HelperService::replaceEmailVariables($verifyEmailTemplateData, $variables);

                $data = array(
                    'email_template' => $verifyEmailTemplate,
                    'email' => $request->email,
                    'title' => $emailTypeData['title'],
                );
                // Send mail with skipPdf = true (3rd argument) as PDF is not needed for password reset
                HelperService::sendMail($data, false, true);
                ApiResponseService::successResponse('Reset link sent to your mail successfully');
            } else {
                ApiResponseService::validationError("No User Found");
            }
        } catch (Exception $e) {
            if (Str::contains($e->getMessage(), [
                'Failed',
                'Mail',
                'Mailer',
                "Connection could not be established"
            ])) {
                ApiResponseService::validationError("There is issue with mail configuration, kindly contact admin regarding this");
            } else {
                ApiResponseService::errorResponse();
            }
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }

        $email = HelperService::verifyToken($request->token);
        if ($email) {
            $user = Customer::where('email', $email)->where('logintype', 3)->first();
            if ($user) {
                $user->password = Hash::make($request->password);
                $user->save();
                HelperService::expireToken($email);
                ApiResponseService::successResponse('Password reset successfully');
            } else {
                ApiResponseService::validationError('User not found');
            }
        } else {
            ApiResponseService::validationError('Invalid or expired token');
        }
    }

    public function generateRazorpayOrderId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id'
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            $packageData = Package::findOrFail($request->package_id);

            $razorPayApiKey = system_setting('razor_key');
            $razorPaySecretKey = system_setting('razor_secret');
            $currencyCode = system_setting('currency_code');
            $supportedCurrencies = array('AED', 'ALL', 'AMD', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BZD', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'GBP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'IQD', 'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SCR', 'SEK', 'SGD', 'SLL', 'SOS', 'SSP', 'SVC', 'SZL', 'THB', 'TND', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VND', 'VUV', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW');

            if (empty($razorPayApiKey) || empty($razorPaySecretKey)) {
                ApiResponseService::validationError("Payment Configuration is invalid, contact admin regarding this");
            }

            if ($packageData->price == 0) {
                ApiResponseService::validationError("Package is Free, no need to proceed for payment");
            }

            if (!empty($currencyCode)) {
                if (!in_array($currencyCode, $supportedCurrencies)) {
                    ApiResponseService::validationError("Currency Selected in system is not supported");
                }
            } else {
                ApiResponseService::validationError("No Currency data available");
            }

            $api = new Api($razorPayApiKey, $razorPaySecretKey);


            $orderData = [
                'receipt'         => Str::uuid(),
                'amount'          => $packageData->price * 100, // Amount in paise, i.e., 50000 paise = ₹500
                'currency'        => $currencyCode,
                'payment_capture' => 1, // 1 for automatic capture, 0 for manual capture
                'notes' => array(
                    'user_id' => Auth::user()->id,
                    'package_id' => $request->package_id
                )
            ];

            $order = $api->order->create($orderData);
            $data = array(
                'order_id' => $order->id,
                'amount'   => $orderData['amount'],
                'currency' => $orderData['currency']
            );
            ApiResponseService::successResponse("Order Created Successfully", $data);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function changeProjectStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            $loggedInUserID = Auth::user()->id;
            // Get Query Data of project based on project id
            $projectQuery = Projects::where('id', $request->project_id);
            $projectQueryData = $projectQuery->firstOrFail();
            if ($projectQueryData->added_by != $loggedInUserID) {
                ApiResponseService::validationError("Cannot change the status of project owned by others");
            }
            if ($projectQueryData->request_status != 'approved') {
                ApiResponseService::validationError("Project is not approved");
            }
            // update user status
            $projectQuery->update(['status' => $request->status == 1 ? 1 : 0]);
            ApiResponseService::successResponse("Data Updated Successfully");
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getFeatures(Request $request)
    {
        try {
            $features = Feature::where('status', 1)->get();
            ApiResponseService::successResponse("Data Fetched Successfully", $features);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }
    public function getPackages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform_type' => 'nullable|in:ios',
        ]);

        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            $packageQuery = Package::query();
            $filteredPackageQuery = $packageQuery->clone()->when($request->has('platform_type') && $request->platform_type == 'ios', function ($query) {
                $query->whereNotNull('ios_product_id')->orWhere('package_type', 'free');
            });

            $auth = Auth::guard('sanctum');
            $getActivePackages = array();
            $getAllActivePackageIds = array();

            if ($auth->check()) {
                $userId = $auth->user()->id;
                $getAllActivePackageIds = HelperService::getAllActivePackageIds($userId);

                if (!empty($getAllActivePackageIds)) {
                    $getActivePackages = $packageQuery->clone()->withTrashed()->whereIn('id', $getAllActivePackageIds)
                        ->with(['package_features' => function ($query) use ($userId) {
                            $query->with(['feature', 'user_package_limits' => function ($subQuery) use ($userId) {
                                $subQuery->whereHas('user_package', function ($userQuery) use ($userId) {
                                    $userQuery->where('user_id', $userId)->orderBy('id', 'desc');
                                });
                            }]);
                        }, 'user_packages' => function ($query) use ($userId) {
                            $query->where('user_id', $userId)->orderBy('id', 'desc');
                        }])
                        ->get()
                        ->map(function ($package) {
                            return [
                                'id'                        => $package->id,
                                'name'                      => $package->name,
                                'package_type'              => $package->package_type,
                                'ios_product_id'            => $package->ios_product_id,
                                'price'                     => $package->price,
                                'duration'                  => $package->duration,
                                'start_date'                => $package->user_packages[0]->start_date,
                                'end_date'                  => $package->user_packages[0]->end_date,
                                'created_at'                => $package->created_at,
                                'package_status'            => $package->package_payment_status,
                                'payment_transaction_id'    => $package->payment_transaction_id,
                                'features'                  => $package->package_features->map(function ($package_feature) {
                                    $usedLimit = !empty($package_feature->user_package_limits && !empty($package_feature->user_package_limits[0])) ? $package_feature->user_package_limits[0]->used_limit : null;
                                    $totalLimit = !empty($package_feature->user_package_limits && !empty($package_feature->user_package_limits[0])) ? $package_feature->user_package_limits[0]->total_limit : null;
                                    return [
                                        'id'            => $package_feature->feature->id,
                                        'name'          => $package_feature->feature->name,
                                        'limit_type'    => $package_feature->limit_type,
                                        'limit'         => $package_feature->limit,
                                        'used_limit'    => $usedLimit,
                                        'total_limit'   => $totalLimit
                                    ];
                                }),
                                'is_active' => 1
                            ];
                        });
                }
            }

            $getOtherPackagesQuery = $filteredPackageQuery->clone()->where('status', 1)
                ->whereHas('package_features', function ($query) {
                    $query->whereHas('feature', function ($query) {
                        $query->where('status', 1);
                    });
                });

            if (!empty($getAllActivePackageIds)) {
                $getOtherPackagesQuery = $getOtherPackagesQuery->whereNotIn('id', $getAllActivePackageIds);
            }

            $getOtherPackageData = $getOtherPackagesQuery->with(['package_features' => function ($query) {
                $query->whereHas('feature', function ($query) {
                    $query->where('status', 1);
                });
            }])
                ->get()
                ->map(function ($package) {
                    return [
                        'id'                        => $package->id,
                        'name'                      => $package->name,
                        'package_type'              => $package->package_type,
                        'price'                     => $package->price,
                        'ios_product_id'            => $package->ios_product_id,
                        'duration'                  => $package->duration,
                        'created_at'                => $package->created_at,
                        'package_status'            => $package->package_payment_status,
                        'payment_transaction_id'    => $package->payment_transaction_id,
                        'features'                  => $package->package_features->map(function ($package_feature) {
                            return [
                                'id'                => $package_feature->feature->id,
                                'name'              => $package_feature->feature->name,
                                'limit_type'        => $package_feature->limit_type,
                                'limit'             => $package_feature->limit,
                            ];
                        }),
                    ];
                });
            $features = Feature::get();

            ApiResponseService::successResponse("Data Fetched Successfully", $getOtherPackageData, [
                'active_packages' => $getActivePackages,
                'all_features'    => $features
            ]);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getPaymentIntent(Request $request)
    {
        // Support both single package_id (backward compatibility) and multiple package_ids
        $validator = Validator::make($request->all(), [
            'package_id'        => 'required_without:package_ids',
            'package_ids'       => 'required_without:package_id|array|min:1',
            'package_ids.*'     => 'required|exists:packages,id',
            'platform_type'     => 'required|in:app,web',
            'payment_method'    => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        
        try {
            DB::beginTransaction();
            
            $preferredMethod = $request->input('payment_method');
            $paymentSettings = HelperService::getActivePaymentDetails($preferredMethod);
            
            if (empty($paymentSettings)) {
                if ($preferredMethod) {
                    ApiResponseService::validationError("Payment method '{$preferredMethod}' is not available or disabled.");
                }
                ApiResponseService::validationError("None of payment method is activated");
            }

            // Check for missing credentials for Paymob to provide better error message
            if ($paymentSettings['payment_method'] == 'paymob' && empty($paymentSettings['paymob_api_key'])) {
                ApiResponseService::validationError("Paymob configuration is missing API Key. Please check your settings or .env file.");
            }

            // Determine package IDs (support both single and multiple)
            $packageIds = [];
            if ($request->has('package_ids') && is_array($request->package_ids)) {
                $packageIds = $request->package_ids;
            } elseif ($request->has('package_id')) {
                $packageIds = [$request->package_id];
            } else {
                ApiResponseService::validationError("Either package_id or package_ids is required");
            }

            $userId = Auth::user()->id;
            
            // Validate all packages
            $packages = Package::whereIn('id', $packageIds)
                ->where('package_type', 'paid')
                ->get();
                
            if ($packages->count() !== count($packageIds)) {
                ApiResponseService::validationError("One or more packages not found or are not paid packages");
            }

            // Check if user already has any of these packages active
            $purchasedPackages = UserPackage::where('user_id', $userId)
                ->whereIn('package_id', $packageIds)
                ->onlyActive()
                ->pluck('package_id')
                ->toArray();
                
            if (!empty($purchasedPackages)) {
                $packageNames = Package::whereIn('id', $purchasedPackages)->pluck('name')->toArray();
                ApiResponseService::validationError("You already have active packages: " . implode(', ', $packageNames));
            }

            // Calculate total amount
            $totalAmount = $packages->sum('price');
            
            // Create a group transaction ID for linking all package transactions
            $groupTransactionId = 'PKG_GROUP_' . Str::uuid();
            
            // Create payment transactions for each package
            $paymentTransactions = [];
            $packageDescriptions = [];
            
            foreach ($packages as $package) {
                $packageTransactionId = $groupTransactionId . '_' . $package->id;
                
                $paymentTransaction = PaymentTransaction::create([
                    'user_id'         => $userId,
                    'package_id'      => $package->id,
                    'amount'          => $package->price,
                    'payment_gateway' => Str::ucfirst($paymentSettings['payment_method']),
                    'payment_status'  => 'pending',
                    'order_id'        => null,
                    'transaction_id' => $packageTransactionId,
                    'payment_type'    => 'online payment'
                ]);
                
                $paymentTransactions[] = $paymentTransaction;
                $packageDescriptions[] = $package->name;
            }

            // Prepare metadata for payment intent (using first transaction as primary)
            $primaryTransaction = $paymentTransactions[0];
            $metadata = [
                'payment_transaction_id' => $primaryTransaction->id,
                'group_transaction_id'   => $groupTransactionId,
                'package_ids'            => $packageIds,
                'package_count'          => count($packageIds),
                'user_id'                => $userId,
                'email'                  => Auth::user()->email,
                'platform_type'          => $request->platform_type,
                'description'            => $request->description ?? (count($packageIds) > 1 ? 'Multiple Packages: ' . implode(', ', $packageDescriptions) : $packages[0]->name),
                'user_name'              => Auth::user()->name ?? "",
                'first_name'             => explode(' ', Auth::user()->name ?? 'Customer')[0] ?? 'Customer',
                'last_name'              => explode(' ', Auth::user()->name ?? 'Customer', 2)[1] ?? 'Customer',
                'phone'                  => Auth::user()->mobile ?? Auth::user()->whatsappnumber ?? 'NA',
            ];

            // Create payment intent with total amount
            $paymentIntent = PaymentService::create($paymentSettings)->createAndFormatPaymentIntent(
                round($totalAmount, 2), 
                $metadata
            );
            
            // Update all payment transactions with the order_id
            foreach ($paymentTransactions as $transaction) {
                $transaction->update(['order_id' => $paymentIntent['id']]);
            }

            // Reload transactions with updated data
            $paymentTransactionsData = PaymentTransaction::whereIn('id', collect($paymentTransactions)->pluck('id'))->get();
            
            // Custom Array to Show as response
            $paymentGatewayDetails = array(
                ...$paymentIntent,
                'group_transaction_id' => $groupTransactionId,
                'total_amount' => $totalAmount,
                'package_count' => count($packageIds),
                'payment_transactions' => $paymentTransactionsData->map(function($pt) {
                    return [
                        'id' => $pt->id,
                        'package_id' => $pt->package_id,
                        'package_name' => $pt->package->name ?? 'N/A',
                        'amount' => $pt->amount,
                        'transaction_id' => $pt->transaction_id,
                    ];
                }),
            );

            DB::commit();
            ApiResponseService::successResponse("", [
                "payment_intent" => $paymentGatewayDetails, 
                "payment_transactions" => $paymentTransactionsData
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            ApiResponseService::logErrorResponse($e);
            ApiResponseService::errorResponse();
        }
    }

    public function makePaymentTransactionFail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_transaction_id' => 'required|exists:payment_transactions,id',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            PaymentTransaction::where('id', $request->payment_transaction_id)->update(['payment_status' => 'failed']);
            ApiResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ApiResponseService::logErrorResponse($e);
            ApiResponseService::errorResponse();
        }
    }

    public function checkPackageLimit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:property_list,property_feature,project_list,mortgage_calculator_detail,premium_properties,project_access',
        ]);
        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = HelperService::checkPackageLimit($request->type, true);
            return ApiResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (Exception $e) {
            return ApiResponseService::errorResponse();
        }
    }

    /** Get Property And Project Featured */
    public function getFeaturedData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:property,project',
            ]);

            if ($validator->fails()) {
                ApiResponseService::validationError($validator->errors()->first());
            }
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 10;
            $loggedInUserID = Auth::user()->id;
            $advertisementQuery = Advertisement::select('id', 'status', 'start_date', 'end_date', 'property_id', 'project_id');
            if ($request->type == 'property') {
                $advertisementQuery->whereHas('property', function ($query) use ($loggedInUserID) {
                    $query->where(['post_type' => 1, 'added_by' => $loggedInUserID]);
                })->with('property:id,category_id,slug_id,title,propery_type,city,state,country,price,title_image', 'property.category:id,category,image');
            } else {
                $advertisementQuery->whereHas('project', function ($query) use ($loggedInUserID) {
                    $query->where(['added_by' => $loggedInUserID]);
                })->with('project:id,category_id,slug_id,title,type,city,state,country,image', 'project.category:id,category,image');
            }

            $total = $advertisementQuery->count();
            $data = $advertisementQuery->take($limit)->skip($offset)->orderBy('id', 'DESC')->get();

            ApiResponseService::successResponse("Data Fetched Successfully", $data, array('total' => $total));
        } catch (Exception $e) {
            return ApiResponseService::errorResponse();
        }
    }

    public function initiateBankTransaction(Request $request)
    {
        Log::info('initiateBankTransaction Request:', $request->all());
        if ($request->hasFile('file')) {
            Log::info('File present:', [
                'name' => $request->file('file')->getClientOriginalName(),
                'mime' => $request->file('file')->getMimeType(),
                'size' => $request->file('file')->getSize()
            ]);
        } else {
            Log::info('File missing in request');
        }

        try {
            $validator = Validator::make($request->all(), [
                'package_id'        => 'required_without:package_ids',
                'package_ids'       => 'required_without:package_id|array|min:1',
                'package_ids.*'     => 'required|exists:packages,id',
                'file' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx',
            ]);

            if ($validator->fails()) {
                Log::error('Validation Failed:', $validator->errors()->toArray());
                ApiResponseService::validationError($validator->errors()->first());
            }

            DB::beginTransaction();
            $loggedInUserId = Auth::user()->id;

            // Determine package IDs
            $packageIds = [];
            if ($request->has('package_ids') && is_array($request->package_ids)) {
                $packageIds = $request->package_ids;
            } elseif ($request->has('package_id')) {
                $packageIds = [$request->package_id];
            }

            $packages = Package::whereIn('id', $packageIds)->get();

            if ($packages->isEmpty()) {
                 ApiResponseService::validationError("No packages found");
            }

            // Shared Order ID
            $sharedOrderId = Str::uuid();

            // Upload File
            $uploadedFile = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $uploadedFile = store_image($file, 'BANK_RECEIPT_FILE_PATH');
                if (empty($uploadedFile)) {
                    ApiResponseService::validationError("File Upload Failed");
                }
            }

            $createdTransactions = [];

            foreach ($packages as $packageData) {
                // Check for free packages
                if ($packageData->package_type == 'free') {
                    ApiResponseService::validationError("Package {$packageData->name} is free and cannot be paid for.");
                }

                // Check if user has already paid for this package
                // $paymentTransaction = PaymentTransaction::where(['user_id' => $loggedInUserId, 'package_id' => $packageData->id])->first();
                
                // if (!empty($paymentTransaction) && ($paymentTransaction->payment_status == 'pending' || $paymentTransaction->payment_status == 'review')) {
                //      ApiResponseService::validationError("Transaction is currently in progress for package {$packageData->name} (Status: " . $paymentTransaction->payment_status . ")");
                // }

                $paymentTransactionData = PaymentTransaction::create([
                    'user_id'         => $loggedInUserId,
                    'package_id'      => $packageData->id,
                    'amount'          => $packageData->price,
                    'payment_gateway' => null,
                    'payment_status'  => 'review',
                    'order_id'        => $sharedOrderId,
                    'payment_type'    => 'bank transfer'
                ]);

                // Create Bank Receipt File
                if ($uploadedFile) {
                    BankReceiptFile::create([
                        'payment_transaction_id' => $paymentTransactionData->id,
                        'file' => $uploadedFile,
                    ]);
                    $paymentTransactionData['bank_receipt_file'] = $uploadedFile;
                }
                
                $createdTransactions[] = $paymentTransactionData;
            }

            // Get Bank Details
            $bankDetailsFieldsQuery = system_setting('bank_details');
            if (isset($bankDetailsFieldsQuery) && !empty($bankDetailsFieldsQuery)) {
                $bankDetailsFields = json_decode($bankDetailsFieldsQuery, true);
            } else {
                $bankDetailsFields = [];
            }
            DB::commit();

            ResponseService::successResponse('Transaction Initiated Successfully', $createdTransactions, array('bank_details' => $bankDetailsFields));
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error("Bank Transfer Error: " . $e->getMessage());
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                throw $e;
            }
            ApiResponseService::validationError($e->getMessage());
        }
    }

    public function uploadBankReceiptFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_transaction_id' => 'required',
                'file' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx',
            ]);

            if ($validator->fails()) {
                ApiResponseService::validationError($validator->errors()->first());
            }

            // Check Payment Transaction
            $paymentTransaction = PaymentTransaction::findOrFail($request->payment_transaction_id);
            if (empty($paymentTransaction)) {
                ApiResponseService::validationError("Payment Transaction Not Found");
            }

            // Check Payment Transaction Status
            if ($paymentTransaction->payment_status == 'review') {
                ApiResponseService::validationError("Your transaction is already in review");
            }

            PaymentTransaction::where('id', $request->payment_transaction_id)->update(['payment_status' => 'review']);

            // Upload File
            $file = $request->file('file');
            $file = store_image($file, 'BANK_RECEIPT_FILE_PATH');
            if (empty($file)) {
                ApiResponseService::validationError("File Upload Failed");
            }

            // Create Bank Receipt File
            $bankReceiptFile = BankReceiptFile::create([
                'payment_transaction_id' => $request->payment_transaction_id,
                'file' => $file,
            ]);

            ApiResponseService::successResponse("File Uploaded Successfully", $bankReceiptFile);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }

    public function getPaymentReceipt(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_transaction_id' => 'required|exists:payment_transactions,id',
            ]);

            if ($validator->fails()) {
                ApiResponseService::validationError($validator->errors()->first());
            }

            $loggedInUserId = Auth::user()->id;
            $payment = PaymentTransaction::with(
                'package:id,name,duration,package_type',
                'customer:id,name,email,mobile'
            )->without('customer.tokens')->findOrFail($request->payment_transaction_id);
            if ($payment->user_id != $loggedInUserId) {
                ApiResponseService::validationError("You are not authorized to view this receipt");
            }

            // Only allow viewing receipts for successful payments
            if ($payment->payment_status !== 'success') {
                ApiResponseService::validationError("Receipt is only available for successful payments");
            }
            $receiptService = new PaymentReceiptService();
            return $receiptService->generateHTML($payment);
        } catch (Exception $e) {
            ApiResponseService::errorResponse();
        }
    }


    public function compareProperties(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'source_property_id' => 'required|exists:propertys,id',
                'target_property_id' => 'required|exists:propertys,id',
            ]);

            if ($validator->fails()) {
                return ApiResponseService::validationError($validator->errors()->first());
            }

            $sourcePropertyId = $request->source_property_id;
            $targetPropertyId = $request->target_property_id;


            $propertyBaseQuery = Property::where(['status' => 1, 'request_status' => 'approved'])->select('id', 'category_id', 'title', 'city', 'state', 'country', 'address', 'price', 'propery_type', 'total_click', 'rentduration', 'is_premium', 'title_image');
            $sourceProperty = $propertyBaseQuery->clone()->where('id', $sourcePropertyId)->first();
            $targetProperty = $propertyBaseQuery->clone()->where('id', $targetPropertyId)->first();
            if (empty($sourceProperty)) {
                return ApiResponseService::errorResponse('Source property not found');
            }
            if (empty($targetProperty)) {
                return ApiResponseService::errorResponse('Target property not found');
            }


            if ($sourceProperty->category_id != $targetProperty->category_id) {
                return ApiResponseService::errorResponse('Properties are not in the same category');
            }
            if ($sourceProperty->id == $targetProperty->id) {
                return ApiResponseService::errorResponse('Source and target properties cannot be the same');
            }
            if ($sourceProperty->is_premium == 1) {
                if (collect(Auth::guard('sanctum')->user())->isEmpty()) {
                    return ApiResponseService::errorResponse('Source property is a premium property');
                } else {
                    $data = HelperService::checkPackageLimit('property_feature', true);
                    if (($data['package_available'] == false || $data['feature_available'] == false) && $data['limit_available'] == false) {
                        ApiResponseService::validationError("Source property is a premium property", $data);
                    }
                }
            }
            if ($targetProperty->is_premium == 1) {
                if (collect(Auth::guard('sanctum')->user())->isEmpty()) {
                    return ApiResponseService::errorResponse('Target property is a premium property');
                } else {
                    $data = HelperService::checkPackageLimit('property_feature', true);
                    if (($data['package_available'] == false || $data['feature_available'] == false) && $data['limit_available'] == false) {
                        ApiResponseService::validationError("Target property is a premium property", $data);
                    }
                }
            }

            if (!$sourceProperty || !$targetProperty) {
                return ApiResponseService::errorResponse('One or both properties not found');
            }

            $sourcePropertyData = $this->getPropertyData($sourceProperty);
            $targetPropertyData = $this->getPropertyData($targetProperty);
            $data = array(
                'source_property' => $sourcePropertyData,
                'target_property' => $targetPropertyData
            );
            return ApiResponseService::successResponseReturn("Properties compared successfully", $data);
        } catch (Exception $e) {
            return ApiResponseService::errorResponse($e->getMessage());
        }
    }
    public function getAllSimilarProperties(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:propertys,id',
                'search' => 'nullable|string',
                'offset' => 'nullable|integer',
                'limit' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return ApiResponseService::validationError($validator->errors()->first());
            }
            $offset = isset($request->offset) ? $request->offset : 0;
            $limit = isset($request->limit) ? $request->limit : 20;
            $getRequestProperty = Property::findOrFail($request->property_id);

            $getAllSimilarProperties = Property::where('id', '!=', $request->property_id)
                ->whereIn('propery_type', [0, 1])
                ->where(['status' => 1, 'request_status' => 'approved', 'category_id' => $getRequestProperty->category_id])
                ->select(
                    'id',
                    'slug_id',
                    'category_id',
                    'city',
                    'state',
                    'country',
                    'price',
                    'propery_type',
                    'title',
                    'title_image',
                    'is_premium',
                    'address',
                    'rentduration',
                    'latitude',
                    'longitude'
                )
                ->with('category:id,slug_id,image,category')
                ->when($request->has('search'), function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                })
                ->when($request->has('offset'), function ($query) use ($offset) {
                    $query->offset($offset);
                })
                ->when($request->has('limit'), function ($query) use ($limit) {
                    $query->limit($limit);
                })
                ->get()
                ->map(function ($propertyData) {
                    $propertyData->promoted = $propertyData->is_promoted;
                    $propertyData->property_type = $propertyData->propery_type;
                    $propertyData->parameters = $propertyData->parameters;
                    $propertyData->is_premium = $propertyData->is_premium == 1;
                    return $propertyData;
                });
            return ApiResponseService::successResponseReturn("Similar properties fetched successfully", $getAllSimilarProperties);
        } catch (Exception $e) {
            return ApiResponseService::errorResponse($e->getMessage());
        }
    }

    public function deepLink(Request $request)
    {
        try {
            $data = HelperService::getMultipleSettingData(['company_name', 'playstore_id', 'appstore_id']);
            $appName = $data['company_name'] ?? 'ebroker';
            $customerPlayStoreUrl = $data['playstore_id'] ?? 'https://play.google.com/store/apps/details?id=com.ebroker.ebroker';
            $customerAppStoreUrl = $data['appstore_id'] ?? 'https://apps.apple.com/app/id1564818806';
            return view('settings.deep-link', compact('appName', 'customerPlayStoreUrl', 'customerAppStoreUrl'));
        } catch (Exception $e) {
            return ApiResponseService::errorResponse($e->getMessage());
        }
    }


    /*****************************************************************************************************************************************
     * Functions
     *****************************************************************************************************************************************
     */


    function getUnsplashData($cityData)
    {
        $apiKey = env('UNSPLASH_API_KEY');
        $query = $cityData->city;
        $apiUrl = "https://api.unsplash.com/search/photos/?query=$query";
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Client-ID ' . $apiKey,
        ]);
        $unsplashResponse = curl_exec($ch);

        curl_close($ch);

        $unsplashData = json_decode($unsplashResponse, true);
        // Check if the response contains data
        if (isset($unsplashData['results'])) {
            $results = $unsplashData['results'];

            // Initialize the image URL
            $imageUrl = '';

            // Loop through the results and get the first image URL
            foreach ($results as $result) {
                $imageUrl = $result['urls']['regular'];
                break; // Stop after getting the first image URL
            }
            if ($imageUrl != "") {
                return array('City' => $cityData->city, 'Count' => $cityData->property_count, 'image' => $imageUrl);
            }
        }
        return array('City' => $cityData->city, 'Count' => $cityData->property_count, 'image' => "");
    }

    public function getAutoApproveStatus($loggedInUserId)
    {
        // Check auto approve is on and is user is verified or not
        $autoApproveSettingStatus = system_setting('auto_approve');
        $autoApproveStatus = false;
        if ($autoApproveSettingStatus == 1) {
            $userData = Customer::where('id', $loggedInUserId)->first();
            $autoApproveStatus = $userData->is_user_verified ? true : false;
        }

        return $autoApproveStatus;
    }
    function roundArrayValues($array, $pointsValue)
    {
        return array_map(function ($item) use ($pointsValue) {
            if (is_array($item)) {
                return $this->roundArrayValues($item, $pointsValue); // Recursive call
            }
            return is_numeric($item) ? round($item, $pointsValue) : $item; // Base Case
        }, $array);
    }


    function mortgageCalculation($loanAmount, $downPayment, $interestRate, $loanTermYear, $showAllDetails)
    {
        if ($downPayment > 0) {
            $downPayment = (int)$downPayment;
            $loanAmount = $loanAmount - $downPayment;
        }

        // Convert annual interest rate to monthly interest rate
        $monthlyInterestRate = ($interestRate / 100) / 12;

        // Convert loan term in years to months
        $loanTermMonths = $loanTermYear * 12;

        // Calculate monthly payment
        $monthlyPayment = $loanAmount * ($monthlyInterestRate * pow(1 + $monthlyInterestRate, $loanTermMonths)) / (pow(1 + $monthlyInterestRate, $loanTermMonths) - 1);

        // Initialize an array to store the mortgage schedule
        $schedule = [];
        $schedule['main_total'] = array();

        // Initialize main totals
        $mainTotal = [
            'principal_amount' => $loanAmount,
            'down_payment' => $downPayment,
            'payable_interest' => 0,
            'monthly_emi' => $monthlyPayment,
            'total_amount' => 0,
        ];

        // Get current year and month
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Initialize the remaining balance
        $remainingBalance = $loanAmount;

        // Loop through each month
        for ($i = 0; $i < $loanTermMonths; $i++) {
            $month = ($currentMonth + $i) % 12; // Ensure month wraps around by using modulo 12, so it does not exceed 12
            $year = $currentYear + floor(($currentMonth + $i - 1) / 12); // Calculate the year by incrementing when months exceed December

            // Correct month format
            $month = $month === 0 ? 12 : $month;

            // Calculate interest and principal
            $interest = $remainingBalance * $monthlyInterestRate;
            $principal = $monthlyPayment - $interest;
            $remainingBalance -= $principal;

            // Ensure remaining balance is not negative
            if ($remainingBalance < 0) {
                $remainingBalance = 0;
            }

            // Update yearly totals
            if ($showAllDetails && !isset($schedule['yearly_totals'][$year])) {
                $schedule['yearly_totals'][$year] = [
                    'year' => $year,
                    'monthly_emi' => 0,
                    'principal_amount' => 0,
                    'interest_paid' => 0,
                    'remaining_balance' => $remainingBalance,
                    'monthly_totals' => []
                ];
            }

            if ($showAllDetails) {
                $schedule['yearly_totals'][$year]['interest_paid'] += $interest;
                $schedule['yearly_totals'][$year]['principal_amount'] += $principal;

                // Store monthly totals
                $schedule['yearly_totals'][$year]['monthly_totals'][] = [
                    'month' => strtolower(date('F', mktime(0, 0, 0, $month, 1, $year))),
                    'principal_amount' => $principal,
                    'payable_interest' => $interest,
                    'remaining_balance' => $remainingBalance
                ];
            }

            // Update main total
            $mainTotal['payable_interest'] += $interest;
        }

        // Re-index the year totals array index, year used as index
        if ($showAllDetails) {
            $schedule['yearly_totals'] = array_values($schedule['yearly_totals']);
        } else {
            $schedule['yearly_totals'] = array();
        }

        // Calculate the total amount by addition of principle amount and total payable_interest
        $mainTotal['total_amount'] = $mainTotal['principal_amount'] + $mainTotal['payable_interest'];

        // Add Main Total in Schedule Variable
        $schedule['main_total'] = $mainTotal;

        // Round off values for display
        $schedule['main_total'] = $this->roundArrayValues($schedule['main_total'], 2);
        $schedule['yearly_totals'] = $this->roundArrayValues($schedule['yearly_totals'], 0);

        // Return the mortgage schedule
        return $schedule;
    }

    /**
     * Get homepage sections based on latitude and longitude also
     * @param float $latitude
     * @param float $longitude
     * @param Builder $propertyBaseQuery
     * @param Builder $projectsBaseQuery
     * @param Closure $propertyMapper
     * @param boolean $homepageLocationDataAvailable
     * @param Builder $locationBasedPropertyQuery
     * @return array
     */

    public function getHomepageSections($latitude, $longitude, $propertyBaseQuery, $propertyMapper, $projectsBaseQuery, $homepageLocationDataAvailable, $locationBasedPropertyQuery)
    {
        $sections = [];
        $homepageSections = HomepageSection::where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        // Build homepageData array based on active sections
        foreach ($homepageSections as $section) {
            switch ($section->section_type) {
                case config('constants.HOMEPAGE_SECTION_TYPES.FAQS_SECTION.TYPE'):
                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => Faq::select('id', 'question', 'answer')
                            ->where('status', 1)
                            ->orderBy('id', 'DESC')
                            ->limit(5)
                            ->get()
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.PROJECTS_SECTION.TYPE'):
                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $projectsBaseQuery->clone()->inRandomOrder()->limit(12)->get()
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.FEATURED_PROJECTS_SECTION.TYPE'):
                    $featuredProjectData = $projectsBaseQuery->clone()
                        ->whereHas('advertisement', function ($query) {
                            $query->where(['is_enable' => 1, 'status' => 0]);
                        })
                        ->inRandomOrder()
                        ->limit(12)
                        ->get();

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $featuredProjectData
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.CATEGORIES_SECTION.TYPE'):
                    $categoriesData = Category::select('id', 'category', 'image', 'slug_id')
                        ->where('status', 1)
                        ->whereHas('properties', function ($query) {
                            $query->where(['status' => 1, 'request_status' => 'approved']);
                        })
                        ->withCount(['properties' => function ($query) {
                            $query->where(['status' => 1, 'request_status' => 'approved']);
                        }])
                        ->limit(12)
                        ->get();

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $categoriesData
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.ARTICLES_SECTION.TYPE'):
                    $articlesData = Article::select('id', 'slug_id', 'category_id', 'title', 'description', 'image', 'created_at')
                        ->with('category:id,slug_id,image,category')
                        ->limit(5)
                        ->get();

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $articlesData
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.AGENTS_LIST_SECTION.TYPE'):
                    $agentsData = Customer::select('id', 'name', 'email', 'profile', 'slug_id')
                        ->withCount([
                            'projects' => function ($query) {
                                $query->where(['status' => 1, 'request_status' => 'approved']);
                            },
                            'property' => function ($query) {
                                $query->where(['status' => 1, 'request_status' => 'approved']);
                            }
                        ])
                        ->where('isActive', 1)
                        ->get()
                        ->map(function ($customer) {
                            $customer->is_verified = $customer->is_user_verified;
                            $customer->total_count = $customer->projects_count + $customer->property_count;
                            $customer->is_admin = false;
                            return $customer;
                        })
                        ->filter(function ($customer) {
                            return $customer->projects_count > 0 || $customer->property_count > 0;
                        })
                        ->sortByDesc(function ($customer) {
                            return [$customer->is_verified, $customer->total_count];
                        })
                        ->values()
                        ->take(12);

                    // Add admin user if they have properties or projects
                    $adminEmail = system_setting('company_email');
                    $adminPropertyQuery = Property::where(['added_by' => 0, 'status' => 1, 'request_status' => 'approved']);
                    $adminProjectQuery = Projects::where(['is_admin_listing' => 1, 'status' => 1]);

                    if ($latitude && $longitude) {
                        if ($homepageLocationDataAvailable) {
                            $adminPropertiesCount = $adminPropertyQuery->where(['latitude' => $latitude, 'longitude' => $longitude])->count();
                            $adminProjectsCount = $adminProjectQuery->where(['latitude' => $latitude, 'longitude' => $longitude])->count();
                        } else {
                            $adminPropertiesCount = $adminPropertyQuery->count();
                            $adminProjectsCount = $adminProjectQuery->count();
                        }
                    } else {
                        $adminPropertiesCount = $adminPropertyQuery->count();
                        $adminProjectsCount = $adminProjectQuery->count();
                    }

                    if ($adminPropertiesCount > 0 || $adminProjectsCount > 0) {
                        $adminQuery = User::where('type', 0)->select('id', 'slug_id', 'profile')->first();
                        if ($adminQuery) {
                            $adminData = [
                                'id' => $adminQuery->id,
                                'name' => 'Admin',
                                'slug_id' => $adminQuery->slug_id,
                                'email' => !empty($adminEmail) ? $adminEmail : "",
                                'property_count' => $adminPropertiesCount,
                                'projects_count' => $adminProjectsCount,
                                'total_count' => $adminPropertiesCount + $adminProjectsCount,
                                'is_verified' => true,
                                'profile' => !empty($adminQuery->getRawOriginal('profile')) ? $adminQuery->profile : url('assets/images/faces/2.jpg'),
                                'is_admin' => true
                            ];
                            $agentsData->prepend((object)$adminData);
                        }
                    }

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $agentsData
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.FEATURED_PROPERTIES_SECTION.TYPE'):
                    $featuredSection = $locationBasedPropertyQuery->clone()
                        ->whereHas('advertisement', function ($subQuery) {
                            $subQuery->where(['is_enable' => 1, 'status' => 0])
                                ->whereNot('type', 'Slider');
                        })
                        ->inRandomOrder()
                        ->limit(12)
                        ->get()
                        ->map($propertyMapper);

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $featuredSection
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.MOST_LIKED_PROPERTIES_SECTION.TYPE'):
                    $mostLikedProperties = $locationBasedPropertyQuery->clone()
                        ->withCount('favourite')
                        ->orderBy('favourite_count', 'DESC')
                        ->limit(12)
                        ->get()
                        ->map($propertyMapper);

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $mostLikedProperties
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.MOST_VIEWED_PROPERTIES_SECTION.TYPE'):
                    $mostViewedProperties = $locationBasedPropertyQuery->clone()
                        ->orderBy('total_click', 'DESC')
                        ->limit(12)
                        ->get()
                        ->map($propertyMapper);

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $mostViewedProperties
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.PREMIUM_PROPERTIES_SECTION.TYPE'):
                    $premiumPropertiesSection = $locationBasedPropertyQuery->clone()
                        ->where('is_premium', 1)
                        ->inRandomOrder()
                        ->limit(12)
                        ->get()
                        ->map($propertyMapper);

                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $premiumPropertiesSection
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.NEARBY_PROPERTIES_SECTION.TYPE'):
                    if (Auth::guard('sanctum')->check()) {
                        $loggedInUser = Auth::guard('sanctum')->user();
                        $cityOfUser = $loggedInUser->city;
                        $nearbySection = $propertyBaseQuery->clone()
                            ->where('city', $cityOfUser)
                            ->inRandomOrder()
                            ->limit(12)
                            ->get()
                            ->map($propertyMapper);
                    }
                    $sections[] = [
                        'type' => $section->section_type,
                        'title' => $section->title,
                        'data' => $nearbySection ?? []
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.USER_RECOMMENDATIONS_SECTION.TYPE'):
                    if (Auth::guard('sanctum')->check()) {
                        $loggedInUser = Auth::guard('sanctum')->user();
                        $userInterestData = UserInterest::where('user_id', $loggedInUser->id)->first();

                        if ($userInterestData) {
                            $userRecommendationQuery = $propertyBaseQuery->clone();

                            // Apply category filters
                            if (!empty($userInterestData->category_ids)) {
                                $categoryIds = explode(',', $userInterestData->category_ids);
                                $userRecommendationQuery->whereIn('category_id', $categoryIds);
                            }

                            // Apply price range filters
                            if (!empty($userInterestData->price_range)) {
                                $priceRange = explode(',', $userInterestData->price_range);
                                if (count($priceRange) >= 2) {
                                    $minPrice = floatval($priceRange[0]);
                                    $maxPrice = floatval($priceRange[1]);
                                    $userRecommendationQuery->whereRaw("CAST(price AS DECIMAL(10, 2)) BETWEEN ? AND ?", [$minPrice, $maxPrice]);
                                }
                            }

                            // Apply city filter
                            if (!empty($userInterestData->city)) {
                                $userRecommendationQuery->where('city', $userInterestData->city);
                            }

                            // Apply property type filter
                            if (!empty($userInterestData->property_type) || $userInterestData->property_type == '0') {
                                $propertyType = explode(',', $userInterestData->property_type);
                                $userRecommendationQuery->whereIn('propery_type', $propertyType);
                            }

                            // Apply outdoor facilities filter
                            if (!empty($userInterestData->outdoor_facilitiy_ids)) {
                                $outdoorFacilityIds = explode(',', $userInterestData->outdoor_facilitiy_ids);
                                $userRecommendationQuery->whereHas('assignfacilities.outdoorfacilities', function ($q) use ($outdoorFacilityIds) {
                                    $q->whereIn('id', $outdoorFacilityIds);
                                });
                            }

                            // Get recommendations
                            $userRecommendations = $userRecommendationQuery
                                ->inRandomOrder()
                                ->limit(12)
                                ->get()
                                ->map($propertyMapper);
                        }
                    }
                    $sections[] = [
                        'type' => config('constants.HOMEPAGE_SECTION_TYPES.USER_RECOMMENDATIONS_SECTION.TYPE'),
                        'title' => $section->title,
                        'data' => $userRecommendations ?? []
                    ];
                    break;

                case config('constants.HOMEPAGE_SECTION_TYPES.PROPERTIES_BY_CITIES_SECTION.TYPE'):
                    $citiesData = CityImage::where('status', 1)->withCount(['property' => function ($query) {
                        $query->whereIn('propery_type', [0, 1])->where(['status' => 1, 'request_status' => 'approved']);
                    }])->having('property_count', '>', 0)->orderBy('property_count', 'DESC')->limit(12)->get();
                    $propertiesByCities = [];
                    foreach ($citiesData as $city) {
                        if (!empty($city->getRawOriginal('image'))) {
                            $rewrittenImageUrl = $this->rewriteImageUrl($city->image);

                            array_push($propertiesByCities, [
                                'City' => $city->city,
                                'Count' => $city->property_count,
                                'image' => $rewrittenImageUrl
                            ]);
                            continue;
                        }

                        // في حالة عدم وجود صورة، استخدم Unsplash
                        $resultArray = $this->getUnsplashData($city);
                        array_push($propertiesByCities, $resultArray);
                    }
                    $sections[] = [
                        'type' => config('constants.HOMEPAGE_SECTION_TYPES.PROPERTIES_BY_CITIES_SECTION.TYPE'),
                        'title' => $section->title,
                        'data' => $propertiesByCities ?? []
                    ];
                    break;
            }
        }
        return $sections;
    }
    private function rewriteImageUrl($imageUrl)
    {
        // Only rewrite when using S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));
        if ($disk !== 's3') {
            return $imageUrl;
        }

        $s3Base = rtrim((string) config('filesystems.disks.s3.url')
            ?: (string) config('filesystems.disks.s3.endpoint'), '/');
        if ($s3Base === '') {
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            $s3Base = "https://{$bucket}.s3.{$region}.amazonaws.com";
        }

        // Match URLs that contain /images/, /json/, or /assets/images/ from any domain
        if (preg_match('#^https?://[^/]+(/images/[^/]+.*)$#', $imageUrl, $matches)) {
            return $s3Base . $matches[1];
        } elseif (preg_match('#^https?://[^/]+(/json/[^/]+.*)$#', $imageUrl, $matches)) {
            return $s3Base . $matches[1];
        }

        return $imageUrl;
    }
    /**
     * Get the data of a property
     */
    function getPropertyData($property)
    {
        $propertyData = [
            'id' => $property->id,
            'title' => $property->title,
            'city' => $property->city,
            'state' => $property->state,
            'country' => $property->country,
            'is_premium' => $property->is_premium,
            'title_image' => $property->title_image,
            'address' => $property->address,
            'created_at' => $property->created_at,
            'price' => $property->price,
            'rentduration' => !empty($property->rentduration) ? $property->rentduration : null,
            'property_type' => $property->propery_type,
            'total_likes' => $property->favourite()->count(),
            'total_views' => $property->total_click,
            'facilities' => $property->parameters,
            'near_by_places' => $property->assign_facilities,
        ];
        return $propertyData;
    }
    /************************************************************************************************************************ */



    // Temp API
    public function removeAccountTemp(Request $request)
    {
        try {
            Customer::where(['email' => $request->email, 'logintype' => 3])->delete();
            ApiResponseService::successResponse("Done");
        } catch (\Throwable $th) {
            ApiResponseService::errorResponse("Issue");
        }
    }

    // Property Terms API Methods
    public function getPropertyTerms()
    {
        try {
            $propertyTerms = PropertyTerms::all();

            return response()->json([
                'success' => true,
                'data' => $propertyTerms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPropertyTermById($id)
    {
        try {
            $propertyTerm = PropertyTerms::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $propertyTerm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTermsByClassification($classificationId)
    {
        try {
            $terms = PropertyTerms::where('classification_id', $classificationId)->first();

            if ($terms) {
                return response()->json([
                    'success' => true,
                    'data' => $terms
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terms not found for this classification'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createPropertyTerm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'classification_id' => 'required|integer|unique:property_terms',
                'terms_conditions' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $propertyTerm = PropertyTerms::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions created successfully',
                'data' => $propertyTerm
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePropertyTerm(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'terms_conditions' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $propertyTerm = PropertyTerms::findOrFail($id);
            $propertyTerm->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions updated successfully',
                'data' => $propertyTerm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deletePropertyTerm($id)
    {
        try {
            $propertyTerm = PropertyTerms::findOrFail($id);
            $propertyTerm->delete();

            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateChatApprovalStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:chats,id',
            'approval_status' => 'required|in:pending,approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        $chat = Chats::find($request->chat_id);
        if (!$chat) {
            return response()->json([
                'error' => true,
                'message' => 'Chat message not found',
            ]);
        }

        $chat->approval_status = $request->approval_status;
        $chat->save();

        // Get the sender and receiver details for notification
        $sender = Customer::select('id', 'name', 'profile')->with(['usertokens' => function ($q) {
            $q->select('fcm_id', 'id', 'customer_id');
        }])->find($chat->sender_id);
        $receiver = Customer::select('id', 'name', 'profile')->with(['usertokens' => function ($q) {
            $q->select('fcm_id', 'id', 'customer_id');
        }])->find($chat->receiver_id);

        // Send notification to the sender about approval status change
        if ($sender && $sender->usertokens && $sender->usertokens->count() > 0) {
            $fcm_ids = [];
            foreach ($sender->usertokens as $usertoken) {
                array_push($fcm_ids, $usertoken->fcm_id);
            }

            if (!empty($fcm_ids)) {
                $statusMessage = '';
                if ($request->approval_status == 'approved') {
                    $statusMessage = 'Your message has been approved';
                } elseif ($request->approval_status == 'rejected') {
                    $statusMessage = 'Your message has been rejected';
                }

                $fcmMsg = array(
                    'title' => 'Chat Status Update',
                    'message' => $statusMessage,
                    'type' => 'chat_approval',
                    'body' => $statusMessage,
                    'chat_id' => $chat->id,
                    'approval_status' => $request->approval_status,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default'
                );

                send_push_notification($fcm_ids, $fcmMsg);
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Chat approval status updated successfully',
            'data' => [
                'chat_id' => $chat->id,
                'approval_status' => $chat->approval_status
            ]
        ]);
    }

    /**
     * Get property question fields by classification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Send email to property customer with client details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPropertyClientEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:propertys,id',
            'client_name' => 'required|string',
            'client_number' => 'required|string',
            'client_email' => 'required|email',
            'corresponding_day' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Additional validation for hotel room reservations
        if ($request->reservable_type === 'hotel_room') {
            if (empty($request->reservable_data) || !is_array($request->reservable_data)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Hotel room reservations require room data'
                ], 422);
            }
            
            // Validate that each room has a valid ID
            foreach ($request->reservable_data as $room) {
                if (empty($room['id'])) {
                    return response()->json([
                    'error' => true,
                    'message' => 'Each room must have a valid room ID'
                ], 422);
                }
            }
        }

        try {
            // Get property with customer
            $property = Property::with('customer')->findOrFail($request->property_id);

            if (!$property->customer) {
                return response()->json([
                    'error' => true,
                    'message' => 'Property customer not found'
                ], 404);
            }

            $appName = Setting::where('type', 'app_name')->first();
            $appNameValue = $appName ? $appName->data : config('app.name');

            // Parse corresponding_day JSON
            $correspondingDayData = json_decode($request->corresponding_day, true);
            $orientationDay = '';
            $orientationTime = '';
            
            if (is_array($correspondingDayData)) {
                $orientationDay = $correspondingDayData['day'] ?? '';
                $orientationTime = $correspondingDayData['from'] ?? '';
            } else {
                $orientationDay = $request->corresponding_day;
            }

            // Format day name for display
            $dayNames = [
                'sun' => 'Sunday',
                'mon' => 'Monday',
                'tue' => 'Tuesday',
                'wed' => 'Wednesday',
                'thu' => 'Thursday',
                'fri' => 'Friday',
                'sat' => 'Saturday'
            ];
            $orientationDayFormatted = $dayNames[$orientationDay] ?? ucfirst($orientationDay);
            $correspondingDayFormatted = $orientationDayFormatted . ($orientationTime ? ' at ' . $orientationTime : '');

            // Save orientation day selection to property
            $orientationDayData = [
                'day' => $orientationDay,
                'time' => $orientationTime,
                'client_name' => $request->client_name,
                'client_email' => $request->client_email,
                'client_number' => $request->client_number,
                'selected_at' => now()->toDateTimeString()
            ];
            $property->orientation_day_selected = json_encode($orientationDayData);
            $property->save();

            // 1. Send email to property owner (existing functionality)
            $emailTypeData = HelperService::getEmailTemplatesTypes("property_client_meeting");
            $emailTemplateData = system_setting($emailTypeData['type']);

            $variables = array(
                'app_name' => $appNameValue,
                'customer_name' => $property->customer->name,
                'client_name' => $request->client_name,
                'corresponding_day' => $correspondingDayFormatted,
                'client_number' => $request->client_number,
                'client_email' => $request->client_email,
                'current_date_today' => now()->format('d M Y, h:i A'),
            );

            if (empty($emailTemplateData)) {
                $emailTemplateData = "Dear {customer_name}, Mr. {client_name} will see you in {corresponding_day}. His contact details: Phone: {client_number}, Email: {client_email}";
            }

            $emailTemplate = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            $data = array(
                'email_template' => $emailTemplate,
                'email' => $property->customer->email,
                'title' => $emailTypeData['title'],
            );

            HelperService::sendMail($data);

            // 2. Send confirmation email to client (user who selected orientation day)
            $clientEmailTypeData = HelperService::getEmailTemplatesTypes("orientation_day_confirmation");
            $clientEmailTemplateData = system_setting($clientEmailTypeData['type']);

            $clientVariables = array(
                'app_name' => $appNameValue,
                'client_name' => $request->client_name,
                'property_name' => $property->title ?? 'Property',
                'property_address' => $property->address ?? 'N/A',
                'orientation_day' => $orientationDayFormatted,
                'orientation_time' => $orientationTime,
                'property_owner_name' => $property->customer->name ?? 'N/A',
                'property_owner_phone' => $property->customer->mobile ?? $property->mobile ?? 'N/A',
                'property_owner_email' => $property->customer->email ?? $property->email ?? 'N/A',
            );

            if (empty($clientEmailTemplateData)) {
                $clientEmailTemplateData = "Dear {client_name},

Thank you for selecting an orientation day for {property_name}!

Your Orientation Day Details:
• Property: {property_name}
• Address: {property_address}
• Day: {orientation_day}
• Time: {orientation_time}

Property Owner Contact Information:
• Name: {property_owner_name}
• Phone: {property_owner_phone}
• Email: {property_owner_email}

We look forward to seeing you on {orientation_day} at {orientation_time}!

Best regards,
{app_name} Team";
            }

            $clientEmailTemplate = HelperService::replaceEmailVariables($clientEmailTemplateData, $clientVariables);

            $clientData = array(
                'email_template' => $clientEmailTemplate,
                'email' => $request->client_email,
                'title' => $clientEmailTypeData['title'] ?? 'Orientation Day Confirmation',
            );

            HelperService::sendMail($clientData);

            return response()->json([
                'error' => false,
                'message' => 'Orientation day saved and emails sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to process orientation day: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get property reviews for hotels and vacation homes
     * Only shows reviews to users who were guests at the property
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPropertyReviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:propertys,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $property = Property::findOrFail($request->property_id);
            
            // Only allow reviews for hotels (5) and vacation homes (4)
            // Use getRawOriginal to get the actual integer value from database
            $propertyClassification = $property->getRawOriginal('property_classification');
            if ($propertyClassification != 4 && $propertyClassification != 5) {
                return response()->json([
                    'error' => true,
                    'message' => 'Reviews are only available for hotels and vacation homes'
                ], 403);
            }

            // Get authenticated user if available (optional for viewing reviews)
            $customer = Auth::guard('sanctum')->user();
            $customerId = $customer ? $customer->id : null;

            // Check if user was a guest at this property (has a reservation)
            $isGuest = false;
            if ($customerId) {
                $isGuest = \App\Models\Reservation::where('customer_id', $customerId)
                    ->where(function($query) use ($property) {
                        $query->where(function($q) use ($property) {
                            $q->where('reservable_type', 'App\Models\Property')
                              ->where('reservable_id', $property->id);
                        })->orWhereHas('reservable', function($q) use ($property) {
                            // For hotel rooms, check if the room belongs to this property
                            $q->where('property_id', $property->id);
                        });
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->exists();
            }

            // Get all reviews for this property
            $reviews = PropertyQuestionAnswer::with([
                'customer:id,name,email',
                'reservation:id,check_in_date,check_out_date',
                'property_question_field:id,name,field_type'
            ])
            ->where('property_id', $property->id)
            ->whereHas('property_question_field', function($query) use ($property) {
                $query->where('property_classification', $property->property_classification);
            })
            ->orderBy('created_at', 'desc')
            ->get();

            // Group reviews by customer and reservation
            $groupedReviews = [];
            foreach ($reviews as $review) {
                $key = ($review->customer_id ?? 'anonymous') . '_' . ($review->reservation_id ?? 'no_reservation');
                
                if (!isset($groupedReviews[$key])) {
                    $groupedReviews[$key] = [
                        'id' => $review->id,
                        'customer_name' => $review->customer ? $review->customer->name : 'Anonymous',
                        'customer_id' => $review->customer_id,
                        'reservation_id' => $review->reservation_id,
                        'check_in_date' => $review->reservation ? $review->reservation->check_in_date : null,
                        'check_out_date' => $review->reservation ? $review->reservation->check_out_date : null,
                        'created_at' => $review->created_at,
                        'answers' => []
                    ];
                }

                // Process value based on field type
                $value = $review->value;
                $fieldType = $review->property_question_field->field_type ?? 'text';
                
                if ($fieldType == 'file') {
                    $value = url('') . config('global.IMG_PATH') . config('global.PROPERTY_QUESTION_PATH') . '/' . $value;
                } elseif ($fieldType == 'checkbox') {
                    $value = json_decode($value, true);
                }

                $groupedReviews[$key]['answers'][] = [
                    'field_id' => $review->property_question_field_id,
                    'field_name' => $review->property_question_field->name ?? 'Unknown',
                    'field_type' => $fieldType,
                    'value' => $value,
                ];
            }

            // Convert to array and reset keys
            $reviewsList = array_values($groupedReviews);

            // Only show reviews if user is a guest OR if there are no reviews yet (to show empty state)
            // If there are reviews but user is not a guest, don't show them
            $canViewReviews = $isGuest || count($reviewsList) === 0;
            $reviewsToShow = $canViewReviews ? $reviewsList : [];

            return response()->json([
                'error' => false,
                'data' => [
                    'reviews' => $reviewsToShow,
                    'total_reviews' => count($reviewsList),
                    'is_guest' => $isGuest,
                    'can_view_reviews' => $canViewReviews,
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('getPropertyReviews error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Failed to fetch reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPropertyQuestionFields(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_classification' => 'required|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $propertyClassification = $request->property_classification;

            $fields = PropertyQuestionField::with('field_values')
                ->where('property_classification', $propertyClassification)
                ->where('status', 'active')
                ->orderBy('rank')
                ->get();

            if ($fields->isEmpty()) {
                return response()->json([
                    'error' => false,
                    'message' => trans('No fields found for this classification'),
                    'data' => []
                ]);
            }

            $formattedFields = [];
            foreach ($fields as $field) {
                $fieldData = [
                    'id' => $field->id,
                    'name' => $field->name,
                    'field_type' => $field->field_type,
                    'values' => []
                ];

                // Add values if field type is radio, checkbox, or dropdown
                if (in_array($field->field_type, ['radio', 'checkbox', 'dropdown']) && $field->field_values->isNotEmpty()) {
                    foreach ($field->field_values as $value) {
                        $fieldData['values'][] = [
                            'id' => $value->id,
                            'value' => $value->value
                        ];
                    }
                }

                $formattedFields[] = $fieldData;
            }

            return response()->json([
                'error' => false,
                'message' => trans('Fields retrieved successfully'),
                'data' => $formattedFields
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => trans('Something went wrong'),
                'data' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save property question answers
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePropertyQuestionAnswers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:propertys,id',
            'answers' => 'required|array',
            'answers.*.field_id' => 'required|exists:property_question_fields,id',
            'answers.*.value' => 'required',
            'reservation_id' => 'nullable|exists:reservations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            // Get authenticated user
            $customer = Auth::guard('sanctum')->user();
            if (!$customer) {
                return response()->json([
                    'error' => true,
                    'message' => trans('User not authenticated')
                ]);
            }

            $propertyId = $request->property_id;
            $reservationId = $request->reservation_id ?? null;
            $customerId = $customer->id;

            $property = Property::find($propertyId);

            if (!$property) {
                return response()->json([
                    'error' => true,
                    'message' => trans('Property not found')
                ]);
            }

            // Check if user has already submitted a review for this property/reservation
            $existingReview = PropertyQuestionAnswer::where('customer_id', $customerId)
                ->where('property_id', $propertyId)
                ->when($reservationId, function ($query) use ($reservationId) {
                    return $query->where('reservation_id', $reservationId);
                })
                ->first();

            if ($existingReview) {
                return response()->json([
                    'error' => true,
                    'message' => trans('You have already submitted a review for this property')
                ]);
            }

            // Begin transaction
            DB::beginTransaction();

            // Delete existing answers for this specific user, property, and reservation combination
            PropertyQuestionAnswer::where('customer_id', $customerId)
                ->where('property_id', $propertyId)
                ->when($reservationId, function ($query) use ($reservationId) {
                    return $query->where('reservation_id', $reservationId);
                })
                ->delete();

            // Process each answer
            foreach ($request->answers as $answer) {
                $field = PropertyQuestionField::find($answer['field_id']);

                // Skip if field doesn't exist
                if (!$field) {
                    continue;
                }

                // Handle file uploads
                $value = $answer['value'];
                if ($field->field_type == 'file' && $request->hasFile($answer['field_id'])) {
                    $file = $request->file($answer['field_id']);
                    $destinationPath = public_path('images') . config('global.PROPERTY_QUESTION_PATH');

                    if (!File::isDirectory($destinationPath)) {
                        File::makeDirectory($destinationPath, 0777, true, true);
                    }

                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->move($destinationPath, $fileName);
                    $value = $fileName;
                }

                // For checkbox type, convert array to JSON
                if ($field->field_type == 'checkbox' && is_array($value)) {
                    $value = json_encode($value);
                }

                // Create new answer with user and reservation tracking
                PropertyQuestionAnswer::create([
                    'property_id' => $propertyId,
                    'customer_id' => $customerId,
                    'reservation_id' => $reservationId,
                    'property_question_field_id' => $answer['field_id'],
                    'value' => $value
                ]);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('Answers saved successfully')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => trans('Something went wrong'),
                'data' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user has already submitted a review for a property/reservation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUserReviewStatus(Request $request)
    {
        try {
            // Get authenticated user
            $customer = Auth::guard('sanctum')->user();
            if (!$customer) {
                return response()->json([
                    'error' => true,
                    'message' => trans('User not authenticated'),
                    'has_submitted' => false
                ]);
            }

            // Get parameters from query string for GET request
            $propertyId = $request->query('property_id') ?? $request->input('property_id');
            $reservationId = $request->query('reservation_id') ?? $request->input('reservation_id');
            
            // Convert property_id to integer
            $propertyId = $propertyId ? (int) $propertyId : null;
            
            // Convert empty string to null and cast to integer if provided
            if ($reservationId === '' || $reservationId === null) {
                $reservationId = null;
            } else {
                $reservationId = (int) $reservationId;
            }

            $validator = Validator::make([
                'property_id' => $propertyId,
                'reservation_id' => $reservationId
            ], [
                'property_id' => 'required|integer|exists:propertys,id',
                'reservation_id' => 'nullable|integer|exists:reservations,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                    'has_submitted' => false
                ]);
            }

            $customerId = $customer->id;

            // Check if user has already submitted a review
            // Use exists() for better performance - just check if any review exists
            $query = PropertyQuestionAnswer::where('customer_id', $customerId)
                ->where('property_id', $propertyId);
            
            // If reservation_id is provided, filter by it; otherwise don't filter by reservation_id
            if ($reservationId !== null) {
                $query->where('reservation_id', $reservationId);
            }
            
            $hasSubmitted = $query->exists();

            return response()->json([
                'error' => false,
                'has_submitted' => $hasSubmitted,
                'message' => $hasSubmitted ? 'Review already submitted' : 'Review not submitted yet'
            ]);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('checkUserReviewStatus error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => trans('Something went wrong'),
                'has_submitted' => false,
                'data' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Save feedback answers via token (public, no authentication required)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveFeedbackAnswers(Request $request)
    {
        // Handle JSON-encoded answers from FormData
        // When FormData is used with JSON.stringify, Laravel receives it as a string
        $answers = $request->answers;
        if (is_string($answers)) {
            $decodedAnswers = json_decode($answers, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAnswers)) {
                $request->merge(['answers' => $decodedAnswers]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => trans('Invalid answers format')
                ]);
            }
        }
        
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'property_id' => 'required|exists:propertys,id',
            'answers' => 'required|array',
            'answers.*.field_id' => 'required|exists:property_question_fields,id',
            'answers.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            // Clean and trim the token to handle any whitespace or encoding issues
            $token = trim($request->token);
            
            // Log token for debugging (remove in production if contains sensitive info)
            \Log::info('Feedback token validation attempt', [
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 20) . '...',
                'has_property_id' => !empty($request->property_id)
            ]);
            
            // Verify token and get reservation
            // Use exact match after trimming
            $reservation = \App\Models\Reservation::where('feedback_token', $token)
                ->whereNotNull('feedback_token')
                ->first();

            if (!$reservation) {
                // Log failed token lookup for debugging
                \Log::warning('Feedback token not found', [
                    'token_length' => strlen($token),
                    'token_preview' => substr($token, 0, 20) . '...',
                    'token_exists_check' => \App\Models\Reservation::whereNotNull('feedback_token')->count()
                ]);
                
                return response()->json([
                    'error' => true,
                    'message' => trans('Invalid or expired feedback token')
                ]);
            }

            $propertyId = $request->property_id;
            $reservationId = $reservation->id;
            $customerId = $reservation->customer_id;

            // Verify property matches reservation
            $property = Property::find($propertyId);
            if (!$property) {
                return response()->json([
                    'error' => true,
                    'message' => trans('Property not found')
                ]);
            }

            // Verify property matches reservation
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                if ($reservation->reservable_id != $propertyId) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('Property does not match reservation')
                    ]);
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = $reservation->reservable;
                if (!$hotelRoom || $hotelRoom->property_id != $propertyId) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('Property does not match reservation')
                    ]);
                }
            }

            // Check if customer has already submitted feedback for this reservation
            $existingReview = PropertyQuestionAnswer::where('customer_id', $customerId)
                ->where('property_id', $propertyId)
                ->where('reservation_id', $reservationId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'error' => true,
                    'message' => trans('You have already submitted feedback for this reservation')
                ]);
            }

            // Begin transaction
            DB::beginTransaction();

            // Process each answer
            foreach ($request->answers as $answer) {
                $field = PropertyQuestionField::find($answer['field_id']);

                // Skip if field doesn't exist
                if (!$field) {
                    continue;
                }

                // Handle file uploads
                $value = $answer['value'];
                if ($field->field_type == 'file' && $request->hasFile($answer['field_id'])) {
                    $file = $request->file($answer['field_id']);
                    $destinationPath = public_path('images') . config('global.PROPERTY_QUESTION_PATH');

                    if (!File::isDirectory($destinationPath)) {
                        File::makeDirectory($destinationPath, 0777, true, true);
                    }

                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->move($destinationPath, $fileName);
                    $value = $fileName;
                }

                // For checkbox type, convert array to JSON
                if ($field->field_type == 'checkbox' && is_array($value)) {
                    $value = json_encode($value);
                }

                // Create new answer with user and reservation tracking
                PropertyQuestionAnswer::create([
                    'property_id' => $propertyId,
                    'customer_id' => $customerId,
                    'reservation_id' => $reservationId,
                    'property_question_field_id' => $answer['field_id'],
                    'value' => $value
                ]);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('Thank you for your feedback! Your response has been saved successfully.')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => trans('Something went wrong'),
                'data' => $e->getMessage()
            ]);
        }
    }

    public function getHotelAddonFields(Request $request)
    {
        $data = HotelAddonField::where('status', 'active')
            ->with('field_values:id,hotel_addon_field_id,value,static_price,multiply_price')
            ->select('id', 'name', 'field_type')
            ->get();

        if (collect($data)->isNotEmpty()) {
            ApiResponseService::successResponse("Data Fetched Successfully", $data, array(), 200);
        } else {
            ApiResponseService::successResponse("No data found!");
        }
    }

    /**
     * Get property taxes by classification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPropertyTaxes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_classification' => 'required|integer|in:4,5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first()
                ]);
            }

            $propertyClassification = $request->property_classification;

            $taxes = \App\Models\PropertyTax::where('property_classification', $propertyClassification)->first();

            if (!$taxes) {
                return response()->json([
                    'error' => false,
                    'message' => 'No taxes found for this property classification',
                    'data' => [
                        'property_classification' => $propertyClassification,
                        'property_classification_name' => $propertyClassification == 4 ? 'vacation_homes' : 'hotel_booking',
                        'service_charge' => null,
                        'sales_tax' => null,
                        'city_tax' => null
                    ]
                ]);
            }

            return response()->json([
                'error' => false,
                'message' => 'Property taxes retrieved successfully',
                'data' => [
                    'property_classification' => $taxes->property_classification,
                    'property_classification_name' => $propertyClassification == 4 ? 'vacation_homes' : 'hotel_booking',
                    'service_charge' => $taxes->service_charge,
                    'sales_tax' => $taxes->sales_tax,
                    'city_tax' => $taxes->city_tax
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store or update property taxes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePropertyTaxes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_classification' => 'required|integer|in:4,5',
                'service_charge' => 'nullable|numeric|min:0',
                'sales_tax' => 'nullable|numeric|min:0',
                'city_tax' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first()
                ]);
            }

            $propertyClassification = $request->property_classification;

            $taxes = \App\Models\PropertyTax::updateOrCreate(
                ['property_classification' => $propertyClassification],
                [
                    'service_charge' => $request->service_charge,
                    'sales_tax' => $request->sales_tax,
                    'city_tax' => $request->city_tax,
                ]
            );

            return response()->json([
                'error' => false,
                'message' => 'Property taxes updated successfully',
                'data' => [
                    'property_classification' => $taxes->property_classification,
                    'property_classification_name' => $propertyClassification == 4 ? 'vacation_homes' : 'hotel_booking',
                    'service_charge' => $taxes->service_charge,
                    'sales_tax' => $taxes->sales_tax,
                    'city_tax' => $taxes->city_tax
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle payment form submission
     * Save form data to database and send email to property owner
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitPaymentForm(Request $request)
    {
        Log::info('submitPaymentForm called', ['request' => $request->all()]);

        // Base validation rules
        $rules = [
            'property_id' => 'required|exists:propertys,id',
            'customer_id' => 'required|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'currency' => 'nullable|string|max:3',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string',
            'reservable_type' => 'required|in:property,hotel_room',
            'reservable_data' => 'nullable|array',
            'review_url' => 'nullable|url',
            // Flexible booking fields
            'property_owner_id' => 'nullable|exists:customers,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'original_amount' => 'nullable|numeric|min:0',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'requires_approval' => 'nullable|boolean',
            'booking_type' => 'nullable|in:reservation_request,flexible_booking',
            'is_flexible_booking' => 'nullable|boolean',
            'flexible_booking_discount' => 'nullable|numeric|min:0',
            'property_details' => 'nullable|array',
            'refund_policy' => 'nullable|string|in:refundable,non-refundable'
        ];

        // Conditional validation: Only require card data for non-flexible bookings
        $isFlexibleBooking = $request->has('booking_type') && $request->booking_type === 'flexible_booking';
        
        if (!$isFlexibleBooking) {
            // Non-flexible bookings require card data
            $rules['card_number'] = 'required|string|min:16|max:19';
            $rules['expiry_date'] = 'required|string|max:7';
            $rules['cvv'] = 'required|string|min:3|max:4';
            $rules['amount'] = 'required|numeric|min:0';
        } else {
            // Flexible bookings: card data optional, amount can be 0
            $rules['card_number'] = 'nullable|string|min:16|max:19';
            $rules['expiry_date'] = 'nullable|string|max:7';
            $rules['cvv'] = 'nullable|string|min:3|max:4';
            $rules['amount'] = 'nullable|numeric|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get property with owner information
            $property = Property::with('customer')->findOrFail($request->property_id);

            // NEW: Enforce cancellation period rules for hotel bookings
            if ($property->property_classification == 5 && $isFlexibleBooking) {
                $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
                $now = HelperService::toAppTimezone(Carbon::now());
                $today = $now->copy()->startOfDay();
                
                // Determine cancellation period (default to 7 days if not set)
                $cancellationDays = 7;
                if (is_numeric($property->cancellation_period)) {
                    $cancellationDays = (int)$property->cancellation_period;
                } elseif ($property->cancellation_period == '7_days') {
                    $cancellationDays = 7;
                } elseif ($property->cancellation_period == 'same_day_6pm') {
                    // Fallback for legacy data
                    $cancellationDays = 0; 
                }

                // Rule 1: Cancellation Period Logic
                // If flexible booking is attempted within X days of check-in, reject it.
                $diffInDays = $today->diffInDays($checkInDate, false);
                
                if ($diffInDays < $cancellationDays) {
                    return response()->json([
                        'error' => true,
                        'message' => "Flexible booking is not allowed within {$cancellationDays} days of check-in for this hotel. Please choose Non-Refundable."
                    ], 400);
                }
                
                // Rule 2: Same Day after 6:00 PM Policy
                // Even if cancellation period allows (e.g. 0 days), 
                // bookings for same day after 6 PM must be Non-Refundable.
                if ($today->isSameDay($checkInDate) && $now->hour >= 18) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Flexible booking is not allowed after 06:00 PM for same-day check-in. Please choose Non-Refundable.'
                    ], 400);
                }
            }

            if (!$property->customer) {
                \Illuminate\Support\Facades\Log::error('Property owner not found for property ID: ' . $property->id . '. Added By: ' . $property->added_by);
                return response()->json([
                    'error' => true,
                    'message' => 'Property owner not found'
                ], 404);
            }

            // Allow booking for pending properties - remove status restrictions
            // Previously: Only approved properties (status=1 AND request_status='approved') could be booked
            // Now: Allow booking for any property with status=1 (including pending request_status)
            if ($property->status != 1) {
                return response()->json([
                    'error' => true,
                    'message' => 'This property is not available for booking'
                ], 400);
            }

            // Handle card data for flexible bookings
            $isFlexibleBooking = $request->has('is_flexible_booking') && $request->is_flexible_booking === true;
            
            // Mask sensitive card information (only if card data is provided)
            $maskedCardNumber = null;
            $maskedCvv = null;
            
            if (!$isFlexibleBooking && $request->has('card_number')) {
                // Non-flexible bookings: mask actual card data
                $cardNumber = $request->card_number;
                $maskedCardNumber = '**** **** **** ' . substr($cardNumber, -4);
                $maskedCvv = str_repeat('*', strlen($request->cvv));
            } elseif ($isFlexibleBooking && $request->has('card_number')) {
                // Flexible bookings with card data: mask it
                $cardNumber = $request->card_number;
                $maskedCardNumber = '**** **** **** ' . substr($cardNumber, -4);
                $maskedCvv = str_repeat('*', strlen($request->cvv));
            } else {
                // Flexible bookings without card data: use placeholder
                $maskedCardNumber = '**** **** **** 0000';
                $maskedCvv = '***';
            }

            // Create payment form submission record
            $submissionData = [
                'property_id' => $request->property_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'amount' => $request->amount ?? 0,
                'currency' => $request->currency ?? 'EGP',
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'number_of_guests' => $request->number_of_guests,
                'special_requests' => $request->special_requests,
                'reservable_type' => $request->reservable_type,
                'reservable_data' => $request->reservable_data,
                'review_url' => $request->review_url,
                'status' => 'pending'
            ];

            // Add card data only if provided
            if ($maskedCardNumber) {
                $submissionData['card_number_masked'] = $maskedCardNumber;
            }
            // Ensure expiry_date is set (required by DB)
            if ($request->has('expiry_date') && !empty($request->expiry_date)) {
                $submissionData['expiry_date'] = $request->expiry_date;
            } else {
                // Default value for flexible bookings or when missing
                $submissionData['expiry_date'] = 'N/A';
            }
            if ($maskedCvv) {
                $submissionData['cvv_masked'] = $maskedCvv;
            }

            $submission = PaymentFormSubmission::create($submissionData);

            // Create reservation record for the revenue tab
            // Note: $request->amount is the discounted amount (after discount is applied)
            
            // Create reservation record for the revenue tab
            // Note: $request->amount is the discounted amount (after discount is applied)
            
            $transactionId = 'PF-' . $submission->id;
            
            // Prepare base reservation data
            $baseReservationData = [
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'reservable_type' => $request->reservable_type,
                'property_id' => $request->property_id, // Added via migration
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'number_of_guests' => $request->number_of_guests,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_status' => 'unpaid',
                'status' => 'confirmed',
                'approval_status' => 'approved',
                'requires_approval' => false,
                'booking_type' => 'reservation_request',
                'refund_policy' => $request->refund_policy ?? 'non-refundable',
                'special_requests' => $request->special_requests,
                'transaction_id' => $transactionId,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Override approval workflow fields based on Property Instant Booking
            if ($property->property_classification == 5 && !$property->instant_booking) {
                // For non-instant hotel bookings, require approval for both flexible and non-refundable
                $baseReservationData['status'] = 'pending';
                $baseReservationData['approval_status'] = 'pending';
                $baseReservationData['requires_approval'] = true;
                $baseReservationData['booking_type'] = 'reservation_request'; // Both types follow this flow
            }

            // Override approval workflow fields if explicitly provided in request
            if ($request->has('approval_status') && $request->approval_status !== null) {
                $baseReservationData['approval_status'] = $request->approval_status;
            }
            if ($request->has('requires_approval') && $request->requires_approval !== null) {
                $baseReservationData['requires_approval'] = $request->requires_approval;
            }
            if ($request->has('booking_type') && $request->booking_type !== null) {
                $baseReservationData['booking_type'] = $request->booking_type;
            }
            
            // Handle flexible reservations overrides for Instant Booking
            if ($isFlexibleBooking) {
                // Determine if we should force pending status
                // If the property is set to NOT instant booking (requires_approval=true from block above),
                // we should respect that even for flexible bookings.
                
                // If the base data already says "requires approval" (because of non-instant property setting), keep it pending.
                // Otherwise, if it's instant booking, auto-confirm flexible bookings.
                
                if (isset($baseReservationData['requires_approval']) && $baseReservationData['requires_approval'] === true) {
                    // Non-Instant Booking: Keep as Pending / Unpaid
                    $baseReservationData['status'] = 'pending';
                    $baseReservationData['payment_status'] = 'unpaid';
                    $baseReservationData['approval_status'] = 'pending';
                } else {
                    // Instant Booking: Auto-confirm Flexible
                    $baseReservationData['status'] = 'confirmed';
                    $baseReservationData['payment_status'] = 'unpaid'; // Still unpaid as it is pay at property
                    $baseReservationData['approval_status'] = 'approved';
                    $baseReservationData['requires_approval'] = false;
                }
                
                $baseReservationData['payment_method'] = 'cash'; // Flexible is always Pay at Property (Cash)
                
                // For flexible bookings, default refund policy to flexible/refundable if not provided
                if (!$request->has('refund_policy')) {
                    $baseReservationData['refund_policy'] = 'flexible';
                }
            }

            // Add property details if provided
            if ($request->has('property_details')) {
                // $baseReservationData['property_details'] = json_encode($request->property_details); // Still missing column? No, migration didn't add it.
            }

            $createdReservations = [];

            // Case 1: Hotel Room Reservations (Potential Multi-Room)
            $reservableData = $request->reservable_data;
            if (is_string($reservableData)) {
                $decoded = json_decode($reservableData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $reservableData = $decoded;
                }
            }
            
            Log::info('SubmitPaymentForm processing', [
                'reservable_type' => $request->reservable_type,
                'reservable_data_type' => gettype($request->reservable_data),
                'reservable_data_count' => is_array($reservableData) ? count($reservableData) : 'N/A'
            ]);

            if (trim($request->reservable_type) === 'hotel_room' && !empty($reservableData) && is_array($reservableData)) {
                $isFlexibleBooking = $request->has('is_flexible_booking') && $request->is_flexible_booking === true;
            
            Log::info('SubmitPaymentForm: Flexible Booking Check', [
                'has_is_flexible_booking' => $request->has('is_flexible_booking'),
                'is_flexible_booking_value' => $request->is_flexible_booking,
                'is_flexible_booking_flag' => $isFlexibleBooking
            ]);

            $reservationService = new \App\Services\ReservationService();
                
                foreach ($reservableData as $roomData) {
                    $reservableId = $roomData['id'] ?? $roomData['roomId'];
                    
                    // Availability Check & Alternative Logic
                    if ($isFlexibleBooking) {
                        // Flexible: Use selected room, skip check
                         Log::info('Flexible booking - using selected room (skipping availability check)', [
                            'property_id' => $request->property_id,
                            'assigned_room_id' => $reservableId
                        ]);
                    } else {
                        // Strict: Check availability & find alternative
                        if ($reservableId) {
                            $isAvailable = $reservationService->areDatesAvailable(
                                'App\\Models\\HotelRoom',
                                $reservableId,
                                $request->check_in_date,
                                $request->check_out_date
                            );
                            
                            if (!$isAvailable) {
                                Log::info('Selected room is not available, searching for alternative', ['room_id' => $reservableId]);
                                
                                $room = HotelRoom::find($reservableId);
                                if ($room) {
                                    $alternativeRooms = HotelRoom::where('property_id', $room->property_id)
                                        ->where('room_type_id', $room->room_type_id)
                                        ->where('id', '!=', $room->id)
                                        ->where('status', 1)
                                        ->get();
                                        
                                    foreach ($alternativeRooms as $altRoom) {
                                        if ($reservationService->areDatesAvailable(
                                            'App\\Models\\HotelRoom',
                                            $altRoom->id,
                                            $request->check_in_date,
                                            $request->check_out_date
                                        )) {
                                            $reservableId = $altRoom->id;
                                            // Update room data ID for consistency in JSON
                                            $roomData['id'] = $reservableId;
                                            Log::info('Found alternative available room', ['new_room_id' => $reservableId]);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Create Reservation for this room
                    $reservationData = $baseReservationData;
                    $reservationData['reservable_id'] = $reservableId;
                    
                    Log::info('SubmitPaymentForm: Creating Reservation', [
                        'reservable_id' => $reservableId,
                        'booking_type' => $reservationData['booking_type'] ?? 'MISSING',
                        'is_flexible_booking' => isset($isFlexibleBooking) ? $isFlexibleBooking : 'MISSING',
                        'refund_policy' => $reservationData['refund_policy'] ?? 'MISSING'
                    ]);
                    // Use individual room amount
                    $reservationData['total_price'] = $roomData['amount'] ?? 0;
                    // Fallback for original amount
                    $reservationData['original_amount'] = $roomData['amount'] ?? 0; 
                    // Set specific guest count if available, else fallback
                    $reservationData['number_of_guests'] = $roomData['guest_count'] ?? $request->number_of_guests;
                    // Store ONLY this room's data
                    $reservationData['reservable_data'] = json_encode([$roomData]);
                    
                    // Calculate discount amount (Pro-rated or simplified)
                    // For now, we set it to 0 per room unless we calculate pro-rata
                    $reservationData['discount_amount'] = 0;
                    $reservationData['discount_percentage'] = $request->discount_percentage ?? 0;
                    
                    $createdReservations[] = \App\Models\Reservation::create($reservationData);
                    
                    // If flexible and instant booking (confirmed), update available dates
                    if ($reservationData['status'] === 'confirmed' && $isFlexibleBooking) {
                        $reservationService->updateAvailableDates(
                            'App\Models\HotelRoom',
                            $reservableId,
                            $request->check_in_date,
                            $request->check_out_date,
                            $createdReservations[count($createdReservations)-1]->id
                        );
                    }
                }
            } 
            // Case 2: Property/Single Reservation
            else {
                $reservableId = $request->property_id;
                // Basic availability check for property could be added here if needed, 
                // but usually handled by 'reservable_type' logic upstream or assumed available if payment form is submitted.
                
                $reservationData = $baseReservationData;
                $reservationData['reservable_id'] = $reservableId;
                $reservationData['total_price'] = $request->amount;
                $reservationData['original_amount'] = $request->original_amount ?? $request->amount;
                $reservationData['discount_amount'] = isset($request->original_amount) ? ($request->original_amount - $request->amount) : 0;
                $reservationData['discount_percentage'] = $request->discount_percentage ?? 0;
                
                // Keep reservable_data if it exists (e.g. for property details not in standard fields)
                if ($request->reservable_data) {
                    $reservationData['reservable_data'] = json_encode($request->reservable_data);
                }
                
                // dd($reservationData); // Debugging
                $createdReservations[] = \App\Models\Reservation::create($reservationData);

                // If flexible and instant booking (confirmed), update available dates
                if ($reservationData['status'] === 'confirmed' && ($reservationData['is_flexible_booking'] ?? false)) {
                    $reservationService = new \App\Services\ReservationService();
                    $reservationService->updateAvailableDates(
                        $reservationData['reservable_type'],
                        $reservableId,
                        $request->check_in_date,
                        $request->check_out_date,
                        $createdReservations[0]->id
                    );
                }
            }
            
            // Set main reservation context for response/emails
            $reservation = $createdReservations[0];
            
            // Define siblings for multi-room reservations
            $siblings = count($createdReservations) > 1 ? array_slice($createdReservations, 1) : [];
            
            Log::info('Created reservations count: ' . count($createdReservations));

            Log::info('Proceeding to send emails...');

            // Generate PayPal URL if payment method is paypal
            $paymentUrl = null;
            if ($request->payment_method === 'paypal') {
                try {
                    $paypal = new Paypal();
                    $paypal->add_field('return', 'https://ashome-eg.com/');
                    $paypal->add_field('cancel_return', 'https://ashome-eg.com/');
                    $paypal->add_field('notify_url', 'https://maroon-fox-767665.hostingersite.com/api/payments/paypal/ipn');
                    $paypal->add_field('item_name', 'Reservation ' . $transactionId);
                    $paypal->add_field('amount', $request->amount);
                    $paypal->add_field('custom', $transactionId);
                    $paypal->add_field('currency_code', env('PAYPAL_CURRENCY', 'USD'));
                    
                    if ($request->customer_email) {
                        $paypal->add_field('email', $request->customer_email);
                    }
                    
                    $paymentUrl = $paypal->get_payment_url();
                    Log::info('PayPal payment URL generated in submitPaymentForm', ['url' => $paymentUrl]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate PayPal URL in submitPaymentForm', ['error' => $e->getMessage()]);
                }
            }

            // Send emails to both property owner and customer (if flexible booking)
            try {
                // 1. Send Payment Form Submission Notification to Property Owner
                $emailTypeData = HelperService::getEmailTemplatesTypes('payment_form_submission');
                $templateData = system_setting('payment_form_submission_mail_template');
                
                // Prepare Room Type or Table for Multi-Room
                $roomType = $request->room_type;
                
                // If room type not provided in request and it's a single reservation, try to fetch from reservation
                if (empty($roomType) && count($createdReservations) === 1) {
                    $res = $createdReservations[0];
                    if (in_array($res->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                        $hotelRoom = $res->reservable;
                        if ($hotelRoom) {
                             $customRoomType = trim($hotelRoom->custom_room_type ?? '');
                             $roomType = !empty($customRoomType) ? $customRoomType : (optional($hotelRoom->roomType)->name ?? 'Standard Room');
                        }
                    } elseif (in_array($res->reservable_type, ['App\Models\Property', 'property'])) {
                        $roomType = $res->reservable->title ?? 'Property';
                    }
                }
                
                // Fallback
                if (empty($roomType)) {
                    $roomType = 'Standard';
                }

                $currencySymbol = $request->currency ?? 'EGP';
                
                if (count($createdReservations) > 1) {
                    $tableRows = '';
                    $hasPackages = false;
                    
                    // First pass to check if any room has a package
                    foreach ($createdReservations as $res) {
                         $reservableData = is_string($res->reservable_data) ? json_decode($res->reservable_data, true) : $res->reservable_data;
                         if (!empty($reservableData) && isset($reservableData[0]['package_name'])) {
                             $hasPackages = true;
                             break;
                         }
                    }

                    foreach ($createdReservations as $res) {
                        $resName = 'Property';
                        $packageName = '-';
                        
                        if (in_array($res->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                             $hotelRoom = $res->reservable;
                             $customRoomType = trim($hotelRoom->custom_room_type ?? '');
                             $resName = !empty($customRoomType) ? $customRoomType : (optional($hotelRoom->roomType)->name ?? 'Standard Room');
                             
                             // Extract package info from reservable_data
                             $reservableData = is_string($res->reservable_data) ? json_decode($res->reservable_data, true) : $res->reservable_data;
                             if (!empty($reservableData) && isset($reservableData[0]['package_name'])) {
                                 $packageName = $reservableData[0]['package_name'];
                             }
                        } elseif (in_array($res->reservable_type, ['App\Models\Property', 'property'])) {
                             $resName = $res->reservable->title ?? 'Property';
                        }
                        
                        $resPrice = number_format($res->total_price, 2);
                        $resGuests = $res->number_of_guests ?: 1;
                        
                        $packageCell = $hasPackages ? "<td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$packageName}</td>" : "";
                        
                        $tableRows .= "
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd;'>{$resName}</td>
                                {$packageCell}
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$resGuests}</td>
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>{$resPrice} {$currencySymbol}</td>
                            </tr>
                        ";
                    }

                    $packageHeader = $hasPackages ? "<th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Package</th>" : "";
                    $colspan = $hasPackages ? "3" : "2";

                    $roomType = "
                        <div style='margin-top: 15px; margin-bottom: 15px;'>
                            <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                                <thead>
                                    <tr style='background-color: #f2f2f2;'>
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Room Type</th>
                                        {$packageHeader}
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Guests</th>
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$tableRows}
                                </tbody>
                                <tfoot>
                                    <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                        <td colspan='{$colspan}' style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Total</td>
                                        <td style='padding: 10px; border: 1px solid #ddd; text-align: right;'>" . number_format($request->amount, 2) . " {$currencySymbol}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    ";
                } elseif (count($createdReservations) === 1) {
                    // Single reservation logic for package display
                    $res = $createdReservations[0];
                    $reservableData = is_string($res->reservable_data) ? json_decode($res->reservable_data, true) : $res->reservable_data;
                    
                    if (!empty($reservableData) && isset($reservableData[0]['package_name']) && $reservableData[0]['package_name'] !== 'Room Only') {
                        $packageName = $reservableData[0]['package_name'];
                        // Append package name to room type if it exists and is not just "Room Only"
                        if ($packageName && $packageName !== '-') {
                            $roomType .= " (Package: {$packageName})";
                        }
                    }
                }
                
                // Calculate Payment Breakdown for Hotel Reservations
                $paymentBreakdown = '';
                // Check if property is a hotel (classification 5)
                // We use getRawOriginal because accessors might transform it
                $propClassification = $property->getRawOriginal('property_classification');
                
                if ($propClassification == 5) {
                    $taxModel = \App\Models\PropertyTax::where('property_classification', 5)->first();
                    $taxPercentage = $taxModel ? $taxModel->total_tax_percentage : 0;
                    
                    // Individual Tax Components
                    $serviceChargeRate = $taxModel ? $taxModel->service_charge : 0;
                    $salesTaxRate = $taxModel ? $taxModel->sales_tax : 0;
                    $cityTaxRate = $taxModel ? $taxModel->city_tax : 0;
                    
                    // Platform commission is 15% for hotels (as per PropertyTax logic)
                    $platformCommissionRate = 12; 
                    
                    $totalGuestPayment = $request->amount;
                    
                    // Base Room Revenue = Total / (1 + Tax%)
                    // Assuming Total Amount includes Tax
                    if ($taxPercentage > 0) {
                        $baseRoomRevenue = $totalGuestPayment / (1 + ($taxPercentage / 100));
                    } else {
                        $baseRoomRevenue = $totalGuestPayment;
                    }
                    
                    // Calculate individual tax amounts based on Base Room Revenue
                    $serviceChargeAmount = $baseRoomRevenue * ($serviceChargeRate / 100);
                    $salesTaxAmount = $baseRoomRevenue * ($salesTaxRate / 100);
                    $cityTaxAmount = $baseRoomRevenue * ($cityTaxRate / 100);
                    
                    $platformCommission = $baseRoomRevenue * ($platformCommissionRate / 100);
                    $totalNetPayout = $baseRoomRevenue - $platformCommission;
                    
                    $paymentBreakdown = "
                        <div style='margin-top: 20px; margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 15px;'>
                            <h3 style='font-size: 16px; margin-bottom: 10px; color: #333;'>Payment Breakdown</h3>
                            <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                                <tbody>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Total Guest Payment</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; font-weight: bold;'>" . number_format($totalGuestPayment, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Base Room Revenue</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right;'>" . number_format($baseRoomRevenue, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Platform Commission (12%)</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($platformCommission, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Service Charge ({$serviceChargeRate}%)</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($serviceChargeAmount, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>Sales Tax ({$salesTaxRate}%)</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($salesTaxAmount, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0;'>City Tax ({$cityTaxRate}%)</td>
                                        <td style='padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #dc3545;'>-" . number_format($cityTaxAmount, 2) . " {$currencySymbol}</td>
                                    </tr>
                                    <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                        <td style='padding: 10px; border-top: 2px solid #ddd;'>Net Room Revenue</td>
                                        <td style='padding: 10px; border-top: 2px solid #ddd; text-align: right; color: #28a745; font-size: 16px;'>" . number_format($totalNetPayout, 2) . " {$currencySymbol}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    ";
                }
                
                $variables = array(
                    'app_name' => env('APP_NAME') ?? 'As-home',
                    'property_owner_name' => $property->customer->name,
                    'customer_name' => $request->customer_name,
                    'customer_email' => $request->customer_email,
                    'customer_phone' => $request->customer_phone,
                    'property_name' => $property->title,
                    'property_address' => $property->address,
                    'room_type' => $roomType,
                    'payment_breakdown' => $paymentBreakdown,
                    'check_in_date' => $request->check_in_date,
                    'check_out_date' => $request->check_out_date,
                    'number_of_guests' => $request->number_of_guests,
                    'total_amount' => number_format($request->amount, 2),
                    'currency_symbol' => $currencySymbol,
                    'card_number_masked' => $maskedCardNumber,
                    'special_requests' => $request->special_requests ?? 'None',
                    'submission_date' => now()->format('Y-m-d H:i:s'),
                    'current_date_today' => now()->format('d M Y, h:i A'),
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $reservation->transaction_id,
                    'approval_status' => $reservation->approval_status ?? 'pending',
                    'booking_type' => $reservation->booking_type ?? 'reservation_request'
                );

                if (empty($templateData)) {
                    $templateData = 'New reservation request received for property "{property_name}" from {customer_name} ({customer_email}). Room Type: {room_type}. Amount: {total_amount} {currency_symbol}. Check-in: {check_in_date}, Check-out: {check_out_date}. Reservation ID: {reservation_id}. Please review and approve this booking in your dashboard.';
                }

                // Add Action Required note for non-instant bookings
                if ($reservation->requires_approval) {
                    $templateData .= "\n\n⏳ **Action Required**: This is a non-instant booking. Please review and approve or reject this request in your dashboard.";
                }

                $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

                $data = array(
                    'email_template' => $emailTemplate,
                    'email' => $property->customer->email,
                    'title' => $emailTypeData['title'],
                );

                HelperService::sendMail($data, false, true);

                // 2. Send appropriate pending approval email to Customer based on property classification
                // Get the customer from the reservation
                $customer = \App\Models\Customer::find($request->customer_id);
                
                if ($customer && $customer->email) {
                    // Use the existing ReservationService to send the appropriate email
                    $reservationService = new \App\Services\ReservationService();
                    $propertyClassification = $property->getRawOriginal('property_classification');
                    
                    // Check if this is a flexible booking
                    $isFlexibleBooking = $reservation->booking_type === 'flexible_booking';
                        
                        Log::info('SubmitPaymentForm: Email Logic', [
                            'reservation_id' => $reservation->id,
                            'booking_type' => $reservation->booking_type,
                            'is_flexible_booking_flag' => $isFlexibleBooking
                        ]);

                        if ($isFlexibleBooking) {
                        try {
                            $reservationService->sendFlexibleHotelBookingConfirmationEmail($reservation, $siblings);
                            Log::info('Flexible booking confirmation email sent to customer', [
                                'reservation_id' => $reservation->id,
                                'customer_email' => $customer->email,
                                'booking_type' => 'flexible_booking',
                                'siblings_count' => count($siblings)
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Flexible booking confirmation email failed: ' . $e->getMessage(), [
                                'reservation_id' => $reservation->id,
                                'customer_email' => $customer->email,
                                'booking_type' => 'flexible_booking',
                                'siblings_count' => count($siblings),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } elseif ($propertyClassification == 4) {
                        // Vacation home - send pending approval email
                        $reservationService->sendVacationHomePendingApprovalEmail($reservation);
                    } elseif ($propertyClassification == 5) {
                        // Hotel booking - send aggregated reservation confirmation email
                        $reservationService->sendAggregatedReservationConfirmationEmail($createdReservations);
                    }
                    
                    Log::info('Both emails sent: Payment form submission to owner and appropriate email to customer', [
                        'reservation_id' => $reservation->id,
                        'property_owner_email' => $property->customer->email,
                        'customer_email' => $customer->email,
                        'property_classification' => $propertyClassification,
                        'property_id' => $property->id,
                        'instant_booking' => $property->instant_booking,
                        'booking_type' => $reservation->booking_type,
                        'is_flexible_booking' => $isFlexibleBooking
                    ]);
                } else {
                    Log::info('Payment form submission email sent to property owner only (customer email not found)', [
                        'reservation_id' => $reservation->id,
                        'property_owner_email' => $property->customer->email,
                        'property_id' => $property->id,
                        'customer_id' => $request->customer_id
                    ]);
                }

                // Update submission status to processed
                $submission->update(['status' => 'processed']);

            } catch (Exception $e) {
                // Log email error but don't fail the transaction
                Log::error('Failed to send payment form submission emails: ' . $e->getMessage());
                $submission->update(['status' => 'failed', 'notes' => 'Email sending failed: ' . $e->getMessage()]);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Payment form submitted successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'reservation_id' => $reservation->id,
                    'status' => $submission->status,
                    'approval_status' => $reservation->approval_status ?? 'pending',
                    'payment_url' => $paymentUrl
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Payment Form Submission Error", "Something Went Wrong");
            
            // Always return JSON response to prevent frontend JSON parsing errors
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while processing your payment. Please try again.',
                'data' => null,
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if property has pending edit request
     *
     * @param int $propertyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPendingEditRequest($propertyId)
    {
        try {
            $pendingRequest = \App\Models\PropertyEditRequest::where('property_id', $propertyId)
                ->where('status', 'pending')
                ->first();
            
            return response()->json([
                'error' => false,
                'data' => [
                    'has_pending_request' => $pendingRequest ? true : false,
                    'message' => $pendingRequest 
                        ? 'This property has a pending edit request. Please wait for approval before making additional changes.'
                        : 'No pending edit request found.',
                    'edit_request' => $pendingRequest ? [
                        'id' => $pendingRequest->id,
                        'status' => $pendingRequest->status,
                        'created_at' => $pendingRequest->created_at,
                    ] : null
                ]
            ]);
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, "Check Pending Edit Request Error", "Something Went Wrong");
        }
    }

    /**
     * Check if approval-required fields have changed
     *
     * @param Property $property
     * @param Request $request
     * @param array $approvalRequiredFields
     * @param int $propertyClassification
     * @return bool
     */
    private function hasApprovalRequiredChanges($property, $request, $approvalRequiredFields, $propertyClassification)
    {
        // Check title fields
        if ($request->has('title') && $request->title != $property->title) {
            return true;
        }
        if ($request->has('title_ar') && $request->title_ar != $property->title_ar) {
            return true;
        }

        // Check description fields
        if ($request->has('description') && $request->description != $property->description) {
            return true;
        }
        if ($request->has('description_ar') && $request->description_ar != $property->description_ar) {
            return true;
        }

        // Check area description fields
        if ($request->has('area_description') && $request->area_description != $property->area_description) {
            return true;
        }
        if ($request->has('area_description_ar') && $request->area_description_ar != $property->area_description_ar) {
            return true;
        }

        // Check images (if new files uploaded)
        if ($request->hasFile('title_image') || 
            $request->hasFile('gallery_images') || 
            $request->hasFile('three_d_image') ||
            $request->hasFile('meta_image')) {
            return true;
        }

        // Check hotel room descriptions (only description field)
        if ($propertyClassification == 5 && $request->has('hotel_rooms') && is_array($request->hotel_rooms)) {
            $originalRooms = \App\Models\HotelRoom::where('property_id', $property->id)->get();
            
            foreach ($request->hotel_rooms as $room) {
                if (isset($room['id']) && isset($room['description'])) {
                    $originalRoom = $originalRooms->find($room['id']);
                    if ($originalRoom && $originalRoom->description != $room['description']) {
                        return true;
                    }
                } else if (isset($room['description']) && !empty($room['description'])) {
                    // New room with description
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find nearby cities by name using coordinate-based search
     * 
     * @param string $cityName
     * @return array
     */
    private function findNearbyCitiesByName($cityName)
    {
        // First, try to find the city in our database to get its coordinates
        $cityProperties = Property::where('city', 'LIKE', '%' . $cityName . '%')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('city', 'latitude', 'longitude')
            ->distinct()
            ->limit(1)
            ->get();

        if ($cityProperties->isEmpty()) {
            return [];
        }

        $referenceCity = $cityProperties->first();
        $referenceLatitude = $referenceCity->latitude;
        $referenceLongitude = $referenceCity->longitude;

        // Calculate nearby cities within a reasonable radius (e.g., 50km)
        // Using the Haversine formula to calculate distance
        $radius = 50; // kilometers
        $earthRadius = 6371; // Earth's radius in kilometers

        // Convert radius to radians
        $radiusInDegrees = $radius / $earthRadius * (180 / pi());

        // Find all cities within the radius
        $nearbyCities = Property::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$referenceLatitude, $referenceLongitude, $referenceLatitude, $radius])
            ->select('city')
            ->distinct()
            ->pluck('city')
            ->toArray();

        return $nearbyCities;
    }

    /**
     * Find an available hotel room for flexible bookings
     * 
     * @param int $propertyId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param array $reservableData
     * @return HotelRoom|null
     */
    private function findAvailableHotelRoom($propertyId, $checkInDate, $checkOutDate, $reservableData)
    {
        // Extract room IDs from reservable_data with type handling
        $roomIds = [];
        foreach ($reservableData as $room) {
            if (isset($room['id']) && $room['id'] !== null && $room['id'] !== '') {
                // Convert to string to handle type mismatches
                $roomIds[] = (string) $room['id'];
            } elseif (isset($room['roomId']) && $room['roomId'] !== null && $room['roomId'] !== '') {
                // Fallback to roomId if id is not present
                $roomIds[] = (string) $room['roomId'];
            }
        }
        
        if (empty($roomIds)) {
            Log::error('No room IDs found in reservable_data for flexible booking', [
                'property_id' => $propertyId,
                'reservable_data' => $reservableData,
                'extracted_room_ids' => $roomIds
            ]);
            return null;
        }
        
        // Convert room IDs to integers for database comparison since Laravel expects integers
        $integerRoomIds = array_map('intval', $roomIds);
        
                Log::info('Finding available hotel room for flexible booking', [
            'property_id' => $propertyId,
            'check_in' => $checkInDate,
            'check_out' => $checkOutDate,
            'candidate_room_ids' => $roomIds,
            'converted_room_ids' => $integerRoomIds,
            'reservable_data_type' => gettype($reservableData),
            'first_item' => !empty($reservableData) ? $reservableData[0] : 'empty'
        ]);
        
        // Find an available room from the provided room IDs
        
        $availableRoom = HotelRoom::where('property_id', $propertyId)
            ->whereIn('id', $integerRoomIds)
            ->where('status', 1)
            ->whereDoesntHave('reservations', function ($query) use ($checkInDate, $checkOutDate) {
                // For flexible bookings, be more lenient with availability checks
                // Only check for exact date conflicts (same check-in and check-out dates)
                $query->whereIn('status', ['confirmed', 'approved', 'pending']) // Also exclude pending/approved to be safe
                    ->where(function ($dateQuery) use ($checkInDate, $checkOutDate) {
                        // Check for exact date matches (most restrictive)
                        $dateQuery->where(function ($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '=', $checkInDate)
                                ->where('check_out_date', '=', $checkOutDate);
                        })
                        // Check for overlapping stays (partial overlap)
                        ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '<', $checkOutDate)
                                ->where('check_out_date', '>', $checkInDate);
                        });
                    });
            })
            ->first();

        if (!$availableRoom) {
             $debugTotal = HotelRoom::where('property_id', $propertyId)->whereIn('id', $integerRoomIds)->count();
             Log::warning('DEBUG: Primary search failed', [
                 'rooms_exist_count' => $debugTotal,
                 'ids' => $integerRoomIds
             ]);
        }
        
        if ($availableRoom) {
            Log::info('Found available room for flexible booking', [
                'property_id' => $propertyId,
                'assigned_room_id' => $availableRoom->id,
                'room_number' => $availableRoom->room_number ?? 'N/A'
            ]);
        } else {
            // Fallback: If specific room is not available, try to find ANY room of the same room type
            // Extract room type IDs from reservable_data
            $roomTypeIds = [];
            foreach ($reservableData as $room) {
                if (isset($room['room_type_id']) && $room['room_type_id'] !== null) {
                    $roomTypeIds[] = $room['room_type_id'];
                }
            }
            
            // Remove duplicates
            $roomTypeIds = array_unique($roomTypeIds);
            
            if (!empty($roomTypeIds)) {
                Log::info('Searching for alternative rooms by type', [
                    'property_id' => $propertyId,
                    'room_type_ids' => $roomTypeIds
                ]);
                
                $availableRoom = HotelRoom::where('property_id', $propertyId)
                    ->whereIn('room_type_id', $roomTypeIds)
                    ->where('status', 1)
                    ->whereDoesntHave('reservations', function ($query) use ($checkInDate, $checkOutDate) {
                        $query->whereIn('status', ['confirmed', 'approved', 'pending'])
                            ->where(function ($dateQuery) use ($checkInDate, $checkOutDate) {
                                // Check for exact date matches (most restrictive)
                                $dateQuery->where(function ($q) use ($checkInDate, $checkOutDate) {
                                    $q->where('check_in_date', '=', $checkInDate)
                                        ->where('check_out_date', '=', $checkOutDate);
                                })
                                // Check for overlapping stays (partial overlap)
                                ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                    $q->where('check_in_date', '<', $checkOutDate)
                                        ->where('check_out_date', '>', $checkInDate);
                                });
                            });
                    })
                    ->first();
                    
                if ($availableRoom) {
                    Log::info('Found alternative room by type', [
                        'property_id' => $propertyId,
                        'original_room_ids' => $integerRoomIds,
                        'assigned_alternative_room_id' => $availableRoom->id,
                        'room_type_id' => $availableRoom->room_type_id
                    ]);
                }
            }
            
            if (!$availableRoom) {
                Log::warning('No available rooms found for flexible booking (checked specific IDs and room types)', [
                    'property_id' => $propertyId,
                    'check_in' => $checkInDate,
                    'check_out' => $checkOutDate,
                    'candidate_room_ids' => $roomIds,
                    'candidate_room_type_ids' => $roomTypeIds ?? []
                ]);
            }
        }
        
        return $availableRoom;
    }
}
