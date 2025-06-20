<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    protected $hidden = [
        'updated_at',
    ];
    protected $fillable = array(
        'email',
        'token',
        'expires_at'
    );
}
