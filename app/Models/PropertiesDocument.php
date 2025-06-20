<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertiesDocument extends Model
{
    use HasFactory;

    protected $table ='properties_documents';

    protected $fillable = [
        'property_id',
        'name',
        'type'
    ];

    public function getNameAttribute($name)
    {
        return $name != '' ? url('') . config('global.IMG_PATH') . config('global.PROPERTY_DOCUMENT_PATH'). $this->property_id . "/" . $name : '';
    }

}
