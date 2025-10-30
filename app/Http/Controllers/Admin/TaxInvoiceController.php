<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonthlyTaxInvoiceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaxInvoiceController extends Controller
{
    protected $taxInvoiceService;

    public function __construct(MonthlyTaxInvoiceService $taxInvoiceService)
    {
        $this->taxInvoiceService = $taxInvoiceService;
    }

    /**
     * Show manual tax invoice generation page
     */
    public function index()
    {
        return view('admin.tax-invoice-manual');
    }

    /**
     * Generate tax invoices manually
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'test_email' => 'nullable|email',
            'dry_run' => 'boolean',
            'command' => 'nullable|string'
        ]);

        $monthYear = $request->input('month');
        $testEmail = $request->input('test_email');
        $dryRun = $request->boolean('dry_run');
        $command = $request->input('command');

        try {
            // If specific command requested, execute it
            if ($command) {
                return $this->executeCommand($command, $monthYear, $testEmail);
            }

            // If test email provided, we'll modify the service to send only to that email
            if ($testEmail) {
                // For now, we'll use the regular service but log the test email
                \Log::info("Manual tax invoice generation for test email: {$testEmail}");
            }

            $results = $this->taxInvoiceService->generateMonthlyTaxInvoices($monthYear);

            return response()->json([
                'success' => true,
                'message' => 'Tax invoices generated successfully',
                'total_owners' => $results['total_owners'],
                'total_emails_sent' => $results['total_emails_sent'],
                'total_errors' => $results['total_errors'],
                'errors' => $results['errors'] ?? []
            ]);

        } catch (\Exception $e) {
            \Log::error('Manual tax invoice generation failed', [
                'month' => $monthYear,
                'test_email' => $testEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate tax invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute specific command
     */
    private function executeCommand($command, $month, $testEmail = null)
    {
        try {
            $kernel = app(\Illuminate\Contracts\Console\Kernel::class);
            
            $arguments = ['month' => $month];
            if ($testEmail) {
                $arguments['--email'] = $testEmail;
            }

            $exitCode = $kernel->call($command, $arguments);

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Command '{$command}' executed successfully",
                    'command' => $command
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Command '{$command}' failed with exit code: {$exitCode}"
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Command execution failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax invoice status
     */
    public function status()
    {
        $lastRun = \Cache::get('tax_invoice_last_run');
        $nextRun = Carbon::now()->day(15)->hour(9)->minute(0);
        
        if ($lastRun) {
            $lastRun = Carbon::parse($lastRun);
        }

        return response()->json([
            'last_run' => $lastRun ? $lastRun->format('Y-m-d H:i:s') : 'Never',
            'next_scheduled' => $nextRun->format('Y-m-d H:i:s'),
            'is_today' => Carbon::now()->day == 15,
            'cron_enabled' => \Cache::get('cron_enabled', false)
        ]);
    }
}
