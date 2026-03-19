<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasAppTimezone;

class Category extends Model
{
    use HasFactory, HasAppTimezone;
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $table = 'categories';

    protected $fillable = [
        'category',
        'image',
        'status',
        'sequence',
        'parameter_types',
        'property_classification',
        'slug_id'
    ];
    protected $hidden = [
        'updated_at'
    ];

    public function getParametersAttribute()
    {
        $parameterTypes = explode(',', $this->parameter_types);
        if (!empty($parameterTypes)) {
            $parameters = parameter::whereIn('id', $parameterTypes)->get();
            $sortedParameters = $parameters->sortBy(function ($item) use ($parameterTypes) {
                return array_search($item->id, $parameterTypes);
            });
            return $sortedParameters;
        }
        return [];
    }

    public function parameter()
    {
        return $this->hasMany(parameter::class, 'id', 'parameter_types');
    }
    public function properties()
    {
        return $this->hasMany(Property::class, 'category_id', 'id');
    }

    public function getImageAttribute($image)
    {
        if ($image === null || $image === '') {
            return '';
        }

        $relativePath = 'images/' . trim(config('global.CATEGORY_IMG_PATH'), '/') . '/' . $image;
        $disk = config('filesystems.default', 'local');

        if ($disk === 's3') {
            try {
                return Storage::disk('s3')->url($relativePath);
            } catch (\Throwable $e) {
            }
        }

        $fullLocalPath = public_path($relativePath);
        if (is_string($fullLocalPath) && file_exists($fullLocalPath)) {
            return url($relativePath);
        }

        $fallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="100%" height="100%" fill="#f2f2f2"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9aa0a6" font-family="Arial, sans-serif" font-size="14">No icon</text></svg>';
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($fallbackSvg);
    }

    public function getPropertyClassificationAttribute($value)
    {
        $classifications = [
            1 => 'Sell/Long Term Rent',
            2 => 'Commercial',
            3 => 'New Project',
            4 => 'Vacation Homes',
            5 => 'Hotel Booking'
        ];

        return isset($classifications[$value]) ? $classifications[$value] : '';
    }

    public function getPropertyClassificationTextAttribute()
    {
        $classifications = [
            1 => 'Sell/Long Term Rent',
            2 => 'Commercial',
            3 => 'New Project',
            4 => 'Vacation Homes',
            5 => 'Hotel Booking'
        ];

        return isset($classifications[$this->property_classification]) ? $classifications[$this->property_classification] : '';
    }

    public function scopeClassification($query, $classification)
    {
        return $query->where('property_classification', $classification);
    }
}
