<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class Projects extends Model
{
    use HasFactory, HasAppTimezone;
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'release_date'];
    protected $fillable = array(
        'title',
        'title_ar',
        'slug_id',
        'category_id',
        'description',
        'description_ar',
        'area_description',
        'area_description_ar',
        'location',
        'added_by',
        'is_admin_listing',
        'country',
        'state',
        'city',
        'latitude',
        'longitude',
        'video_link',
        'type',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',
        'status',
        'request_status',
        'total_click',
        'release_date',
        'bedroom',
        'bathroom',
        'garage',
        'year_built',
        'lot_size',
        // Agreements documents
        'ownership_contract',
        'national_id_passport',
        'alternative_id',
        'utilities_bills',
        'power_of_attorney',
        // Contact details
        'ownership_type',
        'admin_full_name',
        'admin_email',
        'admin_phone_number',
        'admin_whatsapp_number',
        'admin_address',
        'admin_profile_image',
        'company_employee_username',
        'company_employee_email',
        'company_employee_phone_number',
        'company_employee_whatsappnumber',
        'company_legal_name',
        'manager_name',
        'type_of_company',
        'company_email_address',
        'bank_branch',
        'bank_address',
        'company_country',
        'bank_account_number',
        'iban',
        'swift_code',
    );
    protected $appends = [
        'is_promoted',
        'is_feature_available'
    ];
    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($project) {
            if (collect($project)->isNotEmpty()) {
                // before delete() method call this

                // Delete Title Image
                if ($project->getRawOriginal('image') != '') {
                    $url = $project->image;
                    $relativePath = parse_url($url, PHP_URL_PATH);
                    if (file_exists(public_path()  . $relativePath)) {
                        unlink(public_path()  . $relativePath);
                    }
                }

                // Delete Gallery Image
                if (isset($project->gallery) && collect($project->gallery)->isNotEmpty()) {
                    foreach ($project->gallery as $row) {
                        if (ProjectDocuments::where('id', $row->id)->delete()) {
                            $image = $row->getRawOriginal('name');
                            if (file_exists(public_path('images') . config('global.PROJECT_DOCUMENT_PATH') . "/" . $image)) {
                                unlink(public_path('images') . config('global.PROJECT_DOCUMENT_PATH') . "/" . $image);
                            }
                        }
                    }
                }

                // Delete Documents
                if (isset($project->documents) && collect($project->documents)->isNotEmpty()) {
                    foreach ($project->documents as $row) {
                        if (ProjectDocuments::where('id', $row->id)->delete()) {
                            $file = $row->getRawOriginal('name');
                            if (file_exists(public_path('images') . config('global.PROJECT_DOCUMENT_PATH') . "/" . $file)) {
                                unlink(public_path('images') . config('global.PROJECT_DOCUMENT_PATH') . "/" . $file);
                            }
                        }
                    }
                }

                // Delete Floor Plans
                if (isset($project->floor_plans) && collect($project->floor_plans)->isNotEmpty()) {
                    foreach ($project->floor_plans as $row) {
                        unlink_image($row->document);
                        ProjectPlans::where('id', $row->id)->delete();
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
    public function project_documetns()
    {
        return $this->hasMany(ProjectDocuments::class, 'project_id');
    }
    public function gallary_images()
    {
        return $this->hasMany(ProjectDocuments::class, 'project_id')->where('type', 'image');
    }
    public function documents()
    {
        return $this->hasMany(ProjectDocuments::class, 'project_id')->where('type', 'doc');
    }
    public function plans()
    {
        return $this->hasMany(ProjectPlans::class, 'project_id');
    }

    public function reject_reason()
    {
        return $this->hasMany(RejectReason::class, 'project_id');
    }

    public function advertisement()
    {
        return $this->hasMany(Advertisement::class, 'project_id', 'id')->where('for', 'project');
    }

    public function getImageAttribute($image, $fullUrl = true)
    {
        if ($fullUrl) {
            return $image != '' ? url('') . config('global.IMG_PATH') . config('global.PROJECT_TITLE_IMG_PATH') . $image : '';
        } else {
            return $image;
        }
    }
    public function getMetaImageAttribute($image, $fullUrl = true)
    {
        if ($fullUrl) {
            return $image != '' ? url('') . config('global.IMG_PATH') . config('global.PROJECT_SEO_IMG_PATH') . $image : '';
        } else {
            return $image;
        }
    }


    public function getGallaryImagesDirectlyAttribute()
    {
        return $this->project_documetns()->where('type', 'image');
    }
    public function getDocumentsDirectlyAttribute()
    {
        return $this->project_documetns()->where('type', 'doc');
    }

    public function getIsPromotedAttribute()
    {
        $id = $this->id;
        return $this->whereHas('advertisement', function ($query) use ($id) {
            $query->where(['project_id' => $id, 'status' => 0, 'is_enable' => 1, 'for' => 'project']);
        })->count() ? true : false;
    }

    public function getIsFeatureAvailableAttribute()
    {
        $id = $this->id;

        // Check project type
        $isProjectTypeValid = $this->where('id', $this->id)->where(['status' => 1, 'request_status' => 'approved'])->exists();

        // Check if there is no advertisement or if the advertisement has expired
        $hasExpiredAdvertisement = !$this->advertisement()->exists() ||
            $this->whereHas('advertisement', function ($query) use ($id) {
                $query->where(['project_id' => $id, 'status' => 3, 'for' => 'project']);
            })->exists();

        // Check if there are any advertisements with other statuses
        $hasOtherStatusAdvertisement = $this->whereHas('advertisement', function ($query) use ($id) {
            $query->where('status', '!=', 3)->where(['project_id' => $id, 'for' => 'project']);
        })->exists();

        return $isProjectTypeValid && $hasExpiredAdvertisement && !$hasOtherStatusAdvertisement;
    }
}
