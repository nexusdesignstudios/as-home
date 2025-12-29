<?php

use Google\Client;
use App\Models\User;
use GuzzleHttp\Pool;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Favourite;
use App\Models\parameter;
use App\Models\Usertokens;
use Illuminate\Support\Str;
use App\Models\user_reports;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\Utils;
use App\Models\InterestedUser;
use Illuminate\Support\Carbon;
use App\Models\BlockedChatUser;
use App\Services\HelperService;
use App\Models\PropertysInquiry;
use kornrunner\Blurhash\Blurhash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use App\Models\OldUserPurchasedPackage;
use Intervention\Image\ImageManagerStatic as Image;


if (!function_exists('system_setting')) {

    function system_setting($type)
    {

        $db = Setting::where('type', $type)->first();
        return (isset($db)) ? $db->data : '';
    }
}

function form_submit($data = '', $value = '', $extra = '')
{
    $defaults = array(
        'type' => 'submit',
        'name' => is_array($data) ? '' : $data,
        'value' => $value
    );

    return '<input ' . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}
function _parse_form_attributes($attributes, $default)
{
    if (is_array($attributes)) {
        foreach ($default as $key => $val) {
            if (isset($attributes[$key])) {
                $default[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        if (count($attributes) > 0) {
            $default = array_merge($default, $attributes);
        }
    }

    $att = '';

    foreach ($default as $key => $val) {
        if ($key === 'value') {
            $val = ($val);
        } elseif ($key === 'name' && !strlen($default['name'])) {
            continue;
        }

        $att .= $key . '="' . $val . '" ';
    }

    return $att;
}


// ------------------------------------------------------------------------

if (!function_exists('_attributes_to_string')) {
    /**
     * Attributes To String
     *
     * Helper function used by some of the form helpers
     *
     * @param	mixed
     * @return	string
     */
    function _attributes_to_string($attributes)
    {
        if (empty($attributes)) {
            return '';
        }

        if (is_object($attributes)) {
            $attributes = (array) $attributes;
        }

        if (is_array($attributes)) {
            $atts = '';

            foreach ($attributes as $key => $val) {
                $atts .= ' ' . $key . '="' . $val . '"';
            }

            return $atts;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        return FALSE;
    }
}

function send_push_notification($registrationIDs = array(), $fcmMsg = '')
{
    try {
        if (!count($registrationIDs)) {
            return false;
        }
        $client = new GuzzleClient();
        $access_token = getAccessToken(); // Get Access Token
        $projectId = system_setting('firebase_project_id'); // Get Project Id
        $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send'; // Create URL
        // Headers
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ];

        // Create Requests
        $requests = function ($registrationIDs) use ($url, $headers, $fcmMsg) {
            foreach ($registrationIDs as $registrationID) {
                $fcmFields = [
                    'json' => [
                        'message' => [
                            'token' => $registrationID,
                            'notification' => [
                                'title' => $fcmMsg['title'],
                                'body' => $fcmMsg['body']
                            ],
                            'data' => $fcmMsg
                        ]
                    ]
                ];
                yield new Request('POST', $url, $headers, json_encode($fcmFields['json']));
            }
        };

        // This is used to Process multiple Request at a same time
        $pool = new Pool($client, $requests($registrationIDs), [
            'concurrency' => 10, // Adjust based on your server capability
            'fulfilled' => function ($response, $index) {
                // Code after fulfilled Request
            },
            'rejected' => function ($reason, $index) use (&$unregisteredIDsNested, $registrationIDs) {
                $response = $reason->getResponse();
                if ($response) {
                    $decodedResult = json_decode($response->getBody(), true);
                    if (isset($decodedResult['error']['status']) && ($decodedResult['error']['status'] == 'INVALID_ARGUMENT' || $decodedResult['error']['status'] == 'NOT_FOUND')) {
                        $unregisteredIDsNested[] = $registrationIDs[$index];
                    }
                }
                Log::error($reason->getMessage());
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        // Flatten the nested array if it exists
        $unregisteredIDs = !empty($unregisteredIDsNested) ? (is_array($unregisteredIDsNested[0]) ? array_merge(...$unregisteredIDsNested) : $unregisteredIDsNested) : [];

        if (!empty($unregisteredIDs)) {
            Usertokens::whereIn('fcm_id', $unregisteredIDs)->delete();
        }

        return true;
    } catch (Exception $e) {
        Log::error("Error in Notification Sending :- " . $e->getMessage());
        return false;
    }
}



if (!function_exists('get_countries_from_json')) {
    function get_countries_from_json()
    {
        $country =  json_decode(file_get_contents(public_path('json') . "/cities.json"), true);

        $tempRow = array();
        foreach ($country['countries'] as $row) {
            $tempRow[] = $row['country'];
        }
        return $tempRow;
    }
}

if (!function_exists('get_states_from_json')) {
    function get_states_from_json($country)
    {


        $state =  json_decode(file_get_contents(public_path('json') . "/cities.json"), true);

        $tempRow = array();
        foreach ($state['countries'] as $row) {
            // echo $row;
            if ($row['country'] == $country) {
                $tempRow = $row['states'];
            }
        }

        return $tempRow;
    }
}


function update_subscription($userId)
{
    // Array Initialize
    $updateUserPackage = array();
    // User Package Query
    $userPackages = OldUserPurchasedPackage::with('package', 'customer')->where('modal_id', $userId);
    // Result Data
    $result = $userPackages->clone()->with('package', 'customer')->get();

    // Get Package Count
    $packageCount = $userPackages->clone()->where(function ($query) {
        $query->where(function ($subQuery) {
            $subQuery->where('prop_status', 1)->orWhere('adv_status', 1);
        });
    })->count();

    // loop on result data
    if (collect($result)->isNotEmpty()) {
        foreach ($result as $key => $row) {
            // Get end date of current looped data
            $endDate = Carbon::parse($row->end_date, 'UTC')->startOfDay(); // Parse the date with UTC time zone and set time to start of the day
            $currentDate = Carbon::now()->startOfDay(); // Set current date time to start of the day

            // Get Difference in days
            $diffInDays = $currentDate->diffInDays($endDate, false); // Use 'false' parameter to get absolute difference

            // If days are zero or in negative
            if ($diffInDays < 0) {
                $updateUserPackage[] = array(
                    'id' => $row->id,
                    'prop_status' => 0,
                    'adv_status' => 0
                );
                if (!empty($row->package) && $row->package->type == "premium_user") {
                    $customerPremiumStatus = 0;
                }
            }
        }
    }

    // Bulk Update the user packages limits to zero
    if (!empty($updateUserPackage)) {
        OldUserPurchasedPackage::upsert($updateUserPackage, ['id'], ['prop_status', 'adv_status']);
    }

    // if package count is 0 then update the customer's subscription to 0 and is_premium according to $customerPremiumStatus
    if ($packageCount == 0) {
        $customer = Customer::find($userId);
        $customer->subscription = 0;
        // if there is customerPremiumStatus and its zero then only update
        if (isset($customerPremiumStatus) && $customerPremiumStatus == 0) {
            $customer->is_premium = 0;
        }
        $customer->update();
    }
}
function get_hash($img)
{

    $image_make = Image::make($img);
    $width = $image_make->width();
    $height = $image_make->height();

    $pixels = [];
    for ($y = 0; $y < $height; ++$y) {
        $row = [];
        for ($x = 0; $x < $width; ++$x) {
            $colors = $image_make->pickColor($x, $y);

            $row[] = [$colors[0], $colors[1], $colors[2]];
        }
        $pixels[] = $row;
    }

    $components_x = 4;
    $components_y = 3;
    $hash =  Blurhash::encode($pixels, $components_x, $components_y);
    //  "ll";
    return $hash;
}
if (!function_exists('form_hidden')) {
    /**
     * Hidden Input Field
     *
     * Generates hidden fields. You can pass a simple key/value string or
     * an associative array with multiple values.
     *
     * @param	mixed	$name		Field name
     * @param	string	$value		Field value
     * @param	bool	$recursing
     * @return	string
     */
    function form_hidden($name, $value = '', $recursing = FALSE)
    {
        static $form;

        if ($recursing === FALSE) {
            $form = "\n";
        }

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                form_hidden($key, $val, TRUE);
            }

            return $form;
        }

        if (!is_array($value)) {
            $form .= '<input type="hidden" name="' . $name . '" value="' . ($value) . "\" />\n";
        } else {
            foreach ($value as $k => $v) {
                $k = is_int($k) ? '' : $k;
                form_hidden($name . '[' . $k . ']', $v, TRUE);
            }
        }

        return $form;
    }
}
if (!function_exists('form_close')) {
    /**
     * Form Close Tag
     *
     * @param	string
     * @return	string
     */
    function form_close($extra = '')
    {
        return '</form>' . $extra;
    }
}
function get_property_details($result, $current_user = NULL, $skipLimitCheck = false)
{
    $rows = array();
    $tempRow = array();
    $count = 1;
    foreach ($result as $row) {
        // if ($row->is_premium == 1) {
        //     if (Auth::guard('sanctum')->check() && $skipLimitCheck == false) {
        //         // Check if the user has a premium property list feature in package
        //         $response = HelperService::checkPackageLimit('premium_properties', true);
        //         if ($response['package_available'] == false || $response['feature_available'] == false) {
        //             return ApiResponseService::validationError('Cannot Access Premium Property, Feature Not Available', $response);
        //         }
        //     } else {
        //         return ApiResponseService::validationError('Cannot Access Premium Property, Feature Not Available');
        //     }
        // }
        $customer = $row->customer;

        // Get Property's Added by details
        if ($customer && $row->added_by != 0) {
            $isBlockedByMe = false;
            $isBlockedByUser = false;
            if ($current_user) {

                $isBlockedByMe = BlockedChatUser::where('by_user_id', $current_user)
                    ->where('user_id', $row->added_by)
                    ->exists();

                $isBlockedByUser = BlockedChatUser::where('by_user_id', $row->added_by)
                    ->where('user_id', $current_user)
                    ->exists();
            }
            $tempRow['is_blocked_by_me'] = $isBlockedByMe;
            $tempRow['is_blocked_by_user'] = $isBlockedByUser;

            $tempRow['customer_name'] = $customer->name;
            $tempRow['customer_id'] = $customer->id;
            $tempRow['customer_slug_id'] = $customer->slug_id;
            $tempRow['email'] = $customer->email;
            $tempRow['mobile'] = $customer->mobile;
            $tempRow['profile'] = $customer->profile;
            $tempRow['client_address'] = $customer->address;
        } else if ($row->added_by == 0) {
            $isBlockedByMe = false;
            $isBlockedByAdmin = false;

            if ($current_user) {

                $isBlockedByMe = BlockedChatUser::where('by_user_id', $current_user)
                    ->where('admin', 1)
                    ->exists();

                $isBlockedByUser = BlockedChatUser::where('by_admin', 1)
                    ->where('user_id', $current_user)
                    ->exists();
            }
            $tempRow['is_blocked_by_me'] = $isBlockedByMe;
            $tempRow['is_blocked_by_user'] = $isBlockedByAdmin;

            $adminData = User::where('type', 0)->select('id', 'name', 'profile')->first();

            $adminCompanyTel1 = system_setting('company_tel1');
            $adminEmail = system_setting('company_email');
            $tempRow['customer_name'] = "Admin";
            $tempRow['mobile'] = !empty($adminCompanyTel1) ? $adminCompanyTel1 : "";
            $tempRow['email'] = !empty($adminEmail) ? $adminEmail : "";
            $tempRow['profile'] = !empty($adminData->getRawOriginal('profile')) ? $adminData->profile : url('assets/images/faces/2.jpg');
            $tempRow['client_address'] = $row->client_address;
        }

        $tempRow['id'] = $row->id;
        $tempRow['slug_id'] = $row->slug_id;
        $tempRow['title'] = $row->title;
        // Get Arabic fields - use getRawOriginal if available, otherwise use regular accessor
        $tempRow['title_ar'] = isset($row->getAttributes()['title_ar']) ? $row->getRawOriginal('title_ar') : ($row->title_ar ?? null);
        $tempRow['price'] = $row->price;
        $tempRow['category'] = $row->category;
        $tempRow['description'] = $row->description;
        $tempRow['description_ar'] = isset($row->getAttributes()['description_ar']) ? $row->getRawOriginal('description_ar') : ($row->description_ar ?? null);
        $tempRow['area_description'] = $row->area_description;
        $tempRow['area_description_ar'] = isset($row->getAttributes()['area_description_ar']) ? $row->getRawOriginal('area_description_ar') : ($row->area_description_ar ?? null);
        $tempRow['address'] = $row->address;
        $tempRow['property_type'] = $row->propery_type;
        $tempRow['is_interest_available'] = $row->getRawOriginal('propery_type') == 0 || $row->getRawOriginal('propery_type') == 1 ? true : false;
        $tempRow['is_report_available'] = $row->getRawOriginal('propery_type') == 0 || $row->getRawOriginal('propery_type') == 1 ? true : false;
        $tempRow['request_status'] = $row->request_status;
        $tempRow['title_image'] = $row->title_image;
        // title_image_hash might not exist in the database, handle safely
        $tempRow['title_image_hash'] = isset($row->title_image_hash) && $row->title_image_hash != '' ? $row->title_image_hash : '';
        $tempRow['three_d_image'] = $row->three_d_image;
        $tempRow['post_created'] = $row->created_at ? $row->created_at->diffForHumans() : '';
        $tempRow['gallery'] = $row->gallery;
        
        // Add agreement documents (using accessors which return full URLs)
        $tempRow['national_id_passport'] = $row->national_id_passport;
        $tempRow['alternative_id'] = $row->alternative_id;
        $tempRow['ownership_contract'] = $row->ownership_contract;
        $tempRow['utilities_bills'] = $row->utilities_bills;
        $tempRow['power_of_attorney'] = $row->power_of_attorney;
        // Also include policy_data for backward compatibility
        $tempRow['policy_data'] = $row->policy_data;
        
        // Ensure documents are properly loaded and formatted
        // Use the relationship if available, otherwise use the accessor
        if ($row->relationLoaded('propertiesDocuments')) {
            // Use the relationship data directly and format it
            $tempRow['documents'] = $row->propertiesDocuments->map(function ($document) {
                return [
                    'id' => $document->id,
                    'property_id' => $document->property_id,
                    'file_name' => $document->getRawOriginal('name'),
                    'file' => $document->name, // This uses the accessor which includes full URL
                    'type' => $document->type
                ];
            });
        } else {
            // Fallback to accessor if relationship not loaded
            $tempRow['documents'] = $row->documents;
        }
        
        $tempRow['total_view'] = $row->total_click;
        $tempRow['status'] = $row->status;
        $tempRow['state'] = $row->state;
        $tempRow['city'] = $row->city;
        $tempRow['country'] = $row->country;
        $tempRow['latitude'] = $row->latitude;
        $tempRow['longitude'] = $row->longitude;
        $tempRow['added_by'] = $row->added_by;
        $tempRow['video_link'] = $row->video_link;
        $tempRow['rentduration'] = ($row->rentduration != '') ? $row->rentduration : "Monthly";
        $tempRow['meta_title'] = !empty($row->meta_title) ? $row->meta_title : $row->title;
        $tempRow['meta_description'] = !empty($row->meta_description) ? $row->meta_description : $row->description;
        $tempRow['meta_keywords'] = $row->meta_keywords;
        $tempRow['meta_image'] = !empty($row->meta_image) ? $row->meta_image : $row->title_image;
        $tempRow['is_premium'] = $row->is_premium == 1 ? true : false;
        $tempRow['assign_facilities'] = $row->assign_facilities;
        // is_user_verified comes from customer relationship, not property table
        $tempRow['is_verified'] = isset($row->is_user_verified) ? $row->is_user_verified : (isset($row->customer) && isset($row->customer->is_user_verified) ? $row->customer->is_user_verified : false);
        $tempRow['availability_type'] = $row->availability_type;

        // Ensure available_dates has proper structure with type field
        $availableDates = $row->available_dates ?? [];
        if (is_array($availableDates)) {
            foreach ($availableDates as $key => $dateInfo) {
                if (is_array($dateInfo)) {
                    // Ensure each date entry has the required fields
                    if (!isset($dateInfo['price'])) {
                        $availableDates[$key]['price'] = 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        // Set default type based on availability_type
                        if ($row->availability_type === 'busy_days') {
                            $availableDates[$key]['type'] = 'dead';
                        } else {
                            $availableDates[$key]['type'] = 'open';
                        }
                    }
                    // Ensure type is one of the allowed values
                    $allowedTypes = ['dead', 'open', 'reserved'];
                    if (!in_array($availableDates[$key]['type'], $allowedTypes)) {
                        if ($row->availability_type === 'busy_days') {
                            $availableDates[$key]['type'] = 'dead';
                        } else {
                            $availableDates[$key]['type'] = 'open';
                        }
                    }
                    // If type is reserved, ensure reservation_id exists
                    if ($availableDates[$key]['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
                        $availableDates[$key]['reservation_id'] = null;
                    }
                } else {
                    // If the date entry is not an array, convert it to one with defaults
                    $defaultType = ($row->availability_type === 'busy_days') ? 'dead' : 'open';
                    $availableDates[$key] = [
                        'price' => 0,
                        'type' => $defaultType
                    ];
                }
            }
        }
        $tempRow['available_dates'] = $availableDates;

        $tempRow['corresponding_day'] = $row->corresponding_day;
        $tempRow['property_classification'] = $row->getRawOriginal('property_classification');
        $tempRow['rent_package'] = $row->rent_package;
        $tempRow['area_description'] = $row->area_description;
        // Get employee fields - use getRawOriginal if available, otherwise use regular accessor
        $tempRow['company_employee_username'] = isset($row->getAttributes()['company_employee_username']) ? $row->getRawOriginal('company_employee_username') : ($row->company_employee_username ?? null);
        $tempRow['company_employee_phone_number'] = isset($row->getAttributes()['company_employee_phone_number']) ? $row->getRawOriginal('company_employee_phone_number') : ($row->company_employee_phone_number ?? null);
        $tempRow['company_employee_email'] = isset($row->getAttributes()['company_employee_email']) ? $row->getRawOriginal('company_employee_email') : ($row->company_employee_email ?? null);
        $tempRow['company_employee_whatsappnumber'] = isset($row->getAttributes()['company_employee_whatsappnumber']) ? $row->getRawOriginal('company_employee_whatsappnumber') : ($row->company_employee_whatsappnumber ?? null);
        $tempRow['instant_booking'] = $row->instant_booking ? true : false;
        $tempRow['non_refundable'] = $row->non_refundable ? true : false;
        // Add revenue and reservation fields
        $tempRow['revenue_user_name'] = $row->revenue_user_name;
        $tempRow['revenue_phone_number'] = $row->revenue_phone_number;
        $tempRow['revenue_email'] = $row->revenue_email;
        $tempRow['reservation_user_name'] = $row->reservation_user_name;
        $tempRow['reservation_phone_number'] = $row->reservation_phone_number;
        $tempRow['reservation_email'] = $row->reservation_email;

        // Add certificates for all properties
        $tempRow['certificates'] = $row->certificates;

        // Add hotel-specific fields
        // if ($row->getRawOriginal('property_classification') == 5) {
            // Hotel name field removed
            $tempRow['refund_policy'] = $row->refund_policy;
            $tempRow['hotel_rooms'] = $row->hotel_rooms;
            $tempRow['hotel_apartment_type'] = $row->hotel_apartment_type;
            $tempRow['addons_packages'] = $row->addons_packages;
            $tempRow['check_in'] = $row->check_in;
            $tempRow['check_out'] = $row->check_out;
            $tempRow['agent_addons'] = $row->agent_addons;
            $tempRow['available_rooms'] = $row->available_rooms;
            $tempRow['hotel_vat'] = $row->hotel_vat;
            
            // Add vacation apartments for vacation home properties (classification 4)
            $tempRow['vacation_apartments'] = $row->vacationApartments;
        // }

        // Get Property Inquiry Data on the basis of current user and status is completed
        $inquiry = PropertysInquiry::where('customers_id', $current_user)->where('propertys_id', $row->id)->where('status', 2)->first();
        if ($inquiry) {
            $tempRow['inquiry'] = true;
        } else {
            $tempRow['inquiry'] = false;
        }
        $tempRow['promoted'] = $row->is_promoted;

        $interested_users = array();
        $favourite_users = array();
        foreach ($row->favourite as $favourite_user) {
            if ($favourite_user->property_id == $row->id) {
                array_push($favourite_users, $favourite_user->user_id);
            }
        }
        foreach ($row->interested_users as $interested_user) {
            if ($interested_user->property_id == $row->id) {
                array_push($interested_users, $interested_user->customer_id);
            }
        }

        $tempRow['favourite_users'] = $favourite_users;
        $tempRow['total_favourite_users'] = count($favourite_users);
        $tempRow['interested_users'] = $interested_users;
        $tempRow['total_interested_users'] = count($interested_users);

        $favourite = Favourite::where('property_id', $row->id)->where('user_id', $current_user)->get();
        $interest = InterestedUser::where('property_id', $row->id)->where('customer_id', $current_user)->get();
        $report_count = user_reports::where('property_id', $row->id)->where('customer_id', $current_user)->get();

        if (count($report_count) != 0) {
            $tempRow['is_reported'] = true;
        } else {
            $tempRow['is_reported'] = false;
        }

        if (count($favourite) != 0) {
            $tempRow['is_favourite'] = 1;
        } else {
            $tempRow['is_favourite'] = 0;
        }

        if (count($interest) != 0) {
            $tempRow['is_interested'] = 1;
        } else {
            $tempRow['is_interested'] = 0;
        }

        if ($row->advertisement) {
            $tempRow['advertisement'] = $row->advertisement;
        }

        // Unset relationship to force accessor usage for correct ordering based on category
        $row->unsetRelation('parameters');
        $tempRow['parameters'] = $row->parameters; // This will now use the accessor with category ordering

        $rows[] = $tempRow;
        $count++;
    }
    return $rows;
}
function get_language()
{
    return Language::get();
}
function get_unregistered_fcm_ids($registeredIDs = array())
{

    // Convert the arrays to lowercase for case-insensitive comparison
    $registeredIDsLower = array_map('strtolower', $registeredIDs);



    // Retrieve the FCM IDs from the 'usertoken' table
    $fcmIDs = Usertokens::pluck('fcm_id')->toArray();

    // Now you have an array ($fcmIDs) containing all the FCM IDs from the 'usertoken' table

    $allIDsLower = array_map('strtolower', $fcmIDs);


    // Use array_diff to find the FCM IDs that are not registered
    $unregisteredIDsLower = array_diff($allIDsLower, $registeredIDsLower);


    // Convert the IDs back to their original case
    $unregisteredIDs = array_map('strtoupper', $unregisteredIDsLower);
    Usertokens::WhereIn('fcm_id', $fcmIDs)->delete();
}
function handleFileUpload($request, $key, $destinationPath, $filename, $databaseData = null)
{
    Log::info('handleFileUpload: starting', [
        'key' => $key,
        'hasFile' => $request->hasFile($key),
        'allFiles' => array_keys($request->allFiles()),
    ]);

    // Check if file exists in request (handle both real and mock requests)
    $uploadedFile = $request->file($key);
    if (!$uploadedFile && $request->hasFile($key)) {
        $uploadedFile = $request->file($key);
    }

    if ($uploadedFile) {
        // Prefer env at runtime, fallback to config
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

        // Build a relative directory key compatible with both local and S3
        $publicRoot = str_replace('\\', '/', public_path());
        $normalizedDestination = str_replace('\\', '/', $destinationPath);

        // Prefer mapping paths under public/images to an "images" prefix in storage
        $publicImagesRoot = rtrim($publicRoot, '/') . '/images';
        if (strpos($normalizedDestination, $publicImagesRoot) === 0) {
            $suffix = substr($normalizedDestination, strlen($publicImagesRoot));
            $directory = 'images' . $suffix;
        } elseif (strpos($normalizedDestination, $publicRoot) === 0) {
            $suffix = substr($normalizedDestination, strlen($publicRoot));
            $directory = ltrim($suffix, '/');
        } else {
            // Fallback
            $directory = 'uploads';
        }
        $directory = trim(str_replace('\\', '/', $directory), '/');



        if (!empty($databaseData)) {
            // Delete old file on the configured disk (best-effort)
            if ($disk === 's3') {
                try {
                    $deleted = Storage::disk('s3')->delete($directory . '/' . $databaseData);
                    Log::info('handleFileUpload: S3 delete previous', [
                        'key' => $directory . '/' . $databaseData,
                        'deleted' => $deleted,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('handleFileUpload: S3 delete previous failed', [
                        'key' => $directory . '/' . $databaseData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            // Also attempt local cleanup for compatibility
            $oldFilePath = rtrim($destinationPath, '\\/') . '/' . $databaseData;
            if (file_exists($oldFilePath)) {
                $unlinked = @unlink($oldFilePath);
                Log::info('handleFileUpload: local delete previous', [
                    'path' => $oldFilePath,
                    'deleted' => $unlinked,
                ]);
            }
        }

        $extension = $uploadedFile->getClientOriginalExtension();
        $finalFilename = empty($filename) ? (microtime(true) . '.' . $extension) : $filename;

        if ($disk === 's3') {
            try {
                // Use direct AWS SDK since Laravel Storage facade is failing
                $s3Client = new \Aws\S3\S3Client([
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'region' => env('AWS_DEFAULT_REGION'),
                    'version' => 'latest',
                    'http' => ['verify' => false],
                ]);

                $s3Key = trim($directory, '/') . '/' . $finalFilename;
                $fileContent = $uploadedFile->get();

                $result = $s3Client->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $s3Key,
                    'Body' => $fileContent,
                    'ContentType' => $uploadedFile->getClientMimeType(),
                ]);
                $s3Key = trim($directory, '/') . '/' . $finalFilename;
                $exists = null;
                try {
                    $exists = Storage::disk('s3')->exists($s3Key);
                } catch (\Throwable $e) {
                    Log::warning('handleFileUpload: S3 exists check failed', [
                        'key' => $s3Key,
                        'error' => $e->getMessage(),
                    ]);
                }
                $sampleList = null;
                try {
                    $sampleList = array_slice(Storage::disk('s3')->files(trim($directory, '/')), 0, 5);
                } catch (\Throwable $e) {
                    Log::warning('handleFileUpload: S3 list files failed', [
                        'prefix' => trim($directory, '/'),
                        'error' => $e->getMessage(),
                    ]);
                }
                Log::info('handleFileUpload: S3 upload complete', [
                    'key' => $s3Key,
                    'exists' => $exists,
                    'sampleList' => $sampleList,
                ]);
            } catch (\Throwable $e) {
                Log::error('S3 upload failed in handleFileUpload', [
                    'directory' => $directory,
                    'filename' => $finalFilename,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            throw new Exception('S3 disk is required for file uploads');
        }

        return $finalFilename;
    }

    Log::warning('handleFileUpload: no file found', [
        'key' => $key,
        'hasFile' => $request->hasFile($key),
    ]);

    return null;
}
function get_url_contents($url)
{
    $crl = curl_init();

    curl_setopt($crl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
    curl_setopt($crl, CURLOPT_URL, $url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 5);

    $ret = curl_exec($crl);
    curl_close($crl);
    return $ret;
}

function check_subscription($user, $type, $status)
{
    DB::enableQueryLog();
    $current_package = OldUserPurchasedPackage::where('modal_id', $user)
        // ->with(['package' => function ($q) use ($type) {
        //     $q->select('id', $type)->where($type, '>', 0)->orWhere($type, null);
        // }])

        ->whereHas('package', function ($q) use ($type) {
            $q->where($type, '>', 0)->orWhere($type, null);
        })->where($status, 1)
        ->first();

    return $current_package;
}
function store_image($file, $path, $subdir = null)
{
    // Prefer env at runtime, fallback to config
    $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));
    $relativeDir = 'images/' . trim(config('global.' . $path), '/');

    // Add subdirectory if provided
    if ($subdir) {
        $relativeDir = $relativeDir . '/' . trim($subdir, '/');
    }

    try {
        Log::info('store_image: starting', [
            'disk' => $disk,
            'bucket' => config('filesystems.disks.s3.bucket'),
            'region' => config('filesystems.disks.s3.region'),
            'endpoint' => config('filesystems.disks.s3.endpoint'),
            'relativeDir' => $relativeDir,
            'pathKey' => $path,
            'isUploadedFile' => $file instanceof \Illuminate\Http\UploadedFile,
            'originalName' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getClientOriginalName() : null,
            'mime' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getClientMimeType() : null,
            'size' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getSize() : null,
        ]);
    } catch (\Throwable $e) {
        Log::warning('store_image: metadata logging failed', ['error' => $e->getMessage()]);
    }

    if ($file instanceof \Illuminate\Http\UploadedFile) {
        // Get extension from original filename
        $extension = $file->getClientOriginalExtension();
        
        // If extension is empty, try to get it from MIME type
        if (empty($extension)) {
            $mimeType = $file->getClientMimeType();
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'text/plain' => 'txt',
                'application/rtf' => 'rtf',
                'application/zip' => 'zip',
                'application/x-rar-compressed' => 'rar',
                'application/x-zip-compressed' => 'zip',
            ];
            $extension = $extensionMap[$mimeType] ?? 'bin';
        }
        
        // Ensure extension doesn't have leading or trailing dots
        $extension = trim($extension, '.');
        
        // If extension is still empty after all attempts, default to 'bin'
        if (empty($extension)) {
            $extension = 'bin';
        }
        
        // Generate filename with extension (ensure no trailing dots)
        $filename = rtrim(microtime(true), '.') . '.' . $extension;

        if ($disk === 's3') {
            try {
                // Use direct AWS SDK since Laravel Storage facade is failing
                $s3Client = new \Aws\S3\S3Client([
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'region' => env('AWS_DEFAULT_REGION'),
                    'version' => 'latest',
                    'http' => ['verify' => false],
                ]);

                $s3Key = trim($relativeDir, '/') . '/' . $filename;
                $fileContent = $file->get();

                Log::info('store_image: file content details', [
                    'contentLength' => strlen($fileContent),
                    'mimeType' => $file->getClientMimeType(),
                    's3Key' => $s3Key,
                ]);

                $result = $s3Client->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $s3Key,
                    'Body' => $fileContent,
                    'ContentType' => $file->getClientMimeType(),
                ]);

                Log::info('store_image: S3 put result', [
                    'success' => true,
                    's3Key' => $s3Key,
                    'objectUrl' => $result['ObjectURL'],
                ]);
                $s3Key = trim($relativeDir, '/') . '/' . $filename;
                $exists = null;
                try {
                    $exists = Storage::disk('s3')->exists($s3Key);
                } catch (\Throwable $e) {
                    Log::warning('store_image: S3 exists check failed', [
                        'key' => $s3Key,
                        'error' => $e->getMessage(),
                    ]);
                }
                $sampleList = null;
                try {
                    $sampleList = array_slice(Storage::disk('s3')->files(trim($relativeDir, '/')), 0, 5);
                } catch (\Throwable $e) {
                    Log::warning('store_image: S3 list files failed', [
                        'prefix' => trim($relativeDir, '/'),
                        'error' => $e->getMessage(),
                    ]);
                }
                Log::info('store_image: S3 upload complete', [
                    'key' => $s3Key,
                    'exists' => $exists,
                    'sampleList' => $sampleList,
                ]);
            } catch (\Throwable $e) {
                Log::error('S3 upload failed in store_image', [
                    'relativeDir' => $relativeDir,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            throw new Exception('S3 disk is required for file uploads');
        }

        return $filename;
    }

    return null;
}
function unlink_image($url)
{
    if (empty($url)) {
        return;
    }

    $relativePath = parse_url($url, PHP_URL_PATH);
    if (!$relativePath) {
        return;
    }

    $disk = config('filesystems.default', env('FILESYSTEM_DISK', 'local'));

    // Attempt S3 deletion when enabled
    if ($disk === 's3') {
        $path = ltrim(str_replace('\\', '/', $relativePath), '/');
        // Ensure the key does not start with public/ when targeting S3
        if (strpos($path, 'images/') !== false) {
            // Already relative to images root
            $key = $path;
        } else {
            // Fallback: strip leading public/
            $key = preg_replace('#^public/#', '', $path);
        }
        try {
            $deleted = Storage::disk('s3')->delete($key);
            Log::info('unlink_image: S3 delete', [
                'key' => $key,
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            // Best-effort, ignore failures
            Log::warning('unlink_image: S3 delete failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Local cleanup for backward compatibility
    $fullLocalPath = public_path() . $relativePath;
    if (file_exists($fullLocalPath)) {
        $unlinked = @unlink($fullLocalPath);
        Log::info('unlink_image: local delete', [
            'path' => $fullLocalPath,
            'deleted' => $unlinked,
        ]);
    }
}

/** Generate Slugs Functions */
if (!function_exists('generateUniqueSlug')) {
    function generateUniqueSlug($title, $type, $originalSlug = null, $exceptId = null)
    {
        if (!$originalSlug) {
            $originalSlug = Str::slug($title);
        } else {
            $originalSlug = Str::slug($originalSlug);
        }

        if (empty($originalSlug)) {
            $originalSlug = "slug";
        }

        $tableNames = [
            1 => 'propertys',
            2 => 'articles',
            3 => 'categories',
            4 => 'projects',
            5 => 'customers',
            6 => 'users'
        ];

        $tableName = $tableNames[$type] ?? null;

        return generateSlug($originalSlug, $tableName, $exceptId);
    }
}

if (!function_exists('generateSlug')) {
    function generateSlug($originalSlug, $tableName, $exceptId)
    {
        $counter = 1;
        $slug = $originalSlug;

        if (empty($exceptId)) {
            while (DB::table($tableName)->where('slug_id', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        } else {
            while (DB::table($tableName)->whereNot('id', $exceptId)->where('slug_id', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        }
        return $slug;
    }
}
/** END OF Generate Slugs Functions */
if (!function_exists('getAccessToken')) {
    function getAccessToken()
    {
        $file_name = system_setting('firebase_service_json_file');

        $file_path = public_path() . '/assets/' . $file_name;

        $client = new Client();
        $client->setAuthConfig($file_path);
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];


        return $accessToken;
    }
}
if (!function_exists('updateEnv')) {
    function updateEnv($envUpdates)
    {
        $envPath = base_path('.env');
        
        // Check if .env file exists
        if (!file_exists($envPath)) {
            file_put_contents($envPath, '');
        }
        
        $envFile = file_get_contents($envPath);
        if ($envFile === false) {
            $envFile = '';
        }

        foreach ($envUpdates as $key => $value) {
            // Escape special characters in the key for regex
            $escapedKey = preg_quote($key, '/');
            
            // Escape quotes and backslashes in the value
            $escapedValue = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
            
            // Pattern to match the key at the start of a line (with optional spaces before)
            // Using m flag for multiline and ^ anchor for line start
            $pattern = "/^[ \t]*{$escapedKey}[ \t]*=.*/m";
            
            // Check if the key exists in the .env file
            if (preg_match($pattern, $envFile)) {
                // If the key exists, replace its value
                $replacement = "{$key}=\"{$escapedValue}\"";
                $envFile = preg_replace($pattern, $replacement, $envFile);
            } else {
                // If the key doesn't exist, add it at the end
                // Remove trailing newlines before adding
                $envFile = rtrim($envFile);
                if (!empty($envFile) && substr($envFile, -1) !== "\n") {
                    $envFile .= "\n";
                }
                $envFile .= "{$key}=\"{$escapedValue}\"\n";
            }
        }

        // Save the updated .env file
        file_put_contents($envPath, $envFile);
    }
}
