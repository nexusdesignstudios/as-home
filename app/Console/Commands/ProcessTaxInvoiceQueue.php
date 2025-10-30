<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyTaxInvoiceService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProcessTaxInvoiceQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:process-queue {--force : Force process even if not 15th}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending tax invoice queue entries (fallback for shared hosting)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');
        $today = Carbon::now();
        
        if (!$force && $today->day != 15) {
            $this->info("Not the 15th of the month. Use --force to override.");
            return Command::SUCCESS;
        }

        $this->info("Processing tax invoice queue...");

        try {
            $service = new MonthlyTaxInvoiceService();
            $queues = Cache::get('tax_invoice_queues', []);
            $processed = 0;
            $errors = 0;

            foreach ($queues as $key => $queue) {
                if ($queue['status'] === 'pending') {
                    $this->info("Processing queue entry for month: {$queue['month']}");
                    
                    try {
                        $results = $service->generateMonthlyTaxInvoices($queue['month']);
                        
                        // Mark as completed
                        $queue['status'] = 'completed';
                        $queue['completed_at'] = now();
                        $queue['results'] = $results;
                        $queues[$key] = $queue;
                        
                        $this->info("✓ Completed: {$queue['month']} - Owners: {$results['total_owners']}, Emails: {$results['total_emails_sent']}");
                        $processed++;
                        
                    } catch (\Exception $e) {
                        $queue['status'] = 'failed';
                        $queue['error'] = $e->getMessage();
                        $queue['failed_at'] = now();
                        $queues[$key] = $queue;
                        
                        $this->error("✗ Failed: {$queue['month']} - {$e->getMessage()}");
                        $errors++;
                    }
                }
            }

            // Update cache
            Cache::put('tax_invoice_queues', $queues, 3600);

            $this->info("Queue processing completed!");
            $this->info("Processed: {$processed}, Errors: {$errors}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process queue: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
