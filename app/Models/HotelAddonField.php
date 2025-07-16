<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class HotelAddonField extends Model
{
    use HasFactory, HasAppTimezone;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'name',
        'field_type',
        'rank',
        'status'
    ];

    /**
     * Get all of the field values for the HotelAddonField
     */
    public function field_values()
    {
        return $this->hasMany(HotelAddonFieldValue::class, 'hotel_addon_field_id', 'id');
    }

    /**
     * Get all of the property values for the HotelAddonField
     */
    public function property_values()
    {
        return $this->hasMany(PropertyHotelAddonValue::class, 'hotel_addon_field_id', 'id');
    }
}
