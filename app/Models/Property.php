<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAppTimezone;
use App\Models\PropertyTerms;

class Property extends Model
{
    use HasFactory, HasAppTimezone;
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $table = 'propertys';

    protected $fillable = [
        'category_id',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'area_description',
        'area_description_ar',
        'company_employee_username',
        'company_employee_email',
        'company_employee_phone_number',
        'company_employee_whatsappnumber',
        'address',
        'client_address',
        'propery_type',
        'price',
        'title_image',
        'state',
        'country',
        'state',
        'status',
        'request_status',
        'total_click',
        'latitude',
        'longitude',
        'three_d_image',
        'is_premium',
        'property_classification',
        'policy_data',
        'identity_proof',
        'national_id_passport',
        'utilities_bills',
        'power_of_attorney',
        'fact_sheet',
        'weekend_commission',
        'availability_type',
        'available_dates',
        'refund_policy',
        'corresponding_day',
        'orientation_day_selected',
        'hotel_apartment_type_id',
        'rent_package',
        'check_in',
        'check_out',
        'agent_addons',
        'available_rooms',
        'revenue_user_name',
        'revenue_phone_number',
        'revenue_email',
        'reservation_user_name',
        'reservation_phone_number',
        'reservation_email',
        'instant_booking',
        'non_refundable',
        'hotel_vat',
        'hotel_available_rooms'
    ];
    protected $hidden = [
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [
        'gallery',
        'documents',
        'is_favourite',
        'hotel_rooms',
        'hotel_addons',
        'hotel_apartment_type',
        'certificates',
        'property_questions',
        'vacation_apartments'
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($property) {
            if (collect($property)->isNotEmpty()) {
                // before delete() method call this

                // Delete Title Image
                if ($property->getRawOriginal('title_image') != '') {
                    $url = $property->title_image;
                    $relativePath = parse_url($url, PHP_URL_PATH);
                    if (file_exists(public_path()  . $relativePath)) {
                        unlink(public_path()  . $relativePath);
                    }
                }

                // Delete 3D image
                if ($property->getRawOriginal('three_d_image') != '') {
                    $url = $property->three_d_image;
                    $relativePath = parse_url($url, PHP_URL_PATH);
                    if (file_exists(public_path()  . $relativePath)) {
                        unlink(public_path()  . $relativePath);
                    }
                }

                // Delete Gallery Image
                if (isset($property->gallery) && collect($property->gallery)->isNotEmpty()) {
                    $galleryImagePath = url('') . config('global.IMG_PATH') . config('global.PROPERTY_GALLERY_IMG_PATH') . $property->id;
                    foreach ($property->gallery as $row) {
                        if (PropertyImages::where('id', $row->id)->delete()) {
                            if ($row->image != '') {
                                $url = $galleryImagePath . "/" . $row->image;
                                $relativePath = parse_url($url, PHP_URL_PATH);
                                $relativePath = parse_url($url, PHP_URL_PATH);

                                if (file_exists(public_path()  . $relativePath)) {
                                    unlink(public_path()  . $relativePath);
                                }
                            }
                        }
                    }
                    if (is_dir(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $property->id)) {
                        rmdir(public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . $property->id);
                    }
                }

                // Delete Documents
                if (isset($property->documents) && collect($property->documents)->isNotEmpty()) {
                    foreach ($property->documents as $row) {
                        if (PropertiesDocument::where('id', $row->id)->delete()) {
                            if ($row->getRawOriginal('name') != '') {
                                if (file_exists(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $property->id . "/" . $row->getRawOriginal('name'))) {
                                    unlink(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $property->id . "/" . $row->getRawOriginal('name'));
                                }
                            }
                        }
                    }
                    if (is_dir(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $property->id)) {
                        rmdir(public_path('images') . config('global.PROPERTY_DOCUMENT_PATH') . $property->id);
                    }
                }

                // Delete Certificates
                if (isset($property->certificates) && collect($property->certificates)->isNotEmpty()) {
                    foreach ($property->certificates as $certificate) {
                        $certificateFile = $certificate->getRawOriginal('file');
                        if (!empty($certificateFile)) {
                            $filePath = public_path('images') . config('global.PROPERTY_CERTIFICATE_PATH') . $certificateFile;
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        $certificate->delete();
                    }
                }

                /** Delete the properties associated data */
                // Delete Directly without modal boot events
                Advertisement::where('property_id', $property->id)->delete();
                AssignedOutdoorFacilities::where('property_id', $property->id)->delete();
                Favourite::where('property_id', $property->id)->delete();
                AssignParameters::where('property_id', $property->id)->delete();
                InterestedUser::where('property_id', $property->id)->delete();
                PropertysInquiry::where('propertys_id', $property->id)->delete();
                user_reports::where('property_id', $property->id)->delete();
                InterestedUser::where('property_id', $property->id)->delete();

                // Delete hotel rooms
                HotelRoom::where('property_id', $property->id)->delete();

                // Delete The Data with modal boot events
                $chats = Chats::where('property_id', $property->id)->get();
                if (collect($chats)->isNotEmpty()) {
                    foreach ($chats as $chat) {
                        if (collect($chat)->isNotEmpty()) {
                            $chat->delete(); // This will trigger the deleting and deleted events in modal
                        }
                    }
                }
                $sliders = Slider::where('propertys_id', $property->id)->get();
                if (collect($sliders)->isNotEmpty()) {
                    foreach ($sliders as $slider) {
                        if (collect($slider)->isNotEmpty()) {
                            $slider->delete(); // This will trigger the deleting and deleted events in modal
                        }
                    }
                }
                $notifications = Notifications::where('propertys_id', $property->id)->get();
                if (collect($notifications)->isNotEmpty()) {
                    foreach ($notifications as $notification) {
                        if (collect($notification)->isNotEmpty()) {
                            $notification->delete(); // This will trigger the deleting and deleted events in modal
                        }
                    }
                }
            }
        });
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id')->select('id', 'category', 'parameter_types', 'image');
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'added_by');
    }
    public function user()
    {
        return $this->hasMany(User::class, 'id', 'added_by');
    }

    public function assignParameter()
    {
        return  $this->morphMany(AssignParameters::class, 'modal');
    }

    public function parameters()
    {
        return $this->belongsToMany(parameter::class, 'assign_parameters', 'modal_id', 'parameter_id')->withPivot('value');
    }
    public function assignfacilities()
    {
        return $this->hasMany(AssignedOutdoorFacilities::class, 'property_id', 'id');
    }

    public function favourite()
    {
        return $this->hasMany(Favourite::class, 'property_id', 'id');
    }
    public function interested_users()
    {
        return $this->hasMany(InterestedUser::class, 'property_id');
    }

    public function advertisement()
    {
        return $this->hasMany(Advertisement::class)->where('for', 'property');
    }

    public function reject_reason()
    {
        return $this->hasMany(RejectReason::class, 'property_id');
    }

    /**
     * Get the property images for this property.
     */
    public function propertyImages()
    {
        return $this->hasMany(PropertyImages::class, 'propertys_id', 'id');
    }

    /**
     * Get the property documents for this property.
     */
    public function propertiesDocuments()
    {
        return $this->hasMany(PropertiesDocument::class, 'property_id', 'id');
    }

    /**
     * Get the hotel rooms for this property.
     */
    public function hotelRooms()
    {
        return $this->hasMany(HotelRoom::class);
    }

    /**
     * Get the vacation apartments for this property.
     */
    public function vacationApartments()
    {
        return $this->hasMany(VacationApartment::class);
    }

    /**
     * Get hotel rooms attribute for API response
     */
    public function getHotelRoomsAttribute()
    {
        // Only return hotel rooms if this is a hotel property
        if ($this->getRawOriginal('property_classification') == 5) {
            return $this->hotelRooms()->with('roomType')->get();
        }

        return null;
    }

    /**
     * Get vacation apartments attribute for API response
     */
    public function getVacationApartmentsAttribute()
    {
        // Only return vacation apartments if this is a vacation home property
        if ($this->getRawOriginal('property_classification') == 4) {
            return $this->vacationApartments()->get();
        }

        return null;
    }

    public function getGalleryAttribute()
    {
        $data = PropertyImages::select('id', 'image')->where('propertys_id', $this->id)->get();


        foreach ($data as $item) {
            if ($item['image'] != '') {
                $item['image'] = $item['image'];
                $item['image_url'] = ($item['image'] != '') ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_GALLERY_IMG_PATH') . $this->id . "/" . $item['image'] : '';
            }
        }
        return $data;
    }
    public function getTitleImageAttribute($image)
    {
        return $image != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_TITLE_IMG_PATH') . $image : '';
    }

    public function setTitleImageAttribute($value)
    {
        $this->attributes['title_image'] = $value;
    }

    public function getMetaImageAttribute($image)
    {
        return $image != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_SEO_IMG_PATH') . $image : '';
    }

    public function setMetaImageAttribute($value)
    {
        $this->attributes['meta_image'] = $value;
    }

    public function getThreeDImageAttribute($image)
    {
        return $image != '' ? url('') . config('global.IMG_PATH') . config('global.3D_IMG_PATH') . $image : '';
    }

    public function setThreeDImageAttribute($value)
    {
        $this->attributes['three_d_image'] = $value;
    }

    public function getPolicyDataAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_DOCUMENT_PATH') . $value : '';
    }

    public function setPolicyDataAttribute($value)
    {
        $this->attributes['policy_data'] = $value;
    }

    public function getIdentityProofAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_IDENTITY_PROOF_PATH') . $value : '';
    }

    public function setIdentityProofAttribute($value)
    {
        $this->attributes['identity_proof'] = $value;
    }

    public function getNationalIdPassportAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_NATIONAL_ID_PATH') . $value : '';
    }

    public function setNationalIdPassportAttribute($value)
    {
        $this->attributes['national_id_passport'] = $value;
    }

    public function getUtilitiesBillsAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_UTILITIES_PATH') . $value : '';
    }

    public function setUtilitiesBillsAttribute($value)
    {
        $this->attributes['utilities_bills'] = $value;
    }

    public function getPowerOfAttorneyAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_POA_PATH') . $value : '';
    }

    public function setPowerOfAttorneyAttribute($value)
    {
        $this->attributes['power_of_attorney'] = $value;
    }

    public function getFactSheetAttribute($value)
    {
        return $value != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_FACT_SHEET_PATH') . $value : '';
    }

    public function setFactSheetAttribute($value)
    {
        $this->attributes['fact_sheet'] = $value;
    }

    public function getProperyTypeAttribute($value)
    {
        if ($value == 0) {
            return "sell";
        } elseif ($value == 1) {
            return "rent";
        } elseif ($value == 2) {
            return "sold";
        } elseif ($value == 3) {
            return "rented";
        }
    }

    public function setProperyTypeAttribute($value)
    {
        $this->attributes['propery_type'] = $value;
    }

    public function getPropertyClassificationAttribute($value)
    {
        switch ($value) {
            case 1:
                return "sell_rent";
            case 2:
                return "commercial";
            case 3:
                return "new_project";
            case 4:
                return "vacation_homes";
            case 5:
                return "hotel_booking";
            default:
                return null;
        }
    }

    public function getIsPromotedAttribute()
    {
        $id = $this->id;
        return $this->whereHas('advertisement', function ($query) use ($id) {
            $query->where(['property_id' => $id, 'status' => 0, 'is_enable' => 1]);
        })->count() ? true : false;
    }

    public function getHomePromotedAttribute()
    {
        $id = $this->id;
        return $this->whereHas('advertisement', function ($query) use ($id) {
            $query->where(['property_id' => $id, 'type' => 'HomeScreen', 'status' => 0, 'is_enable' => 1]);
        })->count() ? true : false;
    }

    public function getListPromotedAttribute()
    {
        $id = $this->id;
        return $this->whereHas('advertisement', function ($query) use ($id) {
            $query->where(['property_id' => $id, 'type' => 'ProductListing', 'status' => 0, 'is_enable' => 1]);
        })->count() ? true : false;
    }

    public function getIsFavouriteAttribute()
    {
        $propertyId = $this->id;
        $auth = Auth::guard('sanctum');
        if ($auth->check()) {
            $userId = $auth->user()->id;
            return $this->whereHas('favourite', function ($query) use ($userId, $propertyId) {
                $query->where(['user_id' => $userId, 'property_id' => $propertyId]);
            })->count() >= 1 ? 1 : 0;
        }
        return 0;
    }

    public function getParametersAttribute()
    {

        $parameterQueryData = $this->parameters()->get();
        if (isset($parameterQueryData) && !empty($parameterQueryData)) {
            $parameters = [];
            foreach ($parameterQueryData as $res) {
                $res = (object)$res;
                if (is_string($res['pivot']['value']) && is_array(json_decode($res['pivot']['value'], true))) {
                    $value = json_decode($res['pivot']['value'], true);
                } else {
                    if ($res['type_of_parameter'] == "file") {
                        if ($res['pivot']['value'] == "null") {
                            $value = "";
                        } else {
                            $value = url('') . config('global.IMG_PATH') . config('global.PARAMETER_IMG_PATH') . '/' .  $res['pivot']['value'];
                        }
                    } else {
                        if ($res['pivot']['value'] == "null") {
                            $value = "";
                        } else {
                            $value = $res['pivot']['value'];
                        }
                    }
                }

                if (collect($value)->isNotEmpty()) {
                    $parameters[] = [
                        'id' => $res->id,
                        'name' => $res->name,
                        'image' => $res->image,
                        'is_required' => $res->is_required,
                        'type_of_parameter' => $res->type_of_parameter,
                        'type_values' => $res->type_values,
                        'value' => $value,
                    ];
                }
            }
        }
        return $parameters ?? null;
    }
    public function getAssignFacilitiesAttribute()
    {
        $assignFacilitiesQuery = $this->assignfacilities()->with('outdoorfacilities:id,name,image')->get();
        if (collect($assignFacilitiesQuery)->isNotEmpty()) {
            $assignFacilitiesData = [];
            foreach ($assignFacilitiesQuery as $facility) {
                if (collect($facility->outdoorfacilities)->isNotEmpty()) {
                    $assignFacilitiesData[] = [
                        'id' => $facility->id,
                        'property_id' => $facility->property_id,
                        'facility_id' => $facility->facility_id,
                        'distance' => $facility->distance,
                        'created_at' => $facility->created_at,
                        'updated_at' => $facility->updated_at,
                        'name' => $facility->outdoorfacilities->name,
                        'image' => $facility->outdoorfacilities->image,
                    ];
                }
            }
        }
        return !empty($assignFacilitiesData) ? $assignFacilitiesData :  array();
    }


    public function getDocumentsAttribute()
    {
        return PropertiesDocument::select('id', 'property_id', 'name', 'type')
            ->where('property_id', $this->id)
            ->whereNull('deleted_at') // Only get non-deleted documents
            ->get()
            ->map(function ($document) {
                $document->id = $document->id;
                $document->file_name = $document->getRawOriginal('name');
                $document->file = $document->name;
                unset($document->name);
                return $document;
            });
    }

    public function getIsUserVerifiedAttribute()
    {
        return $this->whereHas('customer.verify_customer', function ($query) {
            $query->where(['user_id' => $this->added_by, 'status' => 'success']);
        })->count() ? true : false;
    }

    public function getIsFeatureAvailableAttribute()
    {
        $id = $this->id;

        // Check if the property type is 0 or 1
        // Allow booking for pending properties - removed request_status='approved' requirement
        $isPropertyTypeValid = $this->where('id', $this->id)
            ->whereIn('propery_type', [0, 1])->where(['status' => 1])
            ->exists();

        // Check if there is no advertisement or if the advertisement has expired
        $hasExpiredAdvertisement = !$this->advertisement()->exists() ||
            $this->whereHas('advertisement', function ($query) use ($id) {
                $query->where('property_id', $id)->where('status', 3);
            })->exists();

        return $isPropertyTypeValid && $hasExpiredAdvertisement;
    }

    public function getAvailabilityTypeAttribute($value)
    {
        switch ($value) {
            case 1:
                return "available_days";
            case 2:
                return "busy_days";
            default:
                return null;
        }
    }

    public function setAvailabilityTypeAttribute($value)
    {
        $this->attributes['availability_type'] = $value;
    }

    public function getAvailableDatesAttribute($value)
    {
        $decodedValue = $value ? (is_string($value) ? json_decode($value, true) : $value) : [];

        // Ensure proper structure with type field
        if (is_array($decodedValue)) {
            foreach ($decodedValue as $key => $dateInfo) {
                if (is_array($dateInfo)) {
                    // Ensure each date entry has the required fields
                    // Convert empty string or null price to 0, keep numeric values as-is
                    if (!isset($dateInfo['price']) || $dateInfo['price'] === '' || $dateInfo['price'] === null) {
                        $decodedValue[$key]['price'] = 0;
                    } else {
                        // Ensure price is numeric (convert string numbers to integers/floats)
                        $decodedValue[$key]['price'] = is_numeric($dateInfo['price']) ? (float)$dateInfo['price'] : 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        // Set default type based on availability_type
                        if ($this->availability_type === 'busy_days') {
                            $decodedValue[$key]['type'] = 'dead';
                        } else {
                            $decodedValue[$key]['type'] = 'open';
                        }
                    }
                    // Ensure type is one of the allowed values
                    $allowedTypes = ['dead', 'open', 'reserved'];
                    if (!in_array($decodedValue[$key]['type'], $allowedTypes)) {
                        if ($this->availability_type === 'busy_days') {
                            $decodedValue[$key]['type'] = 'dead';
                        } else {
                            $decodedValue[$key]['type'] = 'open';
                        }
                    }
                    // If type is reserved, ensure reservation_id exists
                    if ($decodedValue[$key]['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
                        $decodedValue[$key]['reservation_id'] = null;
                    }
                } else {
                    // If the date entry is not an array, convert it to one with defaults
                    $defaultType = ($this->availability_type === 'busy_days') ? 'dead' : 'open';
                    $decodedValue[$key] = [
                        'price' => 0,
                        'type' => $defaultType
                    ];
                }
            }
        }

        return $decodedValue;
    }

    public function setAvailableDatesAttribute($value)
    {
        // Ensure each date entry has the required fields (price, type, reservation_id if applicable)
        if (is_array($value)) {
            foreach ($value as $key => $dateInfo) {
                // Make sure each date entry is an array with at least price and type
                if (is_array($dateInfo)) {
                    // Set defaults if not provided
                    // Ensure price is always numeric (0 or positive number), convert empty strings/null to 0
                    if (!isset($dateInfo['price']) || $dateInfo['price'] === '' || $dateInfo['price'] === null) {
                        $value[$key]['price'] = 0;
                    } else {
                        // Ensure price is numeric (convert string numbers to float)
                        $value[$key]['price'] = is_numeric($dateInfo['price']) ? (float)$dateInfo['price'] : 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        $value[$key]['type'] = 'open';
                    } else {
                        // Ensure type is one of the allowed values
                        $allowedTypes = ['dead', 'open', 'reserved'];
                        if (!in_array($dateInfo['type'], $allowedTypes)) {
                            $value[$key]['type'] = 'open';
                        }

                        // If type is reserved, ensure reservation_id exists
                        if ($dateInfo['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
                            $value[$key]['reservation_id'] = null;
                        }
                    }
                } else {
                    // If the date entry is not an array, convert it to one with defaults
                    $value[$key] = [
                        'price' => 0,
                        'type' => 'open'
                    ];
                }
            }
        }

        $this->attributes['available_dates'] = is_array($value) ? json_encode($value) : (is_string($value) ? $value : json_encode([]));
    }

    protected $casts = [
        'category_id' => 'integer',
        'status' => 'integer',
        'property_classification' => 'integer',
        'availability_type' => 'integer',
        'corresponding_day' => 'json',
        'agent_addons' => 'json',
        'instant_booking' => 'boolean',
        'non_refundable' => 'boolean'
    ];

    /**
     * Scope a query to filter properties by classification.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $classification
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClassification($query, $classification)
    {
        return $query->where('property_classification', $classification);
    }

    /**
     * Get the terms and conditions for this property's classification.
     */
    public function terms()
    {
        return PropertyTerms::where('classification_id', $this->property_classification)->first();
    }

    /**
     * Get the hotel addon values for this property.
     */
    public function hotelAddonValues()
    {
        return $this->hasMany(PropertyHotelAddonValue::class);
    }

    /**
     * Get hotel addon fields as an attribute for API response
     */
    public function getHotelAddonsAttribute()
    {
        // Only return hotel addons if this is a hotel property
        if ($this->getRawOriginal('property_classification') == 5) {
            $addonValues = $this->hotelAddonValues()
                ->with('hotel_addon_field:id,name,field_type')
                ->get();

            if ($addonValues->isNotEmpty()) {
                $addons = [];
                foreach ($addonValues as $addonValue) {
                    $fieldType = $addonValue->hotel_addon_field->field_type;
                    $value = $addonValue->value;

                    // Process value based on field type
                    if ($fieldType == 'file') {
                        $value = url('') . config('global.IMG_PATH') . config('global.HOTEL_ADDON_PATH') . '/' . $value;
                    } elseif ($fieldType == 'checkbox') {
                        $value = json_decode($value, true);
                    }

                    $addons[] = [
                        'id' => $addonValue->hotel_addon_field_id,
                        'name' => $addonValue->hotel_addon_field->name,
                        'field_type' => $fieldType,
                        'value' => $value,
                    ];
                }
                return $addons;
            }
        }

        return null;
    }

    /**
     * Get the hotel apartment type for this property.
     */
    public function hotelApartmentType()
    {
        return $this->belongsTo(HotelApartmentType::class);
    }

    /**
     * Get hotel apartment type attribute for API response
     */
    public function getHotelApartmentTypeAttribute()
    {
        // Only return hotel apartment type if this is a hotel property
        if ($this->getRawOriginal('property_classification') == 5) {
            return $this->hotelApartmentType()->select('id', 'name', 'description')->first();
        }

        return null;
    }

    /**
     * Get the certificates for this property.
     */
    public function certificates()
    {
        return $this->hasMany(PropertyCertificate::class);
    }

    /**
     * Get certificates attribute for API response
     */
    public function getCertificatesAttribute()
    {
        // Only return certificates if this is a hotel property
        if ($this->getRawOriginal('property_classification') == 5) {
            return $this->certificates()->get();
        }

        return null;
    }

    /**
     * Get terms and conditions for a specific classification.
     *
     * @param int $classificationId
     * @return \App\Models\PropertyTerms|null
     */
    public static function getTermsByClassification($classificationId)
    {
        return PropertyTerms::where('classification_id', $classificationId)->first();
    }

    /**
     * Get the rent package attribute.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getRentPackageAttribute($value)
    {
        return $value;
    }

    /**
     * Set the rent package attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setRentPackageAttribute($value)
    {
        // Ensure value is either 'basic' or 'premium'
        if ($value && !in_array($value, ['basic', 'premium'])) {
            $value = 'basic'; // Default to basic if invalid value
        }

        $this->attributes['rent_package'] = $value;
    }

    /**
     * Get the check_in attribute.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getCheckInAttribute($value)
    {
        return $value;
    }

    /**
     * Set the check_in attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setCheckInAttribute($value)
    {
        $this->attributes['check_in'] = $value;
    }

    /**
     * Get the check_out attribute.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getCheckOutAttribute($value)
    {
        return $value;
    }

    /**
     * Set the check_out attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setCheckOutAttribute($value)
    {
        $this->attributes['check_out'] = $value;
    }

    /**
     * Get the corresponding_day attribute.
     *
     * @param  string|null  $value
     * @return array|null
     */
    public function getCorrespondingDayAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the corresponding_day attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setCorrespondingDayAttribute($value)
    {
        $this->attributes['corresponding_day'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the orientation_day_selected attribute.
     *
     * @param  string|null  $value
     * @return array|null
     */
    public function getOrientationDaySelectedAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * Set the orientation_day_selected attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setOrientationDaySelectedAttribute($value)
    {
        $this->attributes['orientation_day_selected'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the agent_addons attribute.
     *
     * @param  string|null  $value
     * @return array|null
     */
    public function getAgentAddonsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the agent_addons attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setAgentAddonsAttribute($value)
    {
        $this->attributes['agent_addons'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the reservations for this property as a reservable.
     */
    public function reservations()
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    /**
     * Get all reservations associated with this property (both direct and via hotel rooms).
     */
    public function allReservations()
    {
        return $this->hasMany(Reservation::class, 'property_id');
    }

    public function addons_packages()
    {
        return $this->hasMany(AddonsPackage::class);
    }

    public function getAddonsPackagesAttribute()
    {
        // Only return addon packages if this is a hotel property
        if ($this->getRawOriginal('property_classification') == 5) {
            return $this->addons_packages()
                ->with([
                    'addon_values' => function ($query) {
                        $query->with('hotel_addon_field:id,name,field_type');
                    },
                    'hotel_room_type:id,name'
                ])
                ->get()
                ->map(function ($package) {
                    $packageData = [
                        'id' => $package->id,
                        'name' => $package->name,
                        'description' => $package->description,
                        'price' => $package->price,
                        'status' => $package->status,
                        'room_type' => $package->hotel_room_type,
                        'room_type_id' => $package->room_type_id,
                        'property_id' => $package->property_id,
                        'addons' => []
                    ];

                    // Process addon values for this package
                    foreach ($package->addon_values as $addonValue) {
                        if ($addonValue->hotel_addon_field) {
                            $fieldType = $addonValue->hotel_addon_field->field_type;
                            $value = $addonValue->value;

                            // Process value based on field type
                            if ($fieldType == 'file' && !empty($value)) {
                                $value = url('') . config('global.IMG_PATH') . config('global.HOTEL_ADDON_PATH') . '/' . $value;
                            } elseif ($fieldType == 'checkbox') {
                                $value = json_decode($value, true) ?: [];
                            }

                            $packageData['addons'][] = [
                                'field_id' => $addonValue->hotel_addon_field_id,
                                'field_name' => $addonValue->hotel_addon_field->name,
                                'field_type' => $fieldType,
                                'value' => $value,
                                'static_price' => $addonValue->static_price,
                                'multiply_price' => $addonValue->multiply_price,
                            ];
                        }
                    }

                    return $packageData;
                });
        }

        return collect([]);
    }

    /**
     * Get the property question answers for this property.
     */
    public function propertyQuestionAnswers()
    {
        return $this->hasMany(PropertyQuestionAnswer::class);
    }

    /**
     * Get property question answers as an attribute for API response
     * based on property classification
     */
    public function getPropertyQuestionsAttribute()
    {
        $classification = $this->getRawOriginal('property_classification');

        $answers = $this->propertyQuestionAnswers()
            ->with('property_question_field:id,name,field_type')
            ->whereHas('property_question_field', function ($query) use ($classification) {
                $query->where('property_classification', $classification);
            })
            ->get();

        if ($answers->isNotEmpty()) {
            $questions = [];
            foreach ($answers as $answer) {
                $fieldType = $answer->property_question_field->field_type;
                $value = $answer->value;

                // Process value based on field type
                if ($fieldType == 'file') {
                    $value = url('') . config('global.IMG_PATH') . config('global.PROPERTY_QUESTION_PATH') . '/' . $value;
                } elseif ($fieldType == 'checkbox') {
                    $value = json_decode($value, true);
                }

                $questions[] = [
                    'id' => $answer->property_question_field_id,
                    'name' => $answer->property_question_field->name,
                    'field_type' => $fieldType,
                    'value' => $value,
                ];
            }
            return $questions;
        }

        return null;
    }

    /**
     * Get the instant_booking attribute.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function getInstantBookingAttribute($value)
    {
        return (bool) $value;
    }

    /**
     * Set the instant_booking attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setInstantBookingAttribute($value)
    {
        $this->attributes['instant_booking'] = (bool) $value;
    }

    /**
     * Get the non_refundable attribute.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function getNonRefundableAttribute($value)
    {
        return (bool) $value;
    }

    /**
     * Set the non_refundable attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setNonRefundableAttribute($value)
    {
        $this->attributes['non_refundable'] = (bool) $value;
    }
}
