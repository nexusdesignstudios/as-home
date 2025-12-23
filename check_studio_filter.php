<?php

/**
 * Check Studio bedroom filter
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\Parameter;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Studio Bedroom Filter Check\n";
echo "========================================\n\n";

// 1. Check how Studio is stored in database
echo "1. Studio Values in Database:\n";
$studioParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function ($query) {
        $query->where('parameters.name', 'LIKE', '%bedroom%')
            ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->where(function ($query) {
        $query->where('assign_parameters.value', '0')
            ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
            ->orWhere('assign_parameters.value', 'Studio')
            ->orWhere('assign_parameters.value', 'STUDIO');
    })
    ->select('assign_parameters.id', 'assign_parameters.property_id', 'assign_parameters.value', 'parameters.name')
    ->get();

echo "   Total Studio bedroom parameters: {$studioParams->count()}\n";
foreach ($studioParams->take(10) as $param) {
    echo "   - Property ID: {$param->property_id}, Value: '{$param->value}', Parameter: {$param->name}\n";
}
echo "\n";

// 2. Check properties with Studio bedrooms
echo "2. Properties with Studio Bedrooms:\n";
$studioProperties = Property::whereIn('propery_type', [0, 1])
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

echo "   Total properties with Studio: {$studioProperties->count()}\n";
foreach ($studioProperties->take(10) as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}\n";
}
echo "\n";

// 3. Test the exact query used in API
echo "3. Testing API Query Logic (bedrooms = '0'):\n";
$bedroomsValue = '0';
$isStudio = ($bedroomsValue === '0');

$testQuery = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->where(function ($query) use ($bedroomsValue, $isStudio) {
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
        });
    });

$testResults = $testQuery->get(['id', 'title']);
echo "   Properties found by query: {$testResults->count()}\n";
foreach ($testResults->take(10) as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}\n";
}
echo "\n";

// 4. Check for any issues
echo "4. Potential Issues:\n";
$issues = [];

if ($studioParams->count() == 0) {
    $issues[] = "No Studio bedroom parameters found in database";
}

if ($studioProperties->count() == 0) {
    $issues[] = "No properties with Studio bedrooms found";
}

if ($testResults->count() == 0 && $studioProperties->count() > 0) {
    $issues[] = "Query logic is not matching properties (query found 0, but direct check found {$studioProperties->count()})";
}

if (empty($issues)) {
    echo "   ✅ No issues found\n";
} else {
    foreach ($issues as $issue) {
        echo "   ⚠️  {$issue}\n";
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Studio parameters in DB: {$studioParams->count()}\n";
echo "Properties with Studio: {$studioProperties->count()}\n";
echo "Query results: {$testResults->count()}\n";

