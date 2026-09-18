<?php

namespace App\Http\Controllers;

use App\Models\GradeSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'students' => ['required', 'integer', 'min:1'],
            'units' => ['required', 'integer', 'min:1'],
            'student_records' => ['required', 'array', 'min:1'],
            'student_records.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'student_records.*.name' => ['required', 'string', 'max:255'],
            'student_records.*.midterm' => ['required', 'string', 'max:40'],
            'student_records.*.finals' => ['required', 'string', 'max:40'],
            'student_records.*.finalGrade' => ['required', 'string', 'max:40'],
            'student_records.*.gradePoint' => ['required', 'string', 'max:40'],
            'student_records.*.remarks' => ['nullable', 'string', 'max:255'],
            'student_records.*.scores' => ['sometimes', 'array'],
        ]);

        abort_if(
            (int) $data['students'] !== count($data['student_records']),
            422,
            'The student count must match the submitted student records.',
        );

        $sheet = GradeSheet::updateOrCreate(
            ['code' => $data['code'], 'section' => $data['section'], 'professor_id' => $user->id],
            [...$data, 'submitted' => now()->toDateString(), 'status' => 'Submitted', 'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null],
        );

        $sheet->studentsList()->delete();
        $sheet->studentsList()->createMany(array_map(fn (array $student): array => [
            'student_id' => $student['id'],
            'name' => $student['name'],
            'midterm' => $student['midterm'] ?? null,
            'finals' => $student['finals'] ?? null,
            'final_grade' => $student['finalGrade'] ?? null,
            'grade_point' => $student['gradePoint'] ?? null,
            'remarks' => $student['remarks'] ?? null,
            'assessment_scores' => $student['scores'] ?? null,
        ], $data['student_records'] ?? []));

        return response()->json(['grade_sheet' => $this->serialize($sheet->load('professor:id,name', 'studentsList'))], 201);
    }

    public function updateStatus(Request $request, GradeSheet $gradeSheet): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can review grade sheets.');

        $data = $request->validate([
            'status' => ['required', 'in:Approved,Published,Submitted'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($gradeSheet->status, ['Approved', 'Published'], true) && $data['status'] === 'Submitted') {
            return response()->json(['message' => 'Approved or published grade sheets cannot be rejected.'], 422);
        }

        $validTransition = match ($gradeSheet->status) {
            'Submitted' => in_array($data['status'], ['Submitted', 'Approved'], true),
            'Approved' => in_array($data['status'], ['Approved', 'Published'], true),
            'Published' => $data['status'] === 'Published',
            default => false,
        };

        if (!$validTransition) {
            return response()->json(['message' => "Cannot change a {$gradeSheet->status} grade sheet to {$data['status']}."], 422);
        }

        if ($data['status'] === 'Published') {
            $gradeSheet->load('studentsList');
            $hasIncompleteGrades = $gradeSheet->studentsList->isEmpty() || $gradeSheet->studentsList->contains(function ($student): bool {
                return blank($student->midterm)
                    || blank($student->finals)
                    || blank($student->final_grade)
                    || blank($student->grade_point);
            });

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

        $studentId = $user->student_id ?: $user->username;

        return response()->json([
            'grade_sheets' => GradeSheet::with('professor:id,name', 'studentsList')
                ->where('status', 'Published')
                ->whereHas('studentsList', function ($query) use ($studentId, $user) {
                    $query->where('student_id', $studentId)
                        ->orWhere('student_id', $user->username);
                })
                ->latest()
                ->get()
                ->map(fn (GradeSheet $sheet) => $this->serialize($sheet, $studentId))
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


    private function serialize(GradeSheet $sheet, ?string $studentId = null): array
    {
        $records = $sheet->studentsList;
        if ($studentId) {
            $records = $records->where('student_id', $studentId)->values();
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
