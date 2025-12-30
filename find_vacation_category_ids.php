<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;

echo "================================================\n";
echo "Finding Category IDs for Vacation Homes\n";
echo "================================================\n\n";

// Find all category_ids used by vacation homes
$categoryIds = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('category_id', DB::raw('count(*) as count'))
    ->groupBy('category_id')
    ->orderBy('count', 'desc')
    ->get();

echo "Category IDs used by Vacation Homes:\n";
echo str_repeat("-", 50) . "\n";
foreach ($categoryIds as $cat) {
    $categoryName = DB::table('categories')->where('id', $cat->category_id)->value('category') ?? 'Unknown';
    echo "Category ID: {$cat->category_id} ({$categoryName}) - {$cat->count} properties\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Find vacation homes with property_type = 1 (Rent) and their category_ids
echo "Vacation Homes for Rent (property_type=1) by Category:\n";
echo str_repeat("-", 50) . "\n";

$rentCategoryIds = Property::where('property_classification', 4)
    ->where('propery_type', 1)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('category_id', DB::raw('count(*) as count'))
    ->groupBy('category_id')
    ->orderBy('count', 'desc')
    ->get();

foreach ($rentCategoryIds as $cat) {
    $categoryName = DB::table('categories')->where('id', $cat->category_id)->value('category') ?? 'Unknown';
    echo "Category ID: {$cat->category_id} ({$categoryName}) - {$cat->count} properties\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Show sample properties with their category_ids
echo "Sample Vacation Homes (first 10):\n";
echo str_repeat("-", 50) . "\n";

$sampleProperties = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->with('category:id,category')
    ->limit(10)
    ->get();

foreach ($sampleProperties as $prop) {
    $categoryName = $prop->category ? $prop->category->category : 'No Category';
    echo "ID: {$prop->id}, Title: {$prop->title}\n";
    echo "  Category ID: {$prop->category_id} ({$categoryName}), Property Type: {$prop->propery_type}\n";
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "Diagnostic Complete\n";
echo str_repeat("=", 50) . "\n";

