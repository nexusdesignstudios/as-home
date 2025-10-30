<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\PDF\TaxInvoiceService;
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

                // Split reservations by payment method
                // Manual/Cash = Flexible, Online/Paymob = Non-Refundable
                $flexibleReservations = collect();
                $nonRefundableReservations = collect();

                foreach ($reservations as $reservation) {
                    $paymentMethod = $reservation->payment_method ?? 'cash';
                    $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
                    
                    if ($isOnlinePayment) {
                        $nonRefundableReservations->push($reservation);
                    } else {
                        $flexibleReservations->push($reservation);
                    }
                }

                $emailsSent = 0;

                // Generate and send Flexible invoice (Manual/Cash reservations)
                if ($flexibleReservations->isNotEmpty()) {
                    $emailSent = $this->sendMonthlyTaxInvoice($owner, $flexibleReservations, $monthYear, 'flexible');
                    if ($emailSent) {
                        $emailsSent++;
                        Log::info('Flexible tax invoice sent successfully', [
                            'owner_id' => $ownerId,
                            'owner_email' => $owner->email,
                            'reservations_count' => $flexibleReservations->count(),
                            'month_year' => $monthYear
                        ]);
                    } else {
                        $results['total_errors']++;
                        $results['errors'][] = "Failed to send flexible invoice to owner: {$owner->email}";
                    }
                }

                // Generate and send Non-Refundable invoice (Online/Paymob reservations)
                if ($nonRefundableReservations->isNotEmpty()) {
                    $emailSent = $this->sendMonthlyTaxInvoice($owner, $nonRefundableReservations, $monthYear, 'non-refundable');
                    if ($emailSent) {
                        $emailsSent++;
                        Log::info('Non-Refundable tax invoice sent successfully', [
                            'owner_id' => $ownerId,
                            'owner_email' => $owner->email,
                            'reservations_count' => $nonRefundableReservations->count(),
                            'month_year' => $monthYear
                        ]);
                    } else {
                        $results['total_errors']++;
                        $results['errors'][] = "Failed to send non-refundable invoice to owner: {$owner->email}";
                    }
                }

                if ($emailsSent > 0) {
                    $results['total_emails_sent'] += $emailsSent;
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
        // Include both paid and cash payment statuses
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
            ->whereIn('payment_status', ['paid', 'cash'])
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->with(['reservable', 'customer', 'payment:id,reservation_id,status'])
            ->get();

        return $reservations;
    }

    /**
     * Send monthly tax invoice email to property owner
     *
     * @param Customer $owner
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @param string $monthYear
     * @param string $type 'flexible' or 'non-refundable'
     * @return bool
     */
    private function sendMonthlyTaxInvoice($owner, $reservations, $monthYear, $type = 'flexible')
    {
        try {
            // Calculate totals
            $totalRevenue = $reservations->sum('total_price');
            
            // Calculate property taxes (Service charge, Sales tax, City tax)
            $serviceChargeRate = system_setting('hotel_service_charge_rate') ?? 10; // Default 10%
            $salesTaxRate = system_setting('hotel_sales_tax_rate') ?? 14; // Default 14%
            $cityTaxRate = system_setting('hotel_city_tax_rate') ?? 5; // Default 5%
            
            $serviceChargeAmount = $totalRevenue * ($serviceChargeRate / 100);
            $salesTaxAmount = $totalRevenue * ($salesTaxRate / 100);
            $cityTaxAmount = $totalRevenue * ($cityTaxRate / 100);
            $totalTaxesAmount = $serviceChargeAmount + $salesTaxAmount + $cityTaxAmount;
            
            // Calculate revenue after taxes
            $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;
            
            // For hotel bookings, commission is 15% of revenue AFTER taxes
            // Hotel gets 85% of revenue AFTER taxes
            $commissionRate = 15; // 15% commission for As-home
            $hotelRate = 85; // 85% for hotel
            
            // Calculate commission on revenue AFTER taxes
            $commissionAmount = $revenueAfterTaxes * ($commissionRate / 100);
            $hotelAmount = $revenueAfterTaxes * ($hotelRate / 100);
            
            // Net amount for hotel (85% of revenue after taxes)
            $netAmount = $hotelAmount;

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Generate reservation details HTML
            $reservationDetails = $this->generateReservationDetailsHtml($reservations);

            // Generate property summary HTML
            $propertySummary = $this->generatePropertySummaryHtml($reservations);

            // Determine which email template to use based on type (flexible or non-refundable)
            // For hotel bookings, use hotel_booking_tax_invoice templates
            $emailTemplateType = "hotel_booking_tax_invoice"; // Default template for hotel bookings
            
            if ($property) {
                $propertyClassification = $property->getRawOriginal('property_classification');
                
                if ($propertyClassification == 4) { // Vacation homes
                    $rentPackage = $property->rent_package;
                    if ($rentPackage == 'premium') {
                        $emailTemplateType = "vacation_homes_premium_tax_invoice";
                    } else {
                        $emailTemplateType = "vacation_homes_basic_tax_invoice";
                    }
                } elseif ($propertyClassification == 5) { // Hotel booking
                    // Use new hotel_booking_tax_invoice templates based on payment method
                    if ($type === 'flexible') {
                        $emailTemplateType = "hotel_booking_tax_invoice_flexible";
                    } else {
                        $emailTemplateType = "hotel_booking_tax_invoice_non_refundable";
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
                'service_charge_rate' => $serviceChargeRate,
                'service_charge_amount' => number_format($serviceChargeAmount, 2),
                'sales_tax_rate' => $salesTaxRate,
                'sales_tax_amount' => number_format($salesTaxAmount, 2),
                'city_tax_rate' => $cityTaxRate,
                'city_tax_amount' => number_format($cityTaxAmount, 2),
                'total_taxes_amount' => number_format($totalTaxesAmount, 2),
                'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
                'commission_rate' => $commissionRate,
                'commission_amount' => number_format($commissionAmount, 2),
                'hotel_rate' => $hotelRate,
                'hotel_amount' => number_format($hotelAmount, 2),
                'net_amount' => number_format($netAmount, 2),
                'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
                'reservation_details' => $reservationDetails,
                'property_summary' => $propertySummary,
            ];

            // Generate invoice number and accommodation number for Booking.com-style PDF
            $invoiceNumber = $owner->id . '-' . str_replace('-', '', $monthYear) . '-' . ($type === 'flexible' ? 'F' : 'NR');
            $accommodationNumber = $owner->id ?? 'N/A';
            $vatNumber = system_setting('company_vat_number') ?? system_setting('vat_number') ?? null;

            $variables['invoice_number'] = $invoiceNumber;
            $variables['accommodation_number'] = $accommodationNumber;
            $variables['vat_number'] = $vatNumber;
            $variables['payment_method_type'] = $type === 'flexible' ? 'Flexible (Manual/Cash)' : 'Non-Refundable (Online)';
            $variables['invoice_type_label'] = $type === 'flexible' ? 'Flexible Rate Reservations' : 'Non-Refundable Reservations';
            $variables['hotel_percentage'] = $hotelRate; // 85% for hotel
            $variables['commission_percentage'] = $commissionRate; // 15% for As-home

            // Add bank account details for flexible hotels
            if ($emailTemplateType === "hotel_booking_tax_invoice_flexible" || $emailTemplateType === "monthly_tax_invoice_hotels_flexible") {
                $variables['bank_account_details'] = $this->generateBankAccountDetailsHtml();
            }

            if (empty($templateData)) {
                $templateData = "Your monthly tax invoice for {$monthYearDisplay} - {$variables['invoice_type_label']}";
            }

            $emailTemplate = HelperService::replaceEmailVariables($templateData, $variables);

            $data = [
                'email_template' => $emailTemplate,
                'email' => $owner->email,
                'title' => $emailTypeData['title'],
            ];

            // Generate and attach PDF
            $pdfAttachment = $this->generatePdfAttachment($owner, $variables, $monthYear, $emailTemplateType);
            if ($pdfAttachment) {
                $data['attachments'] = [$pdfAttachment];
            }

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
        $html .= '<strong>Note:</strong> Please transfer the hotel amount ({hotel_amount} {currency_symbol}) to the above account within 7 days of receiving this invoice.';
        $html .= '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate PDF attachment for the tax invoice
     *
     * @param Customer $owner
     * @param array $variables
     * @param string $monthYear
     * @param string $templateType
     * @return array|null
     */
    private function generatePdfAttachment($owner, $variables, $monthYear, $templateType)
    {
        try {
            $taxInvoiceService = new TaxInvoiceService();
            
            // Generate PDF
            $pdf = $taxInvoiceService->generatePDF($owner, $variables, $monthYear, $templateType);
            $pdfContent = $pdf->output();
            
            // Generate filename based on template type
            $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('Y-m');
            $typeSuffix = '';
            if (strpos($templateType, 'flexible') !== false) {
                $typeSuffix = '_Flexible';
            } elseif (strpos($templateType, 'non_refundable') !== false || strpos($templateType, 'non-refundable') !== false) {
                $typeSuffix = '_NonRefundable';
            }
            $filename = 'tax_invoice_' . $owner->id . '_' . $monthYearDisplay . $typeSuffix . '.pdf';
            
            Log::info('PDF generated for tax invoice', [
                'owner_id' => $owner->id,
                'filename' => $filename,
                'month_year' => $monthYear
            ]);
            
            return [
                'content' => $pdfContent,
                'filename' => $filename,
                'mime_type' => 'application/pdf'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error generating PDF attachment for tax invoice', [
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
