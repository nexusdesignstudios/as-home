<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class PaymentFormSubmission extends Model
{
    use HasFactory, HasAppTimezone;

    protected $fillable = [
        'property_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'card_number_masked',
        'expiry_date',
        'cvv_masked',
        'amount',
        'currency',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'special_requests',
        'reservable_type',
        'reservable_data',
        'review_url',
        'status',
        'notes'
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'amount' => 'decimal:2',
        'reservable_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = ['created_at', 'updated_at', 'check_in_date', 'check_out_date'];

    /**
     * Get the property that owns the payment form submission.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the property owner (customer) through the property relationship.
     */
    public function propertyOwner()
    {
        return $this->hasOneThrough(
            Customer::class,
            Property::class,
            'id', // Foreign key on properties table
            'id', // Foreign key on customers table
            'property_id', // Local key on payment_form_submissions table
            'added_by' // Local key on properties table
        );
    }

    /**
     * Scope a query to only include pending submissions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include processed submissions.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope a query to only include failed submissions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get the masked card number for display.
     */
    public function getMaskedCardNumberAttribute()
    {
        return $this->card_number_masked;
    }

    /**
     * Get the masked CVV for display.
     */
    public function getMaskedCvvAttribute()
    {
        return $this->cvv_masked;
    }

    /**
     * Get the formatted amount with currency.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get the duration of stay in nights.
     */
    public function getStayDurationAttribute()
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }
}
