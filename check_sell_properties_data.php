<?php

/**
 * Script to check sell properties for bedroom/bathroom data issues
 * 
 * This script checks:
 * 1. Sell properties (propery_type = 0) missing bedroom parameters
 * 2. Sell properties missing bathroom parameters
 * 3. Properties with null/empty bedroom/bathroom values
 * 4. Inconsistent data formats
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\parameter;
use Illuminate\Support\Facades\DB;

echo "=== Checking Sell Properties for Bedroom/Bathroom Data Issues ===\n\n";

// Get all sell properties (propery_type = 0)
$sellProperties = Property::where('propery_type', 0)
    ->where('status', 1) // Only active properties
    ->where('request_status', 'approved') // Only approved properties
    ->get();

echo "Total Sell Properties (active & approved): " . $sellProperties->count() . "\n\n";

// Find bedroom and bathroom parameter IDs
$bedroomParams = parameter::where(function($query) {
    $query->where('name', 'LIKE', '%bedroom%')
          ->orWhere('name', 'LIKE', '%bed%');
})->pluck('id', 'name')->toArray();

$bathroomParams = parameter::where(function($query) {
    $query->where('name', 'LIKE', '%bathroom%')
          ->orWhere('name', 'LIKE', '%bath%');
})->pluck('id', 'name')->toArray();

echo "Bedroom Parameters Found:\n";
foreach ($bedroomParams as $name => $id) {
    echo "  - ID: $id, Name: $name\n";
}
echo "\n";

echo "Bathroom Parameters Found:\n";
foreach ($bathroomParams as $name => $id) {
    echo "  - ID: $id, Name: $name\n";
}
echo "\n";

// Statistics
$missingBedroom = [];
$missingBathroom = [];
$emptyBedroom = [];
$emptyBathroom = [];
$invalidBedroom = [];
$invalidBathroom = [];

// Check each property
foreach ($sellProperties as $property) {
    $propertyId = $property->id;
    $propertyTitle = $property->title;
    
    // Get assigned parameters for this property
    $assignedParams = AssignParameters::where('property_id', $propertyId)
        ->whereIn('parameter_id', array_merge(array_values($bedroomParams), array_values($bathroomParams)))
        ->get()
        ->keyBy('parameter_id');
    
    // Check for bedroom
    $hasBedroom = false;
    $bedroomValue = null;
    $bedroomParamId = null;
    
    foreach ($bedroomParams as $paramName => $paramId) {
        if ($assignedParams->has($paramId)) {
            $hasBedroom = true;
            $bedroomParamId = $paramId;
            $bedroomValue = $assignedParams[$paramId]->value;
            break;
        }
    }
    
    if (!$hasBedroom) {
        $missingBedroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'category_id' => $property->category_id
        ];
    } elseif (empty($bedroomValue) || $bedroomValue === null || $bedroomValue === '') {
        $emptyBedroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'value' => $bedroomValue,
            'category_id' => $property->category_id
        ];
    } elseif (!is_numeric($bedroomValue) && !ctype_digit((string)$bedroomValue)) {
        $invalidBedroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'value' => $bedroomValue,
            'type' => gettype($bedroomValue),
            'category_id' => $property->category_id
        ];
    }
    
    // Check for bathroom
    $hasBathroom = false;
    $bathroomValue = null;
    $bathroomParamId = null;
    
    foreach ($bathroomParams as $paramName => $paramId) {
        if ($assignedParams->has($paramId)) {
            $hasBathroom = true;
            $bathroomParamId = $paramId;
            $bathroomValue = $assignedParams[$paramId]->value;
            break;
        }
    }
    
    if (!$hasBathroom) {
        $missingBathroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'category_id' => $property->category_id
        ];
    } elseif (empty($bathroomValue) || $bathroomValue === null || $bathroomValue === '') {
        $emptyBathroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'value' => $bathroomValue,
            'category_id' => $property->category_id
        ];
    } elseif (!is_numeric($bathroomValue) && !ctype_digit((string)$bathroomValue)) {
        $invalidBathroom[] = [
            'id' => $propertyId,
            'title' => $propertyTitle,
            'value' => $bathroomValue,
            'type' => gettype($bathroomValue),
            'category_id' => $property->category_id
        ];
    }
}

// Report results
echo "=== RESULTS ===\n\n";

echo "Properties Missing Bedroom Parameter: " . count($missingBedroom) . "\n";
if (count($missingBedroom) > 0) {
    echo "First 20 properties:\n";
    foreach (array_slice($missingBedroom, 0, 20) as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Category ID: {$prop['category_id']}\n";
    }
    if (count($missingBedroom) > 20) {
        echo "  ... and " . (count($missingBedroom) - 20) . " more\n";
    }
}
echo "\n";

echo "Properties with Empty Bedroom Value: " . count($emptyBedroom) . "\n";
if (count($emptyBedroom) > 0) {
    echo "First 20 properties:\n";
    foreach (array_slice($emptyBedroom, 0, 20) as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Value: " . var_export($prop['value'], true) . ", Category ID: {$prop['category_id']}\n";
    }
    if (count($emptyBedroom) > 20) {
        echo "  ... and " . (count($emptyBedroom) - 20) . " more\n";
    }
}
echo "\n";

echo "Properties with Invalid Bedroom Value (non-numeric): " . count($invalidBedroom) . "\n";
if (count($invalidBedroom) > 0) {
    echo "All properties:\n";
    foreach ($invalidBedroom as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Value: " . var_export($prop['value'], true) . " (Type: {$prop['type']}), Category ID: {$prop['category_id']}\n";
    }
}
echo "\n";

echo "Properties Missing Bathroom Parameter: " . count($missingBathroom) . "\n";
if (count($missingBathroom) > 0) {
    echo "First 20 properties:\n";
    foreach (array_slice($missingBathroom, 0, 20) as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Category ID: {$prop['category_id']}\n";
    }
    if (count($missingBathroom) > 20) {
        echo "  ... and " . (count($missingBathroom) - 20) . " more\n";
    }
}
echo "\n";

echo "Properties with Empty Bathroom Value: " . count($emptyBathroom) . "\n";
if (count($emptyBathroom) > 0) {
    echo "First 20 properties:\n";
    foreach (array_slice($emptyBathroom, 0, 20) as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Value: " . var_export($prop['value'], true) . ", Category ID: {$prop['category_id']}\n";
    }
    if (count($emptyBathroom) > 20) {
        echo "  ... and " . (count($emptyBathroom) - 20) . " more\n";
    }
}
echo "\n";

echo "Properties with Invalid Bathroom Value (non-numeric): " . count($invalidBathroom) . "\n";
if (count($invalidBathroom) > 0) {
    echo "All properties:\n";
    foreach ($invalidBathroom as $prop) {
        echo "  - ID: {$prop['id']}, Title: {$prop['title']}, Value: " . var_export($prop['value'], true) . " (Type: {$prop['type']}), Category ID: {$prop['category_id']}\n";
    }
}
echo "\n";

// Summary
echo "=== SUMMARY ===\n";
echo "Total Sell Properties Checked: " . $sellProperties->count() . "\n";
echo "Properties Missing Bedroom: " . count($missingBedroom) . "\n";
echo "Properties Missing Bathroom: " . count($missingBathroom) . "\n";
echo "Properties with Empty Bedroom: " . count($emptyBedroom) . "\n";
echo "Properties with Empty Bathroom: " . count($emptyBathroom) . "\n";
echo "Properties with Invalid Bedroom: " . count($invalidBedroom) . "\n";
echo "Properties with Invalid Bathroom: " . count($invalidBathroom) . "\n";

$totalIssues = count($missingBedroom) + count($missingBathroom) + count($emptyBedroom) + count($emptyBathroom) + count($invalidBedroom) + count($invalidBathroom);
echo "\nTotal Properties with Issues: " . $totalIssues . "\n";

// Export to CSV for detailed analysis
if ($totalIssues > 0) {
    $csvFile = 'sell_properties_data_issues.csv';
    $fp = fopen($csvFile, 'w');
    
    // Header
    fputcsv($fp, ['Property ID', 'Title', 'Issue Type', 'Field', 'Value', 'Category ID']);
    
    // Missing bedroom
    foreach ($missingBedroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Missing Parameter', 'Bedroom', '', $prop['category_id']]);
    }
    
    // Missing bathroom
    foreach ($missingBathroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Missing Parameter', 'Bathroom', '', $prop['category_id']]);
    }
    
    // Empty bedroom
    foreach ($emptyBedroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Empty Value', 'Bedroom', $prop['value'], $prop['category_id']]);
    }
    
    // Empty bathroom
    foreach ($emptyBathroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Empty Value', 'Bathroom', $prop['value'], $prop['category_id']]);
    }
    
    // Invalid bedroom
    foreach ($invalidBedroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Invalid Value', 'Bedroom', $prop['value'], $prop['category_id']]);
    }
    
    // Invalid bathroom
    foreach ($invalidBathroom as $prop) {
        fputcsv($fp, [$prop['id'], $prop['title'], 'Invalid Value', 'Bathroom', $prop['value'], $prop['category_id']]);
    }
    
    fclose($fp);
    echo "\nDetailed report exported to: $csvFile\n";
}

echo "\n=== DONE ===\n";

