<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'property_classification'
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
        return $this->hasMany(parameter::class,'id','parameter_types');
    }
    public function properties()
    {
        return $this->hasMany(Property::class,'category_id','id');
    }

    public function getImageAttribute($image)
    {
        return $image != "" ? url('') . config('global.IMG_PATH') . config('global.CATEGORY_IMG_PATH') . $image : '';
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

    public function scopeClassification($query, $classification)
    {
        return $query->where('property_classification', $classification);
    }
}
