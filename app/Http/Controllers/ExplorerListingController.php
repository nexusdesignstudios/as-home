<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class ExplorerListingController extends Controller
{
    /**
     * Public property listing detail page.
     * No auth required — listing pages are publicly viewable.
     *
     * Eager-loaded relationships:
     *   propertyImages  — gallery thumbnails (FK: propertys_id)
     *   customer        — host/owner info (FK: added_by → Customer model)
     *   category        — property category name
     *   assignfacilities.outdoorfacilities — amenity names and icons
     *
     * For hotel properties (classification=5), hotel rooms are loaded
     * after the initial find to avoid loading them for other types.
     *
     * Fields that don't exist on the Property model (resolved via ?? null):
     *   rating, reviews_count, superhost, beds, baths, max_guests, area_m2
     *   (these live in the parameters pivot or are not stored)
     */
    public function show(Request $request, $id)
    {
        $property = Property::with([
            'propertyImages',
            'customer',
            'category',
            'assignfacilities.outdoorfacilities',
        ])->findOrFail($id);

        // Load hotel rooms only for hotel properties to avoid unnecessary queries
        if ($property->property_classification == 5) {
            $property->load('hotelRooms.roomType');
        }

        return view('explorer.listing.show', compact('property'));
    }
}
