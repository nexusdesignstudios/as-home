<?php

// This is a temporary file to update categories - DELETE AFTER USE FOR SECURITY!

// Try to find the autoload file in different possible locations
$possiblePaths = [
    __DIR__ . '/vendor/autoload.php',             // If Laravel is in the same directory
    __DIR__ . '/../vendor/autoload.php',          // If Laravel is in the parent directory
    dirname(__DIR__) . '/vendor/autoload.php'     // Another way to check parent directory
];

$autoloadPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $autoloadPath = $path;
        break;
    }
}

if (!$autoloadPath) {
    die("Could not find the autoload.php file. Please make sure this script is in the correct location.");
}

// Similarly, find the bootstrap/app.php file
$possibleAppPaths = [
    __DIR__ . '/bootstrap/app.php',
    __DIR__ . '/../bootstrap/app.php',
    dirname(__DIR__) . '/bootstrap/app.php'
];

$appPath = null;
foreach ($possibleAppPaths as $path) {
    if (file_exists($path)) {
        $appPath = $path;
        break;
    }
}

if (!$appPath) {
    die("Could not find the bootstrap/app.php file. Please make sure this script is in the correct location.");
}

// Output the paths for debugging
echo "Using autoload from: " . $autoloadPath . "<br>";
echo "Using app from: " . $appPath . "<br>";

// Bootstrap the Laravel application
require $autoloadPath;
$app = require_once $appPath;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking database structure...<br>";

try {
    // Check if the property_classification column exists in the categories table
    if (Schema::hasTable('categories')) {
        echo "Categories table exists.<br>";

        if (!Schema::hasColumn('categories', 'property_classification')) {
            echo "Property classification column doesn't exist. Adding it now...<br>";

            // Add the column if it doesn't exist
            Schema::table('categories', function ($table) {
                $table->tinyInteger('property_classification')->default(1)->nullable()->after('parameter_types');
            });

            echo "Column added successfully!<br>";
        } else {
            echo "Property classification column already exists.<br>";
        }

        // Update all existing records to have a default value of 1
        $count = DB::table('categories')->whereNull('property_classification')->update(['property_classification' => 1]);
        echo "Updated $count categories with default property_classification value.<br>";

        echo "Categories update completed successfully!<br>";
    } else {
        echo "Categories table doesn't exist yet. Run migrations first.<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo nl2br($e->getTraceAsString());
}
