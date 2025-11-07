<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\PDF\TaxInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class MonthlyTaxInvoiceService
{
    /**
     * Generate and send monthly tax invoices to property owners
     *
     * @param string|null $monthYear Format: '2025-01' or null for current month
     * @param string|null $testEmail Optional email to filter and send only to this email
     * @return array
     */
    public function generateMonthlyTaxInvoices($monthYear = null, $testEmail = null)
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
                'end_date' => $endDate->format('Y-m-d'),
                'test_email' => $testEmail
            ]);

            $results = [
                'total_owners' => 0,
                'total_emails_sent' => 0,
                'total_errors' => 0,
                'errors' => []
            ];

            // Get ONLY hotel properties (classification 5) - exclude vacation homes
            $properties = Property::where('property_classification', 5) // 5 = Hotel Booking only
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

                // Filter by test email if provided
                if ($testEmail && strtolower($owner->email) !== strtolower($testEmail)) {
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

                // Filter reservations to only include cash/offline payments (exclude online/paymob)
                // Only send Flexible invoices for all cash/offline reservations
                $flexibleReservations = collect();  // Cash/Offline reservations only

                foreach ($reservations as $reservation) {
                    $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
                    
                    // If payment_method is explicitly 'cash', treat as flexible even if payment record exists
                    if ($paymentMethod === 'cash') {
                        // Cash/Manual payment → Flexible invoice
                        $flexibleReservations->push($reservation);
                        continue;
                    }
                    
                    // Check if it's an online payment - EXCLUDE these from flexible invoices
                    // Criteria: payment_method is 'paymob' or 'online', OR has a PaymobPayment record (but not if payment_method is 'cash')
                    $isOnlinePayment = (
                        $paymentMethod === 'paymob' || 
                        $paymentMethod === 'online' || 
                        ($reservation->payment !== null && $paymentMethod !== 'cash')
                    );
                    
                    // Only include cash/offline payments (exclude online/paymob)
                    if (!$isOnlinePayment) {
                        // Cash/Manual/Offline payment → Flexible invoice
                        $flexibleReservations->push($reservation);
                    }
                }

                $emailsSent = 0;

                // Generate and send ONLY Flexible invoice (Cash/Offline reservations)
                if ($flexibleReservations->isNotEmpty()) {
                    $emailSent = $this->sendMonthlyTaxInvoice($owner, $flexibleReservations, $monthYear, 'flexible', null);
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

                // Non-Refundable invoices are no longer sent - only flexible invoices for cash/offline reservations

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
        // Get ONLY hotel properties (classification 5) owned by this owner
        $hotelPropertyIds = Property::where('added_by', $ownerId)
            ->where('property_classification', 5) // Only hotels
            ->pluck('id');

        // Get hotel room IDs for this owner's hotel properties
        $hotelRoomIds = \App\Models\HotelRoom::whereIn('property_id', $hotelPropertyIds)->pluck('id');

        // Get reservations ONLY for hotel properties and hotel rooms
        // Include both paid and cash payment statuses (confirmed reservations)
        $reservations = Reservation::where(function ($query) use ($hotelPropertyIds, $hotelRoomIds) {
            $query->where(function ($q) use ($hotelPropertyIds) {
                // Property reservations - only for hotel properties (classification 5)
                $q->where('reservable_type', 'App\Models\Property')
                    ->whereIn('reservable_id', $hotelPropertyIds);
            })->orWhere(function ($q) use ($hotelRoomIds) {
                // HotelRoom reservations (always hotels)
                $q->where('reservable_type', 'App\Models\HotelRoom')
                    ->whereIn('reservable_id', $hotelRoomIds);
            });
        })
            ->where('status', 'confirmed')
            ->whereIn('payment_status', ['paid', 'cash']) // Cash or approved and paid
            ->whereBetween('check_out_date', [$startDate, $endDate]) // Use check_out_date (departure date) as per invoice policy
            ->with(['reservable', 'customer', 'payment:id,reservation_id,status'])
            ->get();

        // Additional filter: Ensure Property reservations are actually for hotels
        // Filter out any vacation home reservations that might have slipped through
        $reservations = $reservations->filter(function ($reservation) {
            if ($reservation->reservable_type === 'App\Models\Property') {
                $property = $reservation->reservable;
                if ($property) {
                    $classification = $property->getRawOriginal('property_classification');
                    // Double-check: Only include if classification is 5 (Hotel Booking)
                    if ($classification != 5) {
                        Log::warning('Non-hotel property reservation filtered out', [
                            'reservation_id' => $reservation->id,
                            'property_id' => $property->id,
                            'classification' => $classification
                        ]);
                        return false;
                    }
                    return true; // Only hotels (classification 5)
                }
                return false;
            } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
                // HotelRoom reservations are always hotels, but verify the property
                $hotelRoom = $reservation->reservable;
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyClassification = $hotelRoom->property->getRawOriginal('property_classification');
                    if ($propertyClassification != 5) {
                        Log::warning('HotelRoom with non-hotel property filtered out', [
                            'reservation_id' => $reservation->id,
                            'hotel_room_id' => $hotelRoom->id,
                            'property_id' => $hotelRoom->property_id,
                            'classification' => $propertyClassification
                        ]);
                        return false;
                    }
                }
                return true; // HotelRoom reservations are always hotels
            }
            return false;
        });

        return $reservations;
    }

    /**
     * Send monthly tax invoice email to property owner
     *
     * @param Customer $owner
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @param string $monthYear
     * @param string $type 'flexible' or 'non-refundable'
     * @param string|null $overrideEmail Optional email to send to instead of owner's email
     * @return bool
     */
    private function sendMonthlyTaxInvoice($owner, $reservations, $monthYear, $type = 'flexible', $overrideEmail = null)
    {
        try {
            // CRITICAL: Verify ALL reservations are for hotels (classification 5) only
            // This is a final safety check to ensure no vacation home reservations slip through
            $nonHotelReservations = collect();
            foreach ($reservations as $reservation) {
                $property = null;
                if ($reservation->reservable_type === 'App\\Models\\Property') {
                    $property = $reservation->reservable;
                } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom' && $reservation->reservable) {
                    $property = $reservation->reservable->property;
                } elseif ($reservation->property_id) {
                    $property = Property::find($reservation->property_id);
                }
                
                if ($property) {
                    $classification = $property->getRawOriginal('property_classification');
                    if ($classification != 5) {
                        $nonHotelReservations->push([
                            'reservation_id' => $reservation->id,
                            'property_id' => $property->id,
                            'classification' => $classification
                        ]);
                    }
                } else {
                    // No property found - this should not happen for valid reservations
                    Log::warning('Reservation without property in hotel invoice service', [
                        'reservation_id' => $reservation->id,
                        'owner_id' => $owner->id
                    ]);
                    $nonHotelReservations->push(['reservation_id' => $reservation->id, 'error' => 'No property found']);
                }
            }
            
            // If any non-hotel reservations found, log and filter them out
            if ($nonHotelReservations->isNotEmpty()) {
                Log::error('Non-hotel reservations detected in hotel invoice service - filtering out', [
                    'owner_id' => $owner->id,
                    'owner_email' => $owner->email,
                    'month_year' => $monthYear,
                    'non_hotel_count' => $nonHotelReservations->count(),
                    'non_hotel_reservations' => $nonHotelReservations->toArray()
                ]);
                
                // Filter out non-hotel reservations
                $nonHotelReservationIds = $nonHotelReservations->pluck('reservation_id')->toArray();
                $reservations = $reservations->filter(function ($reservation) use ($nonHotelReservationIds) {
                    return !in_array($reservation->id, $nonHotelReservationIds);
                });
                
                // If no hotel reservations remain, skip sending invoice
                if ($reservations->isEmpty()) {
                    Log::warning('No hotel reservations remaining after filtering - skipping invoice', [
                        'owner_id' => $owner->id,
                        'owner_email' => $owner->email,
                        'month_year' => $monthYear
                    ]);
                    return false;
                }
            }
            
            // Calculate totals
            $totalRevenue = $reservations->sum('total_price'); // Room Sales (gross revenue)
            
            // Calculate total taxes as 22.36% of total revenue
            $totalTaxRate = 22.36; // Total taxes percentage
            $totalTaxesAmount = (float)$totalRevenue * ($totalTaxRate / 100.0);
            
            // Calculate revenue after taxes
            $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;
            
            // Commission is 15% of REVENUE AFTER TAXES (not gross revenue)
            $commissionRate = 15; // 15% commission for As-home from revenue after taxes
            $commissionAmount = (float)$revenueAfterTaxes * ($commissionRate / 100.0);
            
            // Total amount due is the commission (15% of revenue after taxes)
            $totalAmountDue = $commissionAmount;
            
            // For display purposes (legacy calculations - kept for backwards compatibility)
            $hotelRate = 85; // 85% for hotel (this is calculated on revenue after taxes for display)
            $hotelAmount = $revenueAfterTaxes * ($hotelRate / 100);
            $netAmount = $hotelAmount;
            
            // Individual tax breakdowns for display (legacy - kept for email templates)
            $serviceChargeRate = 10.0;
            $salesTaxRate = 14.0;
            $cityTaxRate = 5.0;
            $serviceChargeAmount = (float)$totalRevenue * ($serviceChargeRate / 100.0);
            $salesTaxAmount = (float)$totalRevenue * ($salesTaxRate / 100.0);
            $cityTaxAmount = (float)$totalRevenue * ($cityTaxRate / 100.0);

            // Get currency symbol (default to EGP instead of $)
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Generate reservation details HTML
            $reservationDetails = $this->generateReservationDetailsHtml($reservations, $currencySymbol);

            // Generate property summary HTML
            $propertySummary = $this->generatePropertySummaryHtml($reservations, $currencySymbol);

            // Get property from first reservation to determine classification
            $property = null;
            $firstReservation = $reservations->first();
            if ($firstReservation) {
                if ($firstReservation->reservable_type === 'App\\Models\\Property') {
                    $property = $firstReservation->reservable;
                } elseif ($firstReservation->reservable_type === 'App\\Models\\HotelRoom' && $firstReservation->reservable) {
                    $property = $firstReservation->reservable->property;
                } elseif ($firstReservation->property_id) {
                    $property = Property::find($firstReservation->property_id);
                }
            }

            // Determine which email template to use - ONLY for hotel bookings
            // This service is for hotel tax invoices only (flexible and non-refundable)
            $emailTemplateType = "hotel_booking_tax_invoice"; // Default template for hotel bookings
            
            if ($property) {
                $propertyClassification = $property->getRawOriginal('property_classification');
                
                // Only send hotel invoices - skip vacation homes
                if ($propertyClassification == 5) { // Hotel booking
                    // Use hotel_booking_tax_invoice templates based on payment method
                    if ($type === 'flexible') {
                        $emailTemplateType = "hotel_booking_tax_invoice_flexible";
                    } else {
                        $emailTemplateType = "hotel_booking_tax_invoice_non_refundable";
                    }
                } else {
                    // Not a hotel - should not happen, but log and skip
                    Log::warning('Non-hotel property detected in hotel invoice service', [
                        'property_id' => $property->id,
                        'classification' => $propertyClassification,
                        'owner_id' => $owner->id
                    ]);
                    return false; // Skip sending invoice for non-hotel properties
                }
            } else {
                // No property found - should not happen for hotel reservations
                Log::warning('No property found for reservation in hotel invoice service', [
                    'owner_id' => $owner->id,
                    'reservations_count' => $reservations->count()
                ]);
                return false;
            }

            // Get email template
            $emailTypeData = HelperService::getEmailTemplatesTypes($emailTemplateType);
            $templateData = system_setting($emailTypeData['type']);
            $appName = env("APP_NAME") ?? "eBroker";

            // Format month year for display
            $monthYearDisplay = Carbon::parse($monthYear . '-01')->format('F Y');

            // Get property name for email template
            $propertyName = $property ? ($property->title ?? 'Hotel') : 'Hotel';
            
            $variables = [
                'app_name' => $appName,
                'owner_name' => $owner->name,
                'property_name' => $propertyName,
                'month_year' => $monthYearDisplay,
                'total_reservations' => $reservations->count(),
                // Formatted values for email templates
                'total_revenue' => number_format($totalRevenue, 2), // Room Sales (gross revenue)
                'room_sales' => number_format($totalRevenue, 2), // Alias for Room Sales in PDF template
                'currency_symbol' => $currencySymbol,
                'service_charge_rate' => $serviceChargeRate,
                'service_charge_amount' => number_format($serviceChargeAmount, 2),
                'sales_tax_rate' => $salesTaxRate,
                'sales_tax_amount' => number_format($salesTaxAmount, 2),
                'city_tax_rate' => $cityTaxRate,
                'city_tax_amount' => number_format($cityTaxAmount, 2),
                'total_taxes_rate' => $totalTaxRate, // 22.36% total taxes
                'total_taxes_amount' => number_format($totalTaxesAmount, 2),
                'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
                'commission_rate' => $commissionRate, // 15% commission
                'commission_amount' => number_format($commissionAmount, 2), // 15% of total revenue
                'total_amount_due' => number_format($totalAmountDue, 2), // Commission amount
                'hotel_rate' => $hotelRate,
                'hotel_amount' => number_format($hotelAmount, 2),
                'net_amount' => number_format($netAmount, 2),
                // Raw numeric values for PDF template (to avoid double formatting)
                'room_sales_raw' => $totalRevenue,
                'commission_amount_raw' => $commissionAmount,
                'total_amount_due_raw' => $totalAmountDue,
                'hotel_amount_raw' => $hotelAmount, // For non-refundable invoices
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
            
            // Add property information for PDF template (hotel name, address, and VAT number)
            if ($property) {
                $variables['property_name'] = $property->title ?? 'Hotel';
                $variables['property_address'] = $property->address ?? ($property->client_address ?? '');
                $variables['property_vat'] = $property->hotel_vat ?? '';
            } else {
                $variables['property_name'] = 'Hotel';
                $variables['property_address'] = '';
                $variables['property_vat'] = '';
            }
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

            // Build styled summary with Property IDs, User ID, and Date
            $propertyIds = $reservations->map(function ($r) {
                if ($r->reservable_type === 'App\\Models\\Property') {
                    return (int)$r->reservable_id;
                } elseif ($r->reservable_type === 'App\\Models\\HotelRoom') {
                    return (int)optional($r->reservable)->property_id;
                }
                return null;
            })->filter()->unique()->values()->all();

            $ownerId = (int)$owner->id;
            $dateStr = e($monthYearDisplay);
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

            // Compose final email HTML content
            $emailTemplate = $styledHeader . $emailTemplate;

            // For flexible emails, append bank details and important notes
            if ($type === 'flexible') {
                $bankHtml = '<div style="font-family:Segoe UI,Arial,sans-serif;background:#ffffff;border:1px solid #e9ecef;border-radius:8px;padding:16px;margin:0 0 16px 0;">'
                    . '<h3 style="margin:0 0 8px 0;color:#0d6efd;">Bank Details</h3>'
                    . '<table style="width:100%;border-collapse:collapse;">'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:30%;"><strong>Bank Name</strong></td><td style="padding:8px;border:1px solid #e9ecef;">National Bank of Egypt</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Branch</strong></td><td style="padding:8px;border:1px solid #e9ecef;">Hurghada Branch</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Bank Address</strong></td><td style="padding:8px;border:1px solid #e9ecef;">EL Kawthar Hurghada Branch</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Currency</strong></td><td style="padding:8px;border:1px solid #e9ecef;">EGP</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Swift Code</strong></td><td style="padding:8px;border:1px solid #e9ecef;">NBEGEGCX341</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Account No.</strong></td><td style="padding:8px;border:1px solid #e9ecef;">3413131856116201017</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>Beneficiary Name</strong></td><td style="padding:8px;border:1px solid #e9ecef;">As Home for Asset Management</td></tr>'
                    . '<tr><td style="padding:8px;border:1px solid #e9ecef;background:#f8f9fa;"><strong>IBAN</strong></td><td style="padding:8px;border:1px solid #e9ecef;">EG100003034131318561162010170</td></tr>'
                    . '</table>'
                    . '</div>';

                $notesHtml = '<div style="font-family:Segoe UI,Arial,sans-serif;background:#fff7e6;border:1px solid #ffe8cc;border-radius:8px;padding:16px;margin:0 0 16px 0;">'
                    . '<h4 style="margin:0 0 8px 0;color:#d48806;">IMPORTANT NOTES</h4>'
                    . '<ul style="margin:0 0 0 18px;padding:0;">'
                    . '<li>This invoice covers all flexible hotel bookings for the month of ' . e($monthYearDisplay) . '</li>'
                    . '<li>Commission has been calculated based on the standard rate of ' . e($commissionRate) . '%</li>'
                    . '<li>All amounts are in ' . e($currencySymbol) . '</li>'
                    . '<li>Please transfer the commission amount (' . e($currencySymbol) . ' ' . e(number_format($commissionAmount, 2)) . ') to the provided bank account within 7 days</li>'
                    . '<li>Please keep this invoice for your tax records</li>'
                    . '<li>For any questions regarding this invoice or payment, please contact our support team</li>'
                    . '</ul>'
                    . '</div>';

                $emailTemplate .= $bankHtml . $notesHtml;
            }

            // Keep body concise; reservation details are in the attached PDF

            // Use override email if provided, otherwise use owner's email
            $recipientEmail = $overrideEmail ?? $owner->email;

            $data = [
                'email_template' => $emailTemplate,
                'email' => $recipientEmail,
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
     * @param string $currencySymbol
     * @return string
     */
    private function generateReservationDetailsHtml($reservations, $currencySymbol = 'EGP')
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
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $currencySymbol . ' ' . number_format($reservation->total_price, 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate HTML for property summary
     *
     * @param \Illuminate\Database\Eloquent\Collection $reservations
     * @param string $currencySymbol
     * @return string
     */
    private function generatePropertySummaryHtml($reservations, $currencySymbol = 'EGP')
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
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $currencySymbol . ' ' . number_format($summary['revenue'], 2) . '</td>';
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
        // CRITICAL: Always use "As Home for Asset Management" as Beneficiary Name
        $accountHolder = 'As Home for Asset Management';

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

    /**
     * Send tax invoice for a specific property to a specific email address
     *
     * @param string $ownerEmail Owner's email address
     * @param string $propertyName Property name to filter by
     * @param string $monthYear Format: '2025-10'
     * @param string $recipientEmail Email address to send invoice to
     * @return bool
     */
    public function sendPropertyInvoiceToEmail($ownerEmail, $propertyName, $monthYear, $recipientEmail)
    {
        try {
            // Find owner by email (case-insensitive)
            $owner = Customer::whereRaw('LOWER(email) = ?', [strtolower($ownerEmail)])->first();
            if (!$owner) {
                Log::error('Owner not found', ['email' => $ownerEmail]);
                return false;
            }

            // Find property by name and owner
            // Check for both numeric 5 and string 'hotel_booking' for property_classification
            $property = Property::where('added_by', $owner->id)
                ->where(function($query) {
                    $query->where('property_classification', 5)
                          ->orWhere('property_classification', 'hotel_booking');
                })
                ->where(function($query) use ($propertyName) {
                    $query->where('title', 'like', '%' . $propertyName . '%')
                          ->orWhere('title', $propertyName);
                })
                ->first();

            if (!$property) {
                Log::error('Property not found', [
                    'owner_email' => $ownerEmail,
                    'property_name' => $propertyName
                ]);
                return false;
            }

            $startDate = Carbon::parse($monthYear . '-01')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Get reservations for this specific property
            $hotelRoomIds = \App\Models\HotelRoom::where('property_id', $property->id)->pluck('id');

            $reservations = Reservation::where(function ($query) use ($property, $hotelRoomIds) {
                $query->where(function ($q) use ($property) {
                    $q->where('reservable_type', 'App\Models\Property')
                      ->where('reservable_id', $property->id);
                })->orWhere(function ($q) use ($hotelRoomIds) {
                    $q->where('reservable_type', 'App\Models\HotelRoom')
                      ->whereIn('reservable_id', $hotelRoomIds);
                });
            })
                ->where('status', 'confirmed')
                ->whereIn('payment_status', ['paid', 'cash'])
                ->whereBetween('check_out_date', [$startDate, $endDate])
                ->with(['reservable', 'customer', 'payment:id,reservation_id,status'])
                ->get();

            // Filter for cash/offline payments only
            // If payment_method is 'cash', treat as flexible even if payment record exists
            $flexibleReservations = $reservations->filter(function ($reservation) {
                $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
                // If payment_method is explicitly 'cash', treat as flexible
                if ($paymentMethod === 'cash') {
                    return true;
                }
                // Otherwise, check if it's online payment
                $isOnlinePayment = (
                    $paymentMethod === 'paymob' || 
                    $paymentMethod === 'online' || 
                    ($reservation->payment !== null && $paymentMethod !== 'cash')
                );
                return !$isOnlinePayment;
            });

            if ($flexibleReservations->isEmpty()) {
                Log::info('No flexible reservations found for property', [
                    'property_id' => $property->id,
                    'property_name' => $propertyName,
                    'month_year' => $monthYear
                ]);
                return false;
            }

            // Send invoice using override email
            return $this->sendMonthlyTaxInvoice($owner, $flexibleReservations, $monthYear, 'flexible', $recipientEmail);

        } catch (\Exception $e) {
            Log::error('Error sending property invoice to specific email', [
                'owner_email' => $ownerEmail,
                'property_name' => $propertyName,
                'recipient_email' => $recipientEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
