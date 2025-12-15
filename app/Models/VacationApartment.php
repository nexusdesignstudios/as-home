<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationApartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'apartment_number',
        'price_per_night',
        'discount_percentage',
        'description',
        'status',
        'availability_type',
        'available_dates',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'quantity',
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'discount_percentage' => 'float',
        'status' => 'boolean',
        'availability_type' => 'integer',
        'max_guests' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Get the property that owns the apartment.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
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
        $decodedValue = $value ? (is_string($value) ? json_decode($value, true) : $value) : [];

        // Ensure proper structure with type field
        if (is_array($decodedValue)) {
            foreach ($decodedValue as $key => $dateInfo) {
                if (is_array($dateInfo)) {
                    // Ensure each date entry has the required fields
                    if (!isset($dateInfo['price'])) {
                        $decodedValue[$key]['price'] = 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        // Check if this apartment uses busy_days availability type
                        if ($this->availability_type === 'busy_days') {
                            $decodedValue[$key]['type'] = 'dead';
                        } else {
                            $decodedValue[$key]['type'] = 'open';
                        }
                    } else {
                        // Ensure type is one of the allowed values
                        $allowedTypes = ['dead', 'open', 'reserved'];
                        if (!in_array($dateInfo['type'], $allowedTypes)) {
                            // For busy_days type, default to dead, otherwise open
                            if ($this->availability_type === 'busy_days') {
                                $decodedValue[$key]['type'] = 'dead';
                            } else {
                                $decodedValue[$key]['type'] = 'open';
                            }
                        }

                        // If type is reserved, ensure reservation_id exists
                        if ($dateInfo['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
                            $decodedValue[$key]['reservation_id'] = null;
                        }
                    }
                } else {
                    // If the date entry is not an array, convert it to one with defaults
                    $defaultType = ($this->availability_type === 'busy_days') ? 'dead' : 'open';
                    $decodedValue[$key] = [
                        'price' => 0,
                        'type' => $defaultType,
                    ];
                }
            }
        }

        return $decodedValue;
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
                    if (!isset($dateInfo['type'])) {
                        // Check if this apartment uses busy_days availability type
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
                    ];
                }
            }
        }

        $this->attributes['available_dates'] = is_array($value) ? json_encode($value) : (is_string($value) ? $value : json_encode([]));
    }

    /**
     * Get the reservations for this apartment.
     */
    public function reservations()
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }
}

