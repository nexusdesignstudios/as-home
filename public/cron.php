<?php
/**
 * Web-based cron job for shared hosting
 * This file can be called by external cron services
 */

// Prevent direct access
if (!isset($_GET['key']) || $_GET['key'] !== 'your-secret-key-here') {
    http_response_code(403);
    die('Access denied');
}

// Set the working directory
chdir(__DIR__ . '/..');

// Include Laravel bootstrap
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Check if it's the 15th of the month
$today = new DateTime();
if ($today->format('d') == '15') {
    // Run the tax invoice generation
    $kernel->call('tax:generate-monthly-invoices');
    echo "Tax invoices generated for " . $today->format('Y-m') . "\n";
} else {
    echo "Not the 15th of the month. Today is " . $today->format('Y-m-d') . "\n";
}

// Also run the general scheduler
$kernel->call('schedule:run');
echo "Scheduler executed\n";
?>
