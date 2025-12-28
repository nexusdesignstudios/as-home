<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableDatesHotelRoom extends Model
{
    use HasFactory;

    protected $table = 'available_dates_hotel_rooms';

    protected $fillable = [
        'property_id',
        'hotel_room_id',
        'from_date',
        'to_date',
        'price',
        'type',
        'nonrefundable_percentage',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'price' => 'float',
        'nonrefundable_percentage' => 'float',
    ];

    /**
     * Get the property that owns this available date.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the hotel room that owns this available date.
     */
    public function hotelRoom()
    {
        return $this->belongsTo(HotelRoom::class, 'hotel_room_id');
    }
}

