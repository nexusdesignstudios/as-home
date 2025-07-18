<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelApartmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    /**
     * Get the properties that belong to this apartment type.
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'hotel_apartment_type_id');
    }
}
