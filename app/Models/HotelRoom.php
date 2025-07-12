<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_number',
        'price_per_night',
        'discount_percentage',
        'refund_policy',
        'description',
        'status',
        'availability_type',
        'available_dates',
        'weekend_commission'
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'discount_percentage' => 'float',
        'status' => 'boolean',
        'availability_type' => 'integer',
        'available_dates' => 'json',
        'weekend_commission' => 'float'
    ];

    /**
     * Get the property that owns the room.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the room type of this room.
     */
    public function roomType()
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id');
    }

    /**
     * Get the availability type attribute.
     *
     * @param  mixed  $value
     * @return string|null
     */
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

    /**
     * Set the availability type attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setAvailabilityTypeAttribute($value)
    {
        $this->attributes['availability_type'] = $value;
    }

    /**
     * Get the available dates attribute.
     *
     * @param  mixed  $value
     * @return array
     */
    public function getAvailableDatesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the available dates attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setAvailableDatesAttribute($value)
    {
        $this->attributes['available_dates'] = is_array($value) ? json_encode($value) : $value;
    }
}
