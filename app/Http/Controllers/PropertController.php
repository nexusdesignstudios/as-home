<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Customer;


use App\Models\Property;
use App\Models\PropertyEditRequest;
use App\Models\CityImage;
use App\Models\parameter;
use App\Models\Usertokens;
use Illuminate\Support\Str;
use App\Models\RejectReason;
use Illuminate\Http\Request;

use App\Models\Notifications;
use App\Models\PropertyImages;
use App\Services\HelperService;
use App\Models\AssignParameters;
use App\Models\OutdoorFacilities;
use App\Services\ResponseService;
use App\Models\PropertiesDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Services\BootstrapTableService;
use App\Models\AssignedOutdoorFacilities;
use Illuminate\Support\Facades\Validator;
use App\Models\HotelRoom;


class PropertController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        } else {
            $customerID = $_GET['customer'] ?? null;
            $category = Category::all();
            return view('property.index', compact('category', 'customerID'));
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!has_permissions('create', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        } else {
            // Get all categories with their classification
            $category = Category::where('status', '1')
                ->select('id', 'category', 'parameter_types', 'property_classification')
                ->get();

            $parameters = parameter::all();
            $currency_symbol = Setting::where('type', 'currency_symbol')->pluck('data')->first();
            $facility = OutdoorFacilities::all();
            $distanceValueDB = system_setting('distance_option');
            $distanceValue = isset($distanceValueDB) && !empty($distanceValueDB) ? $distanceValueDB : 'km';
            return view('property.create', compact('category', 'parameters', 'currency_symbol', 'facility', 'distanceValue'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arr = [];
        if (!has_permissions('create', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        } else {
            $request->validate([
                'title'             => 'required',
                'title_ar'          => 'nullable|string',
                'description'       => 'required',
                'description_ar'    => 'nullable|string',
                'area_description'  => 'nullable|string',
                'area_description_ar' => 'nullable|string',
                'company_employee_username' => 'nullable|string',
                'company_employee_email' => 'nullable|email',
                'company_employee_phone_number' => 'nullable|string',
                'category'          => 'required',
                'property_type'     => 'required',
                'property_classification' => 'nullable|integer|between:1,5',
                'address'           => 'required',
                'title_image'       => 'required|file|max:3000|mimes:jpeg,png,jpg',
                '3d_image'          => 'nullable|mimes:jpg,jpeg,png,gif|max:3000',
                'documents.*'       => 'nullable|mimes:pdf,doc,docx,txt|max:5120',
                'policy_data'       => 'required_unless:property_classification,5|mimes:pdf,doc,docx,txt|max:5120',
                'price'             => 'required_unless:property_classification,5|numeric|min:1|max:9223372036854775807',
                'weekend_commission' => 'nullable|numeric|min:0|max:100|required_unless:property_classification,5',
                'refund_policy'     => 'nullable|in:flexible,non-refundable',
                'availability_type' => 'nullable|integer|in:1,2|required_if:property_classification,4',
                'available_dates'   => 'nullable|json|required_if:property_classification,4',
                'corresponding_day' => 'nullable|json',
                'check_in'          => 'nullable|string',
                'check_out'         => 'nullable|string',
                'available_rooms'   => 'nullable|integer|min:0',
                'agent_addons'      => 'nullable|json',
                'hotel_apartment_type_id' => 'nullable|exists:hotel_apartment_types,id',
                'rent_package' => 'nullable|in:basic,premium',
                'revenue_user_name' => 'nullable|string',
                'revenue_phone_number' => 'nullable|string',
                'revenue_email' => 'nullable|email',
                'reservation_user_name' => 'nullable|string',
                'reservation_phone_number' => 'nullable|string',
                'reservation_email' => 'nullable|email',
                'hotel_rooms'       => 'nullable|array',
                'hotel_rooms.*.room_type_id' => 'required_with:hotel_rooms',
                'hotel_rooms.*.room_number' => 'required_with:hotel_rooms',
                'hotel_rooms.*.price_per_night' => 'required_with:hotel_rooms|numeric|min:0',
                'hotel_rooms.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'hotel_rooms.*.refund_policy' => 'nullable|in:flexible,non-refundable',
                'hotel_rooms.*.availability_type' => 'nullable|integer|in:1,2',
                'hotel_rooms.*.available_dates' => 'nullable|json',
                'hotel_rooms.*.weekend_commission' => 'nullable|numeric|min:0|max:100',
                'hotel_rooms.*.description' => 'nullable|string',
                'addons_packages'       => 'nullable|array',
                'addons_packages.*.name' => 'required_with:addons_packages',
                'addons_packages.*.description' => 'nullable|string',
                'addons_packages.*.room_type_id' => 'nullable|exists:hotel_room_types,id',
                'addons_packages.*.status' => 'nullable|in:active,inactive',
                'addons_packages.*.price' => 'nullable|numeric|min:0',
                'addons_packages.*.addon_values' => 'required_with:addons_packages|array',
                'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'required|exists:hotel_addon_fields,id',
                'addons_packages.*.addon_values.*.value' => 'required',
                'addons_packages.*.addon_values.*.static_price' => 'nullable|numeric|min:0',
                'addons_packages.*.addon_values.*.multiply_price' => 'nullable|numeric|min:0',
                'certificates'      => 'nullable|array',
                'certificates.*.title' => 'required_with:certificates',
                'certificates.*.description' => 'nullable|string',
                'certificates.*.file' => 'required_with:certificates|file|max:5120|mimes:jpeg,png,jpg,pdf,doc,docx',
                'identity_proof'    => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'national_id_passport' => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'alternative_id' => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'utilities_bills'   => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'power_of_attorney' => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'ownership_contract' => 'nullable|file|max:10240', // Accept all file types, max 10MB
                'fact_sheet' => 'nullable|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:5120',
                'video_link' => ['nullable', 'url', function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        // Regular expression to validate YouTube URLs
                        $youtubePattern = '/^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/';

                        if (!preg_match($youtubePattern, $value)) {
                            return $fail("The Video Link must be a valid YouTube URL.");
                        }
                    }
                }],
            ], [], [
                'documents.*' => 'document :position',
                'addons_packages.*.name' => 'package name :position',
                'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'package addon field :position',
                'addons_packages.*.addon_values.*.value' => 'package addon value :position',
                'certificates.*.file' => 'certificate file :position',
            ]);

            try {
                DB::beginTransaction();

                $saveProperty = new Property();
                $saveProperty->category_id = $request->category;
                $saveProperty->title = $request->title;
                $saveProperty->title_ar = $request->title_ar ?? null;
                $saveProperty->slug_id = $request->slug ?? generateUniqueSlug($request->title, 1);
                $saveProperty->description = $request->description;
                $saveProperty->description_ar = $request->description_ar ?? null;
                $saveProperty->area_description = $request->area_description ?? null;
                $saveProperty->area_description_ar = $request->area_description_ar ?? null;
                $saveProperty->company_employee_username = $request->company_employee_username ?? null;
                $saveProperty->company_employee_email = $request->company_employee_email ?? null;
                $saveProperty->company_employee_phone_number = $request->company_employee_phone_number ?? null;
                $saveProperty->address = $request->address;
                $saveProperty->client_address = $request->client_address;
                $saveProperty->propery_type = $request->property_type;
                $saveProperty->property_classification = $request->property_classification;
                $saveProperty->price = $request->price;
                $saveProperty->request_status = "approved";
                $saveProperty->package_id = 0;
                $saveProperty->city = (isset($request->city)) ? $request->city : '';
                $saveProperty->country = (isset($request->country)) ? $request->country : '';
                $saveProperty->state = (isset($request->state)) ? $request->state : '';
                $saveProperty->latitude = (isset($request->latitude)) ? $request->latitude : '';
                $saveProperty->longitude = (isset($request->longitude)) ? $request->longitude : '';
                $saveProperty->video_link = (isset($request->video_link)) ? $request->video_link : '';
                $saveProperty->post_type = 0;
                $saveProperty->added_by = 0;
                $saveProperty->meta_title = isset($request->meta_title) ? $request->meta_title : $request->title;
                $saveProperty->meta_description = $request->meta_description;
                $saveProperty->meta_keywords = $request->keywords;
                $saveProperty->rentduration = $request->price_duration;
                $saveProperty->is_premium = $request->is_premium;

                // Handle corresponding_day field
                if ($request->has('corresponding_day') && !empty($request->corresponding_day)) {
                    $correspondingDay = $request->corresponding_day;
                    // If it's already a JSON string, validate it
                    if (is_string($correspondingDay)) {
                        $decoded = json_decode($correspondingDay, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $saveProperty->corresponding_day = $correspondingDay;
                        }
                    } else {
                        $saveProperty->corresponding_day = $correspondingDay;
                    }
                } else {
                    $saveProperty->corresponding_day = null;
                }

                // Set vacation home specific fields if property classification is vacation_homes (4)
                if (isset($request->property_classification) && $request->property_classification == 4) {
                    $saveProperty->availability_type = $request->availability_type;
                    $saveProperty->available_dates = $request->available_dates;
                }

                // Set hotel specific fields if property classification is hotel_booking (5)
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    $saveProperty->refund_policy = $request->refund_policy ?? 'flexible';
                }

                if ($request->hasFile('title_image')) {
                    $saveProperty->title_image = store_image($request->file('title_image'), 'PROPERTY_TITLE_IMG_PATH');
                } else {
                    $saveProperty->title_image  = '';
                }

                if ($request->hasFile('3d_image')) {
                    $saveProperty->three_d_image = store_image($request->file('3d_image'), '3D_IMG_PATH');
                } else {
                    $saveProperty->three_d_image  = '';
                }

                if ($request->hasFile('meta_image')) {
                    $saveProperty->meta_image = store_image($request->file('meta_image'), 'PROPERTY_SEO_IMG_PATH');
                }

                // Identity Proof
                if ($request->hasFile('identity_proof')) {
                    $saveProperty->identity_proof = store_image($request->file('identity_proof'), 'PROPERTY_IDENTITY_PROOF_PATH');
                }

                // National ID/Passport
                if ($request->hasFile('national_id_passport')) {
                    $saveProperty->national_id_passport = store_image($request->file('national_id_passport'), 'PROPERTY_NATIONAL_ID_PATH');
                }

                // Alternative ID
                if ($request->hasFile('alternative_id')) {
                    $saveProperty->alternative_id = store_image($request->file('alternative_id'), 'PROPERTY_ALTERNATIVE_ID_PATH');
                }

                // Utilities Bills
                if ($request->hasFile('utilities_bills')) {
                    $saveProperty->utilities_bills = store_image($request->file('utilities_bills'), 'PROPERTY_UTILITIES_PATH');
                }

                // Power of Attorney
                if ($request->hasFile('power_of_attorney')) {
                    $saveProperty->power_of_attorney = store_image($request->file('power_of_attorney'), 'PROPERTY_POA_PATH');
                }

                // Ownership Contract
                if ($request->hasFile('ownership_contract')) {
                    $saveProperty->ownership_contract = store_image($request->file('ownership_contract'), 'PROPERTY_OWNERSHIP_CONTRACT_PATH');
                }

                // Fact Sheet (for hotels)
                if ($request->hasFile('fact_sheet')) {
                    $saveProperty->fact_sheet = store_image($request->file('fact_sheet'), 'PROPERTY_FACT_SHEET_PATH');
                }

                // Set hotel specific fields if property classification is hotel (5)
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    $saveProperty->refund_policy = $request->refund_policy ?? 'flexible';
                    $saveProperty->hotel_apartment_type_id = $request->hotel_apartment_type_id;
                    $saveProperty->check_in = $request->check_in;
                    $saveProperty->check_out = $request->check_out;
                    $saveProperty->available_rooms = $request->available_rooms;
                    $saveProperty->rent_package = $request->rent_package;
                    $saveProperty->revenue_user_name = $request->revenue_user_name ?? null;
                    $saveProperty->revenue_phone_number = $request->revenue_phone_number ?? null;
                    $saveProperty->revenue_email = $request->revenue_email ?? null;
                    $saveProperty->reservation_user_name = $request->reservation_user_name ?? null;
                    $saveProperty->reservation_phone_number = $request->reservation_phone_number ?? null;
                    $saveProperty->reservation_email = $request->reservation_email ?? null;
                }

                // Handle agent_addons field (available for all property types)
                if ($request->has('agent_addons') && !empty($request->agent_addons)) {
                    $agentAddons = $request->agent_addons;
                    // If it's already a JSON string, validate it
                    if (is_string($agentAddons)) {
                        $decoded = json_decode($agentAddons, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $saveProperty->agent_addons = $agentAddons;
                        }
                    } else {
                        $saveProperty->agent_addons = $agentAddons;
                    }
                } else {
                    $saveProperty->agent_addons = null;
                }

                $saveProperty->save();

                $facility = OutdoorFacilities::all();
                foreach ($facility as $key => $value) {
                    if ($request->has('facility' . $value->id) && $request->input('facility' . $value->id) != '') {
                        $facilities = new AssignedOutdoorFacilities();
                        $facilities->facility_id = $value->id;
                        $facilities->property_id = $saveProperty->id;
                        $facilities->distance = $request->input('facility' . $value->id);
                        $facilities->save();
                    }
                }
                $parameters = parameter::all();
                foreach ($parameters as $par) {
                    if ($request->has('par_' . $par->id)) {
                        $assign_parameter = new AssignParameters();
                        $assign_parameter->parameter_id = $par->id;
                        if (($request->hasFile('par_' . $par->id))) {
                            $destinationPath = public_path('images') . config('global.PARAMETER_IMG_PATH');
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            $imageName = microtime(true) . "." . ($request->file('par_' . $par->id))->getClientOriginalExtension();
                            ($request->file('par_' . $par->id))->move($destinationPath, $imageName);
                            $assign_parameter->value = $imageName;
                        } else {
                            $assign_parameter->value = is_array($request->input('par_' . $par->id)) ? json_encode($request->input('par_' . $par->id), JSON_FORCE_OBJECT) : ($request->input('par_' . $par->id));
                        }
                        $assign_parameter->modal()->associate($saveProperty);
                        $assign_parameter->save();
                        $arr = $arr + [$par->id => $request->input('par_' . $par->id)];
                    }
                }

                /// START :: UPLOAD GALLERY IMAGE
                $destinationPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $saveProperty->id;
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                if ($request->hasfile('gallery_images')) {
                    foreach ($request->file('gallery_images') as $file) {
                        $name = microtime(true) . '.' . $file->extension();
                        $file->move($destinationPath, $name);
                        PropertyImages::create([
                            'image' => $name,
                            'propertys_id' => $saveProperty->id
                        ]);
                    }
                }
                /// END :: UPLOAD GALLERY IMAGE


                /// START :: UPLOAD DOCUMENT
                $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . "/" . $saveProperty->id;
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                if ($request->hasFile('documents')) {
                    $documentsData = array();
                    foreach ($request->file('documents') as $file) {
                        $type = $file->extension();
                        $name = microtime(true) . '.' . $type;
                        $file->move($destinationPath, $name);

                        $documentsData[] = array(
                            'property_id'   => $saveProperty->id,
                            'name'          => $name,
                            'type'          => $type
                        );
                    }

                    if (collect($documentsData)->isNotEmpty()) {
                        PropertiesDocument::insert($documentsData);
                    }
                }
                /// END :: UPLOAD DOCUMENT

                // START :: ADD CITY DATA
                if (isset($request->city) && !empty($request->city)) {
                    CityImage::updateOrCreate(array('city' => $request->city));
                }
                // END :: ADD CITY DATA

                // START :: ADD HOTEL ROOMS
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->hotel_rooms) && !empty($request->hotel_rooms)) {
                    try {
                        foreach ($request->hotel_rooms as $room) {
                            HotelRoom::create([
                                'property_id' => $saveProperty->id,
                                'room_type_id' => $room['room_type_id'],
                                'room_number' => $room['room_number'],
                                'price_per_night' => (float)$room['price_per_night'],
                                'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                'availability_type' => isset($room['availability_type']) ? (int)$room['availability_type'] : null,
                                'available_dates' => isset($room['available_dates']) ? $room['available_dates'] : null,
                                'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : null,
                                'description' => $room['description'] ?? null,
                                'status' => $room['status'] ?? 1
                            ]);
                        }
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                // END :: ADD HOTEL ROOMS

                // START :: ADD ADDONS PACKAGES
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->addons_packages) && !empty($request->addons_packages)) {
                    try {
                        // Create destination path for hotel addon files
                        $addonFolderPath = public_path('images') . config('global.HOTEL_ADDON_PATH');
                        if (!is_dir($addonFolderPath)) {
                            mkdir($addonFolderPath, 0777, true);
                        }

                        // Process each package
                        foreach ($request->addons_packages as $packageIndex => $package) {
                            // Create the package
                            $addonsPackage = new \App\Models\AddonsPackage();
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
                                    $addonField = \App\Models\HotelAddonField::where('id', $addon['hotel_addon_field_id'])->where('status', 'active')->first();

                                    if (!$addonField) {
                                        continue; // Skip inactive or non-existent fields
                                    }

                                    $value = $addon['value'];

                                    // Handle file uploads
                                    if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                        $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                        $fileName = microtime(true) . '.' . $file->extension();
                                        $file->move($addonFolderPath, $fileName);
                                        $value = $fileName;
                                    }
                                    // Handle checkbox values (convert array to JSON)
                                    else if ($addonField->field_type == 'checkbox' && is_array($value)) {
                                        $value = json_encode($value);
                                    }
                                    // Handle radio and dropdown values (validate against available options)
                                    else if (in_array($addonField->field_type, ['radio', 'dropdown'])) {
                                        $validValue = \App\Models\HotelAddonFieldValue::where('hotel_addon_field_id', $addon['hotel_addon_field_id'])
                                            ->where('value', $value)
                                            ->exists();

                                        if (!$validValue) {
                                            continue; // Skip invalid values
                                        }
                                    }

                                    // Save the addon value with user-provided price fields
                                    \App\Models\PropertyHotelAddonValue::create([
                                        'property_id' => $saveProperty->id,
                                        'hotel_addon_field_id' => $addon['hotel_addon_field_id'],
                                        'value' => $value,
                                        'static_price' => isset($addon['static_price']) ? $addon['static_price'] : null,
                                        'multiply_price' => isset($addon['multiply_price']) ? $addon['multiply_price'] : null,
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

                        // Process each certificate
                        foreach ($request->certificates as $certificateIndex => $certificate) {
                            // Create the certificate
                            $propertyCertificate = new \App\Models\PropertyCertificate();
                            $propertyCertificate->title = $certificate['title'];
                            $propertyCertificate->description = $certificate['description'] ?? null;
                            $propertyCertificate->property_id = $saveProperty->id;

                            // Handle file uploads
                            if ($request->hasFile('certificates.' . $certificateIndex . '.file')) {
                                $file = $request->file('certificates.' . $certificateIndex . '.file');
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

                DB::commit();
                ResponseService::successRedirectResponse('Data Created Successfully');
            } catch (Exception $e) {
                DB::rollBack();
                ResponseService::logErrorRedirectResponse($e, "Create Property Issue");
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!has_permissions('update', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            $category = Category::all()->where('status', '1')->mapWithKeys(function ($item, $key) {
                return [$item['id'] => $item['category']];
            });
            $category = Category::where('status', '1')->get();
            try {
                $list = Property::with([
                    'assignParameter' => function ($q) {
                        $q->with('parameter:id,name,type_of_parameter,type_values,is_required,image')->select('id', 'modal_type', 'modal_id', 'property_id', 'parameter_id', 'value');
                    },
                    'vacationApartments' => function ($q) {
                        // Only eager load if property is vacation home (classification 4)
                        // This prevents errors if the property classification is not 4
                    }
                ])->where('id', $id)->get()->first();
                
                // Check if property exists
                if (!$list) {
                    return redirect()->route('property.index')->with('error', 'Property not found');
                }
            } catch (\Exception $e) {
                Log::error('Error loading property for edit: ' . $e->getMessage(), [
                    'property_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route('property.index')->with('error', 'Error loading property: ' . $e->getMessage());
            }

            $categoryData = Category::find($list->category_id);
            
            // Check if category exists
            if (!$categoryData) {
                Log::error('Category not found for property', [
                    'property_id' => $id,
                    'category_id' => $list->category_id
                ]);
                return redirect()->route('property.index')->with('error', 'Category not found for this property');
            }

            // Check if parameter_types exists and is not empty
            $categoryParameterTypeIds = [];
            if (!empty($categoryData['parameter_types'])) {
                $categoryParameterTypeIds = explode(',', $categoryData['parameter_types']);
                // Filter out empty values
                $categoryParameterTypeIds = array_filter($categoryParameterTypeIds, function($id) {
                    return !empty(trim($id));
                });
            }

            $parameters = parameter::all();
            $edit_parameters = parameter::with(['assigned_parameter' => function ($q) use ($id) {
                $q->where('modal_id', $id)->select('id', 'modal_type', 'modal_id', 'property_id', 'parameter_id', 'value');
            }])->whereIn('id', $categoryParameterTypeIds)->get();

            // Sort the collection by the order of IDs in $categoryParameterTypeIds.
            $edit_parameters = $edit_parameters->sortBy(function ($parameter) use ($categoryParameterTypeIds) {
                return array_search($parameter->id, $categoryParameterTypeIds);
            });

            // Reset the keys on the sorted collection.
            $edit_parameters = $edit_parameters->values();




            $facility = OutdoorFacilities::with(['assign_facilities' => function ($q) use ($id) {
                $q->where('property_id', $id)->select('id', 'property_id', 'facility_id', 'distance');
            }])->get();

            $assignFacility = AssignedOutdoorFacilities::where('property_id', $id)->get();

            $arr = json_decode($list->carpet_area);
            $par_arr = [];
            $par_id = [];
            $type_arr = [];
            foreach ($list->assignParameter as  $par) {
                $par_arr = $par_arr + [$par->parameter->name => $par->value];
                $par_id = $par_id + [$par->parameter->name => $par->value];
            }
            $currency_symbol = Setting::where('type', 'currency_symbol')->pluck('data')->first();
            $distanceValueDB = system_setting('distance_option');
            $distanceValue = isset($distanceValueDB) && !empty($distanceValueDB) ? $distanceValueDB : 'km';
            return view('property.edit', compact('category', 'facility', 'assignFacility', 'edit_parameters', 'list', 'id', 'par_arr', 'parameters', 'par_id', 'currency_symbol', 'distanceValue'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!has_permissions('update', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        } else {
            try {

                DB::beginTransaction();
                $UpdateProperty = Property::with('assignparameter.parameter')->find($id);
                
                // Store original property data BEFORE any modifications
                $originalPropertyData = $UpdateProperty->getAttributes();
                unset($originalPropertyData['created_at'], $originalPropertyData['updated_at'], $originalPropertyData['id']);
                
                // Check if this is an owner edit (not admin)
                // 0 means admin, non-zero means owner/customer
                $isOwnerEdit = $UpdateProperty->added_by != 0;
                
                // Check auto-approve setting for edited listings
                $autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
                
                $destinationPath = public_path('images') . config('global.PROPERTY_TITLE_IMG_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $UpdateProperty->category_id = $request->category;
                $UpdateProperty->title = $request->title;
                $UpdateProperty->title_ar = $request->title_ar ?? null;
                $UpdateProperty->slug_id = $request->slug ?? generateUniqueSlug($request->title, 1, null, $id);
                $UpdateProperty->description = $request->description;
                $UpdateProperty->description_ar = $request->description_ar ?? null;
                $UpdateProperty->area_description = $request->area_description ?? null;
                $UpdateProperty->area_description_ar = $request->area_description_ar ?? null;
                $UpdateProperty->company_employee_username = $request->company_employee_username ?? null;
                $UpdateProperty->company_employee_email = $request->company_employee_email ?? null;
                $UpdateProperty->company_employee_phone_number = $request->company_employee_phone_number ?? null;
                $UpdateProperty->address = $request->address;
                $UpdateProperty->client_address = $request->client_address;
                $UpdateProperty->setAttribute('propery_type', $request->property_type);
                $UpdateProperty->price = $request->price;
                $UpdateProperty->setAttribute('propery_type', $request->property_type);
                $UpdateProperty->property_classification = $request->property_classification;
                $UpdateProperty->price = $request->price;
                $UpdateProperty->state = (isset($request->state)) ? $request->state : '';
                $UpdateProperty->country = (isset($request->country)) ? $request->country : '';
                $UpdateProperty->city = (isset($request->city)) ? $request->city : '';
                $UpdateProperty->latitude = (isset($request->latitude)) ? $request->latitude : '';
                $UpdateProperty->longitude = (isset($request->longitude)) ? $request->longitude : '';
                $UpdateProperty->video_link = (isset($request->video_link)) ? $request->video_link : '';
                $UpdateProperty->is_premium = $request->is_premium;
                $UpdateProperty->meta_title = (isset($request->edit_meta_title)) ? $request->edit_meta_title : '';
                $UpdateProperty->meta_description = (isset($request->edit_meta_description)) ? $request->edit_meta_description : '';
                $UpdateProperty->meta_keywords = (isset($request->Keywords)) ? $request->Keywords : '';

                $UpdateProperty->rentduration = $request->price_duration;

                // Handle corresponding_day field
                if ($request->has('corresponding_day') && !empty($request->corresponding_day)) {
                    $correspondingDay = $request->corresponding_day;
                    // If it's already a JSON string, validate it
                    if (is_string($correspondingDay)) {
                        $decoded = json_decode($correspondingDay, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $UpdateProperty->corresponding_day = $correspondingDay;
                        }
                    } else {
                        $UpdateProperty->corresponding_day = $correspondingDay;
                    }
                } else {
                    $UpdateProperty->corresponding_day = null;
                }

                // Set vacation home specific fields if property classification is vacation_homes (4)
                if (isset($request->property_classification) && $request->property_classification == 4) {
                    $UpdateProperty->availability_type = $request->availability_type;
                    $UpdateProperty->available_dates = $request->available_dates;
                }

                // Set hotel specific fields if property classification is hotel_booking (5)
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    $UpdateProperty->refund_policy = $request->refund_policy ?? 'flexible';
                    $UpdateProperty->hotel_apartment_type_id = $request->hotel_apartment_type_id ?? null;
                    $UpdateProperty->check_in = $request->check_in ?? null;
                    $UpdateProperty->check_out = $request->check_out ?? null;
                    $UpdateProperty->available_rooms = $request->available_rooms ?? null;
                    $UpdateProperty->rent_package = $request->rent_package ?? null;
                    $UpdateProperty->revenue_user_name = $request->revenue_user_name ?? null;
                    $UpdateProperty->revenue_phone_number = $request->revenue_phone_number ?? null;
                    $UpdateProperty->revenue_email = $request->revenue_email ?? null;
                    $UpdateProperty->reservation_user_name = $request->reservation_user_name ?? null;
                    $UpdateProperty->reservation_phone_number = $request->reservation_phone_number ?? null;
                    $UpdateProperty->reservation_email = $request->reservation_email ?? null;
                }

                // Handle agent_addons field (available for all property types)
                if ($request->has('agent_addons') && !empty($request->agent_addons)) {
                    $agentAddons = $request->agent_addons;
                    // If it's already a JSON string, validate it
                    if (is_string($agentAddons)) {
                        $decoded = json_decode($agentAddons, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $UpdateProperty->agent_addons = $agentAddons;
                        }
                    } else {
                        $UpdateProperty->agent_addons = $agentAddons;
                    }
                } else {
                    $UpdateProperty->agent_addons = null;
                }

                if ($request->hasFile('title_image')) {
                    \unlink_image($UpdateProperty->title_image);
                    $UpdateProperty->setAttribute('title_image', \store_image($request->file('title_image'), 'PROPERTY_TITLE_IMG_PATH'));
                }

                if ($request->hasFile('3d_image')) {
                    \unlink_image($UpdateProperty->three_d_image);
                    $UpdateProperty->setAttribute('three_d_image', \store_image($request->file('3d_image'), '3D_IMG_PATH'));
                }

                if ($request->hasFile('meta_image')) {
                    \unlink_image($UpdateProperty->meta_image);
                    $UpdateProperty->setAttribute('meta_image', \store_image($request->file('meta_image'), 'PROPERTY_SEO_IMG_PATH'));
                }

                // Optional identity and ownership documents (no validation)
                if ($request->hasFile('identity_proof')) {
                    \unlink_image($UpdateProperty->identity_proof);
                    $UpdateProperty->setAttribute('identity_proof', \store_image($request->file('identity_proof'), 'PROPERTY_IDENTITY_PROOF_PATH'));
                }

                if ($request->hasFile('national_id_passport')) {
                    \unlink_image($UpdateProperty->national_id_passport);
                    $UpdateProperty->setAttribute('national_id_passport', \store_image($request->file('national_id_passport'), 'PROPERTY_NATIONAL_ID_PATH'));
                }

                if ($request->hasFile('alternative_id')) {
                    \unlink_image($UpdateProperty->alternative_id);
                    $UpdateProperty->setAttribute('alternative_id', \store_image($request->file('alternative_id'), 'PROPERTY_ALTERNATIVE_ID_PATH'));
                }

                if ($request->hasFile('utilities_bills')) {
                    \unlink_image($UpdateProperty->utilities_bills);
                    $UpdateProperty->setAttribute('utilities_bills', \store_image($request->file('utilities_bills'), 'PROPERTY_UTILITIES_PATH'));
                }

                if ($request->hasFile('power_of_attorney')) {
                    \unlink_image($UpdateProperty->power_of_attorney);
                    $UpdateProperty->setAttribute('power_of_attorney', \store_image($request->file('power_of_attorney'), 'PROPERTY_POA_PATH'));
                }

                if ($request->hasFile('ownership_contract')) {
                    \unlink_image($UpdateProperty->ownership_contract);
                    $UpdateProperty->setAttribute('ownership_contract', \store_image($request->file('ownership_contract'), 'PROPERTY_OWNERSHIP_CONTRACT_PATH'));
                }

                // Fact Sheet (for hotels)
                if ($request->hasFile('fact_sheet')) {
                    \unlink_image($UpdateProperty->fact_sheet);
                    $UpdateProperty->setAttribute('fact_sheet', \store_image($request->file('fact_sheet'), 'PROPERTY_FACT_SHEET_PATH'));
                }

                // Get edited data AFTER all modifications but BEFORE saving
                $editedData = $UpdateProperty->getAttributes();
                unset($editedData['created_at'], $editedData['updated_at'], $editedData['id']);

                // Handle vacation apartments for vacation homes (property_classification = 4)
                $updatedVacationApartments = [];
                $originalVacationApartments = [];
                if (isset($request->property_classification) && $request->property_classification == 4) {
                    // Get original vacation apartments
                    $originalVacationApartments = \App\Models\VacationApartment::where('property_id', $UpdateProperty->id)
                        ->get()
                        ->map(function ($apt) {
                            return $apt->getAttributes();
                        })
                        ->toArray();
                    
                    // Get updated vacation apartments from request (will be processed later)
                    $vacationApartments = $request->input('vacation_apartments');
                    if ($vacationApartments !== null && is_array($vacationApartments) && !empty($vacationApartments)) {
                        $updatedVacationApartments = $vacationApartments;
                    }
                }

                // Handle hotel rooms for hotels (property_classification = 5) - get original descriptions
                $originalHotelRooms = [];
                $updatedHotelRooms = [];
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    // Get original hotel rooms with descriptions
                    $originalHotelRooms = \App\Models\HotelRoom::where('property_id', $UpdateProperty->id)
                        ->get()
                        ->map(function ($room) {
                            return [
                                'id' => $room->id,
                                'description' => $room->description,
                            ];
                        })
                        ->toArray();
                    
                    // Get updated hotel rooms from request (if provided)
                    if ($request->has('hotel_rooms') && is_array($request->hotel_rooms)) {
                        foreach ($request->hotel_rooms as $room) {
                            if (isset($room['id']) && array_key_exists('description', $room)) {
                                $updatedHotelRooms[] = [
                                    'id' => $room['id'],
                                    'description' => $room['description'] ?? null,
                                ];
                            }
                        }
                    }
                    
                    // Add hotel_rooms to editedData for filtering
                    if (!empty($updatedHotelRooms)) {
                        $editedData['hotel_rooms'] = $updatedHotelRooms;
                    }
                }

                // Check if owner edit requires approval
                if ($isOwnerEdit && !$autoApproveEdited) {
                    // Owner edits require admin approval when auto-approve is OFF - save as pending request
                    
                    // Filter to only include allowed editable fields
                    $editedData = \App\Services\PropertyEditRequestService::filterAllowedFields($editedData, $UpdateProperty);
                    
                    // Include vacation apartments in edited data if property is vacation home
                    if (isset($request->property_classification) && $request->property_classification == 4 && !empty($updatedVacationApartments)) {
                        $editedData['vacation_apartments'] = $updatedVacationApartments;
                    }
                    
                    // Include original vacation apartments in original data
                    if (isset($request->property_classification) && $request->property_classification == 4 && !empty($originalVacationApartments)) {
                        $originalPropertyData['vacation_apartments'] = $originalVacationApartments;
                    }
                    
                    // Include original hotel rooms in original data if property is hotel
                    if (isset($request->property_classification) && $request->property_classification == 5 && !empty($originalHotelRooms)) {
                        $originalPropertyData['hotel_rooms'] = $originalHotelRooms;
                    }
                    
                    // Use PropertyEditRequestService to save the edit request
                    $editRequestService = new \App\Services\PropertyEditRequestService();
                    $editRequest = $editRequestService->saveEditRequest(
                        $UpdateProperty, 
                        $editedData,  // Only contains allowed fields now
                        $UpdateProperty->added_by, 
                        $originalPropertyData
                    );
                    
                    // Set request_status to pending (property won't be saved yet, but we need to save related data)
                    $UpdateProperty->request_status = 'pending';
                    
                    // Log the edit request creation
                    Log::info('Property edit request created from admin panel', [
                        'edit_request_id' => $editRequest->id,
                        'property_id' => $UpdateProperty->id,
                        'requested_by' => $UpdateProperty->added_by
                    ]);
                } else {
                    // Admin edit OR owner edit with auto-approve enabled - save directly (no approval needed)
                    // If auto-approve is enabled for owner edits, set request_status to approved
                    if ($isOwnerEdit && $autoApproveEdited) {
                        $UpdateProperty->request_status = 'approved';
                    }
                }

                // Save the property (this will save even if edit request was created, but request_status will be pending)
                $UpdateProperty->update();
                AssignedOutdoorFacilities::where('property_id', $UpdateProperty->id)->delete();
                $facility = OutdoorFacilities::all();
                foreach ($facility as $key => $value) {
                    if ($request->has('facility' . $value->id) && $request->input('facility' . $value->id) != '') {
                        $facilities = new AssignedOutdoorFacilities();
                        $facilities->facility_id = $value->id;
                        $facilities->property_id = $UpdateProperty->id;
                        $facilities->distance = $request->input('facility' . $value->id);
                        $facilities->save();
                    }
                }
                $parameters = parameter::all();

                AssignParameters::where('modal_id', $id)->delete();
                foreach ($parameters as $par) {
                    if ($request->has('par_' . $par->id)) {
                        $update_parameter = new AssignParameters();
                        $update_parameter->parameter_id = $par->id;
                        if (($request->hasFile('par_' . $par->id))) {
                            $update_parameter->setAttribute('value', \store_image($request->file('par_' . $par->id), 'PARAMETER_IMG_PATH'));
                        } else {
                            $update_parameter->setAttribute('value', is_array($request->input('par_' . $par->id)) || $request->input('par_' . $par->id) == null ? json_encode($request->input('par_' . $par->id), JSON_FORCE_OBJECT) : ($request->input('par_' . $par->id)));
                        }
                        $update_parameter->modal()->associate($UpdateProperty);
                        $update_parameter->save();
                    }
                }

                /// START :: UPLOAD GALLERY IMAGE
                $FolderPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH');
                if (!is_dir($FolderPath)) {
                    mkdir($FolderPath, 0777, true);
                }

                $destinationPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $UpdateProperty->id;

                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                if ($request->hasfile('gallery_images')) {
                    foreach ($request->file('gallery_images') as $file) {
                        $name = microtime(true) . '.' . $file->extension();
                        $file->move($destinationPath, $name);

                        PropertyImages::create([
                            'image' => $name,
                            'propertys_id' => $UpdateProperty->id
                        ]);
                    }
                }
                /// END :: UPLOAD GALLERY IMAGE


                /// START :: UPLOAD DOCUMENT
                $destinationPath = public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . "/" . $UpdateProperty->id;
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                if ($request->hasFile('documents')) {
                    $documentsData = array();
                    foreach ($request->file('documents') as $file) {
                        $type = $file->extension();
                        $name = microtime(true) . '.' . $type;
                        $file->move($destinationPath, $name);

                        $documentsData[] = array(
                            'property_id'   => $UpdateProperty->id,
                            'name'          => $name,
                            'type'          => $type
                        );
                    }

                    if (collect($documentsData)->isNotEmpty()) {
                        PropertiesDocument::insert($documentsData);
                    }
                }
                /// END :: UPLOAD DOCUMENT

                // START :: ADD CITY DATA
                if (isset($request->city) && !empty($request->city)) {
                    CityImage::updateOrCreate(array('city' => $request->city));
                }
                // END :: ADD CITY DATA

                // START :: UPDATE HOTEL ROOMS
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->hotel_rooms) && !empty($request->hotel_rooms)) {
                    try {
                        \App\Models\HotelRoom::where('property_id', $UpdateProperty->id)->delete();
                        foreach ($request->hotel_rooms as $room) {
                            HotelRoom::create([
                                'property_id' => $UpdateProperty->id,
                                'room_type_id' => $room['room_type_id'] ?? null,
                                'room_number' => $room['room_number'] ?? null,
                                'price_per_night' => isset($room['price_per_night']) ? (float)$room['price_per_night'] : 0,
                                'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                'availability_type' => isset($room['availability_type']) ? (int)$room['availability_type'] : null,
                                'available_dates' => $room['available_dates'] ?? null,
                                'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : null,
                                'description' => $room['description'] ?? null,
                                'status' => $room['status'] ?? 1
                            ]);
                        }
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                // END :: UPDATE HOTEL ROOMS

                // START :: UPDATE VACATION APARTMENTS
                // Check if property classification is 4 (vacation homes)
                // Also handle the case where vacation_apartments might be null/empty to delete existing apartments
                if (isset($request->property_classification) && $request->property_classification == 4) {
                    // Use input() to properly parse nested FormData arrays
                    $vacationApartments = $request->input('vacation_apartments');
                    
                    // If vacation_apartments is explicitly set (even if empty array), process it
                    if ($vacationApartments !== null) {
                        // Get all existing apartment IDs for this property
                        $existingApartmentIds = \App\Models\VacationApartment::where('property_id', $UpdateProperty->id)
                            ->pluck('id')
                            ->toArray();
                        
                        // Track which apartments are being updated/created
                        $processedApartmentIds = [];
                        
                        // Only process apartments if the array is not empty
                        if (!empty($vacationApartments) && is_array($vacationApartments)) {
                            try {
                                foreach ($vacationApartments as $apartment) {
                                    // Parse available_dates if it's a JSON string
                                    $availableDates = $apartment['available_dates'] ?? null;
                                    if (is_string($availableDates)) {
                                        $availableDates = json_decode($availableDates, true);
                                    }
                                    
                                    // Check if this is an existing apartment (has id and id exists for this property)
                                    if (isset($apartment['id']) && !empty($apartment['id']) && $apartment['id'] !== null) {
                                        $apartmentId = (int)$apartment['id'];
                                        $existingApartment = \App\Models\VacationApartment::find($apartmentId);
                                        
                                        // Only update if the apartment exists and belongs to this property
                                        if ($existingApartment && $existingApartment->property_id == $UpdateProperty->id) {
                                            $existingApartment->apartment_number = $apartment['apartment_number'] ?? $existingApartment->apartment_number;
                                            $existingApartment->price_per_night = isset($apartment['price_per_night']) ? (float)$apartment['price_per_night'] : $existingApartment->price_per_night;
                                            $existingApartment->discount_percentage = isset($apartment['discount_percentage']) ? (float)$apartment['discount_percentage'] : $existingApartment->discount_percentage;
                                            $existingApartment->availability_type = isset($apartment['availability_type']) ? (int)$apartment['availability_type'] : $existingApartment->availability_type;
                                            $existingApartment->available_dates = $availableDates ?? $existingApartment->available_dates;
                                            $existingApartment->description = $apartment['description'] ?? $existingApartment->description;
                                            $existingApartment->status = isset($apartment['status']) ? (($apartment['status'] === '1' || $apartment['status'] === 1 || $apartment['status'] === true) ? true : false) : $existingApartment->status;
                                            $existingApartment->max_guests = isset($apartment['max_guests']) ? (int)$apartment['max_guests'] : $existingApartment->max_guests;
                                            $existingApartment->bedrooms = isset($apartment['bedrooms']) ? (int)$apartment['bedrooms'] : $existingApartment->bedrooms;
                                            $existingApartment->bathrooms = isset($apartment['bathrooms']) ? (int)$apartment['bathrooms'] : $existingApartment->bathrooms;
                                            $existingApartment->quantity = isset($apartment['quantity']) ? (int)$apartment['quantity'] : $existingApartment->quantity;
                                            $existingApartment->save();
                                            
                                            $processedApartmentIds[] = $apartmentId;
                                        }
                                    } else {
                                        // Create new apartment (no id provided)
                                        $newApartment = \App\Models\VacationApartment::create([
                                            'property_id' => $UpdateProperty->id,
                                            'apartment_number' => $apartment['apartment_number'] ?? null,
                                            'price_per_night' => isset($apartment['price_per_night']) ? (float)$apartment['price_per_night'] : 0,
                                            'discount_percentage' => isset($apartment['discount_percentage']) ? (float)$apartment['discount_percentage'] : 0,
                                            'availability_type' => isset($apartment['availability_type']) ? (int)$apartment['availability_type'] : null,
                                            'available_dates' => $availableDates,
                                            'description' => $apartment['description'] ?? null,
                                            'status' => isset($apartment['status']) ? (($apartment['status'] === '1' || $apartment['status'] === 1 || $apartment['status'] === true) ? true : false) : true,
                                            'max_guests' => isset($apartment['max_guests']) ? (int)$apartment['max_guests'] : null,
                                            'bedrooms' => isset($apartment['bedrooms']) ? (int)$apartment['bedrooms'] : null,
                                            'bathrooms' => isset($apartment['bathrooms']) ? (int)$apartment['bathrooms'] : null,
                                            'quantity' => isset($apartment['quantity']) ? (int)$apartment['quantity'] : 1,
                                        ]);
                                        
                                        $processedApartmentIds[] = $newApartment->id;
                                    }
                                }
                            } catch (\Exception $e) {
                                throw $e;
                            }
                        }
                        
                        // Delete any apartments that were not included in the request
                        $apartmentsToDelete = array_diff($existingApartmentIds, $processedApartmentIds);
                        if (!empty($apartmentsToDelete)) {
                            \App\Models\VacationApartment::where('property_id', $UpdateProperty->id)
                                ->whereIn('id', $apartmentsToDelete)
                                ->delete();
                        }
                    } else {
                        // If vacation_apartments is explicitly null, delete all existing apartments
                        \App\Models\VacationApartment::where('property_id', $UpdateProperty->id)->delete();
                    }
                }
                // END :: UPDATE VACATION APARTMENTS

                // START :: UPDATE ADDONS PACKAGES
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->addons_packages) && !empty($request->addons_packages)) {
                    try {
                        // Cleanup existing
                        \App\Models\PropertyHotelAddonValue::where('property_id', $UpdateProperty->id)->delete();
                        \App\Models\AddonsPackage::where('property_id', $UpdateProperty->id)->delete();

                        // Ensure folder exists
                        $addonFolderPath = public_path('images') . config('global.HOTEL_ADDON_PATH');
                        if (!is_dir($addonFolderPath)) {
                            mkdir($addonFolderPath, 0777, true);
                        }

                        foreach ($request->addons_packages as $packageIndex => $package) {
                            // Create package
                            $addonsPackage = new \App\Models\AddonsPackage();
                            $addonsPackage->name = $package['name'] ?? null;
                            $addonsPackage->room_type_id = $package['room_type_id'] ?? null;
                            $addonsPackage->description = $package['description'] ?? null;
                            $addonsPackage->property_id = $UpdateProperty->id;
                            $addonsPackage->status = $package['status'] ?? 'active';
                            $addonsPackage->price = isset($package['price']) ? $package['price'] : null;
                            $addonsPackage->save();

                            // Process addon values for this package
                            if (isset($package['addon_values']) && !empty($package['addon_values'])) {
                                foreach ($package['addon_values'] as $addonIndex => $addon) {
                                    $addonField = \App\Models\HotelAddonField::where('id', $addon['hotel_addon_field_id'] ?? null)->where('status', 'active')->first();
                                    if (!$addonField) {
                                        continue;
                                    }
                                    $value = $addon['value'] ?? null;

                                    // Handle file uploads
                                    if ($addonField->field_type == 'file' && $request->hasFile('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value')) {
                                        $file = $request->file('addons_packages.' . $packageIndex . '.addon_values.' . $addonIndex . '.value');
                                        $fileName = microtime(true) . '.' . $file->extension();
                                        $file->move($addonFolderPath, $fileName);
                                        $value = $fileName;
                                    } elseif ($addonField->field_type == 'checkbox' && is_array($value)) {
                                        $value = json_encode($value);
                                    } elseif (in_array($addonField->field_type, ['radio', 'dropdown'])) {
                                        $validValue = \App\Models\HotelAddonFieldValue::where('hotel_addon_field_id', $addon['hotel_addon_field_id'] ?? null)
                                            ->where('value', $value)
                                            ->exists();
                                        if (!$validValue) {
                                            continue;
                                        }
                                    }

                                    \App\Models\PropertyHotelAddonValue::create([
                                        'property_id' => $UpdateProperty->id,
                                        'hotel_addon_field_id' => $addon['hotel_addon_field_id'] ?? null,
                                        'value' => $value,
                                        'static_price' => $addon['static_price'] ?? null,
                                        'multiply_price' => $addon['multiply_price'] ?? null,
                                        'package_id' => $addonsPackage->id
                                    ]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                // END :: UPDATE ADDONS PACKAGES

                // START :: UPDATE CERTIFICATES
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->certificates) && !empty($request->certificates)) {
                    try {
                        // Cleanup existing
                        \App\Models\PropertyCertificate::where('property_id', $UpdateProperty->id)->delete();

                        // Ensure folder exists
                        $certificateFolderPath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH');
                        if (!is_dir($certificateFolderPath)) {
                            mkdir($certificateFolderPath, 0777, true);
                        }

                        foreach ($request->certificates as $certificateIndex => $certificate) {
                            $propertyCertificate = new \App\Models\PropertyCertificate();
                            $propertyCertificate->title = $certificate['title'] ?? null;
                            $propertyCertificate->description = $certificate['description'] ?? null;
                            $propertyCertificate->property_id = $UpdateProperty->id;

                            if ($request->hasFile('certificates.' . $certificateIndex . '.file')) {
                                $file = $request->file('certificates.' . $certificateIndex . '.file');
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
                // END :: UPDATE CERTIFICATES

                DB::commit();
                
                // Show appropriate success message based on whether edit request was created
                if ($isOwnerEdit && !$autoApproveEdited) {
                    ResponseService::successRedirectResponse('Property edit request created successfully. Changes will be applied after admin approval.');
                } else {
                    ResponseService::successRedirectResponse('Data Updated Successfully');
                }
            } catch (Exception $e) {
                DB::rollBack();
                ResponseService::logErrorRedirectResponse($e, "Update Property Issue");
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (env('DEMO_MODE') && Auth::user()->email != "superadmin@gmail.com") {
            return redirect()->back()->with('error', 'This is not allowed in the Demo Version');
        }
        if (!has_permissions('delete', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        } else {
            DB::beginTransaction();
            $property = Property::find($id);

            if ($property->delete()) {
                DB::commit();
                ResponseService::successRedirectResponse('Data Deleted Successfully');
            } else {
                DB::rollBack();
                ResponseService::errorRedirectResponse('Something Wrong');
            }
        }
    }



    public function getPropertyList(Request $request)
    {

        $offset = (int) $request->input('offset', 0); // Ensure integer for pagination
        $limit = (int) $request->input('limit', 10);   // Ensure integer for pagination
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');
        $customerID = $request->input('customerID', null);

        $sql = Property::with('category')
            ->with('customer:id,name,mobile')
            ->with('assignParameter.parameter')
            ->with('interested_users')
            ->with('advertisement')
            ->orderBy($sort, $order);

        $searchQuery = null;
        $propertyType = null;
        $propertyClassification = null;
        $status = null;
        $categoryId = null;
        $propertyAddedBy = null;
        $propertyAccessibility = null;

        // Extract and validate filters
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchQuery = trim($_GET['search']);  // Trim whitespace
        }

        if (isset($_GET['property_type']) && $_GET['property_type'] !== "") {
            $propertyType = $_GET['property_type'];
        }

        if (isset($_GET['property_classification']) && $_GET['property_classification'] !== "") {
            $propertyClassification = (int)$_GET['property_classification'];
        }

        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $status = $_GET['status'];
        }

        if (isset($_GET['category']) && $_GET['category'] !== '') {
            $categoryId = (int) $_GET['category']; // Ensure integer for category ID
        }

        if (isset($_GET['property_added_by']) && $_GET['property_added_by'] !== '') {
            $propertyAddedBy = $_GET['property_added_by']; // Ensure integer for category ID
        }
        if (isset($_GET['property_accessibility']) && $_GET['property_accessibility'] !== '') {
            $propertyAccessibility = $_GET['property_accessibility']; // Ensure integer for category ID
        }

        // Apply filters with proper escaping for security
        if ($searchQuery !== null) {
            $sql = $sql->where(function ($query) use ($searchQuery) {
                $query->where('id', 'LIKE', "%$searchQuery%")->orwhere('title', 'LIKE', "%$searchQuery%")->orwhere('address', 'LIKE', "%$searchQuery%");
                $query->orWhereHas('category', function ($query) use ($searchQuery) {
                    $query->where('category', 'LIKE', "%$searchQuery%");
                })->orWhereHas('customer', function ($query) use ($searchQuery) {
                    $query->where('name', 'LIKE', "%$searchQuery%")->orwhere('email', 'LIKE', "%$searchQuery%");
                });
            });
        }

        if ($propertyType !== null) {
            $sql = $sql->where('propery_type', $propertyType);
        }

        if ($propertyClassification !== null) {
            $sql = $sql->where('property_classification', $propertyClassification);
        }

        if (!empty($customerID)) {
            $sql = $sql->where('added_by', $customerID);
        }

        if ($status !== null) {
            $sql = $sql->where('status', $status);
        }

        if ($categoryId !== null) {
            $sql = $sql->where('category_id', $categoryId);
        }

        if ($propertyAddedBy !== null) {
            if ($propertyAddedBy == 0) {
                $sql = $sql->where('added_by', 0);
            } else {
                $sql = $sql->whereNot('added_by', 0);
            }
        }
        if (isset($propertyAccessibility) && $propertyAccessibility !== null) {
            if ($propertyAccessibility == 1) {
                $sql = $sql->where('is_premium', 1);
            } else {
                $sql = $sql->where('is_premium', 0);
            }
        }

        $total = $sql->count();

        if (isset($limit)) {
            $sql = $sql->skip($offset)->take($limit);
        }

        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $count = 1;

        $operate = '';
        $currency_symbol = Setting::where('type', 'currency_symbol')->pluck('data')->first();

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['property_type_raw'] = $row->getRawOriginal('propery_type');

            // Initialize operate variable
            $operate = '';

            // Always show edit button for all properties
            if (has_permissions('update', 'property')) {
                $operate = BootstrapTableService::editButton(route('property.edit', $row->id), false);
            }

            // Always show Update Property Status button
            if (has_permissions('update', 'property')) {
                $requestStatusButtonCustomClasses = ["btn", "icon", "btn-warning", "btn-sm", "rounded-pill", "request-status-btn"];
                $requestStatusButtonCustomAttributes = ["id" => $row->id, "title" => trans('Change Status'), "data-toggle" => "modal", "data-bs-target" => "#changeRequestStatusModal", "data-bs-toggle" => "modal"];
                $operate .= BootstrapTableService::button('fa fa-exclamation-circle', '', $requestStatusButtonCustomClasses, $requestStatusButtonCustomAttributes);
            }

            // Add delete button if user has permission
            if (has_permissions('delete', 'property')) {
                $operate .= BootstrapTableService::deleteButton(route('property.destroy', $row->id));
            }
            $onlyDeleteOperate = BootstrapTableService::deleteButton(route('property.destroy', $row->id));

            $interested_users = array();
            foreach ($row->interested_users as $interested_user) {
                if ($interested_user->property_id == $row->id) {
                    array_push($interested_users, $interested_user->customer_id);
                }
            }

            $price = null;
            if (!empty($row->propery_type) && $row->getRawOriginal('propery_type') == 1) {
                $price = !empty($row->rentduration) ?  $currency_symbol . '' . $row->price . '/' . $row->rentduration : $row->price;
            } else {
                $price = $currency_symbol . '' . $row->price;
            }

            $tempRow['total_interested_users'] = count($interested_users);
            $tempRow['promoted'] = $row->is_promoted;
            if ($row->added_by == 0 && $row->request_status == "approved") {
                $tempRow['edit_status'] = $row->status;
            } else {
                $tempRow['edit_status'] = null;
            }
            $tempRow['edit_status_url'] = $row->added_by == 0 && $row->request_status == "approved" ? 'updatepropertystatus' : null;
            $tempRow['price'] = $price;
            $featured = count($row->advertisement) ? '<div class="featured_tag"><div class="featured_lable">Featured</div></div>' : '';

            // Rewrite title_image URL if using S3
            $titleImageUrl = $this->rewriteImageUrl($row->title_image);

            $tempRow['Property_name'] = '<div class="propetrty_name d-flex"><img class="property_image" alt="" src="' . $titleImageUrl . '"><div class="property_detail"><div class="property_title">' . $row->title . '</div>' . $featured . '</div></div></div>';

            if ($row->added_by != 0) {
                $tempRow['added_by'] = $row->customer->name;
                $tempRow['mobile'] = (env('DEMO_MODE') ? (env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ($row->customer->mobile) : '****************************') : ($row->customer->mobile));
            }
            if ($row->added_by == 0) {
                $mobile = Setting::where('type', 'company_tel1')->pluck('data');
                $tempRow['added_by'] = trans('Admin');
                $tempRow['mobile'] =   $mobile[0];
            }
            $tempRow['customer_ids'] = $interested_users;

            // Interested Users
            $count = "  " . count($interested_users);
            $interestedUserButton = BootstrapTableService::editButton('', true, null, 'text-secondary', $row->id, null, '', 'bi bi-eye-fill edit_icon', $count);
            $tempRow['interested_users'] = $interestedUserButton;
            foreach ($row->interested_users as $interested_user) {
                if ($interested_user->property_id == $row->id) {
                    $tempRow['interested_users_details'] = Customer::Where('id', $interested_user->customer_id)->get()->toArray();
                }
            }

            // Gallery Images
            $galleryButtonCustomClasses = ["btn", "icon", "btn-primary", "btn-sm", "rounded-pill", "gallery-image-btn"];
            $galleryButtonCustomAttributes = ["id" => $row->id, "title" => trans('Gallery Images'), "data-toggle" => "modal", "data-bs-target" => "#galleryImagesModal", "data-bs-toggle" => "modal"];
            $galleryImagesCount = count($row->gallery);
            $galleryImagesButton = BootstrapTableService::button('bi bi-eye-fill ml-2', '', $galleryButtonCustomClasses, $galleryButtonCustomAttributes, $galleryImagesCount);
            $tempRow['gallery-images-btn'] = $galleryImagesButton;


            // Documents
            $documentsButtonCustomClasses = ["btn", "icon", "btn-primary", "btn-sm", "rounded-pill", "documents-btn"];
            $documentsButtonCustomAttributes = ["id" => $row->id, "title" => trans('Documents'), "data-toggle" => "modal", "data-bs-target" => "#documentsModal", "data-bs-toggle" => "modal"];
            // Get documents using the accessor and convert to array
            $documents = $row->documents ?? collect([]);
            $documentsArray = $documents->toArray();
            $documentsCount = count($documentsArray);
            $documentsButton = BootstrapTableService::button('bi bi-eye-fill', '', $documentsButtonCustomClasses, $documentsButtonCustomAttributes, $documentsCount);
            $tempRow['documents-btn'] = $documentsButton;
            // Ensure documents are included in the response array (convert Collection to array)
            $tempRow['documents'] = $documentsArray;


            $tempRow['operate'] = $operate;
            $tempRow['only_delete_operate'] = $onlyDeleteOperate;
            $rows[] = $tempRow;
            $count++;
        }
        // $cities =  json_decode(file_get_contents(public_path('json') . "/cities.json"), true);

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
    public function updateStatus(Request $request)
    {
        if (!has_permissions('update', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            Property::where('id', $request->id)->update(['status' => $request->status]);
            $Property = Property::with('customer')->find($request->id);

            if (!empty($Property->customer)) {
                if ($Property->customer->isActive == 1 && $Property->customer->notification == 1) {

                    $fcm_ids = array();
                    $user_token = Usertokens::where('customer_id', $Property->customer->id)->pluck('fcm_id')->toArray();
                    $fcm_ids[] = !empty($user_token) ? $user_token : array();

                    $msg = "";
                    if (!empty($fcm_ids)) {
                        $msg = $Property->status == 1 ? 'Activated now by Administrator ' : 'Deactivated now by Administrator ';
                        $registrationIDs = $fcm_ids[0];

                        $fcmMsg = array(
                            'title' =>  $Property->name . 'Property Updated',
                            'message' => 'Your Property Post ' . $msg,
                            'type' => 'property_inquiry',
                            'body' => 'Your Property Post ' . $msg,
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'sound' => 'default',
                            'id' => (string)$Property->id,

                        );
                        send_push_notification($registrationIDs, $fcmMsg);
                    }
                    //END ::  Send Notification To Customer

                    Notifications::create([
                        'title' => $Property->name . 'Property Updated',
                        'message' => 'Your Property Post ' . $msg,
                        'image' => '',
                        'type' => '1',
                        'send_type' => '0',
                        'customers_id' => $Property->customer->id,
                        'propertys_id' => $Property->id
                    ]);
                }
            }
            $response['error'] = false;
            ResponseService::successResponse($request->status ? "Property Activated Successfully" : "Property Deactivated Successfully");
        }
    }


    public function removeGalleryImage(Request $request)
    {

        if (!has_permissions('delete', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
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
            return response()->json($response);
        }
    }



    public function getFeaturedPropertyList()
    {

        $offset = 0;
        $limit = 4;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }

        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }

        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $sql = Property::with('category')->with('customer')->whereHas('advertisement')->orderBy($sort, $order);

        $sql->skip($offset)->take($limit);

        $res = $sql->get();

        $bulkData = array();

        $rows = array();
        $tempRow = array();
        $count = 1;


        $operate = '';

        foreach ($res as $row) {

            if (count($row->advertisement)) {
                if (has_permissions('update', 'property') && $row->added_by == 0) {
                    $operate = '<a  href="' . route('property.edit', $row->id) . '"  class="btn icon btn-primary btn-sm rounded-pill mt-2" id="edit_btn" title="Edit"><i class="fa fa-edit edit_icon"></i></a>';
                } else {
                    $operate = "-";
                }
                $tempRow = $row->toArray();
                $tempRow['type'] = ucfirst($row->propery_type);
                if ($row->added_by == 0 && $row->request_status == "approved") {
                    $tempRow['status'] = $row->status;
                } else {
                    $tempRow['status'] = null;
                }
                $tempRow['edit_status_url'] = 'updatepropertystatus';
                $tempRow['promoted'] = $row->is_promoted;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
                $count++;
            }
        }
        $total = $sql->count();
        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
    public function updateaccessability(Request $request)
    {
        if (!has_permissions('update', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            Property::where('id', $request->id)->update(['is_premium' => $request->status]);
            ResponseService::successResponse("Data Updated Successfully");
        }
    }

    public function generateAndCheckSlug(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'title' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        // Generate the slug or throw exception
        try {
            $title = $request->title;
            $id = $request->has('id') && !empty($request->id) ? $request->id : null;
            if ($id) {
                $slug = generateUniqueSlug($title, 1, null, $id);
            } else {
                $slug = generateUniqueSlug($title, 1);
            }
            ResponseService::successResponse("", $slug);
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, "Property Slug Generation Error", "Something Went Wrong");
        }
    }



    public function removeDocument(Request $request)
    {

        if (!has_permissions('delete', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            $id = $request->id;
            $getDocument = PropertiesDocument::where('id', $id)->first();
            if ($getDocument) {
                $file = $getDocument->getRawOriginal('name');
                $propertyId =  $getDocument->property_id;

                if (PropertiesDocument::where('id', $id)->delete()) {
                    if (file_exists(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $propertyId . "/" . $file)) {
                        unlink(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $propertyId . "/" . $file);
                    }
                    $response['error'] = false;
                } else {
                    $response['error'] = true;
                }

                $countImage = PropertiesDocument::where('property_id', $propertyId)->get();
                if ($countImage->count() == 0) {
                    rmdir(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $propertyId);
                }
                return response()->json($response);
            }
        }
    }


    public function removeThreeDImage($id, Request $request)
    {
        if (!has_permissions('delete', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            try {
                $propertyData = Property::findOrFail($id);
                unlink_image($propertyData->three_d_image);
                $propertyData->three_d_image = null;
                $propertyData->save();
                ResponseService::successResponse("Data Deleted Successfully");
            } catch (Exception $e) {
                ResponseService::logErrorResponse($e, "Remove ThreeD Image Error", "Something Went Wrong");
            }
        }
    }

    /**
     * Display the property edit requests page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function editRequestsIndex(Request $request)
    {
        if (!has_permissions('read', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        
        try {
            $status = $request->get('status', 'pending'); // pending, approved, rejected, all
            
            // Check if table exists
            if (!\Schema::hasTable('property_edit_requests')) {
                return view('property.edit-requests', [
                    'editRequests' => collect([]),
                    'status' => $status,
                    'error' => 'The property_edit_requests table does not exist. Please run the migration.'
                ]);
            }
            
            $query = PropertyEditRequest::with([
                'property:id,title,slug_id',
                'requestedBy:id,name,email',
                'reviewedBy:id,name'
            ]);
            
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            $editRequests = $query->orderBy('created_at', 'desc')->get();
            
            return view('property.edit-requests', compact('editRequests', 'status'));
        } catch (\Exception $e) {
            Log::error('Error loading property edit requests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return view('property.edit-requests', [
                'editRequests' => collect([]),
                'status' => $status,
                'error' => 'Error loading edit requests: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of pending property edit requests (API/JSON).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEditRequests(Request $request)
    {
        if (!has_permissions('read', 'property')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        
        try {
            $status = $request->get('status', 'pending'); // pending, approved, rejected, all
            
            $query = PropertyEditRequest::with([
                'property:id,title,slug_id',
                'requestedBy:id,name,email',
                'reviewedBy:id,name'
            ]);
            
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            $editRequests = $query->orderBy('created_at', 'desc')->get();
            
            ResponseService::successResponse('Property edit requests retrieved successfully', [
                'edit_requests' => $editRequests
            ]);
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, "Get Property Edit Requests Error", "Something Went Wrong");
        }
    }

    /**
     * Get a specific property edit request with details.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEditRequest($id)
    {
        if (!has_permissions('read', 'property')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ], 403);
        }
        
        try {
            $editRequest = PropertyEditRequest::with([
                'property:id,title,slug_id',
                'requestedBy:id,name,email',
                'reviewedBy:id,name'
            ])->findOrFail($id);
            
            return response()->json([
                'error' => false,
                'message' => 'Property edit request retrieved successfully',
                'data' => [
                    'edit_request' => $editRequest
                ]
            ]);
        } catch (Exception $e) {
            Log::error("Get Property Edit Request Error: " . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Update property edit request status (approve or reject).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEditRequestStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'edit_request_id' => 'required|integer|exists:property_edit_requests,id',
            'status' => 'required|in:approved,rejected',
            'reject_reason' => 'required_if:status,rejected|max:300'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        if (!has_permissions('update', 'property')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ], 403);
        }
        
        try {
            DB::beginTransaction();
            
            $editRequest = PropertyEditRequest::findOrFail($request->edit_request_id);
            
            if ($editRequest->status != 'pending') {
                return response()->json([
                    'error' => true,
                    'message' => 'This edit request has already been processed.'
                ], 400);
            }
            
            $editRequestService = new \App\Services\PropertyEditRequestService();
            
            $reviewedBy = Auth::id();
            
            if ($request->status == "approved") {
                // Apply the edits to the property
                $property = $editRequestService->applyEditRequest($editRequest, $reviewedBy);
                
                DB::commit();
                
                return response()->json([
                    'error' => false,
                    'message' => 'Property edit request approved and changes applied successfully.',
                    'data' => [
                        'property_id' => $property->id,
                        'edit_request_id' => $editRequest->id
                    ]
                ]);
            } else {
                // Reject the edit request
                $editRequestService->rejectEditRequest($editRequest, $request->reject_reason, $reviewedBy);
                
                DB::commit();
                
                return response()->json([
                    'error' => false,
                    'message' => 'Property edit request rejected successfully.',
                    'data' => [
                        'edit_request_id' => $editRequest->id
                    ]
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Update Property Edit Request Status Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateRequestStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_status' => 'required|in:approved,rejected',
            'reject_reason' => 'required_if:request_status,rejected|max:300'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            if (!has_permissions('update', 'property')) {
                ResponseService::errorResponse(PERMISSION_ERROR_MSG);
            } else {
                if ($request->request_status == "rejected") {
                    RejectReason::create(array(
                        'property_id' => $request->id,
                        'reason' => $request->reject_reason
                    ));
                    $status = 0;
                } else {
                    $status = 1;
                }
                Property::where('id', $request->id)->update(['request_status' => $request->request_status, 'status' => $status]);
                DB::commit();

                // Send mail for property status
                try {
                    $propertyData = Property::where('id', $request->id)->select('id', 'title', 'request_status', 'added_by', 'city', 'state', 'country', 'rent_package')->with('customer:id,name,email,management_type,address')->firstOrFail();
                    if (!empty($propertyData->customer->email)) {
                        // Get Data of email type
                        $emailTypeData = HelperService::getEmailTemplatesTypes("property_status");

                        // Email Template
                        $propertyStatusTemplateData = system_setting($emailTypeData['type']);
                        $appName = env("APP_NAME") ?? "eBroker";

                        // Build location string
                        $locationParts = [];
                        if (!empty($propertyData->city)) {
                            $locationParts[] = $propertyData->city;
                        }
                        if (!empty($propertyData->state)) {
                            $locationParts[] = $propertyData->state;
                        }
                        if (!empty($propertyData->country)) {
                            $locationParts[] = $propertyData->country;
                        }
                        $locationString = !empty($locationParts) ? ', ' . implode(', ', $locationParts) : '';

                        $variables = array(
                            'app_name' => $appName,
                            'user_name' => $propertyData->customer->name,
                            'property_name' => $propertyData->title . $locationString,
                            'status' => $request->request_status,
                            'reject_reason' => $request->request_status == 'rejected' ? $request->reject_reason : null,
                            'email' => $propertyData->customer->email
                        );
                        if (empty($propertyStatusTemplateData)) {
                            $propertyStatusTemplateData = "Property Status have been changed";
                        }
                        $propertyStatusTemplate = HelperService::replaceEmailVariables($propertyStatusTemplateData, $variables);

                        $data = array(
                            'email_template' => $propertyStatusTemplate,
                            'email' => $propertyData->customer->email,
                            'title' => $emailTypeData['title'],
                        );
                        HelperService::sendMail($data);
                    }
                } catch (Exception $e) {
                    Log::error("Something Went Wrong in Property Status Update Mail Sending");
                }

                // Send contract emails when property is approved
                if ($request->request_status == "approved") {
                    try {
                        if (!empty($propertyData->customer->email)) {
                            // Get customer data with management_type
                            $customerData = $propertyData->customer;

                            // Send the appropriate contract email based on property type
                            // propery_type: 0 = Sell, 1 = Rent
                            $propertyType = $propertyData->getRawOriginal('propery_type');
                            if ($propertyType == 0) {
                                // Send list property sell contract
                                $this->sendContractEmail($propertyData, "list_property_sell_contract");
                            } elseif ($propertyType == 1) {
                                // Send list property rent contract
                                $this->sendContractEmail($propertyData, "list_property_rent_contract");
                            }

                            // Send additional contract emails based on conditions
                            // Check if customer management_type is "himself" and rent_package is "basic"
                            if (
                                isset($customerData->management_type) && $customerData->management_type == 'himself' &&
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'basic' && ($propertyData->getRawOriginal('property_classification') == 1  ||  $propertyData->getRawOriginal('property_classification') == 2) && $propertyData->getRawOriginal('propery_type') == 0
                            ) {
                                // Send additional basic package self managed contract
                                $this->sendContractEmail($propertyData, "basic_package_self_managed");
                            }

                            // Check if customer management_type is "himself" and rent_package is "basic" for renting properties
                            if (
                                isset($customerData->management_type) && $customerData->management_type == 'himself' &&
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'basic' && ($propertyData->getRawOriginal('property_classification') == 1  ||  $propertyData->getRawOriginal('property_classification') == 2) &&
                                $propertyData->getRawOriginal('propery_type') == 1
                            ) {
                                // Send additional basic package renting self managed contract
                                $this->sendContractEmail($propertyData, "basic_package_renting_self_managed");
                            }

                            // Check if rent_package is "premium" for renting properties
                            if (
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'premium' &&
                                ($propertyData->getRawOriginal('property_classification') == 1  ||  $propertyData->getRawOriginal('property_classification') == 2) &&
                                $propertyData->getRawOriginal('propery_type') == 1
                            ) {
                                // Send additional premium package renting contract
                                $this->sendContractEmail($propertyData, "premium_package_renting");
                            }

                            // Check if vacation homes with basic package and self managed
                            if (
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'basic' &&
                                isset($customerData->management_type) && $customerData->management_type == 'himself' &&
                                $propertyData->getRawOriginal('property_classification') == 4
                            ) {
                                // Send additional vacation homes self managed basic package contract
                                $this->sendContractEmail($propertyData, "vacation_homes_self_managed_basic_package");
                            }

                            // Check if vacation homes with premium package and as-home managed
                            if (
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'premium' &&
                                isset($customerData->management_type) && $customerData->management_type == 'as home' &&
                                $propertyData->getRawOriginal('property_classification') == 4
                            ) {
                                // Send additional vacation homes as-home managed premium package contract
                                $this->sendContractEmail($propertyData, "vacation_homes_ashome_managed_premium_package");
                            }

                            // Check if hotel booking (property classification == 5 for hotels)
                            if ($propertyData->getRawOriginal('property_classification') == 5) {
                                // Send hotel booking contract
                                $this->sendContractEmail($propertyData, "hotel_booking");
                            }

                            // Add more conditions here for future contract types
                            // Example:
                            // if (some_condition) {
                            //     $this->sendContractEmail($propertyData, "another_contract_type");
                            // }
                        }
                    } catch (Exception $e) {
                        Log::error("Something Went Wrong in Contract Email Sending: " . $e->getMessage());
                    }
                }



                // Send Notification
                $property = Property::with('customer:id,name,isActive,notification')->select('id', 'title', 'request_status', 'added_by')->find($request->id);
                $fcm_ids = array();
                if ($property->customer->isActive == 1 && $property->customer->notification == 1) {
                    $user_token = Usertokens::where('customer_id', $property->customer->id)->pluck('fcm_id')->toArray();
                }

                $fcm_ids[] = $user_token ?? array();

                $msg = "";
                if (!empty($fcm_ids)) {
                    $msg = $property->request_status == 'approved' ? 'Approved by Administrator ' : 'Rejected by Administrator ';
                    $registrationIDs = $fcm_ids[0];

                    $fcmMsg = array(
                        'title' =>  $property->title . 'Property Updated',
                        'message' => 'Your Property Post ' . $msg,
                        'type' => 'property_inquiry',
                        'body' => 'Your Property Post ' . $msg,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        'id' => (string)$property->id,

                    );
                    send_push_notification($registrationIDs, $fcmMsg);
                }
                //END ::  Send Notification To Customer

                Notifications::create([
                    'title' => $property->name . 'Property Updated',
                    'message' => 'Your Property Post ' . $msg,
                    'image' => '',
                    'type' => '1',
                    'send_type' => '0',
                    'customers_id' => $property->customer->id,
                    'propertys_id' => $property->id
                ]);
                ResponseService::successResponse("Data Updated Successfully");
            }
        } catch (Exception $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "Update Request Status in Property", "Something Went Wrong");
        }
    }

    /**
     * Helper method to send contract emails
     *
     * @param Property $propertyData
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
            if (in_array($contractType, ['selling_or_renting_contract', 'list_property_sell_contract', 'list_property_rent_contract'])) {
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
            if (in_array($contractType, ['selling_or_renting_contract', 'list_property_sell_contract', 'list_property_rent_contract']) && isset($pdfTemplate)) {
                $contractData['pdf_template'] = $pdfTemplate;
            }
            
            // Send email with PDF attachment containing the full contract
            // The PDF generation is configured to handle unlimited content length
            HelperService::sendMail($contractData);
        } catch (Exception $e) {
            Log::error("Something Went Wrong in Contract Email Sending for type {$contractType}: " . $e->getMessage());
        }
    }

    /**
     * Rewrite image URL to use S3 if configured
     *
     * @param string $imageUrl
     * @return string
     */
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
}
