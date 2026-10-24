<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeDraft extends Model
{
    protected $fillable = [
        'professor_id',
        'code',
        'section',
        'semester',
        'subject',
        'units',
        'assessment_headers',
        'student_records',
    ];

    protected function casts(): array
    {
        return [
            'assessment_headers' => 'array',
            'student_records' => 'array',
        ];
    }
}
