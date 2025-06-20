<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

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

    public function parameter()
    {
        return $this->hasMany(parameter::class);
    }
    public function properties()
    {
        return $this->hasMany(Property::class);
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
