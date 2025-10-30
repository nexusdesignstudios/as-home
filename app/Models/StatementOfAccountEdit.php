<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Property;

class StatementOfAccountEdit extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'property_id',
        'description',
        'credit_amount',
        'edited_by',
    ];

    protected $casts = [
        'credit_amount' => 'decimal:2',
    ];

    /**
     * Get the reservation that owns this edit.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the property that owns this edit.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the user who made this edit.
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}

