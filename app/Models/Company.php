<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_legal_name',
        'manager_name',
        'type_of_company',
        'email_address',
        'bank_branch',
        'bank_address',
        'country',
        'bank_account_number',
        'iban',
        'swift_code'
    ];

    /**
     * Get the customers associated with this company.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'company_id');
    }
}
