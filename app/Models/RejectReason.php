<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectReason extends Model
{
    use HasFactory;
    protected $fillable = array(
        'property_id',
        'project_id',
        'reason',
    );
}
