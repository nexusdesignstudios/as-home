<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_classification',
        'service_charge',
        'sales_tax',
        'city_tax'
    ];

    /**
     * Get the property classification name attribute.
     *
     * @param  int  $value
     * @return string|null
     */
    public function getPropertyClassificationNameAttribute()
    {
        switch ($this->property_classification) {
            case 4:
                return "vacation_homes";
            case 5:
                return "hotel_booking";
            default:
                return null;
        }
    }
}
