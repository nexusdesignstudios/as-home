<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAppTimezone;

class PropertyQuestionField extends Model
{
    use HasFactory, HasAppTimezone, SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'name',
        'field_type',
        'property_classification',
        'rank',
        'status'
    ];

    /**
     * Get all of the field values for the PropertyQuestionField
     */
    public function field_values()
    {
        return $this->hasMany(PropertyQuestionFieldValue::class, 'property_question_field_id', 'id');
    }

    /**
     * Get all of the property answers for the PropertyQuestionField
     */
    public function property_answers()
    {
        return $this->hasMany(PropertyQuestionAnswer::class, 'property_question_field_id', 'id');
    }
}
