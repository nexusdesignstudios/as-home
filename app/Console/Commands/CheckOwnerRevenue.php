<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckOwnerRevenue extends Command
{
    protected $signature = 'revenue:check-owners {--month= : Month in Y-m format (default: current month)} {--owner-email= : Filter by specific owner email}';

    protected $description = 'Check revenue gained for each property owner';

    public function handle()
    {
        $monthYear = $this->option('month') ?: Carbon::now()->format('Y-m');
        $ownerEmail = $this->option('owner-email');

        $startDate = Carbon::parse($monthYear . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Checking revenue for property owners - Month: {$monthYear}");
        $this->info("Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n");

        // Get all properties with hotel or vacation home classification
        $propertiesQuery = Property::whereIn('property_classification', [4, 5])
            ->where('status', 1)
            ->where('request_status', 'approved')
            ->with('customer');

        if ($ownerEmail) {
            $propertiesQuery->whereHas('customer', function($q) use ($ownerEmail) {
                $q->where('email', $ownerEmail);
            });
        }

        $properties = $propertiesQuery->get();

        if ($properties->isEmpty()) {
            $this->warn('No properties found.');
            return Command::FAILURE;
        }

        // Group by owner
        $owners = $properties->groupBy('added_by');

        $totalOwners = 0;
        $grandTotalRevenue = 0;
        $grandTotalRevenueAfterTaxes = 0;
        $grandTotalHotelAmount = 0;

        foreach ($owners as $ownerId => $ownerProperties) {
            $owner = Customer::find($ownerId);
            if (!$owner || !$owner->email) {
                continue;
            }

            $totalOwners++;

            // Get reservations for this owner
            $propertyIds = $ownerProperties->pluck('id');
            $hotelRoomIds = \App\Models\HotelRoom::whereIn('property_id', $propertyIds)->pluck('id');

            $reservations = Reservation::whereIn('status', ['confirmed', 'approved', 'completed'])
                ->whereBetween('check_in_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where(function($query) use ($propertyIds, $hotelRoomIds) {
                    $query->where(function($q) use ($propertyIds) {
                        $q->where('reservable_type', 'App\\Models\\Property')
                          ->whereIn('reservable_id', $propertyIds);
                    })->orWhere(function($q) use ($hotelRoomIds) {
                        $q->where('reservable_type', 'App\\Models\\HotelRoom')
                          ->whereIn('reservable_id', $hotelRoomIds);
                    })->orWhereIn('property_id', $propertyIds);
                })
                ->get();

            if ($reservations->isEmpty()) {
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("Owner: {$owner->name} ({$owner->email})");
                $this->warn("  No reservations found for this period");
                $this->line("");
                continue;
            }

            // Calculate totals
            $totalRevenue = $reservations->sum('total_price');
            
            // Normalize rates
            $normalizeRate = function ($value, $default) {
                $raw = is_null($value) ? '' : (string)$value;
                $clean = preg_replace('/[^0-9.]/', '', $raw);
                return $clean === '' ? (float)$default : (float)$clean;
            };

            $serviceChargeRate = $normalizeRate(system_setting('hotel_service_charge_rate'), 10.0);
            $salesTaxRate = $normalizeRate(system_setting('hotel_sales_tax_rate'), 14.0);
            $cityTaxRate = $normalizeRate(system_setting('hotel_city_tax_rate'), 5.0);

            $serviceChargeAmount = (float)$totalRevenue * ($serviceChargeRate / 100.0);
            $salesTaxAmount = (float)$totalRevenue * ($salesTaxRate / 100.0);
            $cityTaxAmount = (float)$totalRevenue * ($cityTaxRate / 100.0);
            $totalTaxesAmount = $serviceChargeAmount + $salesTaxAmount + $cityTaxAmount;

            $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;
            $commissionRate = 15;
            $hotelRate = 85;
            $commissionAmount = $revenueAfterTaxes * ($commissionRate / 100);
            $hotelAmount = $revenueAfterTaxes * ($hotelRate / 100);
            $netAmount = $hotelAmount;

            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Split by payment method
            $flexibleReservations = $reservations->filter(function($r) {
                $paymentMethod = $r->payment_method ?? 'cash';
                $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $r->payment);
                return !$isOnlinePayment;
            });

            $nonRefundableReservations = $reservations->filter(function($r) {
                $paymentMethod = $r->payment_method ?? 'cash';
                $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $r->payment);
                return $isOnlinePayment;
            });

            $flexibleRevenue = $flexibleReservations->sum('total_price');
            $nonRefundableRevenue = $nonRefundableReservations->sum('total_price');

            // Display owner summary
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Owner: {$owner->name} ({$owner->email})");
            $this->line("Owner ID: {$owner->id}");
            $this->line("Properties: " . $ownerProperties->count());
            $this->line("Total Reservations: " . $reservations->count());
            $this->line("  - Flexible (Manual/Cash): " . $flexibleReservations->count());
            $this->line("  - Non-Refundable (Online): " . $nonRefundableReservations->count());
            $this->line("");
            $this->info("Revenue Summary:");
            $this->line("  Total Revenue: {$currencySymbol} " . number_format($totalRevenue, 2));
            $this->line("    - Flexible Revenue: {$currencySymbol} " . number_format($flexibleRevenue, 2));
            $this->line("    - Non-Refundable Revenue: {$currencySymbol} " . number_format($nonRefundableRevenue, 2));
            $this->line("");
            $this->info("Taxes:");
            $this->line("  Service Charge ({$serviceChargeRate}%): {$currencySymbol} " . number_format($serviceChargeAmount, 2));
            $this->line("  Sales Tax ({$salesTaxRate}%): {$currencySymbol} " . number_format($salesTaxAmount, 2));
            $this->line("  City Tax ({$cityTaxRate}%): {$currencySymbol} " . number_format($cityTaxAmount, 2));
            $this->line("  Total Taxes: {$currencySymbol} " . number_format($totalTaxesAmount, 2));
            $this->line("");
            $this->info("After Taxes:");
            $this->line("  Revenue After Taxes: {$currencySymbol} " . number_format($revenueAfterTaxes, 2));
            $this->line("");
            $this->info("Commission Breakdown:");
            $this->line("  As-home Commission ({$commissionRate}%): {$currencySymbol} " . number_format($commissionAmount, 2));
            $this->line("  Hotel Amount ({$hotelRate}%): {$currencySymbol} " . number_format($hotelAmount, 2));
            $this->line("");
            $this->info("Net Amount to Hotel: {$currencySymbol} " . number_format($netAmount, 2));
            $this->line("");

            $grandTotalRevenue += $totalRevenue;
            $grandTotalRevenueAfterTaxes += $revenueAfterTaxes;
            $grandTotalHotelAmount += $hotelAmount;
        }

        // Grand total
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("GRAND TOTAL SUMMARY");
        $this->line("Total Owners: {$totalOwners}");
        $this->line("Total Revenue (All Owners): {$currencySymbol} " . number_format($grandTotalRevenue, 2));
        $this->line("Total Revenue After Taxes: {$currencySymbol} " . number_format($grandTotalRevenueAfterTaxes, 2));
        $this->line("Total Net Amount to Hotels: {$currencySymbol} " . number_format($grandTotalHotelAmount, 2));
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return Command::SUCCESS;
    }
}








