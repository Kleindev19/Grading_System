<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeCriteria extends Model
{
    protected $table = 'grade_criteria';

    protected $fillable = [
        'professor_id',
        'code',
        'section',
        'semester',
        'criteria',
        'assessment_headers',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'assessment_headers' => 'array',
        ];
    }
}
