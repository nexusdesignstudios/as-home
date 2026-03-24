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
use App\Models\HotelRoomType;


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
                'hotel_rooms.*.room_type_id' => 'nullable',
                'hotel_rooms.*.custom_room_type' => 'nullable|string',
                'hotel_rooms.*.room_number' => 'required_with:hotel_rooms',
                'hotel_rooms.*.price_per_night' => 'required_with:hotel_rooms|numeric|min:0',
                'hotel_rooms.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'hotel_rooms.*.refund_policy' => 'nullable|in:flexible,non-refundable',
                'hotel_rooms.*.nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
                'hotel_rooms.*.availability_type' => 'nullable|integer|in:1,2',
                'hotel_rooms.*.available_dates' => 'nullable|json',
                'hotel_rooms.*.weekend_commission' => 'nullable|numeric|min:0|max:100',
                'hotel_rooms.*.description' => 'nullable|string',
                'hotel_rooms.*.min_guests' => 'nullable|integer|min:1',
                'hotel_rooms.*.base_guests' => 'nullable|integer|min:1',
                'hotel_rooms.*.max_guests' => 'nullable|integer|min:1',
                'hotel_rooms.*.guest_pricing_rules' => 'nullable|json',
                'addons_packages'       => 'nullable|array',
                'addons_packages.*.name' => 'required_with:addons_packages',
                'addons_packages.*.description' => 'nullable|string',
                'addons_packages.*.room_type_id' => 'nullable|exists:hotel_room_types,id',
                'addons_packages.*.status' => 'nullable|in:active,inactive',
                'addons_packages.*.price' => 'nullable|numeric|min:0',
                'addons_packages.*.addon_values' => 'required_with:addons_packages|array',
                'addons_packages.*.addon_values.*.hotel_addon_field_id' => 'required|exists:hotel_addon_fields,id',
                'addons_packages.*.addon_values.*.value' => 'required',
                'addons_packages.*.addon_values.*.static_price' => 'nullable',
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
                'cancellation_period' => 'nullable|string|regex:/^(same_day_6pm|\\d+|\\d+_days)$/',
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
                $saveProperty->cancellation_period = ($request->cancellation_period ?? null) === 'on' ? null : ($request->cancellation_period ?: null);
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
                    // Check if columns exist before setting (in case migration hasn't been run)
                    if (\Schema::hasColumn('propertys', 'availability_type') && $request->has('availability_type')) {
                        $saveProperty->availability_type = $request->availability_type;
                    }
                    if (\Schema::hasColumn('propertys', 'available_dates') && $request->has('available_dates')) {
                        $saveProperty->available_dates = $request->available_dates;
                    } elseif ($request->has('available_dates') && !\Schema::hasColumn('propertys', 'available_dates')) {
                        // Log error if column doesn't exist (this is critical for new properties)
                        Log::error('available_dates column not found when creating vacation home property', [
                            'property_classification' => $request->property_classification,
                            'migration_needed' => '2025_07_15_000000_add_vacation_home_fields_to_properties.php'
                        ]);
                        throw new \Exception('Database migration required: available_dates column is missing. Please run migration: 2025_07_15_000000_add_vacation_home_fields_to_properties.php');
                    }
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

                // Set generic fields
                $saveProperty->check_in = $request->check_in;
                $saveProperty->check_out = $request->check_out;

                // Set hotel specific fields if property classification is hotel (5)
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    $saveProperty->refund_policy = $request->refund_policy ?? 'flexible';
                    $saveProperty->hotel_apartment_type_id = $request->hotel_apartment_type_id;
                    $saveProperty->available_rooms = $request->available_rooms;
                    $saveProperty->rent_package = $request->rent_package;
                    $saveProperty->revenue_user_name = $request->revenue_user_name ?? null;
                    $saveProperty->revenue_phone_number = $request->revenue_phone_number ?? null;
                    $saveProperty->revenue_email = $request->revenue_email ?? null;
                    $saveProperty->reservation_user_name = $request->reservation_user_name ?? null;
                    $saveProperty->reservation_phone_number = $request->reservation_phone_number ?? null;
                    $saveProperty->reservation_email = $request->reservation_email ?? null;
                    $saveProperty->hotel_vat = $request->hotel_vat ?? null;
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
                    \Illuminate\Support\Facades\Log::info('Gallery images found: ' . count($request->file('gallery_images')));
                    foreach ($request->file('gallery_images') as $file) {
                        $name = store_image($file, 'PROPERTY_GALLERY_IMG_PATH', $saveProperty->id);
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

                // Track custom room type IDs for mapping to packages
                $customRoomTypeMap = [];

                // START :: ADD HOTEL ROOMS
                if (isset($request->property_classification) && $request->property_classification == 5 && isset($request->hotel_rooms) && !empty($request->hotel_rooms)) {
                    try {
                        foreach ($request->hotel_rooms as $room) {
                            $roomTypeId = $room['room_type_id'] ?? null;
                            $customRoomType = $room['custom_room_type'] ?? null;
                            
                            // Handle custom room type
                            if (($roomTypeId === 'other' || empty($roomTypeId)) && !empty($customRoomType)) {
                                // Check if it already exists to avoid duplicates
                                $existingType = HotelRoomType::where('name', $customRoomType)->first();
                                if ($existingType) {
                                    $roomTypeId = $existingType->id;
                                } else {
                                    $newType = HotelRoomType::create([
                                        'name' => $customRoomType,
                                        'status' => 1
                                    ]);
                                    $roomTypeId = $newType->id;
                                }
                                // Map the custom name to the resolved ID
                                $customRoomTypeMap[$customRoomType] = $roomTypeId;
                            }

                            HotelRoom::create([
                                'property_id' => $saveProperty->id,
                                'room_type_id' => $roomTypeId,
                                'custom_room_type' => $customRoomType,
                                'room_number' => $room['room_number'],
                                'price_per_night' => (float)$room['price_per_night'],
                                'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                'availability_type' => isset($room['availability_type']) ? (int)$room['availability_type'] : null,
                                'available_dates' => isset($room['available_dates']) ? $room['available_dates'] : null,
                                'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : null,
                            'description' => $room['description'] ?? null,
                            'status' => $room['status'] ?? 1,
                            'max_guests' => isset($room['max_guests']) ? (int)$room['max_guests'] : 4,
                            'min_guests' => isset($room['min_guests']) ? (int)$room['min_guests'] : 1,
                            'base_guests' => isset($room['base_guests']) ? (int)$room['base_guests'] : 2,
                            'guest_pricing_rules' => (function() use ($room) {
                                $rules = isset($room['guest_pricing_rules']) ? $room['guest_pricing_rules'] : null;
                                if (is_string($rules)) {
                                    $decoded = json_decode($rules, true);
                                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $rules;
                                }
                                return $rules;
                            })()
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
                            
                            // Resolve room type ID for custom types
                            $pkgRoomTypeId = $package['room_type_id'] ?? null;
                            if (($pkgRoomTypeId === 'other' || empty($pkgRoomTypeId)) && !empty($package['custom_room_type'])) {
                                if (isset($customRoomTypeMap[$package['custom_room_type']])) {
                                    $pkgRoomTypeId = $customRoomTypeMap[$package['custom_room_type']];
                                } else {
                                    // Fallback: try to find existing type by name if not in current map
                                    $existingType = HotelRoomType::where('name', $package['custom_room_type'])->first();
                                    if ($existingType) {
                                        $pkgRoomTypeId = $existingType->id;
                                    }
                                }
                            }
                            
                            $addonsPackage->room_type_id = $pkgRoomTypeId;
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
                // Validate that property exists
                $UpdateProperty = Property::with('assignparameter.parameter')->find($id);
                if (!$UpdateProperty) {
                    return redirect()->route('property.index')->with('error', 'Property not found');
                }

                // Add validation for update method (more lenient than create)
                $validator = Validator::make($request->all(), [
                    'title'             => 'required|string|max:255',
                    'title_ar'          => 'nullable|string|max:255',
                    'description'       => 'required|string',
                    'description_ar'    => 'nullable|string',
                    'area_description'  => 'nullable|string',
                    'area_description_ar' => 'nullable|string',
                    'category'          => 'required',
                    'property_type'     => 'nullable|in:0,1,2',
                    'property_classification' => 'nullable|integer|between:1,5',
                    'address'           => 'required|string',
                    'price'             => 'nullable|numeric|min:0',
                    'refund_policy'     => 'nullable|in:flexible,non-refundable',
                    'title_image'       => 'nullable|file|max:3000|mimes:jpeg,png,jpg',
                    '3d_image'          => 'nullable|mimes:jpg,jpeg,png,gif|max:3000',
                    'documents.*'       => 'nullable|mimes:pdf,doc,docx,txt|max:5120',
                    'corresponding_day' => 'nullable',
                    'agent_addons'      => 'nullable',
                    'hotel_rooms'       => 'nullable|array',
                    'hotel_rooms.*.room_type_id' => 'nullable',
                    'hotel_rooms.*.custom_room_type' => 'nullable|string',
                    'hotel_rooms.*.price_per_night' => 'nullable|numeric|min:0',
                    'hotel_rooms.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                    'hotel_rooms.*.refund_policy' => 'nullable|in:flexible,non-refundable',
                    'hotel_rooms.*.nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
                    'hotel_rooms.*.description' => 'nullable|string',
                    'hotel_rooms.*.min_guests' => 'nullable|integer|min:1',
                    'hotel_rooms.*.base_guests' => 'nullable|integer|min:1',
                    'hotel_rooms.*.max_guests' => 'nullable|integer|min:1',
                    'hotel_rooms.*.guest_pricing_rules' => 'nullable|json',
                    'video_link' => ['nullable', function ($attribute, $value, $fail) {
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $youtubePattern = '/^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/';
                            if (!preg_match($youtubePattern, $value)) {
                                return $fail("The Video Link must be a valid YouTube URL.");
                            }
                        }
                    }],
                    'cancellation_period' => 'nullable|string|regex:/^(same_day_6pm|\\d+|\\d+_days)$/',
                ], [
                    'title.required' => 'Property title is required.',
                    'description.required' => 'Property description is required.',
                    'category.required' => 'Property category is required.',
                    'address.required' => 'Property address is required.',
                    'title_image.mimes' => 'Title image must be a JPEG, PNG, or JPG file.',
                    'title_image.max' => 'Title image must not exceed 3MB.',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    $firstError = $errors->first();
                    
                    Log::warning('Property update validation failed', [
                        'property_id' => $id,
                        'errors' => $errors->all(),
                        'request_keys' => array_keys($request->all()),
                        'request_data_sample' => array_slice($request->except(['title_image', '3d_image', 'documents', 'gallery_images']), 0, 10, true)
                    ]);
                    
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', 'Validation failed: ' . $firstError);
                }

                DB::beginTransaction();
                
                // Store original property data BEFORE any modifications
                $originalPropertyData = $UpdateProperty->getAttributes();
                unset($originalPropertyData['created_at'], $originalPropertyData['updated_at'], $originalPropertyData['id']);
                
                // Check if this is an owner edit (not admin)
                // 0 means admin, non-zero means owner/customer
                // Also check if current user is admin, if so, it is NOT an owner edit regardless of property owner
                // Assuming type 0 is admin (from User migration: 0:Admin 1:Users)
                $isAdminUser = \Auth::check() && \Auth::user()->type == 0; 
                $isOwnerEdit = !$isAdminUser && $UpdateProperty->added_by != 0;
                
                // Check auto-approve setting for edited listings
                $autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
                
                // Log initial state for debugging
                Log::info('Property update started', [
                    'property_id' => $id,
                    'is_owner_edit' => $isOwnerEdit,
                    'auto_approve_edited' => $autoApproveEdited,
                    'added_by' => $UpdateProperty->added_by,
                    'property_classification' => $UpdateProperty->property_classification ?? null
                ]);
                
                $destinationPath = public_path('images') . config('global.PROPERTY_TITLE_IMG_PATH');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $UpdateProperty->category_id = $request->category;
                $UpdateProperty->title = $request->title;
                if (\Schema::hasColumn('propertys', 'title_ar')) {
                    $UpdateProperty->title_ar = $request->title_ar ?? null;
                }
                $UpdateProperty->slug_id = $request->slug ?? generateUniqueSlug($request->title, 1, null, $id);
                $UpdateProperty->description = $request->description;
                if (\Schema::hasColumn('propertys', 'description_ar')) {
                    $UpdateProperty->description_ar = $request->description_ar ?? null;
                }
                if (\Schema::hasColumn('propertys', 'area_description')) {
                    $UpdateProperty->area_description = $request->area_description ?? null;
                }
                if (\Schema::hasColumn('propertys', 'area_description_ar')) {
                    $UpdateProperty->area_description_ar = $request->area_description_ar ?? null;
                }
                if (\Schema::hasColumn('propertys', 'company_employee_username')) {
                    $UpdateProperty->company_employee_username = $request->company_employee_username ?? null;
                }
                if (\Schema::hasColumn('propertys', 'company_employee_email')) {
                    $UpdateProperty->company_employee_email = $request->company_employee_email ?? null;
                }
                if (\Schema::hasColumn('propertys', 'company_employee_phone_number')) {
                    $UpdateProperty->company_employee_phone_number = $request->company_employee_phone_number ?? null;
                }
                $UpdateProperty->address = $request->address;
                $UpdateProperty->client_address = $request->client_address;
                if ($request->has('property_type')) {
                    $UpdateProperty->setAttribute('propery_type', $request->property_type);
                }
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
                if (\Schema::hasColumn('propertys', 'corresponding_day')) {
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
                }

                // Set vacation home specific fields if property classification is vacation_homes (4)
                // Only set these fields if property is actually a vacation home AND columns exist
                if (isset($request->property_classification) && $request->property_classification == 4) {
                    // Check if column exists before setting (in case migration hasn't been run)
                    if (\Schema::hasColumn('propertys', 'availability_type')) {
                        if ($request->has('availability_type') && $request->availability_type !== null) {
                            $UpdateProperty->availability_type = $request->availability_type;
                        }
                    }
                    if (\Schema::hasColumn('propertys', 'available_dates')) {
                        if ($request->has('available_dates') && $request->available_dates !== null && $request->available_dates !== '') {
                            $UpdateProperty->available_dates = $request->available_dates;
                        }
                    } else {
                        // Log warning if column doesn't exist
                        Log::warning('available_dates column not found in propertys table', [
                            'property_id' => $id,
                            'property_classification' => $request->property_classification,
                            'migration_needed' => '2025_07_15_000000_add_vacation_home_fields_to_properties.php',
                            'has_available_dates_in_request' => $request->has('available_dates')
                        ]);
                    }
                } else {
                    // If property is NOT a vacation home, ensure available_dates is not set
                    // This prevents errors if the request accidentally includes this field
                    if ($request->has('available_dates') && \Schema::hasColumn('propertys', 'available_dates')) {
                        // Only clear it if column exists, otherwise don't touch it
                        $UpdateProperty->available_dates = null;
                    }
                }

                // Set generic fields
                if (\Schema::hasColumn('propertys', 'check_in')) {
                    $UpdateProperty->check_in = $request->check_in ?? null;
                }
                if (\Schema::hasColumn('propertys', 'check_out')) {
                    $UpdateProperty->check_out = $request->check_out ?? null;
                }

                // Set instant_booking and non_refundable fields
                if (\Schema::hasColumn('propertys', 'instant_booking')) {
                    if ($request->has('instant_booking')) {
                        $UpdateProperty->instant_booking = $request->instant_booking;
                    }
                }
                if (\Schema::hasColumn('propertys', 'non_refundable')) {
                    if ($request->has('non_refundable')) {
                        $UpdateProperty->non_refundable = $request->non_refundable;
                    }
                }

                // Set hotel specific fields if property classification is hotel_booking (5)
                if (isset($request->property_classification) && $request->property_classification == 5) {
                    $UpdateProperty->refund_policy = $request->refund_policy ?? 'flexible';
                    $UpdateProperty->hotel_apartment_type_id = $request->hotel_apartment_type_id ?? null;
                    $UpdateProperty->available_rooms = $request->available_rooms ?? null;
                    $UpdateProperty->rent_package = $request->rent_package ?? null;
                    if (\Schema::hasColumn('propertys', 'revenue_user_name')) {
                        $UpdateProperty->revenue_user_name = $request->revenue_user_name ?? null;
                    }
                    if (\Schema::hasColumn('propertys', 'revenue_phone_number')) {
                        $UpdateProperty->revenue_phone_number = $request->revenue_phone_number ?? null;
                    }
                    if (\Schema::hasColumn('propertys', 'revenue_email')) {
                        $UpdateProperty->revenue_email = $request->revenue_email ?? null;
                    }
                    if (\Schema::hasColumn('propertys', 'reservation_user_name')) {
                        $UpdateProperty->reservation_user_name = $request->reservation_user_name ?? null;
                    }
                    if (\Schema::hasColumn('propertys', 'reservation_phone_number')) {
                        $UpdateProperty->reservation_phone_number = $request->reservation_phone_number ?? null;
                    }
                    if (\Schema::hasColumn('propertys', 'reservation_email')) {
                        $UpdateProperty->reservation_email = $request->reservation_email ?? null;
                    }
                    $UpdateProperty->hotel_vat = $request->hotel_vat ?? null;
                    $UpdateProperty->cancellation_period = ($request->cancellation_period ?? null) === 'on' ? null : ($request->cancellation_period ?: null);
                }

                // Handle agent_addons field (available for all property types)
                if (\Schema::hasColumn('propertys', 'agent_addons')) {
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

                // Initialize filteredEditedData to avoid undefined variable errors
                $filteredEditedData = [];
                
                // Check if owner edit requires approval
                if ($isOwnerEdit && !$autoApproveEdited) {
                    // Owner edits require admin approval when auto-approve is OFF - save as pending request
                    
                    Log::info('Checking for approval-required changes', [
                        'property_id' => $id,
                        'edited_data_keys' => array_keys($editedData)
                    ]);
                    
                    // Filter to only include allowed editable fields
                    $filteredEditedData = \App\Services\PropertyEditRequestService::filterAllowedFields($editedData, $UpdateProperty);
                    
                    // Handle gallery images for approval (Upload and add to filteredEditedData)
                    if ($request->hasFile('gallery_images')) {
                        $galleryImageNames = [];
                        $destinationPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $UpdateProperty->id;
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0777, true);
                        }
                        
                        foreach ($request->file('gallery_images') as $file) {
                            $name = store_image($file, 'PROPERTY_GALLERY_IMG_PATH', $UpdateProperty->id);
                            $galleryImageNames[] = $name;
                        }
                        
                        if (!empty($galleryImageNames)) {
                            $filteredEditedData['gallery_images'] = $galleryImageNames;
                            // We set hasChanges later, but let's ensure it's tracked
                        }
                    }

                    // Remove single image fields from edited data if no files were actually uploaded
                    // This prevents false positive change detection when users edit other fields
                    // Gallery images are handled separately through PropertyImages table and should not be in allowed fields
                    $singleImageFields = [
                        'title_image' => 'title_image',
                        'three_d_image' => '3d_image', 
                        'meta_image' => 'meta_image'
                    ];
                    
                    foreach ($singleImageFields as $dataField => $fileField) {
                        if (!$request->hasFile($fileField) && isset($filteredEditedData[$dataField])) {
                            unset($filteredEditedData[$dataField]);
                            Log::debug("Removed $dataField from filtered edited data - no file uploaded", [
                                'property_id' => $id,
                                'file_field' => $fileField
                            ]);
                        }
                    }
                    
                    Log::info('Filtered edited data after image field cleanup', [
                        'property_id' => $id,
                        'filtered_keys' => array_keys($filteredEditedData),
                        'filtered_data_sample' => array_slice($filteredEditedData, 0, 3, true)
                    ]);
                    
                    // Note: vacation_apartments are NOT editable by users - only admin can modify them
                    // They are NOT included in filteredEditedData and should not be tracked in edit requests
                    
                    // Include original hotel rooms in original data if property is hotel (for comparison purposes only)
                    if (isset($request->property_classification) && $request->property_classification == 5 && !empty($originalHotelRooms)) {
                        $originalPropertyData['hotel_rooms'] = $originalHotelRooms;
                    }
                    
                    // Only create edit request if there are actual changes in allowed fields
                    // Compare filtered edited data with original data to check for changes
                    $hasChanges = false;
                    $changeDetails = [];
                    
                    foreach ($filteredEditedData as $field => $value) {
                        // Skip hotel_rooms as it needs special comparison
                        if ($field === 'hotel_rooms') {
                            // Will be checked separately below
                            continue;
                        }
                        
                        // Normalize values for comparison
                        $originalValue = $originalPropertyData[$field] ?? null;
                        $normalizedOriginal = $this->normalizeValueForComparison($originalValue);
                        $normalizedNew = $this->normalizeValueForComparison($value);
                        
                        if ($normalizedOriginal !== $normalizedNew) {
                            $hasChanges = true;
                            $changeDetails[$field] = [
                                'original' => $normalizedOriginal,
                                'new' => $normalizedNew,
                                'original_type' => gettype($originalValue),
                                'new_type' => gettype($value)
                            ];
                            Log::debug('Field change detected', [
                                'property_id' => $id,
                                'field' => $field,
                                'original' => $normalizedOriginal,
                                'new' => $normalizedNew
                            ]);
                        }
                    }
                    
                    // Check gallery images changes
                    if (isset($filteredEditedData['gallery_images'])) {
                        $hasChanges = true;
                        $changeDetails['gallery_images'] = [
                            'original' => 'count: ' . PropertyImages::where('propertys_id', $id)->count(),
                            'new' => 'adding ' . count($filteredEditedData['gallery_images']) . ' images'
                        ];
                    }

                    // Also check hotel rooms for changes
                    if (!$hasChanges && isset($filteredEditedData['hotel_rooms']) && isset($originalPropertyData['hotel_rooms'])) {
                        $originalRoomsMap = [];
                        foreach ($originalPropertyData['hotel_rooms'] as $room) {
                            if (isset($room['id'])) {
                                $originalRoomsMap[$room['id']] = $this->normalizeValueForComparison($room['description'] ?? null);
                            }
                        }
                        foreach ($filteredEditedData['hotel_rooms'] as $room) {
                            $roomId = $room['id'] ?? null;
                            $roomDesc = $this->normalizeValueForComparison($room['description'] ?? null);
                            if (!isset($originalRoomsMap[$roomId]) || $originalRoomsMap[$roomId] !== $roomDesc) {
                                $hasChanges = true;
                                $changeDetails['hotel_rooms'] = [
                                    'room_id' => $roomId,
                                    'original' => $originalRoomsMap[$roomId] ?? null,
                                    'new' => $roomDesc
                                ];
                                Log::debug('Hotel room change detected', [
                                    'property_id' => $id,
                                    'room_id' => $roomId,
                                    'original' => $originalRoomsMap[$roomId] ?? null,
                                    'new' => $roomDesc
                                ]);
                                break;
                            }
                        }
                    }
                    
                    Log::info('Change detection completed', [
                        'property_id' => $id,
                        'has_changes' => $hasChanges,
                        'change_details' => $changeDetails,
                        'filtered_data_empty' => empty($filteredEditedData)
                    ]);
                    
                    if ($hasChanges && !empty($filteredEditedData)) {
                        // Store approval-required fields to revert later
                        $approvalRequiredFieldsToRevert = [];
                        $allowedFields = \App\Services\PropertyEditRequestService::getAllowedEditableFields();
                        foreach ($allowedFields as $field) {
                            if ($field === 'hotel_rooms') {
                                continue; // Hotel rooms handled separately
                            }
                            if (array_key_exists($field, $originalPropertyData)) {
                                $approvalRequiredFieldsToRevert[$field] = $originalPropertyData[$field];
                            }
                        }
                        
                        // Revert approval-required fields to original values before saving
                        foreach ($approvalRequiredFieldsToRevert as $field => $originalValue) {
                            $UpdateProperty->$field = $originalValue;
                        }
                        
                        // Note: Hotel room descriptions will be reverted after property save
                        // We'll handle this in a separate step to avoid issues with unsaved property
                        
                        Log::info('Reverted approval-required fields', [
                            'property_id' => $id,
                            'reverted_fields' => array_keys($approvalRequiredFieldsToRevert)
                        ]);
                        
                        // Use PropertyEditRequestService to save the edit request
                        try {
                            $editRequestService = new \App\Services\PropertyEditRequestService();
                            $editRequest = $editRequestService->saveEditRequest(
                                $UpdateProperty, 
                                $filteredEditedData,  // Only contains allowed fields now
                                $UpdateProperty->added_by, 
                                $originalPropertyData
                            );
                            
                            // Set request_status to pending
                            $UpdateProperty->request_status = 'pending';
                            
                            // Log the edit request creation
                            Log::info('Property edit request created from admin panel', [
                                'edit_request_id' => $editRequest->id,
                                'property_id' => $UpdateProperty->id,
                                'requested_by' => $UpdateProperty->added_by,
                                'fields_changed' => array_keys($filteredEditedData)
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to create property edit request', [
                                'property_id' => $id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                                'filtered_data' => $filteredEditedData
                            ]);
                            throw $e;
                        }
                    } else {
                        // No changes in allowed fields, save directly (don't create edit request)
                        Log::info('Property edit skipped - no changes in allowed fields', [
                            'property_id' => $UpdateProperty->id,
                            'requested_by' => $UpdateProperty->added_by,
                            'has_changes' => $hasChanges,
                            'filtered_data_empty' => empty($filteredEditedData)
                        ]);
                    }
                } else {
                    // Admin edit OR owner edit with auto-approve enabled - save directly (no approval needed)
                    // If auto-approve is enabled for owner edits, set request_status to approved
                    if ($isOwnerEdit && $autoApproveEdited) {
                        $UpdateProperty->request_status = 'approved';
                    }
                    
                    Log::info('Property edit - no approval required', [
                        'property_id' => $id,
                        'is_owner_edit' => $isOwnerEdit,
                        'auto_approve' => $autoApproveEdited
                    ]);
                }

                // Save the property (this will save even if edit request was created, but request_status will be pending)
                $UpdateProperty->save();
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
                    // Only process here if NOT handled by edit request (i.e. admin or auto-approve)
                    // If isOwnerEdit && !autoApproveEdited, images were already moved in the approval block above
                    if (!($isOwnerEdit && !$autoApproveEdited)) {
                        \Illuminate\Support\Facades\Log::info('Gallery images found in update: ' . count($request->file('gallery_images')));
                        foreach ($request->file('gallery_images') as $file) {
                            // dd('Inside gallery loop');
                            $name = store_image($file, 'PROPERTY_GALLERY_IMG_PATH', $UpdateProperty->id);

                            PropertyImages::create([
                                'image' => $name,
                                'propertys_id' => $UpdateProperty->id
                            ]);
                        }
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
                        // Check if we need to revert descriptions (approval required scenario)
                        $shouldRevertRoomDescriptions = false;
                        $revertDescriptionsMap = [];
                        if (isset($isOwnerEdit) && isset($autoApproveEdited) && $isOwnerEdit && !$autoApproveEdited && isset($originalHotelRooms) && !empty($originalHotelRooms)) {
                            // Check if descriptions were in filteredEditedData (meaning they changed and need approval)
                            if (isset($filteredEditedData['hotel_rooms']) && !empty($filteredEditedData['hotel_rooms'])) {
                                $shouldRevertRoomDescriptions = true;
                                // Create a map of original descriptions
                                foreach ($originalHotelRooms as $originalRoom) {
                                    if (isset($originalRoom['id'])) {
                                        $revertDescriptionsMap[$originalRoom['id']] = $originalRoom['description'] ?? null;
                                    }
                                }
                            }
                        }
                        
                        if ($shouldRevertRoomDescriptions) {
                            // Only update existing rooms, preserving original descriptions
                            foreach ($request->hotel_rooms as $room) {
                                if (isset($room['id'])) {
                                    $hotelRoom = \App\Models\HotelRoom::find($room['id']);
                                    if ($hotelRoom && $hotelRoom->property_id == $UpdateProperty->id) {
                                        // Update all fields except description (keep original)
                                        $roomTypeId = $room['room_type_id'] ?? $hotelRoom->room_type_id;
                                        
                                        // Handle custom room type
                                        if ($roomTypeId === 'other' && !empty($room['custom_room_type'])) {
                                            $existingType = HotelRoomType::where('name', $room['custom_room_type'])->first();
                                            if ($existingType) {
                                                $roomTypeId = $existingType->id;
                                            } else {
                                                $newType = HotelRoomType::create([
                                                    'name' => $room['custom_room_type'],
                                                    'status' => 1
                                                ]);
                                                $roomTypeId = $newType->id;
                                            }
                                        }

                                        $hotelRoom->room_type_id = $roomTypeId;
                                        $hotelRoom->room_number = $room['room_number'] ?? $hotelRoom->room_number;
                                        $hotelRoom->price_per_night = isset($room['price_per_night']) ? (float)$room['price_per_night'] : $hotelRoom->price_per_night;
                                        $hotelRoom->discount_percentage = isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : $hotelRoom->discount_percentage;
                                        $hotelRoom->refund_policy = $room['refund_policy'] ?? $hotelRoom->refund_policy;
                                        $hotelRoom->nonrefundable_percentage = isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : $hotelRoom->nonrefundable_percentage;
                                        $hotelRoom->availability_type = isset($room['availability_type']) ? (int)$room['availability_type'] : $hotelRoom->availability_type;
                                        $hotelRoom->available_dates = $room['available_dates'] ?? $hotelRoom->available_dates;
                                        $hotelRoom->weekend_commission = isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : $hotelRoom->weekend_commission;
                                        $hotelRoom->status = $room['status'] ?? $hotelRoom->status;
                                        $hotelRoom->max_guests = isset($room['max_guests']) ? (int)$room['max_guests'] : $hotelRoom->max_guests;
                                        $hotelRoom->min_guests = isset($room['min_guests']) ? (int)$room['min_guests'] : $hotelRoom->min_guests;
                                        $hotelRoom->base_guests = isset($room['base_guests']) ? (int)$room['base_guests'] : $hotelRoom->base_guests;
                                        $guestPricingRules = isset($room['guest_pricing_rules']) ? $room['guest_pricing_rules'] : $hotelRoom->guest_pricing_rules;
                                        if (is_string($guestPricingRules)) {
                                            $decoded = json_decode($guestPricingRules, true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $guestPricingRules = $decoded;
                                            }
                                        }
                                        $hotelRoom->guest_pricing_rules = $guestPricingRules;
                                        // Keep original description
                                        if (isset($revertDescriptionsMap[$room['id']])) {
                                            $hotelRoom->description = $revertDescriptionsMap[$room['id']];
                                        }
                                        $hotelRoom->save();
                                    }
                                }
                            }
                            
                            Log::info('Hotel room descriptions preserved for approval', [
                                'property_id' => $UpdateProperty->id,
                                'rooms_count' => count($request->hotel_rooms)
                            ]);
                        } else {
                            // Normal update - delete and recreate
                            \App\Models\HotelRoom::where('property_id', $UpdateProperty->id)->delete();
                            foreach ($request->hotel_rooms as $room) {
                                $roomTypeId = $room['room_type_id'] ?? null;
                                
                                // Handle custom room type
                                if ($roomTypeId === 'other' && !empty($room['custom_room_type'])) {
                                    $existingType = HotelRoomType::where('name', $room['custom_room_type'])->first();
                                    if ($existingType) {
                                        $roomTypeId = $existingType->id;
                                    } else {
                                        $newType = HotelRoomType::create([
                                            'name' => $room['custom_room_type'],
                                            'status' => 1
                                        ]);
                                        $roomTypeId = $newType->id;
                                    }
                                }

                                HotelRoom::create([
                                    'property_id' => $UpdateProperty->id,
                                    'room_type_id' => $roomTypeId,
                                    'room_number' => $room['room_number'] ?? null,
                                    'price_per_night' => isset($room['price_per_night']) ? (float)$room['price_per_night'] : 0,
                                    'discount_percentage' => isset($room['discount_percentage']) ? (float)$room['discount_percentage'] : 0,
                                    'refund_policy' => $room['refund_policy'] ?? 'flexible',
                                    'nonrefundable_percentage' => isset($room['nonrefundable_percentage']) ? (float)$room['nonrefundable_percentage'] : 0,
                                    'availability_type' => isset($room['availability_type']) ? (int)$room['availability_type'] : null,
                                    'available_dates' => $room['available_dates'] ?? null,
                                    'weekend_commission' => isset($room['weekend_commission']) ? (float)$room['weekend_commission'] : null,
                                    'description' => $room['description'] ?? null,
                                    'status' => $room['status'] ?? 1,
                                    'max_guests' => isset($room['max_guests']) ? (int)$room['max_guests'] : 4,
                                    'min_guests' => isset($room['min_guests']) ? (int)$room['min_guests'] : 1,
                                    'base_guests' => isset($room['base_guests']) ? (int)$room['base_guests'] : 2,
                                    'guest_pricing_rules' => (function() use ($room) {
                                        $rules = isset($room['guest_pricing_rules']) ? $room['guest_pricing_rules'] : null;
                                        if (is_string($rules)) {
                                            $decoded = json_decode($rules, true);
                                            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $rules;
                                        }
                                        return $rules;
                                    })()
                                ]);
                            }
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
                                        'static_price' => (isset($addon['static_price']) && is_numeric($addon['static_price'])) ? $addon['static_price'] : null,
                                        'multiply_price' => (isset($addon['multiply_price']) && is_numeric($addon['multiply_price'])) ? $addon['multiply_price'] : 1,
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
                try {
                    if ($isOwnerEdit && !$autoApproveEdited) {
                        return redirect()->back()->with('success', trans('Property edit request created successfully. Changes will be applied after admin approval.'));
                    } else {
                        return redirect()->back()->with('success', trans('Data Updated Successfully'));
                    }
                } catch (\Exception $redirectException) {
                    // If redirect fails, log it and redirect to property list as fallback
                    \Log::error('Redirect failed after property update', [
                        'property_id' => $id,
                        'error' => $redirectException->getMessage(),
                        'trace' => $redirectException->getTraceAsString()
                    ]);
                    return redirect()->route('property.index')->with('success', trans('Data Updated Successfully'));
                }
            } catch (Exception $e) {
                DB::rollBack();
                
                // Log detailed error information
                \Log::error('Property update failed', [
                    'property_id' => $id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => [
                        'city' => $request->city ?? null,
                        'property_classification' => $request->property_classification ?? null,
                        'title' => $request->title ?? null,
                        'category' => $request->category ?? null,
                    ]
                ]);
                
                // Provide user-friendly error message with more details
                $errorMessage = "An error occurred while updating the property.";
                $errorDetails = $e->getMessage();
                
                if (strpos($errorDetails, 'SQLSTATE') !== false) {
                    // Extract more specific database error information
                    if (strpos($errorDetails, 'Integrity constraint violation') !== false) {
                        $errorMessage = "Database constraint error. Please check that all related data is valid.";
                    } elseif (strpos($errorDetails, 'Column not found') !== false || 
                              strpos($errorDetails, 'Unknown column') !== false ||
                              preg_match("/Unknown column ['\"]([^'\"]+)['\"]/i", $errorDetails)) {
                        // Extract column name from error
                        $columnName = 'unknown';
                        if (preg_match("/Unknown column ['\"]([^'\"]+)['\"]/i", $errorDetails, $matches)) {
                            $columnName = $matches[1];
                        } elseif (preg_match("/Column ['\"]([^'\"]+)['\"]/i", $errorDetails, $matches)) {
                            $columnName = $matches[1];
                        }
                        
                        $errorMessage = "Database column error: Column '$columnName' not found. Please contact support.";
                        
                        // Log the actual error for debugging
                        Log::error('Column not found error', [
                            'property_id' => $id,
                            'column_name' => $columnName,
                            'full_error' => $errorDetails,
                            'error_class' => get_class($e),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'request_data_keys' => array_keys($request->except(['title_image', '3d_image', 'documents', 'gallery_images'])),
                            'property_attributes' => array_keys($UpdateProperty->getAttributes())
                        ]);
                    } elseif (strpos($errorDetails, 'Duplicate entry') !== false) {
                        $errorMessage = "Duplicate entry error. This data already exists.";
                    } else {
                        $errorMessage = "Database error occurred. Please check the data and try again.";
                    }
                } elseif (strpos($errorDetails, 'validation') !== false || strpos($errorDetails, 'required') !== false) {
                    $errorMessage = "Validation error: " . $errorDetails;
                } elseif (strpos($errorDetails, 'permission') !== false || strpos($errorDetails, 'unauthorized') !== false) {
                    $errorMessage = "You don't have permission to perform this action.";
                }
                
                // Log the full error for debugging
                Log::error('Property update error details', [
                    'property_id' => $id,
                    'error_message' => $errorDetails,
                    'error_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
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
            ->with('interested_users.customer:id,name,email,mobile')
            ->with('propertiesDocuments')
            ->with('propertyImages')
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

                // NEW: Add Cancellation Period button for Hotels
                if ($row->property_classification == 5) {
                    $cancellationButtonCustomClasses = ["btn", "icon", "btn-info", "btn-sm", "rounded-pill", "update-cancellation-period"];
                    $cancellationButtonCustomAttributes = [
                        "id" => $row->id, 
                        "title" => trans('Update Cancellation Period'), 
                        "data-id" => $row->id,
                        "data-cancellation-period" => $row->cancellation_period ?? '',
                    ];
                    $operate .= BootstrapTableService::button('bi bi-clock', '', $cancellationButtonCustomClasses, $cancellationButtonCustomAttributes);
                }
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
            $interested_users_details = [];
            foreach ($row->interested_users as $interested_user) {
                if ($interested_user->property_id == $row->id && $interested_user->customer) {
                    $interested_users_details[] = $interested_user->customer->toArray();
                }
            }
            $tempRow['interested_users_details'] = $interested_users_details;

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

            if (!$getImage) {
                return response()->json(['error' => true, 'message' => 'Image not found']);
            }

            $image = $getImage->image;
            $propertys_id =  $getImage->propertys_id;

            // Check for edit approval requirement
            $property = Property::find($propertys_id);
            if ($property) {
                $isAdminUser = \Auth::check() && \Auth::user()->type == 0; 
                $isOwnerEdit = !$isAdminUser && $property->added_by != 0;
                $autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
                
                if ($isOwnerEdit && !$autoApproveEdited) {
                    // Create/Update edit request for removal
                    try {
                        $editRequestService = new \App\Services\PropertyEditRequestService();
                        $editRequestService->saveEditRequest(
                            $property, 
                            ['removed_gallery_images' => [$id]], 
                            $property->added_by
                        );
                        
                        // Set request status to pending
                        $property->request_status = 'pending';
                        $property->save();
                        
                        return response()->json([
                            'error' => false, 
                            'message' => trans('Image removal requested. Pending admin approval.')
                        ]);
                    } catch (\Exception $e) {
                        return response()->json(['error' => true, 'message' => 'Failed to create removal request']);
                    }
                }
            }

            if (PropertyImages::where('id', $id)->delete()) {
                $imagePath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $propertys_id . "/" . $image;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $response['error'] = false;
            } else {
                $response['error'] = true;
            }

            $countImage = PropertyImages::where('propertys_id', $propertys_id)->count();
            if ($countImage == 0) {
                $dirPath = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $propertys_id;
                if (is_dir($dirPath)) {
                    rmdir($dirPath);
                }
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
            
            // Log for debugging
            Log::info('Property edit requests loaded', [
                'status' => $status,
                'count' => $editRequests->count(),
                'request_ids' => $editRequests->pluck('id')->toArray()
            ]);
            
            return view('property.edit-requests', compact('editRequests', 'status'));
        } catch (\Exception $e) {
            Log::error('Error loading property edit requests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('property.edit-requests', [
                'editRequests' => collect([]),
                'status' => $request->get('status', 'pending'),
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

                            // Check if rent_package is "basic" for renting properties
                            if (
                                isset($propertyData->rent_package) && $propertyData->rent_package == 'basic' &&
                                ($propertyData->getRawOriginal('property_classification') == 1  ||  $propertyData->getRawOriginal('property_classification') == 2) &&
                                $propertyData->getRawOriginal('propery_type') == 1
                            ) {
                                // Send additional basic package renting contract
                                $this->sendContractEmail($propertyData, "basic_package_renting");
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
     * Normalize value for comparison (handles null, empty string, type mismatches)
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValueForComparison($value)
    {
        // Convert null to empty string for consistent comparison
        if ($value === null) {
            return '';
        }
        
        // Handle JSON strings - decode if valid JSON
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Convert numeric strings to numbers for comparison
        if (is_string($value) && is_numeric($value)) {
            // Check if it's a float or int
            if (strpos($value, '.') !== false) {
                return (float)$value;
            }
            return (int)$value;
        }
        
        // Return as-is for other types
        return $value;
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
