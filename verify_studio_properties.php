<?php

/**
 * Verify all Studio properties are found
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Verify Studio Properties\n";
echo "========================================\n\n";

// 1. Find all properties with Studio bedrooms (excluding vacation homes)
echo "1. Studio Properties (excluding vacation homes):\n";
$studioProperties = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->where('property_classification', '!=', 4) // Exclude vacation homes
    ->whereExists(function ($existsQuery) {
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
            ->where(function ($valueQuery) {
                $valueQuery->where('assign_parameters.value', '0')
                    ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                    ->orWhere('assign_parameters.value', 'Studio')
                    ->orWhere('assign_parameters.value', 'STUDIO');
            });
    })
    ->get(['id', 'title', 'property_classification', 'status', 'request_status']);

echo "   Total: {$studioProperties->count()}\n";
foreach ($studioProperties as $prop) {
    $classification = $prop->property_classification ?? 'N/A';
    echo "   - ID: {$prop->id}, Title: {$prop->title}, Classification: {$classification}\n";
}
echo "\n";

// 2. Check if any Studio properties are missing
echo "2. Checking for missing Studio properties:\n";
$allPropertiesWithStudio = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->whereExists(function ($existsQuery) {
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
            ->where(function ($valueQuery) {
                $valueQuery->where('assign_parameters.value', '0')
                    ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                    ->orWhere('assign_parameters.value', 'Studio')
                    ->orWhere('assign_parameters.value', 'STUDIO');
            });
    })
    ->get(['id', 'title', 'property_classification']);

$vacationHomesWithStudio = $allPropertiesWithStudio->filter(function ($prop) {
    return $prop->property_classification == 4;
});

$regularPropertiesWithStudio = $allPropertiesWithStudio->filter(function ($prop) {
    return $prop->property_classification != 4;
});

echo "   All properties with Studio: {$allPropertiesWithStudio->count()}\n";
echo "   - Regular properties (should show): {$regularPropertiesWithStudio->count()}\n";
echo "   - Vacation homes (should NOT show): {$vacationHomesWithStudio->count()}\n";

if ($vacationHomesWithStudio->count() > 0) {
    echo "\n   Vacation homes with Studio (will be excluded):\n";
    foreach ($vacationHomesWithStudio as $prop) {
        echo "     - ID: {$prop->id}, Title: {$prop->title}\n";
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Studio properties (regular): {$studioProperties->count()}\n";
echo "Expected in filter results: {$regularPropertiesWithStudio->count()}\n";

if ($studioProperties->count() == $regularPropertiesWithStudio->count()) {
    echo "✅ All Studio properties are being found correctly\n";
} else {
    echo "⚠️  Mismatch: Found {$studioProperties->count()}, Expected {$regularPropertiesWithStudio->count()}\n";
}

