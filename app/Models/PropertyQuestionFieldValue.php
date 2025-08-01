<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAppTimezone;

class PropertyQuestionFieldValue extends Model
{
    use HasFactory, HasAppTimezone, SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'property_question_field_id',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the question field that owns the PropertyQuestionFieldValue
     */
    public function property_question_field()
    {
        return $this->belongsTo(PropertyQuestionField::class, 'property_question_field_id');
    }
}
