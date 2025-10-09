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

    /**
     * Get the total tax percentage (sum of service_charge, sales_tax, and city_tax).
     *
     * @return float
     */
    public function getTotalTaxPercentageAttribute()
    {
        return (float)$this->service_charge + (float)$this->sales_tax + (float)$this->city_tax;
    }

    /**
     * Calculate commission rate based on property classification and rent package.
     *
     * @param int $propertyClassification The property classification (4 for vacation homes, 5 for hotel booking)
     * @param string|null $rentPackage The rent package (basic or premium) - only applicable for vacation homes
     * @return float The commission rate as a percentage
     */
    public static function getCommissionRate($propertyClassification, $rentPackage = null)
    {
        // Get the property tax for the given classification
        $propertyTax = self::where('property_classification', $propertyClassification)->first();

        if (!$propertyTax) {
            // Default tax values if no record exists
            $totalTaxPercentage = 0;
        } else {
            $totalTaxPercentage = $propertyTax->total_tax_percentage;
        }

        // Calculate commission based on classification and rent package
        if ($propertyClassification == 4) { // Vacation homes
            if ($rentPackage == 'premium') {
                // Premium package: taxes + 24.99%
                return $totalTaxPercentage + 24.99;
            } else {
                // Basic package (default): taxes + 14.99%
                return $totalTaxPercentage + 14.99;
            }
        } elseif ($propertyClassification == 5) { // Hotel booking
            // Hotel: taxes + 15%
            return $totalTaxPercentage + 15;
        }

        // Default fallback
        return 0;
    }
}
