<?php
/**
 * Hostinger Daily Cron - Runs every day to check for pending tasks
 * This ensures tax invoices are sent even if the monthly cron fails
 */

// Security key - CHANGE THIS!
$secret_key = 'hostinger_daily_cron_2025_secure_key_67890';

// Prevent direct access
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
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

$today = new DateTime();
$log_message = "Hostinger daily cron executed at " . $today->format('Y-m-d H:i:s');
file_put_contents('storage/logs/hostinger-daily-cron.log', $log_message . "\n", FILE_APPEND);

echo "Hostinger Daily Cron - " . $today->format('Y-m-d H:i:s') . "\n";

try {
    // Check if it's the 15th and we haven't sent invoices yet
    if ($today->format('d') == '15') {
        $lastInvoiceSent = \Cache::get('last_tax_invoice_sent_' . $today->format('Y-m'));
        
        if (!$lastInvoiceSent) {
            echo "15th of month detected - checking for pending tax invoices\n";
            
            // Try to send tax invoices
            $exitCode = $kernel->call('tax:generate-monthly-invoices', [
                '--month' => $today->format('Y-m')
            ]);
            
            if ($exitCode === 0) {
                \Cache::put('last_tax_invoice_sent_' . $today->format('Y-m'), true, 86400 * 30); // 30 days
                echo "Tax invoices sent successfully\n";
                file_put_contents('storage/logs/hostinger-daily-cron.log', "SUCCESS: Tax invoices sent for " . $today->format('Y-m') . "\n", FILE_APPEND);
            } else {
                echo "Primary method failed, trying backup method\n";
                
                // Try backup method
                $backupExitCode = $kernel->call('tax:backup-send', [
                    'month' => $today->format('Y-m')
                ]);
                
                if ($backupExitCode === 0) {
                    \Cache::put('last_tax_invoice_sent_' . $today->format('Y-m'), true, 86400 * 30);
                    echo "Backup tax invoices sent successfully\n";
                    file_put_contents('storage/logs/hostinger-daily-cron.log', "SUCCESS: Backup tax invoices sent for " . $today->format('Y-m') . "\n", FILE_APPEND);
                } else {
                    echo "Both methods failed - will retry tomorrow\n";
                    file_put_contents('storage/logs/hostinger-daily-cron.log', "WARNING: Both methods failed for " . $today->format('Y-m') . "\n", FILE_APPEND);
                }
            }
        } else {
            echo "Tax invoices already sent for this month\n";
        }
    } else {
        echo "Not the 15th of the month\n";
    }
    
    // Always run the general scheduler for other tasks
    $kernel->call('schedule:run');
    echo "General scheduler executed\n";
    
    // Process any pending queue entries
    $kernel->call('tax:process-queue');
    echo "Queue processor executed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    file_put_contents('storage/logs/hostinger-daily-cron.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

echo "Daily cron execution completed\n";
?>
