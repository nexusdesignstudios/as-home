<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationChangeRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'reservation_id',
        'requested_check_in',
        'requested_check_out',
        'requested_total_price',
        'old_check_in',
        'old_check_out',
        'old_total_price',
        'status',
        'requester_id',
        'requester_type',
        'reason',
        'payment_transaction_id',
        'handheld_at'
    ];

    protected $casts = [
        'requested_check_in' => 'date',
        'requested_check_out' => 'date',
        'old_check_in' => 'date',
        'old_check_out' => 'date',
        'requested_total_price' => 'decimal:2',
        'old_total_price' => 'decimal:2',
        'handheld_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
