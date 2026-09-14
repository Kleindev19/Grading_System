<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->attributes->get('auth_user')->role, ['registrar', 'professor'], true), 403, 'Only registrars and professors can view students.');

        return response()->json(['students' => Student::latest()->get(['id', 'student_id', 'name', 'institute', 'course', 'year_level', 'section', 'status'])]);
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

        $student = Student::create([...$data, 'created_by' => $user->id]);

        return response()->json(['student' => $student->only(['id', 'student_id', 'name', 'institute', 'course', 'year_level', 'section', 'status'])], 201);
    }
}
