<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyTerms extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification_id',
        'terms_conditions',
    ];

    /**
     * Get the property classification that owns the terms.
     */
    public function getClassificationNameAttribute()
    {
        switch ($this->classification_id) {
            case 1:
                return "sell_rent";
            case 2:
                return "commercial";
            case 3:
                return "new_project";
            case 4:
                return "vacation_homes";
            case 5:
                return "hotel_booking";
            default:
                return null;
        }
    }
}
