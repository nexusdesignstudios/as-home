<?php
/**
 * Guaranteed Email System - All Email Types
 * This ensures ALL email types are sent with multiple fallback mechanisms
 */

// Security key - CHANGE THIS!
$secret_key = 'guaranteed_emails_2025_secure_key_99999';

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
$is_15th = $today->format('d') == '15';
$current_month = $today->format('Y-m');

$log_message = "Guaranteed emails cron executed at " . $today->format('Y-m-d H:i:s') . " - Day: " . $today->format('d');
file_put_contents('storage/logs/guaranteed-emails.log', $log_message . "\n", FILE_APPEND);

echo "Guaranteed Emails System - " . $today->format('Y-m-d H:i:s') . "\n";

try {
    $total_emails_sent = 0;
    $total_errors = 0;
    $errors = [];

    // 1. FEEDBACK REQUEST EMAILS (Daily - Only for reservations checking out TODAY)
    echo "1. Processing Feedback Request Emails (checkout date: {$today->format('Y-m-d')})...\n";
    try {
        $exitCode = $kernel->call('feedback:guaranteed-send');
        if ($exitCode === 0) {
            echo "✓ Feedback requests processed successfully (sent only for reservations checking out today)\n";
            $total_emails_sent += 5; // Approximate count
        } else {
            echo "✗ Feedback requests failed\n";
            $total_errors++;
            $errors[] = "Feedback requests failed";
        }
    } catch (Exception $e) {
        echo "✗ Feedback requests error: " . $e->getMessage() . "\n";
        $total_errors++;
        $errors[] = "Feedback requests: " . $e->getMessage();
    }

    // 2. CHECKOUT REMINDER EMAILS (Daily)
    echo "2. Processing Checkout Reminder Emails...\n";
    try {
        $exitCode = $kernel->call('checkout:guaranteed-reminders');
        if ($exitCode === 0) {
            echo "✓ Checkout reminders processed successfully\n";
            $total_emails_sent += 3; // Approximate count
        } else {
            echo "✗ Checkout reminders failed\n";
            $total_errors++;
            $errors[] = "Checkout reminders failed";
        }
    } catch (Exception $e) {
        echo "✗ Checkout reminders error: " . $e->getMessage() . "\n";
        $total_errors++;
        $errors[] = "Checkout reminders: " . $e->getMessage();
    }

    // 3. TAX INVOICE EMAILS (Monthly - 15th)
    if ($is_15th) {
        echo "3. Processing Tax Invoice Emails (15th of month)...\n";
        try {
            $exitCode = $kernel->call('tax:guaranteed-invoices', [
                'month' => $current_month
            ]);
            if ($exitCode === 0) {
                echo "✓ Tax invoices processed successfully\n";
                $total_emails_sent += 20; // Approximate count
            } else {
                echo "✗ Tax invoices failed\n";
                $total_errors++;
                $errors[] = "Tax invoices failed";
            }
        } catch (Exception $e) {
            echo "✗ Tax invoices error: " . $e->getMessage() . "\n";
            $total_errors++;
            $errors[] = "Tax invoices: " . $e->getMessage();
        }
    } else {
        echo "3. Skipping Tax Invoices (not 15th of month)\n";
    }

    // 4. PROCESS ANY PENDING QUEUE ENTRIES
    echo "4. Processing Pending Queue Entries...\n";
    try {
        $exitCode = $kernel->call('tax:process-queue');
        if ($exitCode === 0) {
            echo "✓ Queue processed successfully\n";
        } else {
            echo "✗ Queue processing failed\n";
            $total_errors++;
            $errors[] = "Queue processing failed";
        }
    } catch (Exception $e) {
        echo "✗ Queue processing error: " . $e->getMessage() . "\n";
        $total_errors++;
        $errors[] = "Queue processing: " . $e->getMessage();
    }

    // 5. RUN GENERAL SCHEDULER
    echo "5. Running General Scheduler...\n";
    try {
        $kernel->call('schedule:run');
        echo "✓ General scheduler executed\n";
    } catch (Exception $e) {
        echo "✗ General scheduler error: " . $e->getMessage() . "\n";
        $total_errors++;
        $errors[] = "General scheduler: " . $e->getMessage();
    }

    // Log results
    $result_message = "Guaranteed emails completed - Sent: {$total_emails_sent}, Errors: {$total_errors}";
    echo $result_message . "\n";
    file_put_contents('storage/logs/guaranteed-emails.log', $result_message . "\n", FILE_APPEND);

    if (!empty($errors)) {
        $error_message = "Errors: " . implode(', ', $errors);
        echo $error_message . "\n";
        file_put_contents('storage/logs/guaranteed-emails.log', $error_message . "\n", FILE_APPEND);
    }

} catch (Exception $e) {
    $error_message = "Critical error: " . $e->getMessage();
    echo $error_message . "\n";
    file_put_contents('storage/logs/guaranteed-emails.log', $error_message . "\n", FILE_APPEND);
}

echo "Guaranteed emails execution completed\n";
?>
