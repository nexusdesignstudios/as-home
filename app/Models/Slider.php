<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAppTimezone;

class Slider extends Model
{
    use HasFactory, HasAppTimezone;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = [
        'type',
        'image',
        'web_image',
        'sequence',
        'category_id',
        'propertys_id',
        'show_property_details',
        'link',
        'default_data'
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($slider) {
            if (collect($slider)->isNotEmpty()) {
                // before delete() method call this

                // Delete Image
                if ($slider->getRawOriginal('image') != '') {
                    $image = $slider->getRawOriginal('image');
                    if (file_exists(public_path('images') . config('global.SLIDER_IMG_PATH') . $image)) {
                        unlink(public_path('images') . config('global.SLIDER_IMG_PATH') . $image);
                    }
                }
            }
        });
    }

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'type' => 'string',
        'sequence' => 'integer',
    ];

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id')->select('id', 'category');
    }

    public function property()
    {
        return $this->hasOne(Property::class, 'id', 'propertys_id');
    }

    public function getImageAttribute($image)
    {
        if (!empty($image)) {
            // تحقق من نوع الـ storage
            $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

            if ($disk === 's3') {
                // إذا كان S3، ارجع الـ URL مباشرة
                return $this->rewriteImageUrl(url('') . config('global.IMG_PATH') . config('global.SLIDER_IMG_PATH') . $image);
            } else {
                // إذا كان local، تحقق من وجود الملف
                if (file_exists(public_path('images') . config('global.SLIDER_IMG_PATH') . $image)) {
                    return url('') . config('global.IMG_PATH') . config('global.SLIDER_IMG_PATH') . $image;
                }
            }
        }

        return url('assets/images/logo/slider-default.png');
    }

    public function getWebImageAttribute($webImage)
    {
        if (!empty($webImage)) {
            // تحقق من نوع الـ storage
            $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

            if ($disk === 's3') {
                // إذا كان S3، ارجع الـ URL مباشرة
                return $this->rewriteImageUrl(url('') . config('global.IMG_PATH') . config('global.SLIDER_IMG_PATH') . $webImage);
            } else {
                // إذا كان local، تحقق من وجود الملف
                if (file_exists(public_path('images') . config('global.SLIDER_IMG_PATH') . $webImage)) {
                    return url('') . config('global.IMG_PATH') . config('global.SLIDER_IMG_PATH') . $webImage;
                }
            }
        }

        return url('assets/images/logo/slider-default.png');
    }

    public function getTypeAttribute($value)
    {
        switch ($value) {
            case '1':
                return trans('Only Image');
                break;
            case '2':
                return trans('Category');
                break;
            case '3':
                return trans('Property');
                break;
            case '4':
                return trans('Other Link');
                break;
            default:
                return trans('Invalid');
                break;
        }
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
}
