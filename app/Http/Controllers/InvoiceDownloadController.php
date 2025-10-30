<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\HotelRoom;
use App\Services\PDF\TaxInvoiceService;
use Carbon\Carbon;

class InvoiceDownloadController extends Controller
{
    public function download(Request $request, $ownerId, $month, $type)
    {
        // Validate inputs
        $type = $type === 'flexible' ? 'flexible' : 'non-refundable';
        $monthYear = $month; // expected Y-m

        $owner = Customer::findOrFail((int)$ownerId);

        $startDate = Carbon::parse($monthYear . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Owner's hotel properties
        $propertyIds = Property::where('added_by', $owner->id)
            ->where('property_classification', 5)
            ->pluck('id');

        // Hotel rooms under these properties
        $hotelRoomIds = HotelRoom::whereIn('property_id', $propertyIds)->pluck('id');

        // Reservations for the month
        $reservations = Reservation::where(function ($query) use ($propertyIds, $hotelRoomIds) {
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

        if ($type === 'flexible') {
            $reservations = $reservations->filter(function($reservation) {
                $paymentMethod = $reservation->payment_method ?? 'cash';
                return !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
            });
        } else {
            $reservations = $reservations->filter(function($reservation) {
                $paymentMethod = $reservation->payment_method ?? 'cash';
                return ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
            });
        }

        // Build variables similar to backup sender
        $totalRevenue = $reservations->sum('total_price');
        $serviceChargeRate = (float) preg_replace('/[^0-9.]/', '', (string) (system_setting('service_charge_rate', 0)));
        $salesTaxRate = (float) preg_replace('/[^0-9.]/', '', (string) (system_setting('sales_tax_rate', 0)));
        $cityTaxRate = (float) preg_replace('/[^0-9.]/', '', (string) (system_setting('city_tax_rate', 0)));

        $serviceCharge = (float)$totalRevenue * ($serviceChargeRate / 100.0);
        $salesTax = (float)$totalRevenue * ($salesTaxRate / 100.0);
        $cityTax = (float)$totalRevenue * ($cityTaxRate / 100.0);
        $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
        $revenueAfterTaxes = (float)$totalRevenue - $totalTaxAmount;

        $commissionAmount = $revenueAfterTaxes * 0.15;
        $hotelAmount = $revenueAfterTaxes * 0.85;

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
            'currency_symbol' => system_setting('currency_symbol') ?? 'EGP',
            'reservation_details' => '',
            'property_summary' => '',
        ];

        // Template type for PDF
        $templateType = $type === 'flexible' ? 'hotel_booking_tax_invoice_flexible' : 'hotel_booking_tax_invoice_non_refundable';

        $pdf = app(TaxInvoiceService::class)->generatePDF($owner, $variables, $monthYear, $templateType);
        return $pdf->download('tax-invoice-' . $owner->id . '-' . $monthYear . '-' . ($type === 'flexible' ? 'F' : 'NR') . '.pdf');
    }
}


