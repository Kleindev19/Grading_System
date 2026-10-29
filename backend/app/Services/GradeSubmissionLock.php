<?php

namespace App\Services;

use App\Models\GradeSchedule;

class GradeSubmissionLock
{
    public static function findForSemester(string $semester): ?GradeSchedule
    {
        if (!preg_match('/(1st|2nd)\s+semester/i', $semester, $semesterMatch)) {
            return null;
        }

        $schoolYear = self::normalizeSchoolYear($semester);
        if (!$schoolYear) {
            return null;
        }

        $semesterName = strtolower($semesterMatch[1] . ' semester');

        return GradeSchedule::query()
            ->where('year_level', 'Grade Period')
            ->get()
            ->first(fn (GradeSchedule $schedule): bool => strtolower(trim($schedule->semester)) === $semesterName
                && self::normalizeSchoolYear($schedule->school_year) === $schoolYear);
    }

    public static function normalizeSchoolYear(string $value): ?string
    {
        if (!preg_match('/(\d{4})\s*[-\/]\s*(\d{2,4})/', $value, $matches)) {
            return null;
        }

        $endYear = strlen($matches[2]) === 2
            ? substr($matches[1], 0, 2) . $matches[2]
            : $matches[2];

        return $matches[1] . '-' . $endYear;
    }

    public static function ensureUnlocked(string $semester): void
    {
        abort_if(
            self::findForSemester($semester)?->submit_grade_locked,
            422,
            'The Registrar has locked grade submission for this semester.',
        );
    }
}
