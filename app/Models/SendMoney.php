<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class SendMoney extends Model
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
        'amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'recipient_customer_id',
        'notes',
        'payment_data',
        'paymob_order_id',
        'paymob_transaction_id',
        'transaction_data',
        'refund_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'payment_data' => 'array',
        'transaction_data' => 'array',
        'refund_data' => 'array',
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
     * Get the customer that owns the send money transaction.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the recipient customer.
     */
    public function recipient()
    {
        return $this->belongsTo(Customer::class, 'recipient_customer_id');
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if the transaction is successful.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->status === 'completed' && $this->payment_status === 'paid';
    }

    /**
     * Check if the transaction is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending' || $this->status === 'processing';
    }

    /**
     * Check if the transaction can be refunded.
     *
     * @return bool
     */
    public function canBeRefunded()
    {
        return $this->isSuccessful() && $this->payment_status === 'paid';
    }
}
