<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class PropertyHotelAddonValue extends Model
{
    use HasFactory, HasAppTimezone;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'property_id',
        'hotel_addon_field_id',
        'value',
        'static_price',
        'multiply_price',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the property that owns this addon value
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the addon field that owns this value
     */
    public function hotel_addon_field()
    {
        return $this->belongsTo(HotelAddonField::class, 'hotel_addon_field_id');
    }
}
