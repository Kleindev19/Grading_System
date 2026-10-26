<?php

namespace App\Http\Controllers;

use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use App\Services\GradeScheduleReleaser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(in_array($user->role, ['registrar', 'professor', 'student'], true), 403);

        app(GradeScheduleReleaser::class)->releaseDueSchedules();
        $schedules = GradeSchedule::latest()->get();
        if ($user->role === 'student') {
            $studentIds = collect([$user->student_id, $user->username])->filter()->unique()->values();
            $gradePeriods = $schedules->filter(fn (GradeSchedule $schedule): bool => $schedule->year_level === 'Grade Period');
            $courseSchedules = $schedules->filter(fn (GradeSchedule $schedule): bool => $schedule->year_level !== 'Grade Period')
                ->map(function (GradeSchedule $schedule) use ($studentIds): GradeSchedule {
                    $courses = collect($schedule->courses ?? [])->filter(function (string $course) use ($studentIds): bool {
                        $sheetId = str_starts_with($course, 'sheet:') ? (int) substr($course, 6) : null;
                        $query = GradeSheet::query();
                        if ($sheetId) {
                            $query->whereKey($sheetId);
                        } else {
                            $query->where('code', $course);
                        }

                        return $query->whereHas('studentsList', fn ($students) => $students->whereIn('student_id', $studentIds))->exists();
                    })->values();

                    $schedule->setAttribute('courses', $courses->all());

                    return $schedule;
                })
                ->filter(fn (GradeSchedule $schedule): bool => count($schedule->courses ?? []) > 0)
                ->values();
            $schedules = $courseSchedules->concat($gradePeriods)->sortByDesc('created_at')->values();
        }

        return response()->json(['schedules' => $schedules->map(fn (GradeSchedule $schedule) => $this->serialize($schedule))]);
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

        $releasedSchedule = app(GradeScheduleReleaser::class)->release($schedule);

        return response()->json(['schedule' => $this->serialize($releasedSchedule)]);
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can create grade periods.');

        $data = $request->validate([
            'schoolYear' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester'])],
            'submitGradeDeadline' => ['required', 'date'],
        ]);
        abort_if(
            !$this->canCreateAcademicYear($data['schoolYear']),
            422,
            $this->academicYearAvailabilityMessage($data['schoolYear']),
        );
        abort_if(
            !$this->canCreateSemester($data['schoolYear'], $data['semester']),
            422,
            $this->semesterAvailabilityMessage($data['schoolYear']),
        );
        abort_if(
            $this->gradePeriodExists($data['schoolYear'], $data['semester']),
            422,
            "A Grade Period already exists for {$data['semester']}, A.Y. {$data['schoolYear']}. Edit the existing schedule instead.",
        );

        $schedule = GradeSchedule::create([
            'school_year' => $data['schoolYear'],
            'semester' => $data['semester'],
            'year_level' => 'Grade Period',
            'courses' => [],
            'submit_grade_deadline' => $data['submitGradeDeadline'],
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
            'schoolYear' => ['required', 'string', Rule::in([$schedule->school_year])],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester'])],
            'submitGradeDeadline' => ['required', 'date'],
        ]);

        $submitGradeDeadline = $schedule->submit_grade_deadline?->toDateString();
        $isUnchangedPeriod = $data['schoolYear'] === $schedule->school_year
            && $data['semester'] === $schedule->semester;
        $deadlineChanged = $data['submitGradeDeadline'] !== $submitGradeDeadline;

        abort_if(
            ($schedule->submit_grade_locked
                || ($submitGradeDeadline !== null && $submitGradeDeadline <= now('Asia/Manila')->toDateString()))
                && $deadlineChanged,
            422,
            'Completed academic years cannot be edited.',
        );
        abort_if(
            !$isUnchangedPeriod && $this->gradePeriodExists($data['schoolYear'], $data['semester'], $schedule->id),
            422,
            "A Grade Period already exists for {$data['semester']}, A.Y. {$data['schoolYear']}. Edit the existing schedule instead.",
        );
        abort_if(
            !$isUnchangedPeriod && !$this->canCreateSemester($data['schoolYear'], $data['semester']),
            422,
            $this->semesterAvailabilityMessage($data['schoolYear']),
        );

        $schedule->update([
            'school_year' => $data['schoolYear'],
            'semester' => $data['semester'],
            'submit_grade_deadline' => $data['submitGradeDeadline'],
        ]);

        return response()->json(['schedule' => $this->serialize($schedule->fresh())]);
    }

    public function lockSubmission(Request $request, GradeSchedule $schedule): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can lock grade submission.');
        abort_unless($schedule->year_level === 'Grade Period', 404);
        abort_if($schedule->submit_grade_locked, 422, 'Grade submission is already locked.');
        abort_if(!$schedule->submit_grade_deadline, 422, 'Set the Submit of Grade Deadline before locking grade submission.');
        abort_if(
            $schedule->submit_grade_deadline->toDateString() > now('Asia/Manila')->toDateString(),
            422,
            'Grade submission cannot be locked before the deadline.',
        );

        $schedule->update(['submit_grade_locked' => true]);

        return response()->json(['schedule' => $this->serialize($schedule->fresh())]);
    }

    public function unlockSubmission(Request $request, GradeSchedule $schedule): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can unlock grade submission.');
        abort_unless($schedule->year_level === 'Grade Period', 404);
        abort_if(!$schedule->submit_grade_locked, 422, 'Grade submission is already unlocked.');

        $schedule->update(['submit_grade_locked' => false]);

        return response()->json(['schedule' => $this->serialize($schedule->fresh())]);
    }

    public function destroyPeriod(Request $request, GradeSchedule $schedule): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can delete grade periods.');
        abort_unless($schedule->year_level === 'Grade Period', 404);
        abort_if(
            $schedule->submit_grade_locked
                || ($schedule->submit_grade_deadline !== null
                    && $schedule->submit_grade_deadline->toDateString() <= now('Asia/Manila')->toDateString()),
            422,
            'Completed academic years cannot be edited.',
        );
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
            'submitGradeDeadline' => $schedule->submit_grade_deadline?->toDateString(),
            'submitGradeLocked' => (bool) $schedule->submit_grade_locked,
        ];
    }

    private function canCreateAcademicYear(string $schoolYear): bool
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $matches)
            || (int) $matches[2] !== (int) $matches[1] + 1) {
            return false;
        }

        $startYear = (int) $matches[1];
        $currentYear = now('Asia/Manila')->year;
        if ($startYear < $currentYear) {
            return false;
        }

        $previousSchoolYear = ($startYear - 1) . '-' . $startYear;
        $previousFinalPeriod = GradeSchedule::query()
            ->where('year_level', 'Grade Period')
            ->where('school_year', $previousSchoolYear)
            ->where('semester', '2nd Semester')
            ->first();

        if (!$previousFinalPeriod) {
            return $startYear === $currentYear;
        }

        return (bool) $previousFinalPeriod->submit_grade_locked;
    }

    private function canCreateSemester(string $schoolYear, string $semester): bool
    {
        if ($semester !== '2nd Semester') {
            return true;
        }

        $firstSemester = GradeSchedule::query()
            ->where('year_level', 'Grade Period')
            ->where('school_year', $schoolYear)
            ->where('semester', '1st Semester')
            ->first();

        return $firstSemester
            && $firstSemester->submit_grade_locked
            && $firstSemester->submit_grade_deadline !== null
            && $firstSemester->submit_grade_deadline->toDateString() <= now('Asia/Manila')->toDateString();
    }

    private function semesterAvailabilityMessage(string $schoolYear): string
    {
        return "The 2nd Semester for A.Y. {$schoolYear} can be scheduled after the 1st Semester Submit of Grade is locked and its deadline has passed.";
    }

    private function academicYearAvailabilityMessage(string $schoolYear): string
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $matches)) {
            return 'Choose a valid school year from the list.';
        }

        $previousSchoolYear = ((int) $matches[1] - 1) . '-' . (int) $matches[1];

        return "The {$schoolYear} school year can be scheduled after Submit of Grade for the 2nd Semester of A.Y. {$previousSchoolYear} is locked.";
    }

    private function gradePeriodExists(string $schoolYear, string $semester, ?int $exceptId = null): bool
    {
        return GradeSchedule::query()
            ->where('year_level', 'Grade Period')
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}