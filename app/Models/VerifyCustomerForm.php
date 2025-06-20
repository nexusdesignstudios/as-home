<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyCustomerForm extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'field_type',
        'rank'
    ];


    /**
     * Get all of the form_fields_values for the VerifyCustomerForm
     */
    public function form_fields_values()
    {
        return $this->hasMany(VerifyCustomerFormValue::class, 'verify_customer_form_id', 'id');
    }
}
