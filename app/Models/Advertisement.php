<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = ['status','is_enable','for'];

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id','customer_id');
    }
    public function property()
    {
        return $this->hasOne(Property::class, 'id', 'property_id');
    }
    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }
    protected $casts = [
        'status' => 'integer'
    ];
}


