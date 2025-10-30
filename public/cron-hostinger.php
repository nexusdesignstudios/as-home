<?php
/**
 * Hostinger-specific cron job with multiple fallback mechanisms
 * This ensures tax invoices are sent even if one method fails
 */

// Security key - CHANGE THIS!
$secret_key = 'hostinger_tax_invoice_2025_secure_key_12345';

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

// Get current date
$today = new DateTime();
$is_15th = $today->format('d') == '15';
$current_month = $today->format('Y-m');

// Log the attempt
$log_message = "Hostinger cron executed at " . $today->format('Y-m-d H:i:s') . " - Day: " . $today->format('d');
file_put_contents('storage/logs/hostinger-cron.log', $log_message . "\n", FILE_APPEND);

if ($is_15th) {
    echo "Today is the 15th - Generating tax invoices for {$current_month}\n";
    
    try {
        // Method 1: Direct command execution
        $exitCode = $kernel->call('tax:generate-monthly-invoices', [
            '--month' => $current_month
        ]);
        
        if ($exitCode === 0) {
            echo "Tax invoices generated successfully via direct command\n";
            file_put_contents('storage/logs/hostinger-cron.log', "SUCCESS: Direct command execution\n", FILE_APPEND);
        } else {
            echo "Direct command failed, trying alternative method\n";
            file_put_contents('storage/logs/hostinger-cron.log', "WARNING: Direct command failed, trying alternative\n", FILE_APPEND);
            
            // Method 2: Alternative execution
            $this->executeAlternativeMethod($current_month);
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        file_put_contents('storage/logs/hostinger-cron.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        
        // Method 3: Fallback execution
        $this->executeFallbackMethod($current_month);
    }
    
} else {
    echo "Not the 15th of the month. Today is " . $today->format('Y-m-d') . "\n";
    
    // Still run the general scheduler for other tasks
    try {
        $kernel->call('schedule:run');
        echo "General scheduler executed\n";
    } catch (Exception $e) {
        echo "Scheduler error: " . $e->getMessage() . "\n";
    }
}

/**
 * Alternative method using direct service call
 */
function executeAlternativeMethod($month) {
    try {
        $app = require_once 'bootstrap/app.php';
        $service = $app->make(\App\Services\MonthlyTaxInvoiceService::class);
        $results = $service->generateMonthlyTaxInvoices($month);
        
        echo "Alternative method successful - Owners: {$results['total_owners']}, Emails: {$results['total_emails_sent']}\n";
        file_put_contents('storage/logs/hostinger-cron.log', "SUCCESS: Alternative method - Owners: {$results['total_owners']}\n", FILE_APPEND);
        
    } catch (Exception $e) {
        echo "Alternative method failed: " . $e->getMessage() . "\n";
        file_put_contents('storage/logs/hostinger-cron.log', "ERROR: Alternative method failed - " . $e->getMessage() . "\n", FILE_APPEND);
        
        // Try fallback
        executeFallbackMethod($month);
    }
}

/**
 * Fallback method using database queue
 */
function executeFallbackMethod($month) {
    try {
        $app = require_once 'bootstrap/app.php';
        
        // Create a simple queue entry in database
        $queueData = [
            'command' => 'tax:generate-monthly-invoices',
            'month' => $month,
            'created_at' => now(),
            'status' => 'pending'
        ];
        
        // Store in a simple table or cache
        \Cache::put('tax_invoice_queue_' . $month, $queueData, 3600); // 1 hour
        
        echo "Fallback method: Queue entry created for {$month}\n";
        file_put_contents('storage/logs/hostinger-cron.log', "FALLBACK: Queue entry created for {$month}\n", FILE_APPEND);
        
        // Try to process the queue immediately
        processQueue();
        
    } catch (Exception $e) {
        echo "Fallback method failed: " . $e->getMessage() . "\n";
        file_put_contents('storage/logs/hostinger-cron.log', "CRITICAL: All methods failed - " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

/**
 * Process the queue
 */
function processQueue() {
    try {
        $app = require_once 'bootstrap/app.php';
        $service = $app->make(\App\Services\MonthlyTaxInvoiceService::class);
        
        // Get all pending queue entries
        $queues = \Cache::get('tax_invoice_queues', []);
        
        foreach ($queues as $key => $queue) {
            if ($queue['status'] === 'pending') {
                $results = $service->generateMonthlyTaxInvoices($queue['month']);
                
                // Mark as completed
                $queue['status'] = 'completed';
                $queue['completed_at'] = now();
                $queue['results'] = $results;
                $queues[$key] = $queue;
                
                echo "Queue processed for {$queue['month']} - Emails: {$results['total_emails_sent']}\n";
            }
        }
        
        \Cache::put('tax_invoice_queues', $queues, 3600);
        
    } catch (Exception $e) {
        echo "Queue processing failed: " . $e->getMessage() . "\n";
    }
}

echo "Hostinger cron execution completed\n";
?>
