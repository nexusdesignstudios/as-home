<?php

// Check if any properties meet the basic requirements
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Checking Basic Property Requirements ===\n\n";

// Check total properties
echo "1. Total properties in database:\n";
$totalProperties = Property::count();
echo "   Total: {$totalProperties}\n\n";

// Check properties with basic status requirements
echo "2. Properties with status = 1:\n";
$status1Count = Property::where('status', 1)->count();
echo "   Count: {$status1Count}\n\n";

// Check properties with request_status = 'approved'
echo "3. Properties with request_status = 'approved':\n";
$approvedCount = Property::where('request_status', 'approved')->count();
echo "   Count: {$approvedCount}\n\n";

// Check properties with both requirements
echo "4. Properties with status = 1 AND request_status = 'approved':\n";
$basicCount = Property::where('status', 1)
    ->where('request_status', 'approved')
    ->count();
echo "   Count: {$basicCount}\n\n";

// Check vacation homes
echo "5. Vacation homes (property_classification = 4):\n";
$vacationCount = Property::where('status', 1)
    ->where('request_status', 'approved')
    ->where('property_classification', 4)
    ->count();
echo "   Count: {$vacationCount}\n\n";

// Show some sample vacation homes
echo "6. Sample vacation homes:\n";
$sampleVacation = Property::where('status', 1)
    ->where('request_status', 'approved')
    ->where('property_classification', 4)
    ->limit(5)
    ->get(['id', 'title', 'property_classification']);

foreach ($sampleVacation as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n=== Analysis ===\n";
if ($basicCount == 0) {
    echo "❌ ISSUE: No properties meet the basic requirements (status=1, request_status='approved')\n";
    echo "   This explains why the API returns 0 results.\n";
} else {
    echo "✅ Properties exist that meet basic requirements.\n";
    
    if ($vacationCount == 0) {
        echo "❌ No vacation homes found.\n";
    } else {
        echo "✅ Vacation homes exist.\n";
    }
}