<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeSheet extends Model
{
    protected $fillable = [
        'code',
        'subject',
        'section',
        'semester',
        'students',
        'units',
        'submitted',
        'status',
        'professor_id',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function studentsList(): HasMany
    {
        return $this->hasMany(GradeSheetStudent::class);
    }
}
