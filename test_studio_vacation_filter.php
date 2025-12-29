<?php

/**
 * Test Studio filter for vacation homes with no assign_parameters bedroom data
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Test Studio Filter for Vacation Homes\n";
echo "========================================\n\n";

// First, check property 333 specifically (the test property we found earlier)
echo "0. Checking Property 333 (Test 01-20-12-2025) vacation apartments:\n";
$property333 = Property::find(333);
if ($property333) {
    echo "   Property ID: 333, Title: {$property333->title}\n";
    echo "   Classification: {$property333->property_classification}\n";
    
    $apts333 = DB::table('vacation_apartments')
        ->where('property_id', 333)
        ->select('id', 'apartment_number', 'bedrooms', 'bathrooms', 'status')
        ->get();
    
    echo "   Vacation Apartments: {$apts333->count()}\n";
    foreach ($apts333 as $apt) {
        echo "     - Apt #{$apt->apartment_number}: bedrooms={$apt->bedrooms}, bathrooms={$apt->bathrooms}, status={$apt->status}\n";
        if ($apt->apartment_number == '101' || $apt->apartment_number == '202' || $apt->apartment_number == '303') {
            echo "       ⭐ MATCHES TEST APARTMENT\n";
        }
    }
    echo "\n";
}

// Find vacation homes with Studio apartments (bedrooms = 0) but no assign_parameters bedroom data
echo "1. Finding vacation homes with Studio apartments (bedrooms = 0):\n";
$vacationHomesWithStudio = DB::table('propertys')
    ->join('vacation_apartments', 'propertys.id', '=', 'vacation_apartments.property_id')
    ->where('propertys.property_classification', 4)
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where('vacation_apartments.status', 1)
    ->where('vacation_apartments.bedrooms', 0)
    ->select('propertys.id', 'propertys.title', 'vacation_apartments.apartment_number', 'vacation_apartments.bedrooms')
    ->distinct()
    ->get();

echo "   Found " . $vacationHomesWithStudio->count() . " vacation apartments with Studio (0 bedrooms)\n\n";

foreach ($vacationHomesWithStudio->take(10) as $apt) {
    echo "   - Property ID: {$apt->id}, Title: {$apt->title}, Apartment: {$apt->apartment_number}\n";
    
    // Check if this property has assign_parameters bedroom data
    $hasAssignParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function ($query) use ($apt) {
            $query->where('assign_parameters.property_id', $apt->id)
                ->orWhere(function ($q) use ($apt) {
                    $q->where('assign_parameters.modal_id', $apt->id)
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
        ->exists();
    
    echo "     Has assign_parameters bedroom data: " . ($hasAssignParams ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n";

// Test the exact query used in API
echo "2. Testing API Query Logic (bedrooms = '0' for Studio):\n";
$bedroomsValue = '0';
$bedroomsIntValue = 0;
$isStudio = true;

$testQuery = Property::where(['status' => 1, 'request_status' => 'approved'])
    ->where(function ($query) use ($bedroomsValue, $bedroomsIntValue, $isStudio) {
        // Check assign_parameters
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
                                    ->orWhere('assign_parameters.value', 'STUDIO')
                                    ->orWhere('assign_parameters.value', '"0"')
                                    ->orWhere('assign_parameters.value', '"Studio"')
                                    ->orWhere('assign_parameters.value', '"studio"')
                                    ->orWhere('assign_parameters.value', '"STUDIO"')
                                    ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['0'])
                                    ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['Studio'])
                                    ->orWhereRaw('LOWER(TRIM(JSON_EXTRACT(assign_parameters.value, "$"))) = ?', ['studio'])
                                    ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['0'])
                                    ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['Studio']);
                            });
                        }
                    });
            });
        })
        // OR check vacation_apartments
        ->orWhere(function ($vacationQuery) use ($bedroomsIntValue, $isStudio) {
            $vacationQuery->where('property_classification', 4)
                ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue, $isStudio) {
                    $aptQuery->where('status', 1);
                    
                    if ($isStudio) {
                        $aptQuery->where('bedrooms', 0);
                    } else {
                        $aptQuery->where('bedrooms', $bedroomsIntValue);
                    }
                });
        });
    });

$testResults = $testQuery->get(['id', 'title', 'property_classification']);

echo "   Properties matched by query: {$testResults->count()}\n";
foreach ($testResults->take(10) as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}, Classification: {$prop->property_classification}\n";
}

echo "\n";

// Check which properties matched via which method
echo "3. Analyzing Match Methods:\n";
$matchedViaAssignParams = [];
$matchedViaVacation = [];
$matchedViaBoth = [];

foreach ($testResults as $prop) {
    // Check assign_parameters
    $hasAssignParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function ($query) use ($prop) {
            $query->where('assign_parameters.property_id', $prop->id)
                ->orWhere(function ($q) use ($prop) {
                    $q->where('assign_parameters.modal_id', $prop->id)
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
        })
        ->exists();
    
    // Check vacation_apartments
    $hasVacationStudio = DB::table('vacation_apartments')
        ->where('property_id', $prop->id)
        ->where('status', 1)
        ->where('bedrooms', 0)
        ->exists();
    
    if ($hasAssignParams && $hasVacationStudio) {
        $matchedViaBoth[] = $prop->id;
    } elseif ($hasAssignParams) {
        $matchedViaAssignParams[] = $prop->id;
    } elseif ($hasVacationStudio) {
        $matchedViaVacation[] = $prop->id;
    }
}

echo "   Matched via assign_parameters only: " . count($matchedViaAssignParams) . " properties\n";
echo "   Matched via vacation_apartments only: " . count($matchedViaVacation) . " properties\n";
echo "   Matched via both: " . count($matchedViaBoth) . " properties\n";

if (count($matchedViaVacation) > 0) {
    echo "\n   ✅ SUCCESS: Query correctly matches vacation homes with ONLY vacation_apartments data!\n";
    echo "   Properties matched via vacation_apartments only: " . implode(', ', $matchedViaVacation) . "\n";
} else {
    echo "\n   ⚠️  WARNING: No properties matched via vacation_apartments only\n";
    echo "   This might indicate the query needs adjustment\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total vacation apartments with Studio: {$vacationHomesWithStudio->count()}\n";
echo "Properties matched by API query: {$testResults->count()}\n";
echo "Properties with NO assign_parameters data: " . count($matchedViaVacation) . "\n";

