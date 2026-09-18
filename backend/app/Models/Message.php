<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'student_id', 'professor_id', 'grade_sheet_id', 'sender_id', 'body', 'attachment_path', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class);
    }
}
