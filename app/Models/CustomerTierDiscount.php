<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTierDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reservable_type',
        'tier_milestone',
        'used',
        'reservation_id',
        'used_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Get the customer that owns this tier discount.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the reservation that used this discount.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}

