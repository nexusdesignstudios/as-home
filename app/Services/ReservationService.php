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
     * @return \App\Models\Reservation
     */
    public function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
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

                // Send reservation confirmation email
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

                if (empty($reservationApprovalTemplateData)) {
                    $reservationApprovalTemplateData = "Your reservation has been approved! Please complete your payment using the link below: <br><br><a href='{{payment_link}}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Complete Payment</a>";
                }
                $reservationApprovalTemplate = \App\Services\HelperService::replaceEmailVariables($reservationApprovalTemplateData, $variables);

                $data = array(
                    'email_template' => $reservationApprovalTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'] ?? 'Reservation Approved - Payment Required',
                );
                \App\Services\HelperService::sendMail($data);

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
     * Send reservation confirmation email.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    protected function sendReservationConfirmationEmail($reservation)
    {
        try {
            $customer = $reservation->customer;
            if ($customer && $customer->email) {
                // Get Data of email type
                $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes("reservation_confirmation");

                // Email Template
                $reservationConfirmationTemplateData = system_setting($emailTypeData['type']);
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

                if (empty($reservationConfirmationTemplateData)) {
                    $reservationConfirmationTemplateData = "Your reservation has been confirmed!";
                }
                $reservationConfirmationTemplate = \App\Services\HelperService::replaceEmailVariables($reservationConfirmationTemplateData, $variables);

                $data = array(
                    'email_template' => $reservationConfirmationTemplate,
                    'email' => $customer->email,
                    'title' => $emailTypeData['title'],
                );
                \App\Services\HelperService::sendMail($data);

                \Illuminate\Support\Facades\Log::info('Reservation confirmation email sent successfully via admin confirmation', [
                    'reservation_id' => $reservation->id,
                    'customer_email' => $customer->email
                ]);
            }
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
     * @return bool
     */
    public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate)
    {
        // Parse the check-in and check-out dates
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);
        $today = Carbon::today();

        // Check for 31st day restriction
        if ($checkIn->format('d') == '31' || $checkOut->format('d') == '31') {
            return false;
        }

        // Check for past dates
        if ($checkIn->lt($today) || $checkOut->lt($today)) {
            return false;
        }

        // Check if there are any overlapping reservations
        $hasOverlap = Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType);
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

        // If there are no available dates defined, assume it's not available
        if (empty($availableDates) || !is_array($availableDates)) {
            return false;
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
        // For each date range, check if our requested dates are covered
        foreach ($availableDates as $dateInfo) {
            // Skip if this isn't a date range format or is reserved
            if (
                !isset($dateInfo['from']) || !isset($dateInfo['to']) ||
                (isset($dateInfo['type']) && $dateInfo['type'] === 'reserved')
            ) {
                continue;
            }

            $fromDate = Carbon::parse($dateInfo['from']);
            $toDate = Carbon::parse($dateInfo['to']);

            // Check if this range fully contains our requested dates
            if ($fromDate->lte($checkIn) && $toDate->gte($checkOut->copy()->subDay())) {
                return true;
            }
        }

        return false;
    }
}
