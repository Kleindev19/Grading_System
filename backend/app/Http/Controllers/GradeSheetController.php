<?php

namespace App\Http\Controllers;

use App\Models\GradeSheet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeSheetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

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
            'students' => ['required', 'integer', 'min:0'],
            'student_records' => ['sometimes', 'array'],
            'student_records.*.id' => ['required', 'string', 'max:80'],
            'student_records.*.name' => ['required', 'string', 'max:255'],
            'student_records.*.midterm' => ['nullable', 'string', 'max:40'],
            'student_records.*.finals' => ['nullable', 'string', 'max:40'],
            'student_records.*.finalGrade' => ['nullable', 'string', 'max:40'],
            'student_records.*.gradePoint' => ['nullable', 'string', 'max:40'],
            'student_records.*.remarks' => ['nullable', 'string', 'max:255'],
            'student_records.*.scores' => ['sometimes', 'array'],
        ]);

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

        $studentId = $user->student_id ?: $user->username;

        return response()->json([
            'grade_sheets' => GradeSheet::with('professor:id,name', 'studentsList')
                ->whereIn('status', ['Published', 'Released'])
                ->whereHas('studentsList', function ($query) use ($studentId, $user) {
                    $query->where('student_id', $studentId)
                        ->orWhere('student_id', $user->username);
                })
                ->latest()
                ->get()
                ->map(fn (GradeSheet $sheet) => $this->serialize($sheet, $studentId))
        ]);
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
