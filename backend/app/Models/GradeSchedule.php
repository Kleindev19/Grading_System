<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSchedule extends Model
{
    protected $fillable = [
        'school_year', 'semester', 'year_level', 'courses', 'released_date',
        'status', 'midterm_opens', 'finals_opens', 'submit_grade_deadline',
        'midterm_locked', 'finals_locked', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'courses' => 'array',
            'released_date' => 'date',
            'midterm_opens' => 'date',
            'finals_opens' => 'date',
            'submit_grade_deadline' => 'date',
            'midterm_locked' => 'boolean',
            'finals_locked' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}