<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class AddonsPackage extends Model
{
    use HasFactory, HasAppTimezone;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'name',
        'description',
        'property_id',
        'status',
        'price',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the property that owns this addon package
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get all of the addon values for this package
     */
    public function addon_values()
    {
        return $this->hasMany(PropertyHotelAddonValue::class, 'package_id');
    }
}
