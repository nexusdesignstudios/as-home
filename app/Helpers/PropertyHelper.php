<?php

namespace App\Helpers;

class PropertyHelper
{
    /**
     * Get property classification options
     *
     * @return array
     */
    public static function getPropertyClassifications()
    {
        return [
            1 => 'Sell/Long Term Rent',
            2 => 'Commercial',
            3 => 'New Project',
            4 => 'Vacation Homes',
            5 => 'Hotel Booking'
        ];
    }

    /**
     * Get property classification name by id
     *
     * @param int|null $id
     * @return string|null
     */
    public static function getClassificationName($id)
    {
        $classifications = self::getPropertyClassifications();
        return $id && isset($classifications[$id]) ? $classifications[$id] : null;
    }
}
