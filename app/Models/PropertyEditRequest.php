<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyEditRequest extends Model
{
    use HasFactory;

    protected $table = 'property_edit_requests';

    protected $fillable = [
        'property_id',
        'requested_by',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
        'edited_data',
        'original_data',
    ];

    protected $casts = [
        'edited_data' => 'array',
        'original_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the property that this edit request belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the customer who requested the edit.
     */
    public function requestedBy()
    {
        return $this->belongsTo(Customer::class, 'requested_by');
    }

    /**
     * Get the admin user who reviewed the edit.
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

