<?php

/**
 * Script to assign "studio" as bedrooms value for properties with "studio" in their title
 * This ensures properties with "studio" in the title are properly tagged for filtering
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\parameter;

echo "========================================\n";
echo "Assign Studio Bedrooms from Title\n";
echo "========================================\n\n";

// Find bedroom parameter ID
$bedroomParam = parameter::where(function($q) {
    $q->where('name', 'LIKE', '%bedroom%')
      ->orWhere('name', 'LIKE', '%bed%');
})->first(['id', 'name']);

if (!$bedroomParam) {
    echo "ERROR: Bedroom parameter not found!\n";
    exit(1);
}

echo "Bedroom Parameter: ID={$bedroomParam->id}, Name='{$bedroomParam->name}'\n\n";

// Find all properties with "studio" in the title (case-insensitive)
$properties = Property::whereRaw('LOWER(title) LIKE ?', ['%studio%'])
    ->where('status', 1) // Only active properties
    ->get(['id', 'title', 'propery_type', 'property_classification']);

echo "Found " . $properties->count() . " properties with 'studio' in title\n\n";

if ($properties->count() == 0) {
    echo "No properties found. Exiting.\n";
    exit(0);
}

$updated = 0;
$created = 0;
$skipped = 0;
$errors = 0;

// Set to true to actually perform the updates
$shouldFix = true; // Change to false for dry-run

if (!$shouldFix) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n\n";
}

foreach ($properties as $property) {
    try {
        // Check if bedrooms parameter already exists for this property
        // Check both property_id and modal_id (polymorphic) relationships
        $existing = AssignParameters::where(function($query) use ($property) {
            $query->where('property_id', $property->id)
                ->orWhere(function($q) use ($property) {
                    $q->where('modal_id', $property->id)
                      ->where(function($typeQuery) {
                          $typeQuery->where('modal_type', 'App\\Models\\Property')
                                    ->orWhere('modal_type', 'property');
                      });
                });
        })
        ->where('parameter_id', $bedroomParam->id)
        ->first();
        
        if ($existing) {
            // Check if value is already "studio" or "0"
            $currentValue = strtolower(trim($existing->value));
            if ($currentValue === 'studio' || $currentValue === '0') {
                $skipped++;
                echo "  ✓ Property ID {$property->id}: Already has bedrooms='{$existing->value}' - Skipped\n";
                continue;
            }
            
            // Update existing parameter
            if ($shouldFix) {
                $existing->value = 'studio';
                $existing->save();
            }
            $updated++;
            echo "  ✓ Property ID {$property->id}: Updated bedrooms from '{$existing->getRawOriginal('value')}' to 'studio'\n";
            echo "    Title: {$property->title}\n";
        } else {
            // Create new parameter assignment
            if ($shouldFix) {
                $assignParam = new AssignParameters();
                $assignParam->parameter_id = $bedroomParam->id;
                $assignParam->value = 'studio';
                $assignParam->modal_type = 'App\\Models\\Property';
                $assignParam->modal_id = $property->id;
                $assignParam->property_id = $property->id; // Also set property_id for compatibility
                $assignParam->save();
            }
            $created++;
            echo "  ✓ Property ID {$property->id}: Created bedrooms='studio'\n";
            echo "    Title: {$property->title}\n";
        }
    } catch (\Exception $e) {
        $errors++;
        echo "  ✗ Property ID {$property->id}: ERROR - " . $e->getMessage() . "\n";
        echo "    Title: {$property->title}\n";
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total properties processed: " . $properties->count() . "\n";
echo "Created: {$created}\n";
echo "Updated: {$updated}\n";
echo "Skipped (already correct): {$skipped}\n";
echo "Errors: {$errors}\n";

if ($shouldFix) {
    echo "\n✅ Studio bedrooms assignment complete!\n";
} else {
    echo "\n⚠️  DRY RUN - No changes were made. Set \$shouldFix = true to apply changes.\n";
}

echo "\n";

