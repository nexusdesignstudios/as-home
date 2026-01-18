<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class ReservationService
{
    /**
     * Create a new reservation and update available dates only if confirmed.
     *
     * @param array $data
     * @param bool $skipEmails Whether to skip sending emails (default: false)
     * @return \App\Models\Reservation
     */
    public function createReservation(array $data, bool $skipEmails = false)
    {
        return DB::transaction(function () use ($data, $skipEmails) {
            // MULTI-UNIT SUPPORT: Check if this is a multi-unit vacation home booking
            $isMultiUnit = false;
            $apartmentId = $data['apartment_id'] ?? null;
            
            if ($data['reservable_type'] === 'App\\Models\\Property' && $apartmentId) {
                // Check if apartment has multiple units
                $apartment = \App\Models\VacationApartment::find($apartmentId);
                
                if ($apartment && $apartment->quantity > 1) {
                    $isMultiUnit = true;
                    $totalUnits = $apartment->quantity;
                    $requestedQuantity = $data['apartment_quantity'] ?? 1;
                    
                    // Count all active bookings (confirmed, pending, approved)
                    // We treat 'pending' as booked to prevent double booking during payment
                    $bookedUnits = $this->countBookedUnitsForApartment(
                        $data['reservable_id'],
                        $apartmentId,
                        $data['check_in_date'],
                        $data['check_out_date'],
                        null,
                        ['confirmed', 'approved', 'pending']
                    );
                    
                    if (($bookedUnits + $requestedQuantity) > $totalUnits) {
                        throw new \Exception("Not enough units available for the selected dates. Available: " . ($totalUnits - $bookedUnits));
                    }
                }
            }

            // Only perform standard single-unit check if NOT multi-unit
            if (!$isMultiUnit) {
                // CRITICAL FIX: Check for existing reservations before creating new one
                $existingReservation = $this->checkExistingReservation(
                    $data['reservable_type'],
                    $data['reservable_id'],
                    $data['check_in_date'],
                    $data['check_out_date']
                );
                
                if ($existingReservation) {
                    throw new \Exception("Room is already booked for the selected dates. Existing reservation ID: " . $existingReservation->id);
                }
            }
            
            // Create the reservation
            $reservation = Reservation::create([
                'customer_id' => $data['customer_id'],
                'reservable_id' => $data['reservable_id'],
                'reservable_type' => $data['reservable_type'],
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_guests' => $data['number_of_guests'] ?? 1,
                'total_price' => $data['total_price'],
                'property_id' => $data['property_id'],
                'status' => $data['status'] ?? 'pending',
                'special_requests' => $data['special_requests'] ?? null,
                'payment_status' => $data['payment_status'] ?? 'unpaid',
                'payment_method' => $data['payment_method'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'booking_type' => $data['booking_type'] ?? null,
            ]);

            // Update available dates based on booking type and payment status
            // Non-refundable: only block when confirmed AND paid
            // Flexible: block when confirmed (regardless of payment status)
            $shouldBlockDates = false;
            $status = $data['status'] ?? 'pending';
            $paymentStatus = $data['payment_status'] ?? 'unpaid';
            $bookingType = $data['booking_type'] ?? null;
            
            if ($status === 'confirmed') {
                if ($bookingType === 'flexible_booking') {
                    // Flexible reservations block dates when confirmed (paid or unpaid)
                    $shouldBlockDates = true;
                } else {
                    // Non-refundable reservations only block dates when confirmed AND paid
                    $shouldBlockDates = ($paymentStatus === 'paid');
                }
            }
            
            // Special case for flexible reservations that are confirmed but unpaid (manual confirmation)
            // They should still block dates to ensure availability is updated
            if ($status === 'confirmed' && $paymentStatus === 'unpaid' && isset($data['refund_policy']) && $data['refund_policy'] === 'flexible') {
                $shouldBlockDates = true;
            }
            
            if ($shouldBlockDates) {
                $this->updateAvailableDates(
                    $data['reservable_type'],
                    $data['reservable_id'],
                    $data['check_in_date'],
                    $data['check_out_date'],
                    $reservation->id
                );
            }

            // Skip sending emails if requested (for checkout without payment)
            if ($skipEmails) {
                \Illuminate\Support\Facades\Log::info('Reservation created without sending emails', [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'skip_emails' => true
                ]);
            }

            return $reservation;
        });
    }

    /**
     * Update the available dates for a reservable model.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int $reservationId
     * @return void
     */
    public function updateAvailableDates($modelType, $modelId, $checkInDate, $checkOutDate, $reservationId)
    {
        // Get the model instance
        $model = $this->getModelInstance($modelType, $modelId);
        if (!$model) {
            return;
        }

        // Get current available dates
        $availableDates = $model->available_dates ?? [];

        // Ensure availableDates is an array
        if (!is_array($availableDates)) {
            $availableDates = [];
        }

        // Generate date range
        $dateRange = $this->generateDateRange($checkInDate, $checkOutDate);

        // Handle different availability types (for HotelRoom model)
        if ($modelType === 'App\\Models\\HotelRoom' && isset($model->availability_type)) {
            $availabilityType = $model->availability_type;

            if ($availabilityType === 'busy_days') {
                // For busy_days, add the reservation dates as busy
                $updatedDates = $this->processBusyDateRange($availableDates, $dateRange, $reservationId, $model);
                $model->available_dates = $updatedDates;
                $model->save();
                return;
            }
        }

        // Default behavior for "available_days" or other models
        // Process each date in the range
        $updatedDates = $this->processDateRange($availableDates, $dateRange, $reservationId, $model);

        // Update the model
        $model->available_dates = $updatedDates;
        $model->save();
    }

    /**
     * Process busy date ranges for "busy_days" availability type.
     *
     * @param array $availableDates
     * @param array $dateRange
     * @param int $reservationId
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function processBusyDateRange($availableDates, $dateRange, $reservationId, $model)
    {
        if (empty($dateRange)) {
            return $availableDates;
        }

        // Create a new busy date range
        $reservationFrom = Carbon::parse($dateRange[0]);
        $reservationTo = Carbon::parse($dateRange[count($dateRange) - 1]);

        // Add the new busy range
        $availableDates[] = [
            'from' => $reservationFrom->format('Y-m-d'),
            'to' => $reservationTo->format('Y-m-d'),
            'price' => $model->price_per_night ?? $model->price,
            'type' => 'busy',
            'reservation_id' => $reservationId
        ];

        return $availableDates;
    }

    /**
     * Get the model instance based on type and ID.
     *
     * @param string $modelType
     * @param int $modelId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getModelInstance($modelType, $modelId)
    {
        if ($modelType === 'App\\Models\\Property') {
            return Property::find($modelId);
        } elseif ($modelType === 'App\\Models\\HotelRoom' || $modelType === 'hotel_room') {
            return HotelRoom::find($modelId);
        }

        return null;
    }

    /**
     * Generate an array of dates between check-in and check-out.
     *
     * @param string $checkInDate
     * @param string $checkOutDate
     * @return array
     */
    protected function generateDateRange($checkInDate, $checkOutDate)
    {
        $dates = [];
        $current = Carbon::parse($checkInDate);
        $end = Carbon::parse($checkOutDate);

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Process the date range and update available dates.
     *
     * @param array $availableDates
     * @param array $dateRange
     * @param int $reservationId
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function processDateRange($availableDates, $dateRange, $reservationId, $model)
    {
        $updatedDates = [];
        $reservedDates = [];

        // Convert dateRange array to a lookup hash for quick access
        foreach ($dateRange as $date) {
            $reservedDates[$date] = true;
        }

        // Process existing date ranges
        foreach ($availableDates as $key => $dateInfo) {
            // Skip if this isn't a date range format
            if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                $updatedDates[$key] = $dateInfo;
                continue;
            }

            // Parse the from and to dates
            $fromDate = Carbon::parse($dateInfo['from']);
            $toDate = Carbon::parse($dateInfo['to']);

            // Check if this range overlaps with our reservation
            $reservationFrom = Carbon::parse($dateRange[0]);
            $reservationTo = Carbon::parse($dateRange[count($dateRange) - 1])->addDay(); // Add a day since the last date in dateRange is inclusive

            // No overlap case - keep the range as is
            if ($toDate->lt($reservationFrom) || $fromDate->gte($reservationTo)) {
                $updatedDates[] = $dateInfo;
                continue;
            }

            // Handle overlap cases

            // Case 1: Reservation starts after range start - create a "before" range
            if ($fromDate->lt($reservationFrom)) {
                $updatedDates[] = [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $reservationFrom->copy()->subDay()->format('Y-m-d'),
                    'price' => $dateInfo['price'] ?? ($model instanceof HotelRoom ? $model->price_per_night : $model->price),
                    'type' => 'open'
                ];
            }

            // Case 2: Create the reserved range
            $updatedDates[] = [
                'from' => $reservationFrom->format('Y-m-d'),
                'to' => $reservationTo->copy()->subDay()->format('Y-m-d'),
                'price' => $dateInfo['price'] ?? ($model instanceof HotelRoom ? $model->price_per_night : $model->price),
                'type' => 'reserved',
                'reservation_id' => $reservationId
            ];

            // Case 3: Reservation ends before range end - create an "after" range
            if ($toDate->gt($reservationTo)) {
                $updatedDates[] = [
                    'from' => $reservationTo->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                    'price' => $dateInfo['price'] ?? ($model instanceof HotelRoom ? $model->price_per_night : $model->price),
                    'type' => 'open'
                ];
            }
        }

        // If there were no existing date ranges that overlapped with our reservation,
        // add the reservation as a new date range
        if (!$this->hasReservationInDates($updatedDates, $reservationId)) {
            $reservationFrom = Carbon::parse($dateRange[0]);
            $reservationTo = Carbon::parse($dateRange[count($dateRange) - 1]);

            $updatedDates[] = [
                'from' => $reservationFrom->format('Y-m-d'),
                'to' => $reservationTo->format('Y-m-d'),
                'price' => $model instanceof HotelRoom ? $model->price_per_night : $model->price,
                'type' => 'reserved',
                'reservation_id' => $reservationId
            ];
        }

        return $updatedDates;
    }

    /**
     * Check if the reservation is already in the updated dates.
     *
     * @param array $dates
     * @param int $reservationId
     * @return bool
     */
    protected function hasReservationInDates($dates, $reservationId)
    {
        foreach ($dates as $date) {
            if (isset($date['reservation_id']) && $date['reservation_id'] == $reservationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cancel a reservation and update available dates.
     *
     * @param int $reservationId
     * @return \App\Models\Reservation
     */
    public function cancelReservation($reservationId)
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = Reservation::findOrFail($reservationId);
            $reservation->status = 'cancelled';
            $reservation->save();

            // Get the model instance
            $model = $reservation->reservable;
            if (!$model) {
                return $reservation;
            }

            // Get current available dates
            $availableDates = $model->available_dates ?? [];

            // Ensure availableDates is an array
            if (!is_array($availableDates)) {
                $availableDates = [];
            }

            // Handle different availability types (for HotelRoom model)
            if ($model instanceof \App\Models\HotelRoom && isset($model->availability_type)) {
                $availabilityType = $model->availability_type;

                if ($availabilityType === 'busy_days') {
                    // For busy_days, remove the reservation dates from busy dates
                    $updatedDates = [];

                    foreach ($availableDates as $dateInfo) {
                        // Keep all date ranges except the one for this reservation
                        if (!isset($dateInfo['reservation_id']) || $dateInfo['reservation_id'] != $reservationId) {
                            $updatedDates[] = $dateInfo;
                        }
                    }

                    // Update the model
                    $model->available_dates = $updatedDates;
                    $model->save();

                    return $reservation;
                }
            }

            // Default behavior for "available_days" or other models
            $updatedDates = [];

            // Process each date range
            foreach ($availableDates as $key => $dateInfo) {
                // If this is a reserved range for this reservation, change it to open
                if (isset($dateInfo['reservation_id']) && $dateInfo['reservation_id'] == $reservationId) {
                    $dateInfo['type'] = 'open';
                    unset($dateInfo['reservation_id']);
                }

                $updatedDates[] = $dateInfo;
            }

            // Merge adjacent "open" ranges
            $updatedDates = $this->mergeAdjacentOpenRanges($updatedDates);

            // Update the model
            $model->available_dates = $updatedDates;
            $model->save();

            return $reservation;
        });
    }

    /**
     * Merge adjacent open date ranges.
     *
     * @param array $dates
     * @return array
     */
    protected function mergeAdjacentOpenRanges($dates)
    {
        // Sort dates by from date
        usort($dates, function ($a, $b) {
            if (!isset($a['from']) || !isset($b['from'])) {
                return 0;
            }
            return strcmp($a['from'], $b['from']);
        });

        $result = [];
        $currentRange = null;

        foreach ($dates as $dateInfo) {
            // Skip if this isn't a date range format
            if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                $result[] = $dateInfo;
                continue;
            }

            // If not an open range, add it as is
            if (!isset($dateInfo['type']) || $dateInfo['type'] !== 'open') {
                $result[] = $dateInfo;
                continue;
            }

            // If this is our first open range, set it as current
            if ($currentRange === null) {
                $currentRange = $dateInfo;
                continue;
            }

            // Check if this range is adjacent to current range
            $currentToDate = Carbon::parse($currentRange['to']);
            $nextFromDate = Carbon::parse($dateInfo['from']);

            if ($currentToDate->addDay()->eq($nextFromDate)) {
                // Merge the ranges
                $currentRange['to'] = $dateInfo['to'];
            } else {
                // Not adjacent, add current to result and set this as new current
                $result[] = $currentRange;
                $currentRange = $dateInfo;
            }
        }

        // Add the last current range if there is one
        if ($currentRange !== null) {
            $result[] = $currentRange;
        }

        return $result;
    }

    /**
     * Handle reservation confirmation logic (extracted from PaymobController).
     * This method handles the same logic that occurs when a Paymob payment succeeds.
     *
     * @param \App\Models\Reservation $reservation
     * @param string $paymentStatus
     * @return void
     */
    public function handleReservationConfirmation($reservation, $paymentStatus = 'paid', $skipEmail = false)
    {
        try {
            // Prevent duplicate email sending - if already confirmed and paid, skip
            if ($reservation->status === 'confirmed' && $reservation->payment_status === 'paid') {
                \Illuminate\Support\Facades\Log::info('Reservation already confirmed and paid, skipping duplicate confirmation', [
                    'reservation_id' => $reservation->id,
                    'status' => $reservation->status,
                    'payment_status' => $reservation->payment_status
                ]);
                return;
            }
            
            // Update reservation status and payment status
            $reservation->status = 'confirmed';
            $reservation->payment_status = $paymentStatus;
            $reservation->save();
            
            // Mark tier discount as used if this reservation used a discount
            $this->markTierDiscountAsUsed($reservation);

            \Illuminate\Support\Facades\Log::info('Reservation status updated via admin confirmation', [
                'reservation_id' => $reservation->id,
                'status' => $reservation->status,
                'payment_status' => $reservation->payment_status
            ]);

            // Update available dates based on booking type and payment status
            try {
                // Check if we should update available dates based on booking type
                $shouldUpdateDates = false;
                $bookingType = $reservation->booking_type;
                
                if ($bookingType === 'flexible_booking') {
                    // Flexible reservations: update dates when confirmed (regardless of payment status)
                    $shouldUpdateDates = true;
                } else {
                    // Non-refundable reservations: only update dates when confirmed AND paid
                    $shouldUpdateDates = ($reservation->payment_status === 'paid');
                }
                
                if ($shouldUpdateDates) {
                    $this->updateAvailableDates(
                        $reservation->reservable_type,
                        $reservation->reservable_id,
                        $reservation->check_in_date,
                        $reservation->check_out_date,
                        $reservation->id
                    );
                    
                    \Illuminate\Support\Facades\Log::info('Available dates updated successfully via admin confirmation', [
                        'reservation_id' => $reservation->id,
                        'booking_type' => $bookingType,
                        'payment_status' => $reservation->payment_status
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::info('Available dates not updated - conditions not met', [
                        'reservation_id' => $reservation->id,
                        'booking_type' => $bookingType,
                        'payment_status' => $reservation->payment_status
                    ]);
                }

                // Send payment completion email to property owner
                $this->sendPaymentCompletionEmailToOwner($reservation);
                
                // Send reservation confirmation email to customer (unless skipped)
                if (!$skipEmail) {
                    $this->sendReservationConfirmationEmail($reservation);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to update available dates via admin confirmation', [
                    'error' => $e->getMessage(),
                    'reservation_id' => $reservation->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to handle reservation confirmation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Mark tier discount as used when reservation is confirmed.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    private function markTierDiscountAsUsed($reservation)
    {
        try {
            $customerId = $reservation->customer_id;
            $reservableType = $reservation->reservable_type;
            
            // Find the unused tier discount for this customer and reservation type
            $tierDiscount = \App\Models\CustomerTierDiscount::where('customer_id', $customerId)
                ->where('reservable_type', $reservableType)
                ->where('used', false)
                ->orderBy('tier_milestone', 'desc') // Get highest tier first
                ->first();
            
            // Also check for hotel room variants
            if (!$tierDiscount && in_array($reservableType, ['App\\Models\\HotelRoom', 'App\Models\HotelRoom', 'HotelRoom'])) {
                $tierDiscount = \App\Models\CustomerTierDiscount::where('customer_id', $customerId)
                    ->whereIn('reservable_type', [
                        'App\\Models\\HotelRoom',
                        'App\Models\HotelRoom',
                        'HotelRoom'
                    ])
                    ->where('used', false)
                    ->orderBy('tier_milestone', 'desc')
                    ->first();
            }
            
            if ($tierDiscount) {
                $tierDiscount->used = true;
                $tierDiscount->reservation_id = $reservation->id;
                $tierDiscount->used_at = now();
                $tierDiscount->save();
                
                \Illuminate\Support\Facades\Log::info('Tier discount marked as used', [
                    'tier_discount_id' => $tierDiscount->id,
                    'reservation_id' => $reservation->id,
                    'tier_milestone' => $tierDiscount->tier_milestone,
                    'customer_id' => $customerId
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to mark tier discount as used', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - this is not critical
        }
    }

    /**
     * Send reservation approval email.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendReservationApprovalEmail($reservation)
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_approval");

                // Email Template
                $reservationApprovalTemplateData = system_setting('reservation_approval_mail_template');
                $appName = env("APP_NAME") ?? "eBroker";

                // Get property name
                $propertyName = '';
                if ($reservation->reservable_type === 'App\Models\Property') {
                    $propertyName = $reservation->reservable->title ?? 'Property';
                } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
                    $propertyName = $reservation->reservable->property->title ?? 'Hotel Room';
                }

                // Get currency symbol
                $currencySymbol = system_setting('currency_symbol') ?? '$';

                $variables = array(
                    'app_name' => $appName,
                    'user_name' => $customer->name,
                    'reservation_id' => $reservation->id,
                    'property_name' => $propertyName,
                    'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                    'number_of_guests' => $reservation->number_of_guests ?: 1,
                    'total_price' => number_format($reservation->total_price, 2),
                    'currency_symbol' => $currencySymbol,
                    'payment_status' => ucfirst($reservation->payment_status),
                    'transaction_id' => $reservation->transaction_id,
                    'special_requests' => $reservation->special_requests ?? 'None',
                );

                if (empty($reservationApprovalTemplateData)) {
                    $reservationApprovalTemplateData = "Your reservation has been approved!";
                }
                $reservationApprovalTemplate = \App\Services\HelperService::replaceEmailVariables($reservationApprovalTemplateData, $variables);

                $data = array(
                    'email_template' => $reservationApprovalTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'],
                );
                \App\Services\HelperService::sendMail($data, false, true); // Skip PDF for booking confirmation emails

                \Illuminate\Support\Facades\Log::info('Reservation approval email sent successfully', [
                    'reservation_id' => $reservation->id,
                    'customer_email' => $customer->email
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reservation approval email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send aggregated reservation approval email for multiple rooms (Pending).
     *
     * @param array $reservations
     * @return void
     */
    public function sendAggregatedReservationApprovalEmail($reservations)
    {
        if (empty($reservations)) {
            return;
        }

        try {
            $firstReservation = $reservations[0];
            $customer = $firstReservation->customer;

            if (!$customer || !$customer->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send aggregated approval email: customer or email not found', [
                    'customer_id' => $firstReservation->customer_id
                ]);
                return;
            }

            // Aggregate Data
            $totalPrice = 0;
            $roomDetails = [];
            $property = null;
            $propertyOwner = null;

            foreach ($reservations as $reservation) {
                $totalPrice += $reservation->total_price;

                if (in_array($reservation->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                    $hotelRoom = $reservation->reservable;
                    $property = $hotelRoom->property;
                    $propertyOwner = $property->customer;
                    $roomName = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->room_type)->name ?? 'Standard Room');
                } else {
                    $roomName = 'Property';
                    if (in_array($reservation->reservable_type, ['App\Models\Property', 'property'])) {
                        $property = $reservation->reservable;
                        $propertyOwner = $property->customer;
                    }
                }

                if (!isset($roomDetails[$roomName])) {
                    $roomDetails[$roomName] = 0;
                }
                $roomDetails[$roomName]++;
            }

            if (!$property) {
                return;
            }

            // Get Email Template
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_approval");
            $reservationApprovalTemplateData = system_setting('reservation_approval_mail_template');

            $appName = env("APP_NAME") ?? "As-home";
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Build HTML Table for Multi-Room details
            $tableRows = '';
            foreach ($reservations as $res) {
                $resName = 'Property';
                if (in_array($res->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                     $hotelRoom = $res->reservable;
                     $resName = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->room_type)->name ?? 'Standard Room');
                } elseif (in_array($res->reservable_type, ['App\Models\Property', 'property'])) {
                     $resName = $res->reservable->title ?? 'Property';
                }
                
                $resPrice = number_format($res->total_price, 2);
                $resGuests = $res->number_of_guests ?: 1;
                
                $tableRows .= "
                    <tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$resName}</td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$resGuests}</td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>{$resPrice} {$currencySymbol}</td>
                    </tr>
                ";
            }

            $roomDetailsTable = "
                <div style='margin-top: 15px; margin-bottom: 15px;'>
                    <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <thead>
                            <tr style='background-color: #f2f2f2;'>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Room Type</th>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Guests</th>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$tableRows}
                        </tbody>
                        <tfoot>
                            <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Total</td>
                                <td style='padding: 10px; border: 1px solid #ddd; text-align: right;'>" . number_format($totalPrice, 2) . " {$currencySymbol}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            ";

            // Use table as the primary display for 'room_type'
            $roomTypeDisplay = $roomDetailsTable;

            // Calculate number of nights
            $numberOfNights = 1;
            if ($firstReservation->check_in_date && $firstReservation->check_out_date) {
                $numberOfNights = $firstReservation->check_in_date->diffInDays($firstReservation->check_out_date);
            }

            // Prepare variables
            $variables = array(
                'app_name' => $appName,
                'user_name' => $customer->name,
                'customer_name' => $customer->name, // Alias
                'customer_email' => $customer->email,
                'customer_phone' => $customer->mobile ?? $firstReservation->customer_phone ?? 'N/A',
                'reservation_id' => $firstReservation->id,
                'property_name' => $property->title,
                'property_address' => $property->address ?? 'N/A',
                'room_type' => $roomTypeDisplay,
                'check_in_date' => $firstReservation->check_in_date ? $firstReservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $firstReservation->check_out_date ? $firstReservation->check_out_date->format('d M Y') : 'N/A',
                'number_of_guests' => $firstReservation->number_of_guests * count($reservations),
                'number_of_nights' => $numberOfNights,
                'total_price' => number_format($totalPrice, 2),
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($firstReservation->payment_status),
                'transaction_id' => $firstReservation->transaction_id ?? 'N/A',
                'special_requests' => $firstReservation->special_requests ?? 'None',
                'booking_type' => $firstReservation->booking_type ?? 'reservation',
                'property_owner_name' => $propertyOwner->name ?? 'Property Owner',
                'property_owner_email' => $propertyOwner->email ?? 'N/A',
                'property_owner_phone' => $propertyOwner->mobile ?? 'N/A',
                'property_phone' => $property->mobile ?? $propertyOwner->mobile ?? 'N/A',
                'property_email' => $property->email ?? $propertyOwner->email ?? 'N/A'
            );

            if (empty($reservationApprovalTemplateData)) {
                $reservationApprovalTemplateData = "Dear {customer_name},\n\nYour reservation for {property_name} has been approved!\n\nDetails:\nRooms: {room_type}\nCheck-in: {check_in_date}\nTotal: {total_price} {currency_symbol}\n\nBest regards,\n{app_name} Team";
            }

            $reservationApprovalTemplate = \App\Services\HelperService::replaceEmailVariables($reservationApprovalTemplateData, $variables);

            $data = array(
                'email_template' => $reservationApprovalTemplate,
                'email' => $customer->email,
                'title' => $emailTypeData['title'] ?? 'Reservation Approved'
            );

            \App\Services\HelperService::sendMail($data, false, true); // Skip PDF

            \Illuminate\Support\Facades\Log::info('Aggregated reservation approval email sent', [
                'customer_email' => $customer->email,
                'total_amount' => $totalPrice,
                'reservations_count' => count($reservations)
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send aggregated approval email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send reservation approval email with payment link.
     *
     * @param \App\Models\Reservation $reservation
     * @param string $paymentLink
     * @return void
     */
    public function sendReservationApprovalWithPaymentEmail($reservation, $paymentLink)
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_approval_payment");

                // Email Template
                $reservationApprovalTemplateData = system_setting('reservation_approval_payment_mail_template');
                $appName = env("APP_NAME") ?? "eBroker";

                // Get property name
                $propertyName = '';
                if ($reservation->reservable_type === 'App\Models\Property') {
                    $propertyName = $reservation->reservable->title ?? 'Property';
                } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
                    $propertyName = $reservation->reservable->property->title ?? 'Hotel Room';
                }

                // Get currency symbol
                $currencySymbol = system_setting('currency_symbol') ?? '$';

                $variables = array(
                    'app_name' => $appName,
                    'user_name' => $customer->name,
                    'reservation_id' => $reservation->id,
                    'property_name' => $propertyName,
                    'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                    'number_of_guests' => $reservation->number_of_guests ?: 1,
                    'total_price' => number_format($reservation->total_price, 2),
                    'currency_symbol' => $currencySymbol,
                    'payment_status' => ucfirst($reservation->payment_status),
                    'transaction_id' => $reservation->transaction_id,
                    'special_requests' => $reservation->special_requests ?? 'None',
                    'payment_link' => $paymentLink,
                );

                // Log the variables for debugging
                \Illuminate\Support\Facades\Log::info('Email variables for payment link email', [
                    'reservation_id' => $reservation->id,
                    'payment_link' => $paymentLink,
                    'variables' => $variables
                ]);

                if (empty($reservationApprovalTemplateData)) {
                    $reservationApprovalTemplateData = "Your reservation has been approved! Please complete your payment to confirm your booking using the link below: <br><br><a href='{payment_link}'>Complete Payment</a><br><br>Your reservation will be confirmed once payment is completed.";
                }
                $reservationApprovalTemplate = \App\Services\HelperService::replaceEmailVariables($reservationApprovalTemplateData, $variables);

                // Log the final email template for debugging
                \Illuminate\Support\Facades\Log::info('Final email template after variable replacement', [
                    'reservation_id' => $reservation->id,
                    'final_template' => $reservationApprovalTemplate
                ]);

                $data = array(
                    'email_template' => $reservationApprovalTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'] ?? 'Reservation Approved - Payment Required',
                );
                \App\Services\HelperService::sendMail($data);

                // Send notification to customer
                \App\Models\Notifications::create([
                    'title' => 'Reservation Approved - Payment Required',
                    'message' => 'Your reservation has been approved! Please complete your payment to confirm your booking.<br><br><a href="' . $paymentLink . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Complete Payment</a>',
                    'image' => '',
                    'type' => '1',
                    'send_type' => '0',
                    'customers_id' => $customer->id,
                    'propertys_id' => $reservation->property_id,
                ]);

                \Illuminate\Support\Facades\Log::info('Reservation approval with payment email sent successfully', [
                    'reservation_id' => $reservation->id,
                    'customer_email' => $customer->email,
                    'payment_link' => $paymentLink
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reservation approval with payment email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send reservation confirmation email to customer after successful payment.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    protected function sendReservationConfirmationEmail($reservation)
    {
        try {
            $customer = $reservation->customer;
            if (!$customer || !$customer->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send reservation confirmation email: customer or email not found', [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $customer->id ?? 'unknown'
                ]);
                return;
            }

            // Get property information
            $property = null;
            $propertyOwner = null;
            $roomType = '';
            
            if ($reservation->reservable_type === 'App\Models\Property') {
                $property = $reservation->reservable;
                $propertyOwner = $property->customer;
            } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
                $hotelRoom = $reservation->reservable;
                $property = $hotelRoom->property;
                $propertyOwner = $property->customer;
                $roomType = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->room_type)->name ?? 'Standard Room');
            }

            if (!$property) {
                \Illuminate\Support\Facades\Log::warning('Cannot send reservation confirmation email: property not found', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Get email template data
            if ($reservation->booking_type === 'flexible_booking') {
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("flexible_reservation_confirmation");
                $reservationConfirmationTemplateData = system_setting('flexible_reservation_confirmation_mail_template');
            } else {
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_confirmation");
                $reservationConfirmationTemplateData = system_setting('reservation_confirmation_mail_template');
            }
            $appName = env("APP_NAME") ?? "As-home";

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Calculate number of nights
            $numberOfNights = 1;
            if ($reservation->check_in_date && $reservation->check_out_date) {
                $numberOfNights = $reservation->check_in_date->diffInDays($reservation->check_out_date);
            }

            // Prepare comprehensive email variables
            $variables = array(
                'app_name' => $appName,
                'user_name' => $customer->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->mobile ?? $reservation->customer_phone ?? 'N/A',
                'reservation_id' => $reservation->id,
                'property_name' => $property->title,
                'property_address' => $property->address ?? 'N/A',
                'room_type' => $roomType,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'check_in_time' => $reservation->check_in_time ?? 'N/A',
                'check_out_time' => $reservation->check_out_time ?? 'N/A',
                'number_of_guests' => $reservation->number_of_guests ?? 1,
                'number_of_nights' => $numberOfNights,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($reservation->payment_status),
                'transaction_id' => $reservation->transaction_id ?? 'N/A',
                'special_requests' => $reservation->special_requests ?? 'None',
                'review_url' => $reservation->review_url ?? '',
                'confirmation_date' => now()->format('d M Y, h:i A'),
                'booking_type' => $reservation->booking_type ?? 'reservation',
                'property_owner_name' => $propertyOwner->name ?? 'Property Owner',
                'property_owner_email' => $propertyOwner->email ?? 'N/A',
                'property_owner_phone' => $propertyOwner->mobile ?? 'N/A',
                'cancellation_policy' => $property->cancellation_policy ?? 'Please contact the property owner for cancellation policy.',
                'property_phone' => $property->mobile ?? $propertyOwner->mobile ?? 'N/A',
                'property_email' => $property->email ?? $propertyOwner->email ?? 'N/A'
            );

            // Default comprehensive template if none is set
            if (empty($reservationConfirmationTemplateData)) {
                $reservationConfirmationTemplateData = '
Dear {customer_name},

🎉 Your reservation has been confirmed!

Reservation Details:
• Reservation ID: {reservation_id}
• Property: {property_name}
• Address: {property_address}
• Room Type: {room_type}
• Check-in: {check_in_date} at {check_in_time}
• Check-out: {check_out_date} at {check_out_time}
• Number of Guests: {number_of_guests}
• Number of Nights: {number_of_nights}
• Total Amount: {total_price} {currency_symbol}
• Payment Status: {payment_status}
• Transaction ID: {transaction_id}

Contact Information:
• Property Owner: {property_owner_name}
• Phone: {property_phone}
• Email: {property_email}

Special Requests: {special_requests}

Cancellation Policy: {cancellation_policy}

We look forward to hosting you! If you have any questions, please don\'t hesitate to contact us.

Best regards,
{app_name} Team

Confirmation Date: {confirmation_date}
                ';
            }

            $reservationConfirmationTemplate = \App\Services\HelperService::replaceEmailVariables($reservationConfirmationTemplateData, $variables);

            $data = array(
                'email_template' => $reservationConfirmationTemplate,
                'email' => $customer->email,
                'title' => $emailTypeData['title'] ?? 'Reservation Confirmed - Payment Successful'
            );

            \App\Services\HelperService::sendMail($data);

            \Illuminate\Support\Facades\Log::info('Reservation confirmation email sent successfully to customer', [
                'reservation_id' => $reservation->id,
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'property_id' => $property->id,
                'total_amount' => $reservation->total_price
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reservation confirmation email via admin confirmation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send aggregated reservation confirmation email for multiple rooms.
     *
     * @param array $reservations
     * @return void
     */
    public function sendAggregatedReservationConfirmationEmail($reservations)
    {
        if (empty($reservations)) {
            return;
        }

        try {
            $firstReservation = $reservations[0];
            $customer = $firstReservation->customer;

            if (!$customer || !$customer->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send aggregated confirmation email: customer or email not found', [
                    'customer_id' => $firstReservation->customer_id
                ]);
                return;
            }

            // Aggregate Data
            $totalPrice = 0;
            $roomDetails = [];
            $property = null;
            $propertyOwner = null;

            foreach ($reservations as $reservation) {
                $totalPrice += $reservation->total_price;

                if (in_array($reservation->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                    $hotelRoom = $reservation->reservable;
                    $property = $hotelRoom->property;
                    $propertyOwner = $property->customer;
                    $roomName = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->room_type)->name ?? 'Standard Room');
                } else {
                    $roomName = 'Property';
                    if (in_array($reservation->reservable_type, ['App\Models\Property', 'property'])) {
                        $property = $reservation->reservable;
                        $propertyOwner = $property->customer;
                    }
                }

                if (!isset($roomDetails[$roomName])) {
                    $roomDetails[$roomName] = 0;
                }
                $roomDetails[$roomName]++;
            }

            if (!$property) {
                return;
            }

            // Get Email Template based on booking type
            if ($firstReservation->booking_type === 'flexible_booking') {
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("flexible_hotel_booking_confirmation");
                $reservationConfirmationTemplateData = system_setting('flexible_hotel_booking_confirmation_mail_template');
            } else {
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_confirmation");
                $reservationConfirmationTemplateData = system_setting('reservation_confirmation_mail_template');
            }

            $appName = env("APP_NAME") ?? "As-home";
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Build HTML Table for Multi-Room details
            $tableRows = '';
            foreach ($reservations as $res) {
                $resName = 'Property';
                if (in_array($res->reservable_type, ['App\Models\HotelRoom', 'hotel_room'])) {
                     $hotelRoom = $res->reservable;
                     $resName = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->room_type)->name ?? 'Standard Room');
                } elseif (in_array($res->reservable_type, ['App\Models\Property', 'property'])) {
                     $resName = $res->reservable->title ?? 'Property';
                }
                
                $resPrice = number_format($res->total_price, 2);
                $resGuests = $res->number_of_guests ?: 1;
                
                $tableRows .= "
                    <tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$resName}</td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$resGuests}</td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>{$resPrice} {$currencySymbol}</td>
                    </tr>
                ";
            }

            $roomDetailsTable = "
                <div style='margin-top: 15px; margin-bottom: 15px;'>
                    <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <thead>
                            <tr style='background-color: #f2f2f2;'>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Room Type</th>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Guests</th>
                                <th style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$tableRows}
                        </tbody>
                        <tfoot>
                            <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Total</td>
                                <td style='padding: 10px; border: 1px solid #ddd; text-align: right;'>" . number_format($totalPrice, 2) . " {$currencySymbol}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            ";

            // Format Room Type String (Summary) - kept for reference or specific templates
            $roomTypeParts = [];
            foreach ($roomDetails as $name => $count) {
                $roomTypeParts[] = "{$count}x {$name}";
            }
            $roomTypeSummary = implode(', ', $roomTypeParts);
            
            // Use table as the primary display for 'room_type' to ensure visibility in default templates
            $roomTypeDisplay = $roomDetailsTable;

            // Calculate number of nights (using first reservation)
            $numberOfNights = 1;
            if ($firstReservation->check_in_date && $firstReservation->check_out_date) {
                $numberOfNights = $firstReservation->check_in_date->diffInDays($firstReservation->check_out_date);
            }

            // Prepare variables
            $variables = array(
                'app_name' => $appName,
                'user_name' => $customer->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->mobile ?? $firstReservation->customer_phone ?? 'N/A',
                'reservation_id' => $firstReservation->id,
                'property_name' => $property->title,
                'hotel_name' => $property->title, // Alias for flexible template
                'property_address' => $property->address ?? 'N/A',
                'hotel_address' => $property->address ?? 'N/A', // Alias for flexible template
                'room_type' => $roomTypeDisplay, // Aggregated Room Types as Table
                'room_type_summary' => $roomTypeSummary,
                'room_number' => 'Multiple', // For flexible template
                'check_in_date' => $firstReservation->check_in_date ? $firstReservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $firstReservation->check_out_date ? $firstReservation->check_out_date->format('d M Y') : 'N/A',
                'check_in_time' => $firstReservation->check_in_time ?? 'N/A',
                'check_out_time' => $firstReservation->check_out_time ?? 'N/A',
                'number_of_guests' => $firstReservation->number_of_guests * count($reservations),
                'number_of_nights' => $numberOfNights,
                'total_price' => number_format($totalPrice, 2),
                'total_amount' => number_format($totalPrice, 2), // Alias for flexible template
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($firstReservation->payment_status),
                'transaction_id' => $firstReservation->transaction_id ?? 'N/A',
                'special_requests' => $firstReservation->special_requests ?? 'None',
                'review_url' => $firstReservation->review_url ?? '',
                'confirmation_date' => now()->format('d M Y, h:i A'),
                'booking_type' => $firstReservation->booking_type ?? 'reservation',
                'property_owner_name' => $propertyOwner->name ?? 'Property Owner',
                'property_owner_email' => $propertyOwner->email ?? 'N/A',
                'property_owner_phone' => $propertyOwner->mobile ?? 'N/A',
                'cancellation_policy' => $property->cancellation_policy ?? 'Please contact the property owner for cancellation policy.',
                'property_phone' => $property->mobile ?? $propertyOwner->mobile ?? 'N/A',
                'property_email' => $property->email ?? $propertyOwner->email ?? 'N/A'
            );

             // Default comprehensive template if none is set
             if (empty($reservationConfirmationTemplateData)) {
                $reservationConfirmationTemplateData = '
Dear {customer_name},

🎉 Your reservation has been confirmed!

Reservation Details:
• Reservation ID: {reservation_id}
• Property: {property_name}
• Address: {property_address}
• Rooms: {room_type}
• Check-in: {check_in_date}
• Check-out: {check_out_date}
• Total Amount: {total_price} {currency_symbol}
• Payment Status: {payment_status}

We look forward to hosting you!

Best regards,
{app_name} Team
                ';
            }

            $reservationConfirmationTemplate = \App\Services\HelperService::replaceEmailVariables($reservationConfirmationTemplateData, $variables);

            $data = array(
                'email_template' => $reservationConfirmationTemplate,
                'email' => $customer->email,
                'title' => $emailTypeData['title'] ?? 'Reservation Confirmed'
            );

            \App\Services\HelperService::sendMail($data);

            \Illuminate\Support\Facades\Log::info('Aggregated reservation confirmation email sent', [
                'customer_email' => $customer->email,
                'total_amount' => $totalPrice,
                'reservations_count' => count($reservations),
                'booking_type' => $firstReservation->booking_type
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send aggregated confirmation email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if dates are available for reservation.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $excludeReservationId Optional reservation ID to exclude from overlap check
     * @return bool
     */
    /**
     * Count booked units for a specific apartment on given dates.
     *
     * @param int $propertyId
     * @param int $apartmentId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $excludeReservationId
     * @return int
     */
    public function countBookedUnitsForApartment($propertyId, $apartmentId, $checkInDate, $checkOutDate, $excludeReservationId = null, $statuses = ['confirmed'])
    {
        $checkIn = Carbon::parse($checkInDate)->startOfDay();
        $checkOut = Carbon::parse($checkOutDate)->startOfDay();
        
        // Get all reservations for this property on overlapping dates with specified statuses
        $reservations = Reservation::where('reservable_type', 'App\\Models\\Property')
            ->where('reservable_id', $propertyId)
            ->whereIn('status', $statuses)
            ->where(function($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
            });
        
        if ($excludeReservationId) {
            $reservations->where('id', '!=', $excludeReservationId);
        }
        
        // SAFETY: Check if apartment_id column exists (for backward compatibility)
        if (Schema::hasColumn('reservations', 'apartment_id')) {
            // Use direct column query (faster, more reliable) - only for multi-unit vacation homes
            $reservations->where('apartment_id', $apartmentId);
            $totalBookedUnits = $reservations->sum('apartment_quantity') ?? 0;
            return (int)$totalBookedUnits;
        } else {
            // Fallback: Parse special_requests (existing logic for backward compatibility)
            $reservations = $reservations->get();
            $totalBookedUnits = 0;
            
            foreach ($reservations as $reservation) {
                $specialRequests = $reservation->special_requests ?? '';
                
                // Look for "Apartment ID: X, Quantity: Y" pattern
                if (preg_match('/Apartment ID:\s*(\d+).*?Quantity:\s*(\d+)/i', $specialRequests, $matches)) {
                    $reservationApartmentId = (int)$matches[1];
                    $reservationQuantity = (int)$matches[2];
                    
                    // Only count if it's for the same apartment
                    if ($reservationApartmentId == $apartmentId) {
                        $totalBookedUnits += $reservationQuantity;
                    }
                }
            }
            
            return $totalBookedUnits;
        }
    }

    public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate, $excludeReservationId = null, $data = [])
    {
        try {
            // Parse the check-in and check-out dates with error handling
            try {
                // Parse dates and set to start of day to avoid timezone issues
                $checkIn = Carbon::parse($checkInDate)->startOfDay();
                $checkOut = Carbon::parse($checkOutDate)->startOfDay();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Date parsing error in areDatesAvailable', [
                    'checkInDate' => $checkInDate,
                    'checkOutDate' => $checkOutDate,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
            
            // Get today's date in the application timezone, set to start of day for accurate comparison
            $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
            $today = Carbon::today($appTimezone)->startOfDay();

            // Check for past dates - compare dates only (not time)
            // Use format comparison to avoid timezone issues
            if ($checkIn->format('Y-m-d') < $today->format('Y-m-d') || 
                $checkOut->format('Y-m-d') < $today->format('Y-m-d')) {
                return false;
            }

            // Get the model instance first
            $model = $this->getModelInstance($modelType, $modelId);
            if (!$model) {
                return false;
            }

            // NEW CONDITION: Only block if there are confirmed reservations for this specific room
            // Don't block for any other conditions (available_dates, availability_type, etc.)
            // Only block when room type availability equals zero AND no available rooms for this day
            try {
                // For hotel rooms, check if this specific room has a confirmed reservation
                if ($modelType === 'App\\Models\\HotelRoom' || $modelType === 'hotel_room') {
                    // TEMP DEBUG: Log what we're checking
                    \Illuminate\Support\Facades\Log::info('Checking room availability', [
                        'modelId' => $modelId,
                        'checkInDate' => $checkInDate,
                        'checkOutDate' => $checkOutDate,
                        'excludeReservationId' => $excludeReservationId
                    ]);
                    
                    // Check for any existing reservations first
                    $existingReservations = \App\Models\Reservation::where('reservable_id', $modelId)
                        ->where('reservable_type', 'App\\Models\\HotelRoom')
                        ->whereIn('status', ['confirmed', 'approved', 'pending']) // Match frontend logic
                        ->where(function($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '>=', $checkInDate)
                                ->where('check_in_date', '<', $checkOutDate);
                        })->orWhere(function($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_out_date', '>', $checkInDate)
                                ->where('check_out_date', '<', $checkOutDate);
                        })->orWhere(function($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '<=', $checkInDate)
                                ->where('check_out_date', '>', $checkOutDate);
                        })->get();
                    
                    if ($existingReservations->count() > 0) {
                        \Illuminate\Support\Facades\Log::info('Existing confirmed reservations for room (Direct Query)', [
                            'modelId' => $modelId,
                            'reservations' => $existingReservations->toArray(),
                            'count' => $existingReservations->count()
                        ]);
                    }
                    
                    $hasOverlap = Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType, $excludeReservationId);
                    
                    if ($hasOverlap) {
                        // Check if the overlapping reservation is actually 'confirmed' or 'approved'
                        // If it's a flexible reservation that is 'confirmed' but 'unpaid', it should block dates
                        // If it's a non-refundable reservation that is 'pending' or 'unpaid', it might not block dates depending on policy
                        // But for safety, we currently block all 'confirmed', 'approved', 'pending' in datesOverlap scope
                        
                        // FIX: Ensure we only block for truly conflicting reservations
                        // The existing check in datesOverlap includes 'pending', which might be too aggressive for some flows
                        // However, to prevent double booking, 'pending' usually needs to block
                        
                        // For now, we trust datesOverlap as the source of truth for "is this room taken"
                        \Illuminate\Support\Facades\Log::info('Room not available - has overlapping reservation (datesOverlap)', [
                            'modelId' => $modelId
                        ]);
                        return false;
                    }
                } else {
                    // For properties (vacation homes), check unit-level availability if apartment_id is provided
                    // Check if this is a vacation home property with apartment_id in request context
                    $apartmentId = $data['apartment_id'] ?? null;
                    $requestedQuantity = $data['apartment_quantity'] ?? 1;
                    
                    // SAFETY CHECK: Only apply multi-unit logic if:
                    // 1. apartment_id is provided
                    // 2. It's a vacation home (property_classification = 4)
                    // 3. The apartment has quantity > 1 (multi-unit)
                    if ($apartmentId && $modelType === 'App\\Models\\Property') {
                        // Get the apartment to check its quantity
                        $apartment = \App\Models\VacationApartment::find($apartmentId);
                        
                        if ($apartment && $apartment->property_id == $modelId) {
                            // Check if property is vacation home
                            $property = Property::find($modelId);
                            $isVacationHome = $property && $property->getRawOriginal('property_classification') == 4;
                            
                            if ($isVacationHome) {
                                $totalUnits = $apartment->quantity;
                                
                                // SAFETY: Only use multi-unit logic if quantity > 1
                                if ($totalUnits > 1) {
                                    // MULTI-UNIT LOGIC: Count booked units
                                    $bookedUnits = $this->countBookedUnitsForApartment(
                                        $modelId,
                                        $apartmentId,
                                        $checkInDate,
                                        $checkOutDate,
                                        $excludeReservationId,
                                        ['confirmed', 'approved', 'pending']
                                    );
                                    
                                    $availableUnits = $totalUnits - $bookedUnits;
                                    
                                    \Illuminate\Support\Facades\Log::info('Multi-unit vacation home availability check', [
                                        'property_id' => $modelId,
                                        'apartment_id' => $apartmentId,
                                        'total_units' => $totalUnits,
                                        'booked_units' => $bookedUnits,
                                        'available_units' => $availableUnits,
                                        'requested_quantity' => $requestedQuantity,
                                        'can_book' => $availableUnits >= $requestedQuantity
                                    ]);
                                    
                                    // Allow booking if enough units are available
                                    return $availableUnits >= $requestedQuantity;
                                } else {
                                    // SINGLE-UNIT LOGIC: Use apartment-specific check
                                    // If apartment_id is provided, we MUST check if THIS apartment is booked.
                                    // We cannot use generic datesOverlap because it checks property-level which blocks all apartments.
                                    
                                    $bookedUnits = $this->countBookedUnitsForApartment(
                                        $modelId,
                                        $apartmentId,
                                        $checkInDate,
                                        $checkOutDate,
                                        $excludeReservationId,
                                        ['confirmed', 'approved', 'pending']
                                    );

                                    \Illuminate\Support\Facades\Log::info('Single-unit vacation home availability check', [
                                        'property_id' => $modelId,
                                        'apartment_id' => $apartmentId,
                                        'booked_units' => $bookedUnits,
                                        'can_book' => $bookedUnits < 1
                                    ]);
                                    
                                    // Since quantity is 1, any booking blocks it
                                    return $bookedUnits < 1;
                                }
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Apartment verification failed in areDatesAvailable', [
                                'apartment_id' => $apartmentId,
                                'model_id' => $modelId,
                                'apartment_exists' => !!$apartment,
                                'apartment_property_id' => $apartment ? $apartment->property_id : null
                            ]);
                        }
                    }
                    
                    // FALLBACK: For all other properties (non-vacation homes, or vacation homes without apartment_id)
                    // Use existing standard overlap check - NO CHANGES
                    $hasOverlap = Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType, $excludeReservationId);
                    
                    if ($hasOverlap) {
                        \Illuminate\Support\Facades\Log::info('Property not available - has overlapping reservation (Fallback datesOverlap)', [
                            'modelId' => $modelId,
                            'checkIn' => $checkInDate,
                            'checkOut' => $checkOutDate
                        ]);
                        return false;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error checking date overlap', [
                    'checkInDate' => $checkInDate,
                    'checkOutDate' => $checkOutDate,
                    'modelId' => $modelId,
                    'modelType' => $modelType,
                    'error' => $e->getMessage()
                ]);
                // On error, allow booking to avoid false blocks
                return true;
            }

            // If no confirmed reservations blocking, check available_dates for hotel rooms
            if ($modelType === 'App\\Models\\HotelRoom' || $modelType === 'hotel_room') {
                // Treat rooms as available by default unless there is an explicit block
                // (dead/reserved) in available_dates_hotel_rooms overlapping the stay.
                $blockingDatesQuery = \DB::table('available_dates_hotel_rooms')
                    ->where('hotel_room_id', $modelId)
                    ->whereIn('type', ['dead', 'reserved'])
                    // Overlap condition for [checkIn, checkOut) nights:
                    // blocked_from < checkOut AND blocked_to >= checkIn
                    ->where('from_date', '<', $checkOut->format('Y-m-d'))
                    ->where('to_date', '>=', $checkIn->format('Y-m-d'));

                // Keep the property_id filter only when we have a property_id
                if (!empty($model->property_id)) {
                    $blockingDatesQuery->where(function ($q) use ($model) {
                        $q->whereNull('property_id')
                          ->orWhere('property_id', $model->property_id);
                    });
                }

                $hasBlockingDates = $blockingDatesQuery->exists();

                if ($hasBlockingDates) {
                    $blockingDates = $blockingDatesQuery->get();
                    \Illuminate\Support\Facades\Log::info('Room has blocked available_dates during selected dates, not available', [
                        'modelId' => $modelId,
                        'checkInDate' => $checkInDate,
                        'checkOutDate' => $checkOutDate,
                        'blockingEntries' => $blockingDates->toArray()
                    ]);
                    return false;
                }

                return true;
            }
            
            // For properties, allow booking if no confirmed reservations blocking
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error in areDatesAvailable', [
                'modelType' => $modelType,
                'modelId' => $modelId,
                'checkInDate' => $checkInDate,
                'checkOutDate' => $checkOutDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return false on error to be safe
            return false;
        }
    }

    /**
     * Send payment completion email to property owner.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendPaymentCompletionEmailToOwner($reservation)
    {
        try {
            // Get property owner information
            $property = null;
            $propertyOwner = null;
            
            if ($reservation->reservable_type === 'App\Models\Property') {
                $property = $reservation->reservable;
                $propertyOwner = $property->customer;
            } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
                $hotelRoom = $reservation->reservable;
                $property = $hotelRoom->property;
                $propertyOwner = $property->customer;
            }

            if (!$propertyOwner || !$propertyOwner->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send payment completion email to property owner: owner or email not found', [
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id ?? 'unknown',
                    'owner_id' => $propertyOwner->id ?? 'unknown'
                ]);
                return;
            }

            // Get customer information
            $customer = $reservation->customer;
            if (!$customer) {
                \Illuminate\Support\Facades\Log::warning('Cannot send payment completion email: customer not found', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Get email template data
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("payment_completion_owner");
            $emailTemplateData = system_setting('payment_completion_owner_mail_template');
            $appName = env("APP_NAME") ?? "As-home";

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Prepare email variables
            $variables = array(
                'app_name' => $appName,
                'property_owner_name' => $propertyOwner->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->mobile ?? $reservation->customer_phone,
                'property_name' => $property->title,
                'property_address' => $property->address,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($reservation->payment_status),
                'transaction_id' => $reservation->transaction_id,
                'reservation_id' => $reservation->id,
                'special_requests' => $reservation->special_requests ?? 'None',
                'payment_completion_date' => now()->format('d M Y, h:i A'),
                'booking_type' => $reservation->booking_type ?? 'reservation'
            );

            // Default template if none is set
            if (empty($emailTemplateData)) {
                $emailTemplateData = 'Payment completed for your property "{property_name}"! Customer {customer_name} ({customer_email}) has successfully paid {total_price} {currency_symbol} for their reservation. Check-in: {check_in_date}, Check-out: {check_out_date}. Reservation ID: {reservation_id}. Please prepare for their arrival.';
            }

            $emailContent = \App\Services\HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email to property owner
            $data = array(
                'email_template' => $emailContent,
                'email' => $propertyOwner->email,
                'title' => $emailTypeData['title'] ?? 'Payment Completed - New Booking'
            );

            \App\Services\HelperService::sendMail($data);

            \Illuminate\Support\Facades\Log::info('Payment completion email sent to property owner', [
                'reservation_id' => $reservation->id,
                'property_id' => $property->id,
                'owner_id' => $propertyOwner->id,
                'owner_email' => $propertyOwner->email,
                'customer_id' => $customer->id
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send payment completion email to property owner: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send flexible hotel booking approval email (alias for confirmation email).
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendFlexibleHotelBookingApprovalEmail($reservation)
    {
        return $this->sendFlexibleHotelBookingConfirmationEmail($reservation);
    }

    /**
     * Send flexible hotel booking confirmation email to customer.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendFlexibleHotelBookingConfirmationEmail($reservation, $siblings = [])
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("flexible_hotel_booking_confirmation");

                // Email Template
                $emailTemplateData = system_setting('flexible_hotel_booking_confirmation_mail_template');
                $appName = env("APP_NAME") ?? "As Home";

                // Combine reservation with siblings for multi-room logic
                $allReservations = array_merge([$reservation], $siblings);
                $isMultiRoom = count($allReservations) > 1;

                // Get hotel and room information
                $hotelName = '';
                $roomType = '';
                $roomNumber = '';
                $hotelAddress = '';

                if ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                    $hotelRoom = $reservation->reservable;
                    if ($hotelRoom) {
                        $hotelName = $hotelRoom->property->title ?? 'Hotel';
                        $roomNumber = $hotelRoom->room_number ?? 'N/A';
                        $hotelAddress = $hotelRoom->property->address ?? 'N/A';
                        
                        // Get room type name
                        if ($hotelRoom->roomType) {
                            $roomType = $hotelRoom->roomType->name ?? 'Standard Room';
                        } else {
                            $roomType = 'Standard Room';
                        }
                    }
                } else {
                    // For property reservations, use property details
                    $hotelName = $reservation->property->title ?? 'Property';
                    $roomType = 'Property';
                    $roomNumber = 'N/A';
                    $hotelAddress = $reservation->property->address ?? 'N/A';
                }

                // Get currency symbol
                $currencySymbol = system_setting('currency_symbol') ?? '$';

                // Calculate Total Price and Build Room Table if Multi-Room
                $totalPriceValue = $reservation->total_price;
                
                if ($isMultiRoom) {
                    $totalPriceValue = 0;
                    $tableRows = '';
                    
                    foreach ($allReservations as $res) {
                        $totalPriceValue += $res->total_price;
                        
                        $resName = 'Property';
                        if ($res->reservable_type === 'App\\Models\\HotelRoom') {
                             $hRoom = $res->reservable;
                             $resName = !empty($hRoom->custom_room_type) ? $hRoom->custom_room_type : (optional($hRoom->roomType)->name ?? 'Standard Room');
                        } elseif ($res->reservable_type === 'App\\Models\\Property') {
                             $resName = $res->reservable->title ?? 'Property';
                        }
                        
                        $resPrice = number_format($res->total_price, 2);
                        $resGuests = $res->number_of_guests;
                        
                        $tableRows .= "
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd;'>{$resName}</td>
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$resGuests}</td>
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>{$resPrice} {$currencySymbol}</td>
                            </tr>
                        ";
                    }

                    $roomDetailsTable = "
                        <div style='margin-top: 15px; margin-bottom: 15px;'>
                            <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                                <thead>
                                    <tr style='background-color: #f2f2f2;'>
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Room Type</th>
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Guests</th>
                                        <th style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$tableRows}
                                </tbody>
                                <tfoot>
                                    <tr style='font-weight: bold; background-color: #f9f9f9;'>
                                        <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Total</td>
                                        <td style='padding: 10px; border: 1px solid #ddd; text-align: right;'>" . number_format($totalPriceValue, 2) . " {$currencySymbol}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    ";
                    
                    // Use table as room_type for visibility in template
                    $roomType = $roomDetailsTable;
                    // Clear room number as it's multiple
                    $roomNumber = 'Multiple';
                }

                // Get customer email and phone
                $guestEmail = $customer->email ?? $reservation->customer_email ?? 'N/A';
                $guestPhone = $customer->mobile ?? $reservation->customer_phone ?? 'N/A';
                $totalAmount = number_format($totalPriceValue, 2);

                $variables = array(
                    'app_name' => $appName,
                    'customer_name' => $customer->name,
                    'user_name' => $customer->name, // Keep both for compatibility
                    'guest_email' => $guestEmail,
                    'guest_phone' => $guestPhone,
                    'property_name' => $hotelName,
                    'hotel_name' => $hotelName, // Keep both for compatibility
                    'reservation_id' => $reservation->id,
                    'room_type' => $roomType,
                    'room_number' => $roomNumber,
                    'hotel_address' => $hotelAddress,
                    'property_address' => $hotelAddress, // Add property_address for template compatibility
                    'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                    'number_of_guests' => $reservation->number_of_guests * ($isMultiRoom ? count($allReservations) : 1), // Approximate if multi
                    'total_price' => $totalAmount,
                    'total_amount' => $totalAmount, // Add total_amount alias for template compatibility
                    'currency_symbol' => $currencySymbol,
                    'payment_status' => ucfirst($reservation->payment_status),
                    'special_requests' => $reservation->special_requests ?? 'None',
                );

                if (empty($emailTemplateData)) {
                    $emailTemplateData = "Dear {customer_name},\n\nYour reservation for {property_name} has been confirmed!\n\nWe are pleased to confirm your booking details:\n\nProperty: {property_name}\nRoom Number: {room_number}\nAddress: {property_address}\nCheck-in Date: {check_in_date}\nCheck-out Date: {check_out_date}\nNumber of Guests: {number_of_guests}\nTotal Amount: {total_price} {currency_symbol}\n\nYour reservation is now confirmed and the room has been reserved for you.\n\nThank you for choosing {app_name}!\n\nBest regards,\n{app_name} Team";
                }
                
                // Log the template and variables for debugging
                \Illuminate\Support\Facades\Log::info('Email template before variable replacement', [
                    'reservation_id' => $reservation->id,
                    'template' => $emailTemplateData,
                    'variables' => $variables
                ]);
                
                $emailTemplate = \App\Services\HelperService::replaceEmailVariables($emailTemplateData, $variables);
                
                // Log the template after variable replacement
                \Illuminate\Support\Facades\Log::info('Email template after variable replacement', [
                    'reservation_id' => $reservation->id,
                    'final_template' => $emailTemplate
                ]);

                $data = array(
                    'email_template' => $emailTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'],
                );
                \App\Services\HelperService::sendMail($data);

                \Illuminate\Support\Facades\Log::info('Flexible hotel booking approval email sent successfully', [
                    'reservation_id' => $reservation->id,
                    'customer_email' => $customer->email,
                    'hotel_name' => $hotelName,
                    'room_type' => $roomType
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send flexible hotel booking approval email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send vacation home pending approval email to customer.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendVacationHomePendingApprovalEmail($reservation)
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("vacation_home_pending_approval");

                // Email Template
                $emailTemplateData = system_setting('vacation_home_pending_approval_mail_template');
                $appName = env("APP_NAME") ?? "As Home";

                // Get property information
                $propertyName = '';
                $propertyAddress = '';

                if ($reservation->reservable_type === 'App\\Models\\Property') {
                    $property = $reservation->reservable;
                    if ($property) {
                        $propertyName = $property->title ?? 'Property';
                        $propertyAddress = $property->address ?? 'N/A';
                    }
                } else {
                    // For hotel room reservations, use property relation
                    $propertyName = $reservation->property->title ?? 'Property';
                    $propertyAddress = $reservation->property->address ?? 'N/A';
                }

                // Get currency symbol
                $currencySymbol = system_setting('currency_symbol') ?? '$';

                // Get customer email and phone
                $guestEmail = $customer->email ?? $reservation->customer_email ?? 'N/A';
                $guestPhone = $customer->mobile ?? $reservation->customer_phone ?? 'N/A';
                $totalAmount = number_format($reservation->total_price, 2);

                $variables = array(
                    'app_name' => $appName,
                    'customer_name' => $customer->name,
                    'user_name' => $customer->name, // Keep both for compatibility
                    'guest_email' => $guestEmail,
                    'guest_phone' => $guestPhone,
                    'property_name' => $propertyName,
                    'property_address' => $propertyAddress,
                    'reservation_id' => $reservation->id,
                    'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                    'number_of_guests' => $reservation->number_of_guests,
                    'total_price' => $totalAmount,
                    'total_amount' => $totalAmount, // Add total_amount alias for template compatibility
                    'currency_symbol' => $currencySymbol,
                    'payment_status' => ucfirst($reservation->payment_status),
                    'special_requests' => $reservation->special_requests ?? 'None',
                );

                if (empty($emailTemplateData)) {
                    $emailTemplateData = "Dear {customer_name},\n\nWe have received your reservation request for {property_name} and it is now pending approval from the property owner.\n\nYou'll receive a confirmation email once your booking is approved.\n\nThank you for choosing {app_name}!\n\nBest regards,\n{app_name} Team";
                }
                
                // Log the template and variables for debugging
                \Illuminate\Support\Facades\Log::info('Email template before variable replacement', [
                    'reservation_id' => $reservation->id,
                    'template' => $emailTemplateData,
                    'variables' => $variables
                ]);
                
                $emailTemplate = \App\Services\HelperService::replaceEmailVariables($emailTemplateData, $variables);
                
                // Log the template after variable replacement
                \Illuminate\Support\Facades\Log::info('Email template after variable replacement', [
                    'reservation_id' => $reservation->id,
                    'final_template' => $emailTemplate
                ]);

                $data = array(
                    'email_template' => $emailTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'],
                );
                \App\Services\HelperService::sendMail($data);

                \Illuminate\Support\Facades\Log::info('Vacation home pending approval email sent successfully', [
                    'reservation_id' => $reservation->id,
                    'customer_email' => $customer->email,
                    'property_name' => $propertyName
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send vacation home pending approval email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if there's an existing reservation for the same room and overlapping dates
     *
     * @param string $reservableType
     * @param int $reservableId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @return \App\Models\Reservation|null
     */
    public function checkExistingReservation($reservableType, $reservableId, $checkInDate, $checkOutDate)
    {
        // Check for existing reservations that overlap with the requested dates
        // Using standard hotel booking logic: check-in is inclusive, check-out is exclusive
        $existingReservation = Reservation::where('reservable_type', $reservableType)
            ->where('reservable_id', $reservableId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                // Check for overlapping reservations:
                // 1. Existing reservation starts before new check-out AND ends after new check-in
                $query->where(function ($subQuery) use ($checkInDate, $checkOutDate) {
                    $subQuery->where('check_in_date', '<', $checkOutDate)
                           ->where('check_out_date', '>', $checkInDate);
                });
            })
            ->first();

        if ($existingReservation) {
            \Illuminate\Support\Facades\Log::warning('Reservation conflict detected', [
                'reservable_type' => $reservableType,
                'reservable_id' => $reservableId,
                'requested_check_in' => $checkInDate,
                'requested_check_out' => $checkOutDate,
                'existing_reservation_id' => $existingReservation->id,
                'existing_check_in' => $existingReservation->check_in_date,
                'existing_check_out' => $existingReservation->check_out_date,
                'existing_status' => $existingReservation->status,
                'existing_customer' => $existingReservation->customer_name
            ]);
        }

        return $existingReservation;
    }
}
