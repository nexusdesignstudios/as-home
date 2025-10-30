<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyTaxInvoiceService;
use Carbon\Carbon;

class TestTaxInvoiceGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tax-invoice {--month= : Specific month in Y-m format (e.g., 2025-01)} {--email= : Test email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test tax invoice generation and send to specific email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $monthYear = $this->option('month') ?? Carbon::now()->subMonth()->format('Y-m');
        $testEmail = $this->option('email');

        if (!$testEmail) {
            $this->error('Please provide a test email address using --email option');
            return Command::FAILURE;
        }

        $this->info("Testing tax invoice generation for: {$monthYear}");
        $this->info("Test email: {$testEmail}");

        try {
            $taxInvoiceService = new MonthlyTaxInvoiceService();
            $results = $taxInvoiceService->generateMonthlyTaxInvoices($monthYear);

            $this->info("Tax invoice generation completed!");
            $this->info("Total owners processed: {$results['total_owners']}");
            $this->info("Total emails sent: {$results['total_emails_sent']}");
            $this->info("Total errors: {$results['total_errors']}");

            if (!empty($results['errors'])) {
                $this->error("Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->error("- {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to generate tax invoices: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
