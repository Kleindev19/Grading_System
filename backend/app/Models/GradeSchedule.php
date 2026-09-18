<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSchedule extends Model
{
    protected $fillable = [
        'school_year', 'semester', 'year_level', 'courses', 'released_date',
        'status', 'midterm_opens', 'midterm_deadline', 'finals_opens', 'finals_deadline', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'courses' => 'array',
            'released_date' => 'date',
            'midterm_opens' => 'date',
            'midterm_deadline' => 'date',
            'finals_opens' => 'date',
            'finals_deadline' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}