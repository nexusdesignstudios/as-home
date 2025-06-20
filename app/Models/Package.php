<?php

namespace App\Models;

use App\Services\HelperService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory,SoftDeletes;
    protected $hidden = array('created_at','updated_at','deleted_at');
    protected $fillable = array(
        'id',
        'name',
        'ios_product_id',
        'package_type',
        'price',
        'duration',
        'status'
    );

    /** Relations */
    /**
     * Get all of the features for the Package
     *
     */
    public function package_features()
    {
        return $this->hasMany(PackageFeature::class, 'package_id', 'id')->with('feature');
    }

    public function user_packages(){
        return $this->hasMany(UserPackage::class, 'package_id', 'id');
    }


    public function getIsActiveAttribute(){
        $userId = Auth::guard('sanctum')->user()->id;
        $packageId = $this->id;

        $getActivePackages = HelperService::getActivePackage($userId,$packageId);
        if(collect($getActivePackages)->isNotEmpty()){
            return true;
        }
        return false;
    }
}
