<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyTaxInvoiceService;
use Carbon\Carbon;

class GenerateMonthlyTaxInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:generate-monthly-invoices
                            {--month= : The month to generate invoices for (format: YYYY-MM, e.g., 2025-01)}
                            {--dry-run : Run without sending emails to test the process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send monthly tax invoices to property owners';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month');
        $dryRun = $this->option('dry-run');

        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Invalid month format. Use YYYY-MM (e.g., 2025-01)');
            return 1;
        }

        $this->info('Starting monthly tax invoice generation...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No emails will be sent');
        }

        if ($month) {
            $this->info("Generating invoices for month: {$month}");
        } else {
            $this->info('Generating invoices for current month: ' . Carbon::now()->format('Y-m'));
        }

        try {
            $service = new MonthlyTaxInvoiceService();
            $results = $service->generateMonthlyTaxInvoices($month);

            $this->info('Monthly tax invoice generation completed!');
            $this->info("Total owners processed: {$results['total_owners']}");
            $this->info("Total emails sent: {$results['total_emails_sent']}");
            $this->info("Total errors: {$results['total_errors']}");

            if (!empty($results['errors'])) {
                $this->error('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->error("- {$error}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error generating monthly tax invoices: ' . $e->getMessage());
            return 1;
        }
    }
}
