<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class PaymobPayment extends Model
{
    use HasFactory, HasAppTimezone;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'transaction_id',
        'paymob_order_id',
        'paymob_transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_data',
        'refund_data',
        'reservation_id',
        'reservable_type',
        'reservable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the customer that owns the payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the reservation associated with the payment.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the reservable model (Property or HotelRoom).
     */
    public function reservable()
    {
        return $this->morphTo();
    }
}
