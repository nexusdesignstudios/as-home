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
        'custom_room_type',
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
        'max_guests',
        'min_guests',
        'base_guests',
        'max_guests',
        'guest_pricing_rules',
        'available_rooms'
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'discount_percentage' => 'float',
        'status' => 'boolean',
        'availability_type' => 'integer',
        'weekend_commission' => 'float',
        'nonrefundable_percentage' => 'float',
        'max_guests' => 'integer',
        'min_guests' => 'integer',
        'base_guests' => 'integer',
        'guest_pricing_rules' => 'array',
        'available_rooms' => 'integer',
        'available_dates' => 'array'
    ];

    // Automatically include available_dates in JSON responses
    protected $appends = ['available_dates'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($room) {
            // Only sync if available_dates was changed or if it's a new room
            // We check attributes directly because available_dates accessor/setter logic might interfere with isDirty()
            if ($room->wasRecentlyCreated || $room->isDirty('available_dates')) {
                try {
                    // Sync with available_dates_hotel_rooms table
                    // First, delete existing entries for this room
                    \DB::table('available_dates_hotel_rooms')->where('hotel_room_id', $room->id)->delete();

                    // Get the raw attribute value (JSON string) and decode it
                    $availableDates = $room->getAttributes()['available_dates'] ?? null;
                    if (is_string($availableDates)) {
                        $availableDates = json_decode($availableDates, true);
                    }

                    if (is_array($availableDates)) {
                        foreach ($availableDates as $dateInfo) {
                            $fromDate = $dateInfo['from'] ?? $dateInfo['from_date'] ?? null;
                            $toDate = $dateInfo['to'] ?? $dateInfo['to_date'] ?? null;

                            if ($fromDate && $toDate) {
                                \DB::table('available_dates_hotel_rooms')->insert([
                                    'property_id' => $room->property_id,
                                    'hotel_room_id' => $room->id,
                                    'from_date' => $fromDate,
                                    'to_date' => $toDate,
                                    'price' => $dateInfo['price'] ?? 0,
                                    'type' => $dateInfo['type'] ?? 'open',
                                    'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? $room->nonrefundable_percentage ?? 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to sync hotel room available dates: ' . $e->getMessage());
                }
            }
        });

        static::deleted(function ($room) {
            // Clean up available dates when room is deleted
            \DB::table('available_dates_hotel_rooms')->where('hotel_room_id', $room->id)->delete();
        });
    }

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
        // Read from the available_dates_hotel_rooms table instead of the old column
        $availableDatesQuery = \DB::table('available_dates_hotel_rooms')
            ->where('hotel_room_id', $this->id)
            ->orderBy('from_date', 'asc');

        // Keep the property_id filter only when we have a property_id (older rows may have null)
        if (!empty($this->property_id)) {
            $availableDatesQuery->where(function ($q) {
                $q->whereNull('property_id')
                    ->orWhere('property_id', $this->property_id);
            });
        }

        $availableDates = $availableDatesQuery
            ->get()
            ->map(function ($item) {
                $type = $item->type;
                if ($type === 'closed') {
                    $type = 'dead';
                }
                return [
                    'from' => $item->from_date,
                    'to' => $item->to_date,
                    'price' => (float) $item->price,
                    'type' => $type,
                    'nonrefundable_percentage' => (float) ($item->nonrefundable_percentage ?? $this->nonrefundable_percentage ?? 0)
                ];
            })
            ->toArray();

        // Backwards compatibility: if the new table has no rows, fallback to the legacy JSON column.
        $decodedValue = $availableDates;
        if (empty($availableDates)) {
            if (is_string($value) && $value !== '') {
                $legacy = json_decode($value, true);
                $decodedValue = is_array($legacy) ? $legacy : [];
            } elseif (is_array($value)) {
                $decodedValue = $value;
            }

            // Normalize legacy keys (from_date/to_date/etc) into (from/to)
            if (is_array($decodedValue)) {
                $decodedValue = array_values(array_filter(array_map(function ($dateInfo) {
                    if (!is_array($dateInfo)) {
                        return $dateInfo;
                    }
                    if (!isset($dateInfo['from'])) {
                        $dateInfo['from'] = $dateInfo['from_date'] ?? $dateInfo['fromDate'] ?? $dateInfo['start_date'] ?? $dateInfo['startDate'] ?? $dateInfo['start'] ?? null;
                    }
                    if (!isset($dateInfo['to'])) {
                        $dateInfo['to'] = $dateInfo['to_date'] ?? $dateInfo['toDate'] ?? $dateInfo['end_date'] ?? $dateInfo['endDate'] ?? $dateInfo['end'] ?? null;
                    }
                    return $dateInfo;
                }, $decodedValue), function ($v) {
                    return $v !== null;
                }));
            }
        }

        // Ensure proper structure with type field
        if (is_array($decodedValue)) {
            foreach ($decodedValue as $key => $dateInfo) {
                if (is_array($dateInfo)) {
                    if (isset($dateInfo['type']) && $dateInfo['type'] === 'closed') {
                        $decodedValue[$key]['type'] = 'dead';
                        $dateInfo['type'] = 'dead';
                    }
                    // Ensure each date entry has the required fields
                    if (!isset($dateInfo['price'])) {
                        $decodedValue[$key]['price'] = 0;
                    }
                    if (!isset($dateInfo['nonrefundable_percentage'])) {
                        $decodedValue[$key]['nonrefundable_percentage'] = $this->nonrefundable_percentage ?? 0;
                    }
                    if (!isset($dateInfo['type'])) {
                        // Check if this room uses busy_days availability type
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
                        'nonrefundable_percentage' => $this->nonrefundable_percentage ?? 0
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
                        // Map 'closed' to 'dead' for consistency with getter
                        if ($dateInfo['type'] === 'closed') {
                            $value[$key]['type'] = 'dead';
                        }

                        // Ensure type is one of the allowed values
                        $allowedTypes = ['dead', 'open', 'reserved'];
                        if (!in_array($value[$key]['type'], $allowedTypes)) {
                            // For busy_days type, default to dead, otherwise open
                            if ($this->availability_type === 'busy_days') {
                                $value[$key]['type'] = 'dead';
                            } else {
                                $value[$key]['type'] = 'open';
                            }
                        }

                        // If type is reserved, ensure reservation_id exists
                        if ($value[$key]['type'] === 'reserved' && !isset($dateInfo['reservation_id'])) {
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

        $this->attributes['available_dates'] = is_array($value) ? json_encode($value) : (is_string($value) ? $value : json_encode([]));
    }

    /**
     * Get the reservations for this room.
     */
    public function reservations()
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    /**
     * Get the available dates for this room.
     */
    public function availableDates()
    {
        return $this->hasMany(AvailableDatesHotelRoom::class, 'hotel_room_id');
    }

    /**
     * Calculate price based on guest count and pricing rules.
     * 
     * @param int $guests
     * @param float|null $currentBasePrice Optional base price to use instead of model's price_per_night
     * @return float
     */
    public function calculatePrice(int $guests, ?float $currentBasePrice = null): float
    {
        $basePrice = $currentBasePrice ?? $this->price_per_night;
        $baseGuests = $this->base_guests ?? 2;
        
        if ($guests == $baseGuests) {
            return $basePrice;
        }

        $rules = $this->guest_pricing_rules ?? [];
        
        // Rules is array [guest_count => percentage]
        // e.g. [1 => 90, 3 => 110]
        
        if (isset($rules[$guests])) {
            $percentage = (float) $rules[$guests];
            return round($basePrice * ($percentage / 100), 2);
        }
        
        return $basePrice;
    }
}
