<?php

// Check property_type values in database
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Property Type Values Analysis ===\n\n";

// Check unique property_type values
echo "1. Unique property_type values:\n";
$propertyTypes = Property::select('propery_type')
    ->distinct()
    ->orderBy('propery_type')
    ->get();

foreach ($propertyTypes as $type) {
    echo "   - '{$type->propery_type}' (type: " . gettype($type->propery_type) . ")\n";
}

// Check property 333 specifically
echo "\n2. Property 333 details:\n";
$prop333 = Property::find(333);
echo "   - propery_type: '{$prop333->propery_type}' (type: " . gettype($prop333->propery_type) . ")\n";
echo "   - Classification: {$prop333->property_classification}\n\n";

// Test the actual filter
echo "3. Testing property_type filter:\n";

// Test with string 'rent'
echo "   Testing with propery_type = 'rent':\n";
$count1 = Property::where('status', 1)
    ->where('request_status', 'approved')
    ->where('propery_type', 'rent')
    ->where('property_classification', 4)
    ->count();
echo "   Count: {$count1}\n";

// Test with integer 1
echo "   Testing with propery_type = 1:\n";
$count2 = Property::where('status', 1)
    ->where('request_status', 'approved')
    ->where('propery_type', 1)
    ->where('property_classification', 4)
    ->count();
echo "   Count: {$count2}\n";

// Check what values vacation homes have
echo "\n4. Vacation homes property_type values:\n";
$vacationTypes = Property::select('propery_type', DB::raw('COUNT(*) as count'))
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where('property_classification', 4)
    ->groupBy('propery_type')
    ->get();

foreach ($vacationTypes as $type) {
    echo "   - '{$type->propery_type}': {$type->count} properties\n";
}

echo "\n=== Conclusion ===\n";
if ($count1 > 0 && $count2 == 0) {
    echo "❌ ISSUE: Database uses string 'rent' but API expects integer 1\n";
    echo "   The controller is filtering for propery_type = 1 but vacation homes use 'rent'\n";
} elseif ($count1 == 0 && $count2 > 0) {
    echo "✅ Database uses integer 1 as expected\n";
} else {
    echo "⚠️  Mixed property_type values found\n";
}