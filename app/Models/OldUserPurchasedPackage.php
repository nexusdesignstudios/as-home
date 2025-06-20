<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldUserPurchasedPackage extends Model
{
    use HasFactory;
    protected $table = 'old_user_purchased_packages';
    public function modal()
    {
        return $this->morphTo();
    }
    public function package()
    {
        return $this->belongsTo(OldPackage::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'modal_id');
    }
}
