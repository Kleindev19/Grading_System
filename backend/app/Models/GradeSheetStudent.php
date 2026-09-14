<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSheetStudent extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'midterm',
        'finals',
        'final_grade',
        'grade_point',
        'remarks',
        'assessment_scores',
    ];

    protected function casts(): array
    {
        return ['assessment_scores' => 'array'];
    }

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class);
    }
}
