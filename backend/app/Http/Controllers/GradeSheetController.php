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
        ]);

        $sheet = GradeSheet::updateOrCreate(
            ['code' => $data['code'], 'section' => $data['section'], 'professor_id' => $user->id],
            [...$data, 'submitted' => now()->toDateString(), 'status' => 'Submitted', 'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null],
        );

        $sheet->studentsList()->delete();
        $sheet->studentsList()->createMany(array_map(fn (array $student): array => [
            'student_id' => $student['id'],
            'name' => $student['name'],
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

        return response()->json(['grade_sheet' => $this->serialize($gradeSheet->fresh()->load('professor:id,name'))]);
    }

    public function published(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'student', 403, 'Only students can view published grades.');

        return response()->json(['grade_sheets' => GradeSheet::with('professor:id,name')->where('status', 'Published')->latest()->get()->map(fn (GradeSheet $sheet) => $this->serialize($sheet))]);
    }

    private function serialize(GradeSheet $sheet): array
    {
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
            'student_records' => $sheet->studentsList->map(fn ($student) => [
                'id' => $student->student_id,
                'name' => $student->name,
                'midterm' => $student->midterm ?? '',
                'finals' => $student->finals ?? '',
                'finalGrade' => $student->final_grade ?? '',
                'gradePoint' => $student->grade_point ?? '',
                'remarks' => $student->remarks ?? '',
            ])->values(),
        ];
    }
}
