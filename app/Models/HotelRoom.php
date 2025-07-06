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
        'status'
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'discount_percentage' => 'float',
        'status' => 'boolean',
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
}
