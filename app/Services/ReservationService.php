<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
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
            ]);

            // Only update available dates if the reservation is confirmed
            // Pending reservations should not block other bookings
            if (($data['status'] ?? 'pending') === 'confirmed') {
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
        } elseif ($modelType === 'App\\Models\\HotelRoom') {
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
    public function handleReservationConfirmation($reservation, $paymentStatus = 'paid')
    {
        try {
            // Update reservation status and payment status
            $reservation->status = 'confirmed';
            $reservation->payment_status = $paymentStatus;
            $reservation->save();

            \Illuminate\Support\Facades\Log::info('Reservation status updated via admin confirmation', [
                'reservation_id' => $reservation->id,
                'status' => $reservation->status,
                'payment_status' => $reservation->payment_status
            ]);

            // Update available dates
            try {
                $this->updateAvailableDates(
                    $reservation->reservable_type,
                    $reservation->reservable_id,
                    $reservation->check_in_date,
                    $reservation->check_out_date,
                    $reservation->id
                );

                \Illuminate\Support\Facades\Log::info('Available dates updated successfully via admin confirmation', [
                    'reservation_id' => $reservation->id
                ]);

                // Send payment completion email to property owner
                $this->sendPaymentCompletionEmailToOwner($reservation);
                
                // Send reservation confirmation email to customer
                $this->sendReservationConfirmationEmail($reservation);
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
                    'number_of_guests' => $reservation->number_of_guests,
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
                \App\Services\HelperService::sendMail($data);

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
                    'number_of_guests' => $reservation->number_of_guests,
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
                $roomType = $hotelRoom->room_type->name ?? 'Standard Room';
            }

            if (!$property) {
                \Illuminate\Support\Facades\Log::warning('Cannot send reservation confirmation email: property not found', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Get email template data
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_confirmation");
            $reservationConfirmationTemplateData = system_setting('reservation_confirmation_mail_template');
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
     * Check if dates are available for reservation.
     *
     * @param string $modelType
     * @param int $modelId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $excludeReservationId Optional reservation ID to exclude from overlap check
     * @return bool
     */
    public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate, $excludeReservationId = null)
    {
        // Parse the check-in and check-out dates
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);
        $today = Carbon::today();

        // Check for past dates
        if ($checkIn->lt($today) || $checkOut->lt($today)) {
            return false;
        }

        // Check if there are any overlapping CONFIRMED reservations
        // Only confirmed reservations should block availability
        $hasOverlap = Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType, $excludeReservationId);
        if ($hasOverlap) {
            return false;
        }

        // Get the model instance
        $model = $this->getModelInstance($modelType, $modelId);
        if (!$model) {
            return false;
        }

        // Get available dates
        $availableDates = $model->available_dates ?? [];

        // If there are no available dates defined, allow booking
        // This allows booking even when room availability is not properly configured
        if (empty($availableDates) || !is_array($availableDates)) {
            return true;
        }

        // Handle different availability types (for HotelRoom model)
        if ($modelType === 'App\\Models\\HotelRoom' && isset($model->availability_type)) {
            $availabilityType = $model->availability_type;

            // If availability_type is "busy_days", then dates NOT in the array are available
            if ($availabilityType === 'busy_days') {
                // Check if any of the requested dates are in the busy dates
                $requestedDates = $this->generateDateRange($checkInDate, $checkOutDate);

                foreach ($requestedDates as $date) {
                    // Check if this date is within any busy date range
                    foreach ($availableDates as $dateInfo) {
                        if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                            continue;
                        }

                        // Skip if this is a reserved date range for the excluded reservation
                        if ($excludeReservationId && isset($dateInfo['reservation_id']) && $dateInfo['reservation_id'] == $excludeReservationId) {
                            continue;
                        }

                        $fromDate = Carbon::parse($dateInfo['from']);
                        $toDate = Carbon::parse($dateInfo['to']);
                        $currentDate = Carbon::parse($date);

                        // If the date is within a busy range, it's not available
                        if ($currentDate->gte($fromDate) && $currentDate->lte($toDate)) {
                            return false;
                        }
                    }
                }

                // If we got here, none of the requested dates are in busy ranges
                return true;
            }
        }

        // Default behavior for "available_days" or other models
        // Check if any of the requested dates overlap with reserved dates
        $requestedDates = $this->generateDateRange($checkInDate, $checkOutDate);
        
        foreach ($requestedDates as $date) {
            $currentDate = Carbon::parse($date);
            
            foreach ($availableDates as $dateInfo) {
                if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                    continue;
                }

                // Skip if this is a reserved date range for the excluded reservation
                if ($excludeReservationId && isset($dateInfo['reservation_id']) && $dateInfo['reservation_id'] == $excludeReservationId) {
                    continue;
                }

                // If this date range is marked as reserved, check if our date falls within it
                if (isset($dateInfo['type']) && $dateInfo['type'] === 'reserved') {
                    $fromDate = Carbon::parse($dateInfo['from']);
                    $toDate = Carbon::parse($dateInfo['to']);
                    
                    // If the date falls within a reserved range, it's not available
                    if ($currentDate->gte($fromDate) && $currentDate->lte($toDate)) {
                        // But only if there's a confirmed reservation for it
                        // Check if there's actually a confirmed reservation for this date range
                        if (isset($dateInfo['reservation_id'])) {
                            $reservation = Reservation::find($dateInfo['reservation_id']);
                            if ($reservation && $reservation->status === 'confirmed') {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        // For "available_days" type, check if the requested dates are within any available (open) date range
        foreach ($availableDates as $dateInfo) {
            // Skip if this isn't a date range format
            if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                continue;
            }

            // Skip reserved dates (we already checked those above)
            if (isset($dateInfo['type']) && $dateInfo['type'] === 'reserved') {
                continue;
            }

            $fromDate = Carbon::parse($dateInfo['from']);
            $toDate = Carbon::parse($dateInfo['to']);

            // Check if this range fully contains our requested dates
            if ($fromDate->lte($checkIn) && $toDate->gte($checkOut->copy()->subDay())) {
                return true;
            }
        }

        // If no available date ranges cover the requested dates, 
        // but there are no confirmed reservations blocking it, allow booking
        // This is more permissive to handle cases where availability isn't fully configured
        return true;
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
     * Send flexible hotel booking approval email to customer.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendFlexibleHotelBookingApprovalEmail($reservation)
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("flexible_hotel_booking_approval");

                // Email Template
                $emailTemplateData = system_setting('flexible_hotel_booking_approval_mail_template');
                $appName = env("APP_NAME") ?? "As Home";

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
                    'property_name' => $hotelName,
                    'hotel_name' => $hotelName, // Keep both for compatibility
                    'reservation_id' => $reservation->id,
                    'room_type' => $roomType,
                    'room_number' => $roomNumber,
                    'hotel_address' => $hotelAddress,
                    'property_address' => $hotelAddress, // Add property_address for template compatibility
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
                    $emailTemplateData = "Dear {customer_name},\n\nYour reservation request for {property_name} has been received and is now pending the property owner's approval.\n\nYou'll receive a confirmation email once your booking is approved.\n\nThank you for choosing {app_name}!\n\nBest regards,\n{app_name} Team";
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
}
