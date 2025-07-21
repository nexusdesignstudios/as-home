<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAppTimezone;

class PropertyCertificate extends Model
{
    use HasFactory, HasAppTimezone;

    protected $fillable = [
        'property_id',
        'title',
        'description',
        'file'
    ];

    /**
     * Get the property that owns the certificate.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the file attribute with URL.
     *
     * @param  string  $value
     * @return string
     */
    public function getFileAttribute($value)
    {
        return $value ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_CERTIFICATE_PATH') . $value : '';
    }

    /**
     * Set the file attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setFileAttribute($value)
    {
        $this->attributes['file'] = $value;
    }
}
