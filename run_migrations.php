<?php

// This is a temporary file to run migrations - DELETE AFTER USE FOR SECURITY!

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

// Run the migrations
echo "Running migrations...<br>";
try {
    // Artisan::call doesn't output anything, so we capture it
    $output = new \Symfony\Component\Console\Output\BufferedOutput;
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true], $output);
    echo nl2br($output->fetch());
    echo "<br>Migrations completed!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo nl2br($e->getTraceAsString());
}
