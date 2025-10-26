<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonthlyTaxInvoiceService
{
    /**
     * Generate and send monthly tax invoices to property owners
     *
     * @param string|null $monthYear Format: '2025-01' or null for current month
     * @return array
     */
    public function generateMonthlyTaxInvoices($monthYear = null)
    {
        try {
            // If no month specified, use current month
            if (!$monthYear) {
                $monthYear = Carbon::now()->format('Y-m');
            }

            $startDate = Carbon::parse($monthYear . '-01')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            Log::info('Generating monthly tax invoices for period', [
                'month_year' => $monthYear,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]);

            $results = [
                'total_owners' => 0,
                'total_emails_sent' => 0,
                'total_errors' => 0,
                'errors' => []
            ];

            // Get all properties with hotel or vacation home classification
            $properties = Property::whereIn('property_classification', [4, 5]) // 4 = Vacation Homes, 5 = Hotel Booking
                ->where('status', 1)
                ->where('request_status', 'approved')
                ->with('customer')
                ->get();

            // Group properties by owner (added_by)
            $owners = $properties->groupBy('added_by');

            foreach ($owners as $ownerId => $ownerProperties) {
                $owner = Customer::find($ownerId);
                if (!$owner || !$owner->email) {
                    $results['errors'][] = "Owner not found or no email for owner ID: {$ownerId}";
                    $results['total_errors']++;
                    continue;
                }

                $results['total_owners']++;

                // Get all reservations for this owner's properties in the specified month
                $reservations = $this->getOwnerReservations($ownerId, $startDate, $endDate);

                if ($reservations->isEmpty()) {
                    Log::info('No reservations found for owner', [
                        'owner_id' => $ownerId,
                        'owner_email' => $owner->email,
                        'month_year' => $monthYear
                    ]);
                    continue;
                }

                // Generate and send invoice
                $emailSent = $this->sendMonthlyTaxInvoice($owner, $reservations, $monthYear);

                if ($emailSent) {
                    $results['total_emails_sent']++;
                    Log::info('Monthly tax invoice sent successfully', [
                        'owner_id' => $ownerId,
                        'owner_email' => $owner->email,
                        'reservations_count' => $reservations->count(),
                        'month_year' => $monthYear
                    ]);
                } else {
                    $results['total_errors']++;
                    $results['errors'][] = "Failed to send email to owner: {$owner->email}";
                }
            }

            Log::info('Monthly tax invoice generation completed', $results);
            return $results;
        } catch (\Exception $e) {
            Log::error('Error generating monthly tax invoices', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'total_owners' => 0,
                'total_emails_sent' => 0,
                'total_errors' => 1,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Get all reservations for a specific owner in a given month
     *
     * @param int $ownerId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getOwnerReservations($ownerId, $startDate, $endDate)
    {
        // Get all properties owned by this owner
        $propertyIds = Property::where('added_by', $ownerId)
            ->whereIn('property_classification', [4, 5])
            ->pluck('id');

        // Get hotel room IDs for this owner's properties
        $hotelRoomIds = \App\Models\HotelRoom::whereIn('property_id', $propertyIds)->pluck('id');

        // Get reservations for properties and hotel rooms
        $reservations = Reservation::where(function ($query) use ($propertyIds, $hotelRoomIds) {
            $query->where(function ($q) use ($propertyIds) {
                $q->where('reservable_type', 'App\Models\Property')
                    ->whereIn('reservable_id', $propertyIds);
            })->orWhere(function ($q) use ($hotelRoomIds) {
                $q->where('reservable_type', 'App\Models\HotelRoom')
                    ->whereIn('reservable_id', $hotelRoomIds);
            });
        })
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->with(['reservable', 'customer'])
            ->get();

        return $reservations;
    }

    /**
     * Send monthly tax invoice email to property owner
     *
     * @param Customer $owner
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @param string $monthYear
     * @return bool
     */
    private function sendMonthlyTaxInvoice($owner, $reservations, $monthYear)
    {
        try {
            // Calculate totals
            $totalRevenue = $reservations->sum('total_price');
            // Calculate commission based on property classification and rent package
            // For simplicity, we'll use the first property's classification and rent package
            // In a real-world scenario, you might want to calculate commission per property
            $firstReservation = $reservations->first();
            $property = null;

            if ($firstReservation->reservable_type === 'App\\Models\\Property') {
                $property = $firstReservation->reservable;
            } elseif ($firstReservation->reservable_type === 'App\\Models\\HotelRoom') {
                $property = $firstReservation->reservable->property;
            }

            if ($property) {
                $propertyClassification = $property->getRawOriginal('property_classification');
                $rentPackage = $property->rent_package;
                $commissionRate = \App\Models\PropertyTax::getCommissionRate($propertyClassification, $rentPackage);
            } else {
                $commissionRate = 15; // Default fallback
            }

            $commissionAmount = $totalRevenue * ($commissionRate / 100);
            $netAmount = $totalRevenue - $commissionAmount;

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Generate reservation details HTML
            $reservationDetails = $this->generateReservationDetailsHtml($reservations);

            // Generate property summary HTML
            $propertySummary = $this->generatePropertySummaryHtml($reservations);

            // Determine which email template to use based on property classification and rent package
            $emailTemplateType = "monthly_tax_invoice"; // Default template

            if ($property) {
                $propertyClassification = $property->getRawOriginal('property_classification');
                $rentPackage = $property->rent_package;

                if ($propertyClassification == 4) { // Vacation homes
                    if ($rentPackage == 'premium') {
                        $emailTemplateType = "vacation_homes_premium_tax_invoice";
                    } else {
                        $emailTemplateType = "vacation_homes_basic_tax_invoice";
                    }
                } elseif ($propertyClassification == 5) { // Hotel booking
                    // Check if it's a flexible or non-refundable hotel
                    if ($rentPackage == 'flexible') {
                        $emailTemplateType = "monthly_tax_invoice_hotels_flexible";
                    } else {
                        $emailTemplateType = "monthly_tax_invoice_hotels_non_refundable";
                    }
                }
            }

            // Get email template
            $emailTypeData = HelperService::getEmailTemplatesTypes($emailTemplateType);
            $templateData = system_setting($emailTypeData['type']);
            $appName = env("APP_NAME") ?? "eBroker";

            // Format month year for display
            $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('F Y');

            $variables = [
                'app_name' => $appName,
                'owner_name' => $owner->name,
                'month_year' => $monthYearDisplay,
                'total_reservations' => $reservations->count(),
                'total_revenue' => number_format($totalRevenue, 2),
                'currency_symbol' => $currencySymbol,
                'commission_rate' => $commissionRate,
                'commission_amount' => number_format($commissionAmount, 2),
                'net_amount' => number_format($netAmount, 2),
                'reservation_details' => $reservationDetails,
                'property_summary' => $propertySummary,
            ];

            // Add bank account details for flexible hotels
            if ($emailTemplateType === "monthly_tax_invoice_hotels_flexible") {
                $variables['bank_account_details'] = $this->generateBankAccountDetailsHtml();
            }

            if (empty($templateData)) {
                $templateData = "Your monthly tax invoice for {$monthYearDisplay}";
            }

            $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

            $data = [
                'email_template' => $emailTemplate,
                'email' => $owner->email,
                'title' => $emailTypeData['title'],
            ];

            HelperService::sendMail($data);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending monthly tax invoice', [
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Generate HTML for reservation details
     *
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @return string
     */
    private function generateReservationDetailsHtml($reservations)
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $html .= '<thead><tr style="background-color: #f8f9fa;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservation ID</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-in</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-out</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Guests</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Amount</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($reservations as $reservation) {
            $propertyName = $this->getPropertyName($reservation);

            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">#' . $reservation->id . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $propertyName . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_in_date->format('d M Y') . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_out_date->format('d M Y') . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->number_of_guests . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">$' . number_format($reservation->total_price, 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate HTML for property summary
     *
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @return string
     */
    private function generatePropertySummaryHtml($reservations)
    {
        $propertySummary = $reservations->groupBy('reservable_id')->map(function ($reservations, $propertyId) {
            $firstReservation = $reservations->first();
            $propertyName = $this->getPropertyName($firstReservation);
            $totalRevenue = $reservations->sum('total_price');
            $reservationCount = $reservations->count();

            return [
                'property_name' => $propertyName,
                'reservations' => $reservationCount,
                'revenue' => $totalRevenue
            ];
        });

        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $html .= '<thead><tr style="background-color: #f8f9fa;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservations</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Revenue</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($propertySummary as $summary) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $summary['property_name'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $summary['reservations'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">$' . number_format($summary['revenue'], 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Get property name from reservation
     *
     * @param Reservation $reservation
     * @return string
     */
    private function getPropertyName($reservation)
    {
        if ($reservation->reservable_type === 'App\Models\Property') {
            return $reservation->reservable->title ?? 'Property';
        } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
            return ($reservation->reservable->property->title ?? 'Hotel') . ' - Room';
        }

        return 'Unknown Property';
    }

    /**
     * Generate HTML for bank account details for flexible hotels
     *
     * @return string
     */
    private function generateBankAccountDetailsHtml()
    {
        // Get bank account details from system settings or use default
        $bankName = system_setting('bank_name') ?? 'As-home Bank';
        $accountNumber = system_setting('bank_account_number') ?? '1234567890';
        $routingNumber = system_setting('bank_routing_number') ?? '987654321';
        $swiftCode = system_setting('bank_swift_code') ?? 'ASHOMEXX';
        $accountHolder = system_setting('bank_account_holder') ?? 'As-home Group';

        $html = '<div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
        $html .= '<h3 style="color: #495057; margin-bottom: 15px;">Bank Account Details for Commission Payment</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 8px; font-weight: bold; width: 30%;">Bank Name:</td><td style="padding: 8px;">' . $bankName . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Holder:</td><td style="padding: 8px;">' . $accountHolder . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Number:</td><td style="padding: 8px;">' . $accountNumber . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Routing Number:</td><td style="padding: 8px;">' . $routingNumber . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">SWIFT Code:</td><td style="padding: 8px;">' . $swiftCode . '</td></tr>';
        $html .= '</table>';
        $html .= '<p style="margin-top: 15px; color: #6c757d; font-size: 14px;">';
        $html .= '<strong>Note:</strong> Please transfer the commission amount ({commission_amount} {currency_symbol}) to the above account within 7 days of receiving this invoice.';
        $html .= '</p>';
        $html .= '</div>';

        return $html;
    }
}
