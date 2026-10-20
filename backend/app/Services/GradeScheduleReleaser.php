<?php

namespace App\Services;

use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use Illuminate\Support\Facades\DB;

class GradeScheduleReleaser
{
    public function releaseDueSchedules(): void
    {
        GradeSchedule::query()
            ->where('status', 'Scheduled')
            ->whereNotNull('released_date')
            ->whereDate('released_date', '<=', today())
            ->where('year_level', '!=', 'Grade Period')
            ->orderBy('id')
            ->each(fn (GradeSchedule $schedule) => $this->release($schedule));
    }

    public function release(GradeSchedule $schedule): GradeSchedule
    {
        DB::transaction(function () use ($schedule): void {
            $lockedSchedule = GradeSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($lockedSchedule->status !== 'Scheduled') {
                return;
            }

            $lockedSchedule->forceFill([
                'status' => 'Released',
                'released_date' => $lockedSchedule->released_date ?: today(),
            ])->save();

            $sheetIds = collect($lockedSchedule->courses ?? [])
                ->filter(fn (string $course): bool => str_starts_with($course, 'sheet:'))
                ->map(fn (string $course): int => (int) str_replace('sheet:', '', $course))
                ->filter()
                ->values();

            if ($sheetIds->isNotEmpty()) {
                $this->completeApprovedSheets(GradeSheet::query())
                    ->whereIn('id', $sheetIds)
                    ->update(['status' => 'Published']);

                return;
            }

            $courseCodes = collect($lockedSchedule->courses ?? [])
                ->filter(fn (string $course): bool => !str_starts_with($course, 'sheet:'))
                ->values();

            if ($courseCodes->isNotEmpty()) {
                $semesterPrefix = str_ireplace('semester', 'sem', $lockedSchedule->semester);
                $this->completeApprovedSheets(GradeSheet::query())
                    ->where(function ($query) use ($lockedSchedule, $semesterPrefix) {
                        $query->where('semester', $lockedSchedule->semester)
                            ->orWhere('semester', 'like', $lockedSchedule->semester . '%')
                            ->orWhere('semester', 'like', $semesterPrefix . '%');
                    })
                    ->whereIn('code', $courseCodes)
                    ->update(['status' => 'Published']);
            }
        });

        return $schedule->fresh();
    }

    private function completeApprovedSheets($query)
    {
        return $query
            ->where('status', 'Approved')
            ->whereHas('studentsList')
            ->whereDoesntHave('studentsList', function ($studentQuery): void {
                $studentQuery->whereNull('midterm')->orWhere('midterm', '')
                    ->orWhereNull('finals')->orWhere('finals', '')
                    ->orWhereNull('final_grade')->orWhere('final_grade', '')
                    ->orWhereNull('grade_point')->orWhere('grade_point', '');
            });
    }
}
