<?php

/**
 * Script to normalize bedroom and bathroom values in assign_parameters table
 * Converts all values to plain string format (e.g., "2" instead of JSON-encoded or other formats)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\parameter;

echo "========================================\n";
echo "Normalizing Bedroom and Bathroom Values\n";
echo "========================================\n\n";

// Find bedroom and bathroom parameter IDs
$bedroomParam = parameter::where(function($q) {
    $q->where('name', 'LIKE', '%bedroom%')
      ->orWhere('name', 'LIKE', '%bed%');
})->first(['id', 'name']);

$bathroomParam = parameter::where(function($q) {
    $q->where('name', 'LIKE', '%bathroom%')
      ->orWhere('name', 'LIKE', '%bath%');
})->first(['id', 'name']);

if (!$bedroomParam) {
    echo "ERROR: Bedroom parameter not found!\n";
    exit(1);
}

if (!$bathroomParam) {
    echo "ERROR: Bathroom parameter not found!\n";
    exit(1);
}

echo "Bedroom Parameter: ID={$bedroomParam->id}, Name='{$bedroomParam->name}'\n";
echo "Bathroom Parameter: ID={$bathroomParam->id}, Name='{$bathroomParam->name}'\n\n";

// Get all assign_parameters records for bedrooms and bathrooms
$bedroomRecords = DB::table('assign_parameters')
    ->where('parameter_id', $bedroomParam->id)
    ->get();

$bathroomRecords = DB::table('assign_parameters')
    ->where('parameter_id', $bathroomParam->id)
    ->get();

echo "Found {$bedroomRecords->count()} bedroom records\n";
echo "Found {$bathroomRecords->count()} bathroom records\n\n";

$bedroomUpdated = 0;
$bathroomUpdated = 0;
$bedroomSkipped = 0;
$bathroomSkipped = 0;

// Normalize bedroom values
echo "Normalizing bedroom values...\n";
foreach ($bedroomRecords as $record) {
    $rawValue = $record->value;
    $normalizedValue = null;
    
    // Try to extract the numeric value
    if (is_numeric($rawValue)) {
        $normalizedValue = (string)(int)$rawValue; // Convert to string integer
    } elseif (is_string($rawValue)) {
        // Try JSON decode
        $decoded = json_decode($rawValue, true);
        if (json_last_error() == JSON_ERROR_NONE && is_numeric($decoded)) {
            $normalizedValue = (string)(int)$decoded;
        } elseif (preg_match('/^"?(\d+)"?$/', $rawValue, $matches)) {
            // Extract number from string like "2" or '"2"'
            $normalizedValue = (string)(int)$matches[1];
        } elseif (is_numeric(trim($rawValue))) {
            $normalizedValue = (string)(int)trim($rawValue);
        }
    }
    
    if ($normalizedValue !== null && $normalizedValue !== $rawValue) {
        DB::table('assign_parameters')
            ->where('id', $record->id)
            ->update(['value' => $normalizedValue]);
        $bedroomUpdated++;
        echo "  Updated ID {$record->id}: '{$rawValue}' -> '{$normalizedValue}'\n";
    } else {
        $bedroomSkipped++;
    }
}

echo "\nNormalizing bathroom values...\n";
foreach ($bathroomRecords as $record) {
    $rawValue = $record->value;
    $normalizedValue = null;
    
    // Try to extract the numeric value
    if (is_numeric($rawValue)) {
        $normalizedValue = (string)(int)$rawValue; // Convert to string integer
    } elseif (is_string($rawValue)) {
        // Try JSON decode
        $decoded = json_decode($rawValue, true);
        if (json_last_error() == JSON_ERROR_NONE && is_numeric($decoded)) {
            $normalizedValue = (string)(int)$decoded;
        } elseif (preg_match('/^"?(\d+)"?$/', $rawValue, $matches)) {
            // Extract number from string like "2" or '"2"'
            $normalizedValue = (string)(int)$matches[1];
        } elseif (is_numeric(trim($rawValue))) {
            $normalizedValue = (string)(int)trim($rawValue);
        }
    }
    
    if ($normalizedValue !== null && $normalizedValue !== $rawValue) {
        DB::table('assign_parameters')
            ->where('id', $record->id)
            ->update(['value' => $normalizedValue]);
        $bathroomUpdated++;
        echo "  Updated ID {$record->id}: '{$rawValue}' -> '{$normalizedValue}'\n";
    } else {
        $bathroomSkipped++;
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Bedroom records updated: {$bedroomUpdated}\n";
echo "Bedroom records skipped (already correct): {$bedroomSkipped}\n";
echo "Bathroom records updated: {$bathroomUpdated}\n";
echo "Bathroom records skipped (already correct): {$bathroomSkipped}\n";
echo "\n✅ Normalization complete!\n\n";

// Verify: Check all sell properties with 2 bedrooms
echo "Verifying: Checking sell properties with 2 bedrooms...\n";
$bedroomsValue = "2";
$filteredProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
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
    ->count();

echo "Properties with 2 bedrooms found: {$filteredProperties}\n";
echo "Expected: 23\n\n";

if ($filteredProperties == 23) {
    echo "✅ SUCCESS: All 23 properties are now being returned!\n";
} else {
    echo "⚠️  Still missing " . (23 - $filteredProperties) . " properties\n";
}

echo "\n";

