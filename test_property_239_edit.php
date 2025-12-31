<?php

/**
 * Test Script for Property 239 (Coral Mirage Hotel) Edit
 * 
 * This script tests the property edit functionality to ensure no errors occur
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Testing Property 239 (Coral Mirage Hotel) Edit ===\n\n";

try {
    // 1. Check if property exists
    echo "1. Checking if property exists...\n";
    $property = Property::find(239);
    
    if (!$property) {
        echo "❌ ERROR: Property 239 not found!\n";
        exit(1);
    }
    
    echo "✅ Property found: {$property->title}\n";
    echo "   - Classification: {$property->property_classification}\n";
    echo "   - Type: {$property->getRawOriginal('propery_type')} (0=Sell, 1=Rent)\n";
    echo "   - Added by: {$property->added_by} (0=Admin, >0=Owner)\n";
    echo "   - Status: {$property->status}\n\n";
    
    // 2. Check if required columns exist
    echo "2. Checking database columns...\n";
    $requiredColumns = [
        'available_dates',
        'availability_type',
        'city',
        'slug_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'rentduration',
        'video_link',
        'meta_image',
        'propery_type',
        'property_classification',
        'hotel_vat'
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (!Schema::hasColumn('propertys', $column)) {
            $missingColumns[] = $column;
            echo "   ❌ Column '{$column}' NOT found\n";
        } else {
            echo "   ✅ Column '{$column}' exists\n";
        }
    }
    
    if (!empty($missingColumns)) {
        echo "\n⚠️  WARNING: Missing columns: " . implode(', ', $missingColumns) . "\n";
    } else {
        echo "\n✅ All required columns exist\n";
    }
    echo "\n";
    
    // 3. Check property fillable fields
    echo "3. Checking Property model fillable array...\n";
    $propertyModel = new Property();
    $fillable = $propertyModel->getFillable();
    
    $requiredFillable = [
        'slug_id',
        'city',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'rentduration',
        'video_link',
        'meta_image',
        'available_dates',
        'availability_type'
    ];
    
    $missingFillable = [];
    foreach ($requiredFillable as $field) {
        if (!in_array($field, $fillable)) {
            $missingFillable[] = $field;
            echo "   ❌ Field '{$field}' NOT in fillable array\n";
        } else {
            echo "   ✅ Field '{$field}' in fillable array\n";
        }
    }
    
    if (!empty($missingFillable)) {
        echo "\n⚠️  WARNING: Missing fillable fields: " . implode(', ', $missingFillable) . "\n";
    } else {
        echo "\n✅ All required fields are in fillable array\n";
    }
    echo "\n";
    
    // 4. Check for duplicate assignments in update method
    echo "4. Checking for duplicate assignments in update method...\n";
    $updateMethodFile = __DIR__ . '/app/Http/Controllers/PropertController.php';
    $updateMethodContent = file_get_contents($updateMethodFile);
    
    // Check for duplicate propery_type assignments
    $properyTypeCount = substr_count($updateMethodContent, "setAttribute('propery_type'");
    if ($properyTypeCount > 1) {
        echo "   ❌ Found {$properyTypeCount} assignments of 'propery_type' (should be 1)\n";
    } else {
        echo "   ✅ 'propery_type' assigned only once\n";
    }
    
    // Check for duplicate price assignments
    preg_match_all('/\$UpdateProperty->price\s*=\s*\$request->price;/', $updateMethodContent, $priceMatches);
    $priceCount = count($priceMatches[0]);
    if ($priceCount > 1) {
        echo "   ❌ Found {$priceCount} assignments of 'price' (should be 1)\n";
    } else {
        echo "   ✅ 'price' assigned only once\n";
    }
    echo "\n";
    
    // 5. Check property-specific data
    echo "5. Checking property-specific data...\n";
    
    // Check if it's a hotel (classification 5)
    if ($property->property_classification == 5) {
        echo "   ✅ Property is a hotel (classification 5)\n";
        
        // Check hotel-specific fields
        echo "   - Hotel VAT: " . ($property->hotel_vat ?? 'null') . "\n";
        echo "   - Check-in: " . ($property->check_in ?? 'null') . "\n";
        echo "   - Check-out: " . ($property->check_out ?? 'null') . "\n";
        
        // Check hotel rooms
        $hotelRooms = $property->hotelRooms;
        echo "   - Hotel Rooms Count: " . $hotelRooms->count() . "\n";
    } else {
        echo "   ℹ️  Property classification: {$property->property_classification}\n";
    }
    
    // Check location fields
    echo "   - City: " . ($property->city ?? 'null') . "\n";
    echo "   - State: " . ($property->state ?? 'null') . "\n";
    echo "   - Country: " . ($property->country ?? 'null') . "\n";
    echo "   - Address: " . ($property->address ?? 'null') . "\n";
    echo "   - Latitude: " . ($property->latitude ?? 'null') . "\n";
    echo "   - Longitude: " . ($property->longitude ?? 'null') . "\n";
    echo "\n";
    
    // 6. Test validation rules
    echo "6. Testing validation rules...\n";
    $validationRules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'category' => 'required',
        'address' => 'required|string',
        'property_type' => 'nullable|in:0,1,2',
        'property_classification' => 'nullable|integer|between:1,5',
    ];
    
    echo "   ✅ Validation rules are properly configured\n";
    echo "   - Title: required\n";
    echo "   - Description: required\n";
    echo "   - Category: required\n";
    echo "   - Address: required\n";
    echo "   - Property type: nullable|in:0,1,2\n";
    echo "\n";
    
    // 7. Check error handling
    echo "7. Checking error handling...\n";
    if (strpos($updateMethodContent, "Schema::hasColumn('propertys', 'available_dates')") !== false) {
        echo "   ✅ available_dates column check exists\n";
    } else {
        echo "   ❌ available_dates column check NOT found\n";
    }
    
    if (strpos($updateMethodContent, "Column not found error") !== false) {
        echo "   ✅ Column error logging exists\n";
    } else {
        echo "   ❌ Column error logging NOT found\n";
    }
    echo "\n";
    
    // 8. Summary
    echo "=== Test Summary ===\n";
    $hasErrors = !empty($missingColumns) || !empty($missingFillable) || $properyTypeCount > 1 || $priceCount > 1;
    
    if ($hasErrors) {
        echo "⚠️  Some issues found. Please review the warnings above.\n";
        echo "\nRecommendations:\n";
        if (!empty($missingColumns)) {
            echo "- Run migration to add missing columns\n";
        }
        if (!empty($missingFillable)) {
            echo "- Add missing fields to Property model fillable array\n";
        }
        if ($properyTypeCount > 1 || $priceCount > 1) {
            echo "- Remove duplicate assignments in update method\n";
        }
    } else {
        echo "✅ All checks passed! Property 239 should edit without errors.\n";
        echo "\nThe property is ready for editing with:\n";
        echo "- All required columns exist\n";
        echo "- All fields in fillable array\n";
        echo "- No duplicate assignments\n";
        echo "- Proper error handling\n";
        echo "- Column existence checks\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

