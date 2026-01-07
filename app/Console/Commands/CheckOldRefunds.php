<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymobPayment;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class CheckOldRefunds extends Command
{
    protected $signature = 'refunds:check-old';
    protected $description = 'Check for old refund data in the database';

    public function handle()
    {
        $this->info('=== Checking for Old Refund Data ===');
        
        // Check for very old refund records (before 2026)
        $this->info('1. Checking for old refund records (before 2026):');
        $oldRefunds = PaymobPayment::where('created_at', '<', '2026-01-01')
            ->where(function($query) {
                $query->where('refund_status', '!=', null)
                      ->orWhere('requires_approval', true);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->info("Found " . $oldRefunds->count() . " old refund records:");

        foreach ($oldRefunds as $payment) {
            $this->line("Payment ID: {$payment->id} | Created: {$payment->created_at} | Refund Status: " . ($payment->refund_status ?? 'N/A') . " | Amount: {$payment->amount}");
            
            // Check if there's an associated reservation
            if ($payment->reservation) {
                $this->line("  -> Reservation ID: {$payment->reservation->id} | Status: {$payment->reservation->status} | Property: {$payment->reservation->property_id}");
            }
        }

        $this->info('');
        
        // Check for specific old refund that might be causing issues
        $this->info('2. Checking for specific problematic refund records:');
        $problematicRefunds = PaymobPayment::where('transaction_id', 'like', '%RES_%')
            ->where('refund_status', 'pending')
            ->where('created_at', '<', '2026-01-01')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $this->info("Found " . $problematicRefunds->count() . " pending old refund approvals:");

        foreach ($problematicRefunds as $payment) {
            $this->line("Payment ID: {$payment->id} | Transaction: {$payment->transaction_id} | Created: {$payment->created_at}");
            $this->line("  Refund Amount: {$payment->refund_amount} | Reason: {$payment->refund_reason}");
            
            if ($payment->reservation) {
                $this->line("  -> Reservation: {$payment->reservation->id} | Customer: " . ($payment->reservation->customer_email ?? 'N/A'));
            }
        }

        $this->info('');
        
        // Check for any orphaned refund records (no reservation association)
        $this->info('3. Checking for orphaned refund records:');
        $orphanedRefunds = PaymobPayment::whereNull('reservation_id')
            ->where('refund_status', '!=', null)
            ->where('created_at', '<', '2026-01-01')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $this->info("Found " . $orphanedRefunds->count() . " orphaned refund records:");

        foreach ($orphanedRefunds as $payment) {
            $this->line("Payment ID: {$payment->id} | Transaction: {$payment->transaction_id} | Created: {$payment->created_at}");
            $this->line("  Refund Amount: {$payment->refund_amount} | Status: {$payment->refund_status}");
        }

        $this->info('=== Analysis Complete ===');
        
        // Summary for quick action
        $totalOldRefunds = $oldRefunds->count();
        $pendingApprovals = $problematicRefunds->count();
        $orphanedCount = $orphanedRefunds->count();
        
        if ($totalOldRefunds > 0 || $pendingApprovals > 0 || $orphanedCount > 0) {
            $this->warn('=== ACTION REQUIRED ===');
            $this->warn("Found old refund data that may need attention:");
            $this->warn("- Total old refunds: {$totalOldRefunds}");
            $this->warn("- Pending approvals: {$pendingApprovals}");
            $this->warn("- Orphaned records: {$orphanedCount}");
            
            if ($pendingApprovals > 0) {
                $this->warn("RECOMMENDATION: Review and process pending refund approvals");
            }
            
            if ($orphanedCount > 0) {
                $this->warn("RECOMMENDATION: Investigate orphaned refund records");
            }
        } else {
            $this->info('=== RESULT: No problematic old refund data found ===');
        }
        
        return Command::SUCCESS;
    }
}
