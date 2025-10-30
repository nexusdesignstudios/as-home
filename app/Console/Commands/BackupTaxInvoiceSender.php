<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyTaxInvoiceService;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\HelperService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

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
            // Get all hotel property owners (by properties table)
            $ownerIds = \App\Models\Property::where('property_classification', 5)
                ->where('status', 1)
                ->where('request_status', 'approved')
                ->pluck('added_by')
                ->unique()
                ->filter();

            $owners = Customer::whereIn('id', $ownerIds)->get();

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

        // Owner's hotel properties
        $propertyIds = \App\Models\Property::where('added_by', $owner->id)
            ->where('property_classification', 5)
            ->pluck('id');

        // Hotel rooms under these properties
        $hotelRoomIds = \App\Models\HotelRoom::whereIn('property_id', $propertyIds)->pluck('id');

        return Reservation::where(function ($query) use ($propertyIds, $hotelRoomIds) {
            $query->where(function ($q) use ($propertyIds) {
                $q->where('reservable_type', 'App\\Models\\Property')
                  ->whereIn('reservable_id', $propertyIds);
            })->orWhere(function ($q) use ($hotelRoomIds) {
                $q->where('reservable_type', 'App\\Models\\HotelRoom')
                  ->whereIn('reservable_id', $hotelRoomIds);
            });
        })
        ->whereIn('status', ['confirmed', 'approved', 'completed'])
        ->whereIn('payment_status', ['paid', 'cash'])
        ->whereBetween('check_in_date', [$startDate, $endDate])
        ->with(['reservable', 'payment'])
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
        // Normalize rates: handle strings like "14%" or empty
        $normalizeRate = function ($value) {
            $raw = is_null($value) ? '' : (string)$value;
            $clean = preg_replace('/[^0-9.]/', '', $raw);
            return $clean === '' ? 0.0 : (float)$clean;
        };

        $serviceChargeRate = $normalizeRate(system_setting('service_charge_rate', 0));
        $salesTaxRate = $normalizeRate(system_setting('sales_tax_rate', 0));
        $cityTaxRate = $normalizeRate(system_setting('city_tax_rate', 0));
        
        $serviceCharge = (float)$totalRevenue * ($serviceChargeRate / 100.0);
        $salesTax = (float)$totalRevenue * ($salesTaxRate / 100.0);
        $cityTax = (float)$totalRevenue * ($cityTaxRate / 100.0);
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
            'commission_rate' => 15,
            'commission_amount' => number_format($commissionAmount, 2),
            'hotel_rate' => 85,
            'hotel_amount' => number_format($hotelAmount, 2),
            'net_amount' => number_format($hotelAmount, 2),
            'currency_symbol' => 'EGP',
            'reservation_details' => $this->formatReservationDetails($reservations),
            'property_summary' => $this->formatPropertySummary($reservations),
        ];

        // Ensure a default template exists
        if (empty($emailTemplateData)) {
            if ($type === 'non-refundable') {
                $emailTemplateData = <<<HTML
<p>Dear {owner_name},</p>
<p>We are pleased to provide your monthly tax invoice for {month_year} for your Hotel Property - Non-Refundable Reservations (Online Payments).</p>
<p><strong>Summary:</strong></p>
<ul>
  <li>Total Reservations: {total_reservations}</li>
  <li>Total Revenue: {currency_symbol}{total_revenue}</li>
  <li>Revenue After Taxes: {currency_symbol}{revenue_after_taxes}</li>
  <li>Commission Rate: {commission_rate}% (As-home)</li>
  <li>Commission Amount: {currency_symbol}{commission_amount}</li>
  <li>Hotel Rate: {hotel_rate}% (Hotel)</li>
  <li>Hotel Amount: {currency_symbol}{hotel_amount}</li>
  <li>Net Amount to Hotel: {currency_symbol}{net_amount}</li>
  </ul>
<p><strong>Reservation Details:</strong></p>
<div>{reservation_details}</div>
<p><strong>Property Summary:</strong></p>
<div>{property_summary}</div>
<p>A detailed PDF invoice is attached to this email.</p>
<p>As a hotel partner, you benefit from our specialized hotel booking platform, global visibility, and integrated management tools.</p>
<p>If you have any questions regarding this invoice or your hotel services, please don't hesitate to contact our hotel partner support team.</p>
<p>Thank you for your continued partnership.</p>
<p>Best regards,<br/>The {app_name} Team</p>
HTML;
            } else {
                $emailTemplateData = <<<HTML
<p>Dear {owner_name},</p>
<p>Please find your monthly tax invoice for {month_year} for Hotel Property - Flexible Reservations (Manual/Cash).</p>
<p><strong>Summary:</strong></p>
<ul>
  <li>Total Reservations: {total_reservations}</li>
  <li>Total Revenue: {currency_symbol}{total_revenue}</li>
  <li>Revenue After Taxes: {currency_symbol}{revenue_after_taxes}</li>
  <li>Commission Rate: {commission_rate}% (As-home)</li>
  <li>Commission Amount: {currency_symbol}{commission_amount}</li>
  <li>Hotel Rate: {hotel_rate}% (Hotel)</li>
  <li>Hotel Amount: {currency_symbol}{hotel_amount}</li>
  <li>Net Amount to Hotel: {currency_symbol}{net_amount}</li>
</ul>
<h3>Bank Details</h3>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">
  <tr><td><strong>Bank Name</strong></td><td>National Bank of Egypt</td></tr>
  <tr><td><strong>Branch</strong></td><td>Hurghada Branch</td></tr>
  <tr><td><strong>Bank Address</strong></td><td>EL Kawthar Hurghada Branch</td></tr>
  <tr><td><strong>Currency</strong></td><td>EGP</td></tr>
  <tr><td><strong>Swift Code</strong></td><td>NBEGEGCX341</td></tr>
  <tr><td><strong>Account No.</strong></td><td>3413131856116201017</td></tr>
  <tr><td><strong>Beneficiary Name</strong></td><td>As Home for Asset Management<br/>اذ هوم لاداره الاصول</td></tr>
  <tr><td><strong>IBAN</strong></td><td>EG100003034131318561162010170</td></tr>
</table>
<h4>IMPORTANT NOTES</h4>
<ul>
  <li>This invoice covers all flexible hotel bookings for the month of {month_year}</li>
  <li>Commission has been calculated based on the standard rate of {commission_rate}%</li>
  <li>All amounts are in {currency_symbol}</li>
  <li>Please transfer the commission amount ({currency_symbol} {commission_amount}) to the provided bank account within 7 days</li>
  <li>Please keep this invoice for your tax records</li>
  <li>For any questions regarding this invoice or payment, please contact our support team</li>
</ul>
<p>Best regards,<br/>The {app_name} Team</p>
HTML;
            }
        }

        $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

        // Styled header with Property IDs, User ID, and Date
        $propertyIds = $reservations->map(function ($r) {
            if ($r->reservable_type === 'App\\Models\\Property') {
                return (int)$r->reservable_id;
            } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                return (int)optional($r->reservable)->property_id;
            }
            return null;
        })->filter()->unique()->values()->all();

        $ownerId = (int)$owner->id;
        $dateStr = e(Carbon::parse($monthYear . '-01')->format('F Y'));
        $propList = implode(', ', array_map('intval', $propertyIds));

        $styledHeader = '<div style="font-family:Segoe UI,Arial,sans-serif;background:#ffffff;border:1px solid #e9ecef;border-radius:8px;padding:16px;margin:0 0 16px 0;">'
            . '<h2 style="margin:0 0 8px 0;color:#0d6efd;">Monthly Tax Invoice Summary</h2>'
            . '<table style="width:100%;border-collapse:collapse;">'
            . '  <tr>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:30%;"><strong>Owner (User) ID</strong></td>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;">' . $ownerId . '</td>'
            . '  </tr>'
            . '  <tr>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Property ID(s)</strong></td>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;">' . e($propList) . '</td>'
            . '  </tr>'
            . '  <tr>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Invoice Month</strong></td>'
            . '    <td style="padding:8px;border:1px solid #e9ecef;">' . $dateStr . '</td>'
            . '  </tr>'
            . '</table>'
            . '</div>';

        $emailContent = $styledHeader . $emailContent;

        // Keep body concise; reservation details are in the attached PDF

        // Keep email body minimal; full details are inside the attached PDF

        // Derive property titles and addresses for email context
        $propertyTitles = $reservations->map(function ($r) {
            if ($r->reservable_type === 'App\\Models\\Property') {
                return optional($r->reservable)->title;
            } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                return optional(optional($r->reservable)->property)->title;
            }
            return null;
        })->filter()->unique()->values()->all();

        $propertyAddresses = $reservations->map(function ($r) {
            if ($r->reservable_type === 'App\\Models\\Property') {
                return optional($r->reservable)->address;
            } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                return optional(optional($r->reservable)->property)->address;
            }
            return null;
        })->filter()->unique()->values()->all();

        $data = [
            'email' => $email,
            'title' => $emailTypeData['title'] ?? "Monthly Tax Invoice - {$type}",
            'email_template' => $emailContent,
            // Use specialized blade for flexible invoice mail body if available
            'view' => $type === 'flexible' ? 'mail-templates.flexible-invoice-mail' : 'mail-templates.mail-template',
            // Map blade variables expected by flexible-invoice-mail
            'app_name' => config('app.name'),
            'app_domain' => parse_url(config('app.url') ?? 'ashom-eg.com', PHP_URL_HOST) ?? 'ashom-eg.com',
            'app_logo' => null,
            'invoice_date' => now()->format('Y-m-d'),
            'accommodation_number' => (string)($variables['payment_code'] ?? $owner->id),
            'vat_number' => $variables['vat_number'] ?? (system_setting('company_vat_number') ?? ''),
            'invoice_number' => $variables['invoice_number'] ?? ($owner->id . '-' . str_replace('-', '', $monthYear) . '-' . ($type === 'flexible' ? 'F' : 'NR')),
            'invoice_period_start' => Carbon::parse($monthYear . '-01')->startOfMonth()->format('Y-m-d'),
            'invoice_period_end' => Carbon::parse($monthYear . '-01')->endOfMonth()->format('Y-m-d'),
            'currency_symbol' => $variables['currency_symbol'] ?? 'EGP',
            'room_sales' => number_format((float)$totalRevenue, 2),
            'commission_amount' => number_format((float)$commissionAmount, 2),
            // Total Amount Due = Room Sales - Commission
            'total_due' => number_format(max(0, (float)$totalRevenue - (float)$commissionAmount), 2),
            'payment_due_date' => Carbon::parse($monthYear . '-01')->endOfMonth()->addDays(7)->format('Y-m-d'),
            'commission_rate' => 15,
            // Additional context for the template
            'reservations_count' => (int)$reservations->count(),
            'property_title_list' => implode(', ', array_map('strval', $propertyTitles)),
            'property_address_list' => implode(', ', array_map('strval', $propertyAddresses)),
            'owner_full_name' => $owner->name,
            'owner_email' => $owner->email,
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
        $html = '<table style="width:100%; border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f8f9fa;">';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Reservation ID</th>';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Property</th>';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Check-in</th>';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Check-out</th>';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Guests</th>';
        $html .= '<th style="border:1px solid #e9ecef; padding:8px; text-align:left;">Amount</th>';
        $html .= '</tr></thead><tbody>';

        $i = 0;
        foreach ($reservations as $reservation) {
            $bg = ($i % 2 === 0) ? '#ffffff' : '#fcfcfd';
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $propertyName = $reservation->reservable->title ?? 'Property';
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $propertyName = ($reservation->reservable->property->title ?? 'Hotel') . ' - Room';
            }

            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">#' . (int)$reservation->id . '</td>';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">' . e($propertyName) . '</td>';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">' . ($reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A') . '</td>';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">' . ($reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A') . '</td>';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">' . (int)($reservation->number_of_guests ?? 0) . '</td>';
            $html .= '<td style="border:1px solid #e9ecef; padding:8px;">EGP ' . number_format((float)$reservation->total_price, 2) . '</td>';
            $html .= '</tr>';
            $i++;
        }

        $html .= '</tbody></table>';
        return $html;
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
