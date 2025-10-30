<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyTaxInvoiceService;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\HelperService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackupTaxInvoiceSender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:backup-send {month? : Month in Y-m format} {--email= : Send to specific email only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup method to send tax invoices (guaranteed delivery)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $monthYear = $this->argument('month') ?? Carbon::now()->subMonth()->format('Y-m');
        $testEmail = $this->option('email');

        $this->info("Backup tax invoice sender for: {$monthYear}");
        
        if ($testEmail) {
            $this->warn("Test mode: Sending only to {$testEmail}");
        }

        try {
            // Get all hotel property owners
            $owners = Customer::whereHas('properties', function($query) {
                $query->where('property_classification', 5); // Hotel properties
            })->get();

            if ($owners->isEmpty()) {
                $this->warn("No hotel property owners found.");
                return Command::SUCCESS;
            }

            $totalSent = 0;
            $totalErrors = 0;

            foreach ($owners as $owner) {
                try {
                    // Get reservations for this owner
                    $reservations = $this->getOwnerReservations($owner, $monthYear);
                    
                    if ($reservations->isEmpty()) {
                        $this->info("No reservations found for owner: {$owner->name}");
                        continue;
                    }

                    // Split by payment method
                    $flexibleReservations = $reservations->filter(function($reservation) {
                        $paymentMethod = $reservation->payment_method ?? 'cash';
                        return !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
                    });

                    $nonRefundableReservations = $reservations->filter(function($reservation) {
                        $paymentMethod = $reservation->payment_method ?? 'cash';
                        return ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
                    });

                    // Send flexible invoice if exists
                    if ($flexibleReservations->isNotEmpty()) {
                        $this->sendBackupInvoice($owner, $flexibleReservations, $monthYear, 'flexible', $testEmail);
                        $totalSent++;
                    }

                    // Send non-refundable invoice if exists
                    if ($nonRefundableReservations->isNotEmpty()) {
                        $this->sendBackupInvoice($owner, $nonRefundableReservations, $monthYear, 'non-refundable', $testEmail);
                        $totalSent++;
                    }

                } catch (\Exception $e) {
                    $this->error("Failed to process owner {$owner->name}: " . $e->getMessage());
                    $totalErrors++;
                    Log::error('Backup tax invoice error', [
                        'owner_id' => $owner->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("Backup tax invoice sending completed!");
            $this->info("Total invoices sent: {$totalSent}");
            $this->info("Total errors: {$totalErrors}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup tax invoice sending failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get reservations for owner
     */
    private function getOwnerReservations($owner, $monthYear)
    {
        $startDate = Carbon::parse($monthYear . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return Reservation::whereHas('reservable.property', function($query) use ($owner) {
            $query->where('added_by', $owner->id)
                  ->where('property_classification', 5);
        })
        ->whereIn('status', ['confirmed', 'approved', 'completed'])
        ->whereIn('payment_status', ['paid', 'cash'])
        ->whereBetween('check_in_date', [$startDate, $endDate])
        ->with(['reservable.property', 'payment'])
        ->get();
    }

    /**
     * Send backup invoice
     */
    private function sendBackupInvoice($owner, $reservations, $monthYear, $type, $testEmail = null)
    {
        $email = $testEmail ?: $owner->email;
        
        if (!$email) {
            throw new \Exception("No email address for owner: {$owner->name}");
        }

        // Calculate totals
        $totalRevenue = $reservations->sum('total_price');
        $serviceChargeRate = (float) system_setting('service_charge_rate', 0);
        $salesTaxRate = (float) system_setting('sales_tax_rate', 0);
        $cityTaxRate = (float) system_setting('city_tax_rate', 0);
        
        $serviceCharge = $totalRevenue * ($serviceChargeRate / 100);
        $salesTax = $totalRevenue * ($salesTaxRate / 100);
        $cityTax = $totalRevenue * ($cityTaxRate / 100);
        $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
        $revenueAfterTaxes = $totalRevenue - $totalTaxAmount;
        
        $commissionAmount = $revenueAfterTaxes * (15 / 100);
        $hotelAmount = $revenueAfterTaxes * (85 / 100);

        // Prepare email data
        $emailTypeData = HelperService::getEmailTemplatesTypes("hotel_booking_tax_invoice_{$type}");
        $emailTemplateData = system_setting("hotel_booking_tax_invoice_{$type}_mail_template");

        $variables = [
            'app_name' => config('app.name'),
            'owner_name' => $owner->name,
            'month_year' => Carbon::parse($monthYear . '-01')->format('M Y'),
            'total_reservations' => $reservations->count(),
            'total_revenue' => number_format($totalRevenue, 2),
            'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
            'commission_rate' => '15%',
            'commission_amount' => number_format($commissionAmount, 2),
            'hotel_rate' => '85%',
            'hotel_amount' => number_format($hotelAmount, 2),
            'net_amount' => number_format($hotelAmount, 2),
            'currency_symbol' => 'EGP',
            'reservation_details' => $this->formatReservationDetails($reservations),
            'property_summary' => $this->formatPropertySummary($reservations),
        ];

        $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

        $data = [
            'email' => $email,
            'title' => $emailTypeData['title'] ?? "Monthly Tax Invoice - {$type}",
            'email_template' => $emailContent,
        ];

        // Generate PDF attachment
        $pdfAttachment = $this->generateBackupPdf($owner, $variables, $monthYear, $type);
        if ($pdfAttachment) {
            $data['attachments'] = [$pdfAttachment];
        }

        HelperService::sendMail($data);
        
        $this->info("✓ Sent {$type} invoice to {$email}");
    }

    /**
     * Generate backup PDF
     */
    private function generateBackupPdf($owner, $variables, $monthYear, $type)
    {
        try {
            $pdfService = app(\App\Services\PDF\TaxInvoiceService::class);
            return $pdfService->generatePDF($owner, $variables, $monthYear, "hotel_booking_tax_invoice_{$type}");
        } catch (\Exception $e) {
            Log::error('Backup PDF generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Format reservation details
     */
    private function formatReservationDetails($reservations)
    {
        $details = '';
        foreach ($reservations as $reservation) {
            $details .= "Reservation #{$reservation->id}: ";
            $details .= $reservation->check_in_date ? $reservation->check_in_date->format('M d, Y') : 'N/A';
            $details .= " - ";
            $details .= $reservation->check_out_date ? $reservation->check_out_date->format('M d, Y') : 'N/A';
            $details .= " (EGP " . number_format($reservation->total_price, 2) . ")\n";
        }
        return $details;
    }

    /**
     * Format property summary
     */
    private function formatPropertySummary($reservations)
    {
        $properties = $reservations->pluck('reservable.property.title')->unique();
        return implode(', ', $properties->toArray());
    }
}
