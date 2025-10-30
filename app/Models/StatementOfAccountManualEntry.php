<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementOfAccountManualEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'date',
        'reference',
        'description',
        'debit_amount',
        'credit_amount',
        'comments',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
