<?php

/**
 * Test the API endpoint with the exact parameters the frontend uses
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;

echo "Testing API filter with frontend parameters...\n";
echo "bedrooms=2&selectedClassification=1&selectedPropType=0\n\n";

// Simulate the API request
$bedroomsValue = "2";
$propertyClassification = 1; // selectedClassification=1
$propertyType = 0; // selectedPropType=0 (Sell)

$properties = Property::where('propery_type', $propertyType)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where('property_classification', $propertyClassification)
    ->where(function ($query) use ($bedroomsValue) {
        $query->whereExists(function ($existsQuery) use ($bedroomsValue) {
            $existsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function ($linkQuery) {
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function ($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where(function ($typeQuery) {
                                    $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                        ->orWhere('assign_parameters.modal_type', 'property');
                                });
                        });
                })
                ->where(function ($nameQuery) {
                    $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                        ->orWhere('parameters.name', 'LIKE', '%bed%');
                })
                ->where('assign_parameters.value', $bedroomsValue);
        });
    })
    ->get(['id', 'title', 'propery_type', 'property_classification']);

echo "Properties found: " . $properties->count() . "\n\n";

if ($properties->count() > 0) {
    echo "Properties:\n";
    foreach ($properties as $prop) {
        echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
    }
} else {
    echo "⚠️  No properties found!\n";
}

echo "\n";

