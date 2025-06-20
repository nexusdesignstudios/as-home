<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityImage extends Model
{
    use HasFactory;
    protected $table ='city_images';

    protected $fillable = [
        'city',
        'image',
        'status'
    ];

    public function getImageAttribute($image)
    {
        if($image){
            return $image != '' ? url('') . config('global.IMG_PATH') . config('global.CITY_IMAGE_PATH'). $image : '';
        }
        return null;
    }

    public function property(){
        return $this->hasMany(Property::class,'city','city');
    }
}
