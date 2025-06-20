<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedChatUser extends Model
{
    use HasFactory;
    protected $fillable = array(
        'by_user_id',
        'by_admin',
        'user_id',
        'admin',
        'reason'
    );
}
