<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAppTimezone;

class PropertyQuestionAnswer extends Model
{
    use HasFactory, HasAppTimezone, SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'property_id',
        'customer_id',
        'reservation_id',
        'property_question_field_id',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the property that owns this answer
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the question field that owns this answer
     */
    public function property_question_field()
    {
        return $this->belongsTo(PropertyQuestionField::class, 'property_question_field_id');
    }
}
