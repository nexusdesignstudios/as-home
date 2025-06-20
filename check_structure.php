<?php
// This is a simple script to check the Laravel directory structure
// DELETE AFTER USE FOR SECURITY!

echo "<h1>Laravel Directory Structure Check</h1>";

echo "<h2>Current Directory</h2>";
echo "Current script location: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Parent directory: " . dirname(__DIR__) . "<br>";

echo "<h2>Checking for Laravel Files</h2>";

$checkPaths = [
    // Check current directory
    __DIR__ . '/artisan' => 'Artisan in current directory',
    __DIR__ . '/vendor/autoload.php' => 'Composer autoload in current directory',
    __DIR__ . '/bootstrap/app.php' => 'Bootstrap app in current directory',

    // Check parent directory
    dirname(__DIR__) . '/artisan' => 'Artisan in parent directory',
    dirname(__DIR__) . '/vendor/autoload.php' => 'Composer autoload in parent directory',
    dirname(__DIR__) . '/bootstrap/app.php' => 'Bootstrap app in parent directory',
];

echo "<ul>";
foreach ($checkPaths as $path => $description) {
    echo "<li>$description: " . (file_exists($path) ? "✅ Found" : "❌ Not found") . " ($path)</li>";
}
echo "</ul>";

echo "<h2>Directory Listing</h2>";
echo "<h3>Current Directory:</h3>";
echo "<ul>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file" . (is_dir(__DIR__ . '/' . $file) ? ' (directory)' : '') . "</li>";
    }
}
echo "</ul>";

echo "<h3>Parent Directory:</h3>";
echo "<ul>";
$parentFiles = scandir(dirname(__DIR__));
foreach ($parentFiles as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file" . (is_dir(dirname(__DIR__) . '/' . $file) ? ' (directory)' : '') . "</li>";
    }
}
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<p>Based on the results above:</p>";
echo "<ol>";
echo "<li>If you see Laravel files in the current directory, place the migration scripts in the same directory as this file.</li>";
echo "<li>If you see Laravel files in the parent directory, place the migration scripts in the parent directory.</li>";
echo "<li>If you don't see Laravel files in either place, you may need to check with your hosting provider about the correct location of your Laravel application.</li>";
echo "</ol>";
