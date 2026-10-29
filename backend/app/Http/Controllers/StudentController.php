<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\GradeSheetStudent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->attributes->get('auth_user')->role, ['registrar', 'professor'], true), 403, 'Only registrars and professors can view students.');

        $students = Student::latest()->get(['id', 'student_id', 'name', 'institute', 'course', 'year_level', 'section', 'status']);

        $students->each(function (Student $student): void {
            if ($student->institute && $student->course && $student->year_level && $student->section) return;

            $grade = GradeSheetStudent::with('gradeSheet')
                ->where('student_id', $student->student_id)
                ->latest()
                ->first();
            $sheet = $grade?->gradeSheet;
            if (!$sheet) return;

            $program = strtoupper(strtok($sheet->section, ' -'));
            $metadata = match (true) {
                str_starts_with($program, 'BSIT') || str_starts_with($program, 'IT') => ['institute' => 'ICS'],
                str_starts_with($program, 'BEED') || str_starts_with($program, 'BSED') => ['institute' => 'ITE'],
                str_starts_with($program, 'BSBA') || str_starts_with($program, 'BS') => ['institute' => 'IBE'],
                default => [],
            };
            if (!$metadata) return;

            $section = strtoupper(trim(strrchr($sheet->section, ' ') ?: ''));
            $letter = preg_match('/([A-D])$/', $section, $matches) ? $matches[1] : null;
            $year = match (preg_match('/([1-4])[A-D]$/i', $section, $matches) ? $matches[1] : null) {
                '1' => '1st Year',
                '2' => '2nd Year',
                '3' => '3rd Year',
                '4' => '4th Year',
                default => null,
            };
            $student->forceFill([
                ...$metadata,
                'course' => $student->course ?: $program,
                'year_level' => $student->year_level ?: $year,
                'section' => $student->section ?: $letter,
            ])->save();
        });

        return response()->json(['students' => $students->fresh()->values()]);
    }

    public function grades(Request $request, string $studentId): JsonResponse
    {
        abort_unless(in_array($request->attributes->get('auth_user')->role, ['registrar', 'professor'], true), 403, 'Only registrars and professors can view student grades.');

        $student = Student::query()->where('student_id', $studentId)->firstOrFail();
        $studentIdentifiers = collect([$student->student_id])
            ->merge(User::query()
                ->where('student_id', $student->student_id)
                ->orWhere('username', $student->student_id)
                ->pluck('username'))
            ->filter()
            ->unique()
            ->values();
        $grades = GradeSheetStudent::with('gradeSheet.professor:id,name')
            ->whereIn('student_id', $studentIdentifiers)
            ->whereHas('gradeSheet', fn ($query) => $query->where('status', 'Published'))
            ->get()
            ->map(fn (GradeSheetStudent $record): array => [
                'id' => $record->gradeSheet->id,
                'code' => $record->gradeSheet->code,
                'subject' => $record->gradeSheet->subject,
                'semester' => $record->gradeSheet->semester,
                'professor' => $record->gradeSheet->professor?->name ?? '',
                'units' => $record->gradeSheet->units,
                'midterm' => $record->midterm ?? '',
                'finals' => $record->finals ?? '',
                'finalGrade' => $record->final_grade ?? '',
                'gradePoint' => $record->grade_point ?? '',
                'remarks' => strtoupper(trim((string) $record->remarks)) === 'INC' ? 'FAILED' : ($record->remarks ?? ''),
            ])
            ->values();

        return response()->json(['grades' => $grades]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can manage students.');

        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:80', 'unique:students,student_id'],
            'name' => ['required', 'string', 'max:255'],
            'institute' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:40'],
            'section' => ['nullable', 'string', 'max:1', 'in:A,B,C,D'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);

        $student = Student::create([...$data, 'status' => $data['status'] ?? 'Active', 'created_by' => $user->id]);

        return response()->json(['student' => $student->only(['id', 'student_id', 'name', 'institute', 'course', 'year_level', 'section', 'status'])], 201);
    }
}
