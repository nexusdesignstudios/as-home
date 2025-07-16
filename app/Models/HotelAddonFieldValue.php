<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class HotelAddonFieldValue extends Model
{
    use HasFactory, HasAppTimezone;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'hotel_addon_field_id',
        'value',
        'static_price',
        'multiply_price',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the addon field that owns the HotelAddonFieldValue
     */
    public function hotel_addon_field()
    {
        return $this->belongsTo(HotelAddonField::class, 'hotel_addon_field_id');
    }
}
