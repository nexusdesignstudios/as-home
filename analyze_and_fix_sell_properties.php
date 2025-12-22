<?php

/**
 * Script to analyze and potentially fix sell properties missing bedroom/bathroom data
 * 
 * This script:
 * 1. Analyzes property titles to extract bedroom/bathroom information
 * 2. Identifies which properties can be auto-fixed
 * 3. Optionally creates the missing parameter assignments
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\parameter;
use Illuminate\Support\Facades\DB;

echo "=== Analyzing Sell Properties for Bedroom/Bathroom Data ===\n\n";

// Get bedroom and bathroom parameter IDs
$bedroomParam = parameter::where(function($query) {
    $query->where('name', 'LIKE', '%bedroom%')
          ->orWhere('name', 'LIKE', '%bed%');
})->first();

$bathroomParam = parameter::where(function($query) {
    $query->where('name', 'LIKE', '%bathroom%')
          ->orWhere('name', 'LIKE', '%bath%');
})->first();

if (!$bedroomParam) {
    echo "ERROR: Bedroom parameter not found!\n";
    exit(1);
}

if (!$bathroomParam) {
    echo "ERROR: Bathroom parameter not found!\n";
    exit(1);
}

echo "Bedroom Parameter: ID {$bedroomParam->id}, Name: {$bedroomParam->name}\n";
echo "Bathroom Parameter: ID {$bathroomParam->id}, Name: {$bathroomParam->name}\n\n";

// Function to extract bedroom count from title
function extractBedroomCount($title) {
    $titleLower = strtolower($title);
    
    // Try numeric patterns first (with or without hyphen, with or without space)
    if (preg_match('/(\d+)[\s-]*bedroom/i', $title, $matches)) {
        return (int)$matches[1];
    }
    if (preg_match('/(\d+)[\s-]*bed\b/i', $title, $matches)) {
        return (int)$matches[1];
    }
    
    // Try text patterns (with or without hyphen)
    if (preg_match('/one[\s-]*bedroom/i', $titleLower)) return 1;
    if (preg_match('/two[\s-]*bedroom/i', $titleLower)) return 2;
    if (preg_match('/three[\s-]*bedroom/i', $titleLower)) return 3;
    if (preg_match('/four[\s-]*bedroom/i', $titleLower)) return 4;
    if (preg_match('/five[\s-]*bedroom/i', $titleLower)) return 5;
    if (preg_match('/six[\s-]*bedroom/i', $titleLower)) return 6;
    if (preg_match('/seven[\s-]*bedroom/i', $titleLower)) return 7;
    if (preg_match('/eight[\s-]*bedroom/i', $titleLower)) return 8;
    if (preg_match('/nine[\s-]*bedroom/i', $titleLower)) return 9;
    if (preg_match('/ten[\s-]*bedroom/i', $titleLower)) return 10;
    
    // Studio = 0 bedrooms
    if (preg_match('/\bstudio\b/i', $titleLower)) return 0;
    
    return null;
}

// Function to extract bathroom count from title (less reliable)
function extractBathroomCount($title) {
    $titleLower = strtolower($title);
    
    // Try numeric patterns (with or without hyphen, with or without space)
    if (preg_match('/(\d+)[\s-]*bathroom/i', $title, $matches)) {
        return (int)$matches[1];
    }
    if (preg_match('/(\d+)[\s-]*bath\b/i', $title, $matches)) {
        return (int)$matches[1];
    }
    
    // Text patterns (with or without hyphen)
    if (preg_match('/one[\s-]*bathroom/i', $titleLower)) return 1;
    if (preg_match('/two[\s-]*bathroom/i', $titleLower)) return 2;
    if (preg_match('/three[\s-]*bathroom/i', $titleLower)) return 3;
    if (preg_match('/four[\s-]*bathroom/i', $titleLower)) return 4;
    if (preg_match('/five[\s-]*bathroom/i', $titleLower)) return 5;
    
    return null;
}

// Get all sell properties
$sellProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->get();

echo "Total Sell Properties: " . $sellProperties->count() . "\n\n";

$canFixBedroom = [];
$canFixBathroom = [];
$cannotFixBedroom = [];
$cannotFixBathroom = [];
$alreadyHasBedroom = [];
$alreadyHasBathroom = [];

foreach ($sellProperties as $property) {
    $propertyId = $property->id;
    
    // Check if already has bedroom parameter
    $hasBedroom = AssignParameters::where('property_id', $propertyId)
        ->where('parameter_id', $bedroomParam->id)
        ->whereNotNull('value')
        ->where('value', '!=', '')
        ->exists();
    
    // Check if already has bathroom parameter
    $hasBathroom = AssignParameters::where('property_id', $propertyId)
        ->where('parameter_id', $bathroomParam->id)
        ->whereNotNull('value')
        ->where('value', '!=', '')
        ->exists();
    
    if ($hasBedroom) {
        $alreadyHasBedroom[] = $property;
        continue;
    }
    
    if ($hasBathroom) {
        $alreadyHasBathroom[] = $property;
    }
    
    // Try to extract bedroom from title
    $bedroomCount = extractBedroomCount($property->title);
    if ($bedroomCount !== null) {
        $canFixBedroom[] = [
            'property' => $property,
            'bedroom_count' => $bedroomCount
        ];
    } else {
        $cannotFixBedroom[] = $property;
    }
    
    // Try to extract bathroom from title
    $bathroomCount = extractBathroomCount($property->title);
    if ($bathroomCount !== null) {
        $canFixBathroom[] = [
            'property' => $property,
            'bathroom_count' => $bathroomCount
        ];
    } else {
        $cannotFixBathroom[] = $property;
    }
}

echo "=== ANALYSIS RESULTS ===\n\n";

echo "Bedroom Analysis:\n";
echo "  - Already has bedroom parameter: " . count($alreadyHasBedroom) . "\n";
echo "  - Can extract from title: " . count($canFixBedroom) . "\n";
echo "  - Cannot extract from title: " . count($cannotFixBedroom) . "\n\n";

echo "Bathroom Analysis:\n";
echo "  - Already has bathroom parameter: " . count($alreadyHasBathroom) . "\n";
echo "  - Can extract from title: " . count($canFixBathroom) . "\n";
echo "  - Cannot extract from title: " . count($cannotFixBathroom) . "\n\n";

// Show sample of properties that can be fixed
if (count($canFixBedroom) > 0) {
    echo "Sample properties that can be auto-fixed (Bedroom):\n";
    foreach (array_slice($canFixBedroom, 0, 10) as $item) {
        $prop = $item['property'];
        echo "  - ID: {$prop->id}, Title: {$prop->title}, Extracted: {$item['bedroom_count']} bedrooms\n";
    }
    if (count($canFixBedroom) > 10) {
        echo "  ... and " . (count($canFixBedroom) - 10) . " more\n";
    }
    echo "\n";
}

if (count($cannotFixBedroom) > 0) {
    echo "Sample properties that CANNOT be auto-fixed (Bedroom):\n";
    foreach (array_slice($cannotFixBedroom, 0, 10) as $prop) {
        echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
    }
    if (count($cannotFixBedroom) > 10) {
        echo "  ... and " . (count($cannotFixBedroom) - 10) . " more\n";
    }
    echo "\n";
}

// Ask if user wants to fix
echo "=== AUTO-FIX OPTION ===\n";
echo "This script can automatically assign bedroom/bathroom parameters for properties where\n";
echo "the information can be extracted from the title.\n\n";
echo "Properties that can be fixed:\n";
echo "  - Bedroom: " . count($canFixBedroom) . " properties\n";
echo "  - Bathroom: " . count($canFixBathroom) . " properties\n\n";

// For now, just show what would be fixed (comment out the actual fix)
$shouldFix = false; // Set to true to actually perform the fix

if ($shouldFix && count($canFixBedroom) > 0) {
    echo "Fixing bedroom parameters...\n";
    DB::beginTransaction();
    try {
        foreach ($canFixBedroom as $item) {
            $prop = $item['property'];
            $bedroomCount = $item['bedroom_count'];
            
            // Check if already exists
            $existing = AssignParameters::where('property_id', $prop->id)
                ->where('parameter_id', $bedroomParam->id)
                ->first();
            
            if ($existing) {
                $existing->value = (string)$bedroomCount;
                $existing->save();
            } else {
                $assignParam = new AssignParameters();
                $assignParam->property_id = $prop->id;
                $assignParam->parameter_id = $bedroomParam->id;
                $assignParam->value = (string)$bedroomCount;
                $assignParam->modal_type = 'App\\Models\\Property';
                $assignParam->modal_id = $prop->id;
                $assignParam->save();
            }
        }
        DB::commit();
        echo "Bedroom parameters fixed successfully!\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR fixing bedroom parameters: " . $e->getMessage() . "\n";
    }
}

if ($shouldFix && count($canFixBathroom) > 0) {
    echo "Fixing bathroom parameters...\n";
    DB::beginTransaction();
    try {
        foreach ($canFixBathroom as $item) {
            $prop = $item['property'];
            $bathroomCount = $item['bathroom_count'];
            
            // Check if already exists
            $existing = AssignParameters::where('property_id', $prop->id)
                ->where('parameter_id', $bathroomParam->id)
                ->first();
            
            if ($existing) {
                $existing->value = (string)$bathroomCount;
                $existing->save();
            } else {
                $assignParam = new AssignParameters();
                $assignParam->property_id = $prop->id;
                $assignParam->parameter_id = $bathroomParam->id;
                $assignParam->value = (string)$bathroomCount;
                $assignParam->modal_type = 'App\\Models\\Property';
                $assignParam->modal_id = $prop->id;
                $assignParam->save();
            }
        }
        DB::commit();
        echo "Bathroom parameters fixed successfully!\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR fixing bathroom parameters: " . $e->getMessage() . "\n";
    }
}

// Export detailed report
$csvFile = 'sell_properties_analysis.csv';
$fp = fopen($csvFile, 'w');
fputcsv($fp, ['Property ID', 'Title', 'Bedroom Status', 'Bedroom Value', 'Bathroom Status', 'Bathroom Value', 'Category ID']);

foreach ($sellProperties as $prop) {
    $bedroomStatus = 'Missing';
    $bedroomValue = '';
    $bathroomStatus = 'Missing';
    $bathroomValue = '';
    
    // Check bedroom
    $bedroomParamData = AssignParameters::where('property_id', $prop->id)
        ->where('parameter_id', $bedroomParam->id)
        ->first();
    
    if ($bedroomParamData && !empty($bedroomParamData->value)) {
        $bedroomStatus = 'Has Parameter';
        $bedroomValue = $bedroomParamData->value;
    } else {
        $extracted = extractBedroomCount($prop->title);
        if ($extracted !== null) {
            $bedroomStatus = 'Can Extract';
            $bedroomValue = $extracted;
        }
    }
    
    // Check bathroom
    $bathroomParamData = AssignParameters::where('property_id', $prop->id)
        ->where('parameter_id', $bathroomParam->id)
        ->first();
    
    if ($bathroomParamData && !empty($bathroomParamData->value)) {
        $bathroomStatus = 'Has Parameter';
        $bathroomValue = $bathroomParamData->value;
    } else {
        $extracted = extractBathroomCount($prop->title);
        if ($extracted !== null) {
            $bathroomStatus = 'Can Extract';
            $bathroomValue = $extracted;
        }
    }
    
    fputcsv($fp, [
        $prop->id,
        $prop->title,
        $bedroomStatus,
        $bedroomValue,
        $bathroomStatus,
        $bathroomValue,
        $prop->category_id
    ]);
}

fclose($fp);
echo "\nDetailed analysis exported to: $csvFile\n";

echo "\n=== DONE ===\n";
echo "\nNOTE: To actually fix the properties, set \$shouldFix = true in the script.\n";

