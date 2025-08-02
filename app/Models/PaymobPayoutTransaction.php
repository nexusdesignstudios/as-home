<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class PaymobPayoutTransaction extends Model
{
    use HasFactory, HasAppTimezone;

    protected $fillable = [
        'customer_id',
        'property_id',
        'transaction_id',
        'issuer',
        'amount',
        'original_amount',
        'commission_percentage',
        'msisdn',
        'full_name',
        'first_name',
        'last_name',
        'email',
        'bank_card_number',
        'bank_transaction_type',
        'bank_code',
        'client_reference_id',
        'disbursement_status',
        'status_code',
        'status_description',
        'reference_number',
        'paid',
        'aman_cashing_details',
        'transaction_data',
        'notes',
        'payout_month',
        'payout_year',
        'is_processed',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'amount' => 'float',
        'original_amount' => 'float',
        'commission_percentage' => 'float',
        'paid' => 'boolean',
        'is_processed' => 'boolean',
        'aman_cashing_details' => 'array',
        'transaction_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the customer that owns the payout transaction
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the property associated with this payout transaction
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('disbursement_status', $status);
    }

    /**
     * Scope to filter by issuer
     */
    public function scopeByIssuer($query, $issuer)
    {
        return $query->where('issuer', $issuer);
    }

    /**
     * Scope to filter successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('disbursement_status', 'success')
            ->orWhere('disbursement_status', 'successful');
    }

    /**
     * Scope to filter failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('disbursement_status', 'failed');
    }

    /**
     * Scope to filter pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('disbursement_status', 'pending');
    }

    /**
     * Get the full name (first_name + last_name or full_name)
     */
    public function getFullNameAttribute()
    {
        if ($this->full_name) {
            return $this->full_name;
        }

        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }

        return null;
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful()
    {
        return in_array($this->disbursement_status, ['success', 'successful']);
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed()
    {
        return $this->disbursement_status === 'failed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->disbursement_status === 'pending';
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' EGP';
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->disbursement_status) {
            case 'success':
            case 'successful':
                return 'badge-success';
            case 'failed':
                return 'badge-danger';
            case 'pending':
                return 'badge-warning';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Calculate commission based on property classification and rent package
     *
     * @param Property $property
     * @param float $amount
     * @return array [commission_percentage, amount_after_commission]
     */
    public static function calculateCommission($property, $amount)
    {
        $commissionPercentage = 0;
        $classification = $property->getRawOriginal('property_classification');
        $rentPackage = $property->getRawOriginal('rent_package') ?: 'basic';

        if ($classification == 4) { // vacation homes
            if ($rentPackage == 'basic') {
                $commissionPercentage = 14.99;
            } elseif ($rentPackage == 'premium') {
                $commissionPercentage = 24.99;
            } else {
                $commissionPercentage = 14.99; // Default to basic rate
            }
        } 

        $commissionAmount = ($amount * $commissionPercentage) / 100;
        $amountAfterCommission = $amount - $commissionAmount;

        return [
            'commission_percentage' => $commissionPercentage,
            'amount_after_commission' => $amountAfterCommission
        ];
    }

    /**
     * Scope a query to filter by month and year
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('payout_month', $month)
            ->where('payout_year', $year);
    }

    /**
     * Scope a query to filter by processing status
     */
    public function scopeProcessed($query, $processed = true)
    {
        return $query->where('is_processed', $processed);
    }
}
