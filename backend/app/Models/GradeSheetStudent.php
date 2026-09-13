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
    ];

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class);
    }
}
