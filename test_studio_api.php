<?php

/**
 * Test Studio filter via API simulation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Testing Studio Filter via API Logic\n";
echo "========================================\n\n";

// Simulate API request with bedrooms = '0' (Studio)
$request = new \Illuminate\Http\Request();
$request->merge([
    'bedrooms' => '0',
    'offset' => 0,
    'limit' => 10
]);

// Build query exactly as API does
$propertyQuery = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    });

$bedroomsValue = (string) $request->bedrooms;
$bedroomsIntValue = (int) $bedroomsValue;
$isStudio = ($bedroomsValue === '0');

echo "1. Filter Parameters:\n";
echo "   bedroomsValue: '{$bedroomsValue}'\n";
echo "   bedroomsIntValue: {$bedroomsIntValue}\n";
echo "   isStudio: " . ($isStudio ? 'YES' : 'NO') . "\n\n";

$propertyQuery = $propertyQuery->where(function ($query) use ($bedroomsValue, $bedroomsIntValue, $isStudio) {
    // Check parameters table (for regular properties)
    $query->where(function ($subQuery) use ($bedroomsValue, $isStudio) {
        $subQuery->whereExists(function ($existsQuery) use ($bedroomsValue, $isStudio) {
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
                ->where(function ($valueQuery) use ($isStudio) {
                    $valueQuery->whereNotNull('assign_parameters.value')
                        ->where('assign_parameters.value', '!=', '')
                        ->where('assign_parameters.value', '!=', 'null')
                        ->whereRaw('TRIM(assign_parameters.value) != ?', ['']);
                    
                    if ($isStudio) {
                        $valueQuery->where(function ($studioQuery) {
                            $studioQuery->where('assign_parameters.value', '0')
                                ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                ->orWhere('assign_parameters.value', 'Studio')
                                ->orWhere('assign_parameters.value', 'STUDIO');
                        });
                    }
                });
        });
    })
    // OR check vacation_apartments table (for vacation homes)
    ->orWhere(function ($vacationQuery) use ($bedroomsIntValue) {
        $vacationQuery->where('property_classification', 4)
            ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
                $aptQuery->where('bedrooms', $bedroomsIntValue);
            });
    });
});

$results = $propertyQuery->get(['id', 'title', 'property_classification']);

echo "2. Query Results:\n";
echo "   Total found: {$results->count()}\n";
foreach ($results as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}, Classification: {$prop->property_classification}\n";
}

echo "\n3. Checking if any Studio properties are missing:\n";
$allStudioProps = Property::whereIn('propery_type', [0, 1])
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
    ->pluck('id')
    ->toArray();

$foundIds = $results->pluck('id')->toArray();
$missingIds = array_diff($allStudioProps, $foundIds);

if (empty($missingIds)) {
    echo "   ✅ All Studio properties are found\n";
} else {
    echo "   ⚠️  Missing properties: " . implode(', ', $missingIds) . "\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Studio properties in DB: " . count($allStudioProps) . "\n";
echo "Properties found by query: {$results->count()}\n";
if (count($allStudioProps) != $results->count()) {
    echo "⚠️  Mismatch detected!\n";
} else {
    echo "✅ Query is working correctly\n";
}

