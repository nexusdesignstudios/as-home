<?php

/**
 * Comprehensive script to verify vacation homes search functionality
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Vacation Homes Search Verification\n";
echo "========================================\n\n";

// 1. Check all vacation homes in database
echo "1. ALL Vacation Homes in Database:\n";
$allVacationHomes = Property::where('property_classification', 4)
    ->get(['id', 'title', 'status', 'request_status', 'propery_type', 'property_classification']);

echo "   Total found: {$allVacationHomes->count()}\n";
foreach ($allVacationHomes as $home) {
    echo "   - ID: {$home->id}, Title: {$home->title}, Status: {$home->status}, Request Status: {$home->request_status}, Property Type: {$home->propery_type}\n";
}
echo "\n";

// 2. Check approved and active vacation homes (what should show in search)
echo "2. Approved & Active Vacation Homes (Should Show in Search):\n";
$approvedVacationHomes = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereIn('propery_type', [0, 1])
    ->get(['id', 'title', 'status', 'request_status', 'propery_type']);

echo "   Total: {$approvedVacationHomes->count()}\n";
foreach ($approvedVacationHomes as $home) {
    echo "   - ID: {$home->id}, Title: {$home->title}\n";
}
echo "\n";

// 3. Check vacation apartments for each approved vacation home
echo "3. Vacation Apartments for Each Approved Vacation Home:\n";
$totalActiveApartments = 0;
foreach ($approvedVacationHomes as $home) {
    $apartments = VacationApartment::where('property_id', $home->id)
        ->where('status', 1)
        ->get(['id', 'apartment_number', 'status', 'bedrooms', 'bathrooms', 'price_per_night']);
    
    $activeCount = $apartments->where('status', 1)->count();
    $totalActiveApartments += $activeCount;
    
    echo "   Property ID {$home->id} ({$home->title}):\n";
    if ($apartments->count() > 0) {
        echo "      Total apartments: {$apartments->count()}\n";
        echo "      Active apartments: {$activeCount}\n";
        foreach ($apartments as $apt) {
            $statusText = $apt->status ? 'Active' : 'Inactive';
            echo "        - Apt #{$apt->apartment_number} (ID: {$apt->id}), Status: {$statusText}, Bedrooms: {$apt->bedrooms}, Bathrooms: {$apt->bathrooms}\n";
        }
    } else {
        echo "      No apartments found\n";
    }
    echo "\n";
}

// 4. Calculate expected search results
echo "4. Expected Search Results:\n";
$propertiesWithoutApts = 0;
$propertiesWithApts = 0;
$totalListings = 0;

foreach ($approvedVacationHomes as $home) {
    $apartments = VacationApartment::where('property_id', $home->id)
        ->where('status', 1)
        ->count();
    
    if ($apartments > 0) {
        $propertiesWithApts++;
        $totalListings += $apartments; // Each apartment = 1 listing
    } else {
        $propertiesWithoutApts++;
        $totalListings += 1; // Property without apartments = 1 listing
    }
}

echo "   Properties with apartments: {$propertiesWithApts}\n";
echo "   Properties without apartments: {$propertiesWithoutApts}\n";
echo "   Total active apartments: {$totalActiveApartments}\n";
echo "   Expected total listings: {$totalListings}\n";
echo "\n";

// 5. Simulate the search query
echo "5. Simulating Search Query (property_classification = 4):\n";
$searchQuery = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->where('property_classification', 4);

$totalFromQuery = $searchQuery->count();
echo "   Total properties from query: {$totalFromQuery}\n";

$searchResults = $searchQuery->with([
    'vacationApartments' => function($query) {
        $query->where('status', 1);
    }
])->get();

echo "   Properties with active apartments loaded:\n";
$expandedCount = 0;
foreach ($searchResults as $property) {
    $aptCount = $property->vacationApartments ? $property->vacationApartments->count() : 0;
    if ($aptCount > 0) {
        $expandedCount += $aptCount;
        echo "      - Property ID {$property->id}: {$aptCount} active apartments\n";
    } else {
        $expandedCount += 1;
        echo "      - Property ID {$property->id}: No apartments (counts as 1 listing)\n";
    }
}

echo "   Total expanded listings: {$expandedCount}\n";
echo "\n";

// 6. Check for any issues
echo "6. Potential Issues:\n";
$issues = [];

// Check for vacation homes with status != 1
$inactiveHomes = Property::where('property_classification', 4)
    ->where('status', '!=', 1)
    ->count();
if ($inactiveHomes > 0) {
    $issues[] = "Found {$inactiveHomes} vacation homes with status != 1 (inactive)";
}

// Check for vacation homes with request_status != 'approved'
$pendingHomes = Property::where('property_classification', 4)
    ->where('request_status', '!=', 'approved')
    ->count();
if ($pendingHomes > 0) {
    $issues[] = "Found {$pendingHomes} vacation homes with request_status != 'approved'";
}

// Check for vacation homes with propery_type not in [0, 1]
$wrongTypeHomes = Property::where('property_classification', 4)
    ->whereNotIn('propery_type', [0, 1])
    ->count();
if ($wrongTypeHomes > 0) {
    $issues[] = "Found {$wrongTypeHomes} vacation homes with propery_type not in [0, 1]";
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
echo "Total vacation homes in DB: {$allVacationHomes->count()}\n";
echo "Approved & active vacation homes: {$approvedVacationHomes->count()}\n";
echo "Total active apartments: {$totalActiveApartments}\n";
echo "Expected search listings: {$totalListings}\n";
echo "Simulated search results: {$expandedCount} listings\n";
echo "\n";

if ($totalListings != $expandedCount) {
    echo "⚠️  WARNING: Expected listings ({$totalListings}) doesn't match simulated results ({$expandedCount})\n";
} else {
    echo "✅ Expected listings match simulated results\n";
}

