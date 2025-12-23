<?php

/**
 * Script to check vacation homes in search results
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;

echo "========================================\n";
echo "Vacation Homes Search Check\n";
echo "========================================\n\n";

// 1. Count all vacation homes (approved and active)
echo "1. Total Vacation Homes in Database:\n";
$totalVacationHomes = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereIn('propery_type', [0, 1])
    ->count();
echo "   Total: {$totalVacationHomes}\n\n";

// 2. Count vacation homes with apartments
echo "2. Vacation Homes with Apartments:\n";
$vacationHomesWithApts = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereIn('propery_type', [0, 1])
    ->with('vacationApartments')
    ->get();

$withApts = 0;
$withoutApts = 0;
$totalApartments = 0;

foreach ($vacationHomesWithApts as $property) {
    if ($property->vacationApartments && $property->vacationApartments->count() > 0) {
        $withApts++;
        $totalApartments += $property->vacationApartments->count();
    } else {
        $withoutApts++;
    }
}

echo "   With apartments: {$withApts}\n";
echo "   Without apartments: {$withoutApts}\n";
echo "   Total apartments: {$totalApartments}\n\n";

// 3. Calculate expected total listings
echo "3. Expected Total Listings in Search:\n";
$expectedListings = $withApts * 0 + $totalApartments + $withoutApts; // Each apartment becomes a listing, properties without apts stay as 1
// Actually: properties with apartments are replaced by apartment listings
// Properties without apartments stay as 1 listing each
$expectedListings = $totalApartments + $withoutApts;
echo "   Expected: {$expectedListings} listings\n";
echo "   (Each apartment = 1 listing, properties without apartments = 1 listing each)\n\n";

// 4. Show sample vacation homes
echo "4. Sample Vacation Homes:\n";
$sampleHomes = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereIn('propery_type', [0, 1])
    ->with('vacationApartments')
    ->take(5)
    ->get(['id', 'title', 'status', 'request_status']);

foreach ($sampleHomes as $home) {
    $aptCount = $home->vacationApartments ? $home->vacationApartments->count() : 0;
    echo "   - ID: {$home->id}, Title: {$home->title}, Apartments: {$aptCount}\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total vacation homes: {$totalVacationHomes}\n";
echo "With apartments: {$withApts}\n";
echo "Without apartments: {$withoutApts}\n";
echo "Total apartments: {$totalApartments}\n";
echo "Expected listings: {$expectedListings}\n";
echo "\n";
echo "If search shows only 2 results, check:\n";
echo "1. Are filters excluding some vacation homes?\n";
echo "2. Is pagination limiting results?\n";
echo "3. Are vacation homes missing apartments being excluded?\n";

