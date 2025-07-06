<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    /**
     * Get the rooms that belong to this room type.
     */
    public function rooms()
    {
        return $this->hasMany(HotelRoom::class, 'room_type_id');
    }
}
