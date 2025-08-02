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
        'weekend_commission',
        'nonrefundable_percentage',
        'max_guests'
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'discount_percentage' => 'float',
        'status' => 'boolean',
        'availability_type' => 'integer',
        'available_dates' => 'json',
        'weekend_commission' => 'float',
        'nonrefundable_percentage' => 'float',
        'max_guests' => 'integer'
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
        // Ensure each date entry has the required fields (price, type, reservation_id if applicable)
        if (is_array($value)) {
            foreach ($value as $key => $dateInfo) {
                // Make sure each date entry is an array with at least price and type
                if (is_array($dateInfo)) {
                    // Set defaults if not provided
                    if (!isset($dateInfo['price'])) {
                        $value[$key]['price'] = 0;
                    }
                    if (!isset($dateInfo['nonrefundable_percentage'])) {
                        $value[$key]['nonrefundable_percentage'] = $this->nonrefundable_percentage ?? 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        // Check if this room uses busy_days availability type
                        if ($this->availability_type === 'busy_days') {
                            $value[$key]['type'] = 'dead';
                        } else {
                            $value[$key]['type'] = 'open';
                        }
                    } else {
                        // Ensure type is one of the allowed values
                        $allowedTypes = ['dead', 'open', 'reserved'];
                        if (!in_array($dateInfo['type'], $allowedTypes)) {
                            // For busy_days type, default to dead, otherwise open
                            if ($this->availability_type === 'busy_days') {
                                $value[$key]['type'] = 'dead';
                            } else {
                                $value[$key]['type'] = 'open';
                            }
                        }

                        // If type is reserved, ensure reservation_id exists
                        if ($dateInfo['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
                            $value[$key]['reservation_id'] = null;
                        }
                    }
                } else {
                    // If the date entry is not an array, convert it to one with defaults
                    $defaultType = ($this->availability_type === 'busy_days') ? 'dead' : 'open';
                    $value[$key] = [
                        'price' => 0,
                        'type' => $defaultType,
                        'nonrefundable_percentage' => $this->nonrefundable_percentage ?? 0
                    ];
                }
            }
        }

        $this->attributes['available_dates'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the reservations for this room.
     */
    public function reservations()
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }
}
