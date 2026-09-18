<?php

namespace App\Http\Controllers;

use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->attributes->get('auth_user')->role, ['registrar', 'professor', 'student'], true), 403);

        return response()->json(['schedules' => GradeSchedule::latest()->get()->map(fn (GradeSchedule $schedule) => $this->serialize($schedule))]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can create schedules.');

        $data = $request->validate([
            'schoolYear' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:40'],
            'yearLevel' => ['required', 'string', 'max:40'],
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['required', 'string', 'max:255'],
            'releasedDate' => ['nullable', 'date'],
        ]);

        $schedule = GradeSchedule::create([
            'school_year' => $data['schoolYear'],
            'semester' => $data['semester'],
            'year_level' => $data['yearLevel'],
            'courses' => $data['courses'],
            'released_date' => $data['releasedDate'] ?? null,
            'status' => 'Scheduled',
            'created_by' => $user->id,
        ]);

        return response()->json(['schedule' => $this->serialize($schedule)], 201);
    }

    public function release(Request $request, GradeSchedule $schedule): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can release schedules.');
        abort_if($schedule->released_date?->isFuture(), 422, 'This release is scheduled for a future date.');

        $schedule->forceFill(['status' => 'Released', 'released_date' => $schedule->released_date ?: now()->toDateString()])->save();

        $sheetIds = collect($schedule->courses ?? [])
            ->filter(fn (string $course): bool => str_starts_with($course, 'sheet:'))
            ->map(fn (string $course): int => (int) str_replace('sheet:', '', $course))
            ->filter()
            ->values();

        if ($sheetIds->isNotEmpty()) {
            $this->completeApprovedSheets(GradeSheet::query())
                ->whereIn('id', $sheetIds)
                ->update(['status' => 'Published']);
        } else {
            $semester = str_ireplace('semester', 'sem', $schedule->semester);

            $this->completeApprovedSheets(GradeSheet::query())
                ->where(function ($query) use ($schedule, $semester) {
                    $query->where('semester', $schedule->semester)
                        ->orWhere('semester', 'like', $semester . '%');
                })
                ->update(['status' => 'Published']);
        }

        return response()->json(['schedule' => $this->serialize($schedule->fresh())]);
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

    public function storePeriod(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can create grade periods.');

        $data = $request->validate([
            'schoolYear' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:40'],
            'midtermOpens' => ['nullable', 'date'],
            'midtermDeadline' => ['nullable', 'date', 'after_or_equal:midtermOpens'],
            'finalsOpens' => ['nullable', 'date'],
            'finalsDeadline' => ['nullable', 'date', 'after_or_equal:finalsOpens'],
        ]);

        $schedule = GradeSchedule::create([
            'school_year' => $data['schoolYear'],
            'semester' => $data['semester'],
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => $data['midtermOpens'] ?? null,
            'midterm_deadline' => $data['midtermDeadline'] ?? null,
            'finals_opens' => $data['finalsOpens'] ?? null,
            'finals_deadline' => $data['finalsDeadline'] ?? null,
            'status' => 'Released',
            'created_by' => $user->id,
        ]);

        return response()->json(['schedule' => $this->serialize($schedule)], 201);
    }

    public function updatePeriod(Request $request, GradeSchedule $schedule): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can update grade periods.');
        abort_unless($schedule->year_level === 'Grade Period', 404);

        $data = $request->validate([
            'schoolYear' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:40'],
            'midtermOpens' => ['nullable', 'date'],
            'midtermDeadline' => ['nullable', 'date', 'after_or_equal:midtermOpens'],
            'finalsOpens' => ['nullable', 'date'],
            'finalsDeadline' => ['nullable', 'date', 'after_or_equal:finalsOpens'],
        ]);

        $schedule->update([
            'school_year' => $data['schoolYear'],
            'semester' => $data['semester'],
            'midterm_opens' => $data['midtermOpens'] ?? null,
            'midterm_deadline' => $data['midtermDeadline'] ?? null,
            'finals_opens' => $data['finalsOpens'] ?? null,
            'finals_deadline' => $data['finalsDeadline'] ?? null,
        ]);

        return response()->json(['schedule' => $this->serialize($schedule->fresh())]);
    }

    public function destroyPeriod(Request $request, GradeSchedule $schedule): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can delete grade periods.');
        abort_unless($schedule->year_level === 'Grade Period', 404);
        $schedule->delete();

        return response()->json(['message' => 'Grade period removed.']);
    }

    private function serialize(GradeSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'schoolYear' => $schedule->school_year,
            'semester' => $schedule->semester,
            'yearLevel' => $schedule->year_level,
            'courses' => $schedule->courses ?? [],
            'releasedDate' => $schedule->released_date?->toDateString(),
            'status' => $schedule->status,
            'midtermOpens' => $schedule->midterm_opens?->toDateString(),
            'midtermDeadline' => $schedule->midterm_deadline?->toDateString(),
            'finalsOpens' => $schedule->finals_opens?->toDateString(),
            'finalsDeadline' => $schedule->finals_deadline?->toDateString(),
        ];
    }
}