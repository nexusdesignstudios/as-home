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
        'refund_status',
        'refund_reason',
        'requires_approval',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'refund_amount',
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
        'refund_amount' => 'float',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
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

    /**
     * Get the user who approved the refund.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if refund requires approval.
     *
     * @return bool
     */
    public function requiresApproval()
    {
        return $this->requires_approval;
    }

    /**
     * Check if refund is pending approval.
     *
     * @return bool
     */
    public function isPendingApproval()
    {
        return $this->refund_status === 'pending' && $this->requires_approval;
    }

    /**
     * Check if refund has been approved.
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->refund_status === 'approved';
    }

    /**
     * Check if refund has been rejected.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->refund_status === 'rejected';
    }

    /**
     * Scope a query to only include payments that require refund approval.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true)
                     ->where('refund_status', 'pending');
    }

    /**
     * Scope a query to only include payments with a specific refund status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRefundStatus($query, $status)
    {
        return $query->where('refund_status', $status);
    }
}
