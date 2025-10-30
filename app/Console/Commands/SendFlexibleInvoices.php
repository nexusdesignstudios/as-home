<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\FlexibleInvoiceMail;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Reservation;
use Carbon\Carbon;

class SendFlexibleInvoices extends Command
{
    protected $signature = 'invoices:send-flexible {--month= : Month in Y-m (defaults to previous month)} {--email= : Test single owner email}';

    protected $description = 'Send monthly tax invoices for flexible bookings.';

    public function handle(): int
    {
        $monthYear = $this->option('month') ?: Carbon::now()->subMonth()->format('Y-m');
        $start = Carbon::parse($monthYear . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $this->info("Sending flexible invoices for: {$monthYear}");

        // Find hotel owners (properties classification = 5) with flexible policy
        // We treat flexible as manual/cash reservations (non-online)
        $ownerIds = Property::where('property_classification', 5)
            ->where('status', 1)
            ->where('request_status', 'approved')
            ->pluck('added_by')
            ->unique()
            ->filter();

        $owners = Customer::whereIn('id', $ownerIds);
        if ($this->option('email')) {
            $owners = $owners->where('email', $this->option('email'));
        }
        $owners = $owners->get();

        if ($owners->isEmpty()) {
            $this->warn('No hotel owners found.');
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($owners as $owner) {
            $reservations = $this->getFlexibleReservationsForOwner($owner->id, $start, $end);
            if ($reservations->isEmpty()) {
                $this->line("- Skipping {$owner->email}: no flexible reservations");
                continue;
            }

            // Compute totals
            $totalRevenue = (float)$reservations->sum('total_price');
            $service = system_setting('service_charge_rate') ?? 0;
            $sales = system_setting('sales_tax_rate') ?? 0;
            $city = system_setting('city_tax_rate') ?? 0;
            $clean = function ($val) { $c=preg_replace('/[^0-9.]/','',(string)$val); return $c===''?0.0:(float)$c; };
            $service = $clean($service); $sales = $clean($sales); $city = $clean($city);
            $serviceAmt = $totalRevenue * ($service/100.0);
            $salesAmt = $totalRevenue * ($sales/100.0);
            $cityAmt = $totalRevenue * ($city/100.0);
            $revenueAfterTaxes = $totalRevenue - ($serviceAmt + $salesAmt + $cityAmt);
            $commissionRate = 15.0;
            $commissionAmount = $revenueAfterTaxes * ($commissionRate/100.0);
            $totalDue = max(0.0, $totalRevenue - $commissionAmount);

            $invoiceData = [
                'app_name' => config('app.name'),
                'app_domain' => parse_url(config('app.url') ?? 'ashom-eg.com', PHP_URL_HOST) ?? 'ashom-eg.com',
                'app_logo' => null,
                'invoice_date' => $end->format('Y-m-d'),
                'accommodation_number' => (string)$owner->id,
                'vat_number' => system_setting('company_vat_number') ?? '',
                'invoice_number' => $owner->id . '-' . str_replace('-', '', $monthYear) . '-F',
                'invoice_period_start' => $start->format('Y-m-d'),
                'invoice_period_end' => $end->format('Y-m-d'),
                'currency_symbol' => system_setting('currency_symbol') ?? 'EGP',
                'reservations_count' => $reservations->count(),
                'property_title_list' => $this->getPropertyTitles($reservations),
                'property_address_list' => $this->getPropertyAddresses($reservations),
                'owner_full_name' => $owner->name,
                'owner_email' => $owner->email,
                'room_sales' => number_format($totalRevenue, 2),
                'commission_amount' => number_format($commissionAmount, 2),
                'total_due' => number_format($totalDue, 2),
                'payment_due_date' => $end->copy()->addDays(7)->format('Y-m-d'),
                'commission_rate' => $commissionRate,
            ];

            Mail::to($owner->email)->send(new FlexibleInvoiceMail($invoiceData));
            $this->info("✓ Sent invoice to {$owner->email}");
            $sent++;
        }

        $this->info("Completed. Sent: {$sent}");
        return Command::SUCCESS;
    }

    private function getFlexibleReservationsForOwner(int $ownerId, Carbon $start, Carbon $end)
    {
        $propertyIds = Property::where('added_by', $ownerId)
            ->where('property_classification', 5)
            ->pluck('id');
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
            ->whereBetween('check_in_date', [$start, $end])
            ->with(['reservable', 'payment'])
            ->get()
            ->filter(function ($r) {
                $method = $r->payment_method ?? 'cash';
                return !($method === 'paymob' || $method === 'online' || $r->payment);
            });
    }

    private function getPropertyTitles($reservations): string
    {
        $titles = $reservations->map(function ($r) {
            if ($r->reservable_type === 'App\\Models\\Property') {
                return optional($r->reservable)->title;
            } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                return optional(optional($r->reservable)->property)->title;
            }
            return null;
        })->filter()->unique()->values()->all();
        return implode(', ', array_map('strval', $titles));
    }

    private function getPropertyAddresses($reservations): string
    {
        $addresses = $reservations->map(function ($r) {
            if ($r->reservable_type === 'App\\Models\\Property') {
                return optional($r->reservable)->address;
            } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                return optional(optional($r->reservable)->property)->address;
            }
            return null;
        })->filter()->unique()->values()->all();
        return implode(', ', array_map('strval', $addresses));
    }
}


