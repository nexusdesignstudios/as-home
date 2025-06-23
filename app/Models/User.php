<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'profile',
        'email',
        'password',
        'type', // 'agent' or 'property_owner'
        'agent_type', // 'individual' or 'company'
        'management_type', // 'self' or 'as_home'
        'permissions',
        'slug_id',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'agent_type' => 'not_determined',
        'management_type' => 'not_determined',
        'status' => 1, // Default to active
    ];

    /**
     * Check if the user is active.
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->status == 1) {
            return true;
        }
        return false;
    }

    /**
     * Check if the user is an agent.
     *
     * @return bool
     */
    public function isAgent()
    {
        return $this->type == 1;
    }

    /**
     * Check if the user is a property owner.
     *
     * @return bool
     */
    public function isPropertyOwner()
    {
        return $this->type == 2;
    }

    /**
     * Get the profile attribute.
     *
     * @param string|null $image
     * @return string
     */
    public function getProfileAttribute($image)
    {
        // Check if $image is a valid URL
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image; // If $image is already a URL, return it as it is
        } else {
            // If $image is not a URL, construct the URL using configurations
            return $image != '' ? url('') . config('global.IMG_PATH') . config('global.ADMIN_PROFILE_IMG_PATH') . $image : '';
        }
    }
}
