<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_of_company',
        'bank_branch',
        'bank_address',
        'country',
        'bank_account_number',
        'iban',
        'swift_code'
    ];

    /**
     * Get the customers that own this bank detail.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'bankDetails_id');
    }
}
