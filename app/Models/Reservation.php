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
        'reservable_id',
        'reservable_type',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_price',
        'status',
        'special_requests',
        'payment_status',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_price' => 'float',
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
    public static function datesOverlap($checkInDate, $checkOutDate, $reservableId, $reservableType, $excludeReservationId = null)
    {
        $query = self::where('reservable_id', $reservableId)
            ->where('reservable_type', $reservableType)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                // Check if the dates overlap
                $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_in_date', '>=', $checkInDate)
                        ->where('check_in_date', '<', $checkOutDate);
                })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_out_date', '>', $checkInDate)
                        ->where('check_out_date', '<=', $checkOutDate);
                })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_in_date', '<=', $checkInDate)
                        ->where('check_out_date', '>=', $checkOutDate);
                });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }
}
