<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class Reservation extends Model
{
    use HasFactory, HasAppTimezone;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'first_name',
        'last_name',
        'nationality',
        'booking_source',
        'customer_phone',
        'customer_email',
        'user_name',
        'user_email',
        'booking_date',
        'reservable_id',
        'reservable_type',
        'property_id',
        // Multi-unit vacation homes only (nullable)
        'apartment_id',
        'apartment_quantity',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_price',
        'original_amount',
        'discount_percentage',
        'discount_amount',
        'status',
        'special_requests',
        'payment_status',
        'payment_method',
        'transaction_id',
        'review_url',
        'approval_status',
        'requires_approval',
        'booking_type',
        'refund_policy',
        'property_details',
        'reservable_data',
        'feedback_token',
        'feedback_email_sent_at',
        // Flexible booking fields
        'flexible_booking_discount',
        'is_flexible_booking',
        'property_owner_id',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_price' => 'float',
        'property_details' => 'array',
        'reservable_data' => 'array',
        'requires_approval' => 'boolean',
        'feedback_email_sent_at' => 'datetime',
    ];

    /**
     * Get the customer who made the reservation.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the reservable model (Property or HotelRoom).
     */
    public function reservable()
    {
        return $this->morphTo();
    }

    /**
     * Get the payment associated with this reservation.
     */
    public function payment()
    {
        return $this->hasOne(PaymobPayment::class);
    }

    /**
     * Get the property associated with this reservation.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope a query to only include active reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Check if the reservation dates overlap with another reservation.
     *
     * @param  string  $checkInDate
     * @param  string  $checkOutDate
     * @param  int  $reservableId
     * @param  string  $reservableType
     * @param  int|null  $excludeReservationId
     * @return bool
     */
    public static function datesOverlap($checkInDate, $checkOutDate, $reservableId, $reservableType, $excludeReservationId = null, $apartmentId = null)
    {
        $query = self::where('reservable_id', $reservableId)
            ->where(function($query) use ($reservableType) {
                // Handle both possible reservable_type values
                if ($reservableType === 'App\\Models\\HotelRoom') {
                    $query->where('reservable_type', 'App\\Models\\HotelRoom')
                          ->orWhere('reservable_type', 'hotel_room');
                } else {
                    $query->where('reservable_type', $reservableType);
                }
            })
            ->whereIn('status', ['confirmed', 'approved', 'pending']) // Match frontend logic
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                // Check if the dates overlap using hotel reservation logic
                // Check-in is INCLUSIVE, Check-out is EXCLUSIVE
                $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                    // Reservation starts during requested period
                    $q->where('check_in_date', '>=', $checkInDate)
                        ->where('check_in_date', '<', $checkOutDate);
                })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                    // Reservation ends during requested period (check-out day is available)
                    $q->where('check_out_date', '>', $checkInDate)
                        ->where('check_out_date', '<', $checkOutDate);
                })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                    // Reservation completely contains requested period (check-out day is available)
                    $q->where('check_in_date', '<=', $checkInDate)
                        ->where('check_out_date', '>', $checkOutDate);
                });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        if ($apartmentId) {
            $query->where('apartment_id', $apartmentId);
        }

        return $query->exists();
    }
}
