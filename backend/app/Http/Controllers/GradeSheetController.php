<?php

namespace App\Http\Controllers;

use App\Models\GradeSheet;
use App\Services\GradingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeSheetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $this->returnIncompleteSheetsToProfessor();

        if ($user->role === 'registrar') {
            return response()->json(['grade_sheets' => GradeSheet::with('professor:id,name')->latest()->get()->map(fn (GradeSheet $sheet) => $this->serialize($sheet))]);
        }

        if ($user->role === 'professor') {
            return response()->json(['grade_sheets' => GradeSheet::with('professor:id,name')->where('professor_id', $user->id)->latest()->get()->map(fn (GradeSheet $sheet) => $this->serialize($sheet))]);
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can submit grade sheets.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
            'period' => ['sometimes', 'in:midterm,finals,both'],
            'students' => ['required', 'integer', 'min:1'],
            'units' => ['required', 'integer', 'min:1'],
            'student_records' => ['required', 'array', 'min:1'],
            'student_records.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'student_records.*.name' => ['required', 'string', 'max:255'],
            'student_records.*.scores' => ['required', 'array'],
            'student_records.*.scores.midterm' => ['sometimes', 'array'],
            'student_records.*.scores.midterm.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'student_records.*.scores.finals' => ['sometimes', 'array'],
            'student_records.*.scores.finals.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $period = $data['period'] ?? 'both';
        unset($data['period']);

        foreach ($data['student_records'] as $index => $student) {
            foreach ($period === 'both' ? ['midterm', 'finals'] : [$period] as $requiredPeriod) {
                if (!isset($student['scores'][$requiredPeriod]) || !is_array($student['scores'][$requiredPeriod])) {
                    throw ValidationException::withMessages(["student_records.{$index}.scores.{$requiredPeriod}" => "The {$requiredPeriod} scores are required for this submission."]);
                }
            }
        }

        abort_if(
            (int) $data['students'] !== count($data['student_records']),
            422,
            'The student count must match the submitted student records.',
        );

        $sheetKey = ['code' => $data['code'], 'section' => $data['section'], 'professor_id' => $user->id, 'semester' => $data['semester']];
        $existingSheet = GradeSheet::where($sheetKey)->with('studentsList')->first();
        $existingRecords = $existingSheet?->studentsList->keyBy('student_id') ?? collect();
        $calculator = app(GradingCalculator::class);

        $records = array_map(function (array $student) use ($existingRecords, $period, $calculator): array {
            $existing = $existingRecords->get($student['id']);
            $scores = $this->mergePeriodScores($existing?->assessment_scores ?? [], $student['scores'], $period);
            $termGrades = [];

            foreach (['midterm', 'finals'] as $term) {
                try {
                    $termGrades[$term] = $calculator->calculatePeriod($term, $scores[$term] ?? []);
                } catch (\InvalidArgumentException $exception) {
                    if ($period === 'both' || $period === $term) {
                        throw ValidationException::withMessages(['student_records' => $exception->getMessage()]);
                    }
                    $savedTerm = $existing?->{$term};
                    $termGrades[$term] = is_numeric($savedTerm) ? (float) $savedTerm : null;
                }
            }

            $finalGrades = $termGrades['midterm'] !== null && $termGrades['finals'] !== null
                ? $calculator->calculateFromTerms($termGrades['midterm'], $termGrades['finals'])
                : null;

            return [
                'student_id' => $student['id'],
                'name' => $student['name'],
                'midterm' => $termGrades['midterm'] === null ? null : number_format($termGrades['midterm'], 2, '.', ''),
                'finals' => $termGrades['finals'] === null ? null : number_format($termGrades['finals'], 2, '.', ''),
                'final_grade' => $finalGrades['finalGrade'] ?? null,
                'grade_point' => $finalGrades['gradePoint'] ?? null,
                'remarks' => $finalGrades['remarks'] ?? null,
                'assessment_scores' => $scores,
            ];
        }, $data['student_records']);

        $sheet = DB::transaction(function () use ($data, $user, $records, $sheetKey): GradeSheet {
            $sheet = GradeSheet::updateOrCreate(
                $sheetKey,
                [...$data, 'submitted' => now()->toDateString(), 'status' => 'Submitted', 'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null],
            );
            $sheet->studentsList()->delete();
            $sheet->studentsList()->createMany($records);

            return $sheet;
        });

        return response()->json(['grade_sheet' => $this->serialize($sheet->load('professor:id,name', 'studentsList'))], 201);
    }

    private function mergePeriodScores(array $existingScores, array $submittedScores, string $period): array
    {
        if (!isset($existingScores['midterm']) || !is_array($existingScores['midterm'])
            || !isset($existingScores['finals']) || !is_array($existingScores['finals'])) {
            $legacyScores = collect($existingScores)
                ->filter(fn ($score): bool => is_numeric($score))
                ->map(fn ($score): string => (string) min(100, (float) $score * 5))
                ->all();
            $existingScores = ['midterm' => $legacyScores, 'finals' => $legacyScores];
        }

        if ($period === 'both') {
            return [
                'midterm' => $submittedScores['midterm'],
                'finals' => $submittedScores['finals'],
            ];
        }

        return [
            'midterm' => $period === 'midterm' ? $submittedScores['midterm'] : ($existingScores['midterm'] ?? []),
            'finals' => $period === 'finals' ? $submittedScores['finals'] : ($existingScores['finals'] ?? []),
        ];
    }

    public function updateStatus(Request $request, GradeSheet $gradeSheet): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can review grade sheets.');

        $data = $request->validate([
            'status' => ['required', 'in:Approved,Published,Submitted'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $hasIncompleteGrades = false;

        if ($data['status'] === 'Submitted' && $gradeSheet->status === 'Published') {
            return response()->json(['message' => 'Published grade sheets cannot be returned.'], 422);
        }

        if ($data['status'] === 'Submitted' && $gradeSheet->status === 'Approved') {
            $gradeSheet->load('studentsList');
            $hasIncompleteGrades = $this->hasIncompleteGrades($gradeSheet);
            if (!$hasIncompleteGrades) {
                return response()->json(['message' => 'Complete approved grade sheets cannot be returned.'], 422);
            }
        }

        if ($data['status'] === 'Submitted' && blank($data['rejection_reason'] ?? null)) {
            return response()->json(['message' => 'A reason is required when returning a grade sheet.'], 422);
        }

        $validTransition = match ($gradeSheet->status) {
            'Submitted' => in_array($data['status'], ['Submitted', 'Approved'], true),
            'Approved' => in_array($data['status'], ['Approved', 'Published'], true)
                || ($data['status'] === 'Submitted' && $hasIncompleteGrades),
            'Published' => $data['status'] === 'Published',
            default => false,
        };

        if (!$validTransition) {
            return response()->json(['message' => "Cannot change a {$gradeSheet->status} grade sheet to {$data['status']}."], 422);
        }

        if ($data['status'] === 'Approved') {
            $gradeSheet->load('studentsList');
            if ($this->hasIncompleteGrades($gradeSheet)) {
                return response()->json(['message' => 'Cannot approve grades until Midterm, Finals, and Final Grade are complete for every student.'], 422);
            }
        }

        if ($data['status'] === 'Published') {
            $gradeSheet->load('studentsList');
            $hasIncompleteGrades = $this->hasIncompleteGrades($gradeSheet);

            if ($hasIncompleteGrades) {
                return response()->json(['message' => 'Cannot publish grades while any student grade is incomplete.'], 422);
            }
        }

        $gradeSheet->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ])->save();

        if ($data['status'] === 'Published') {
            $gradeSheet->refresh();
        }

        return response()->json(['grade_sheet' => $this->serialize($gradeSheet->fresh()->load('professor:id,name'))]);
    }

    public function published(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'student', 403, 'Only students can view published grades.');

        $this->returnIncompleteSheetsToProfessor();

        $studentIds = collect([$user->student_id, $user->username])->filter()->unique()->values();

        return response()->json([
            'grade_sheets' => GradeSheet::with('professor:id,name', 'studentsList')
                ->where('status', 'Published')
                ->whereHas('studentsList', function ($query) use ($studentIds) {
                    $query->whereIn('student_id', $studentIds);
                })
                ->latest()
                ->get()
                ->map(fn (GradeSheet $sheet) => $this->serialize($sheet, $studentIds->all()))
        ]);
    }

    private function returnIncompleteSheetsToProfessor(): void
    {
        GradeSheet::with('studentsList')
            ->whereIn('status', ['Published', 'Released'])
            ->get()
            ->each(function (GradeSheet $sheet): void {
                $hasIncompleteGrades = $sheet->studentsList->isEmpty() || $sheet->studentsList->contains(function ($student): bool {
                    return blank($student->midterm)
                        || blank($student->finals)
                        || blank($student->final_grade)
                        || blank($student->grade_point);
                });

                if ($hasIncompleteGrades) {
                    $sheet->forceFill([
                        'status' => 'Submitted',
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => 'Returned to professor: incomplete grades.',
                    ])->save();
                }
            });
    }

    private function hasIncompleteGrades(GradeSheet $gradeSheet): bool
    {
        return $gradeSheet->studentsList->isEmpty() || $gradeSheet->studentsList->contains(function ($student): bool {
            return blank($student->midterm)
                || blank($student->finals)
                || blank($student->final_grade)
                || blank($student->grade_point);
        });
    }


    private function serialize(GradeSheet $sheet, string|array|null $studentId = null): array
    {
        $records = $sheet->studentsList;
        if ($studentId) {
            $records = $records->whereIn('student_id', (array) $studentId)->values();
        }

        return [
            'id' => $sheet->id,
            'code' => $sheet->code,
            'subject' => $sheet->subject,
            'section' => $sheet->section,
            'semester' => $sheet->semester,
            'students' => $sheet->students,
            'units' => $sheet->units,
            'submitted' => $sheet->submitted?->toDateString(),
            'status' => $sheet->status,
            'professor' => $sheet->professor?->name ?? '',
            'professor_id' => $sheet->professor_id,
            'reviewed_by' => $sheet->reviewed_by,
            'reviewed_at' => $sheet->reviewed_at?->toISOString(),
            'rejection_reason' => $sheet->rejection_reason,
            'student_records' => $records->map(fn ($student) => [
                'id' => $student->student_id,
                'name' => $student->name,
                'midterm' => $student->midterm ?? '',
                'finals' => $student->finals ?? '',
                'finalGrade' => $student->final_grade ?? '',
                'gradePoint' => $student->grade_point ?? '',
                'remarks' => $student->remarks ?? '',
                'scores' => $student->assessment_scores ?? [],
            ])->values(),
        ];
    }
}
