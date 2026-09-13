<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can manage students.');

        return response()->json(['students' => Student::latest()->get(['id', 'student_id', 'name'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'registrar', 403, 'Only registrars can manage students.');

        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:80', 'unique:students,student_id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $student = Student::create([...$data, 'created_by' => $user->id]);

        return response()->json(['student' => $student->only(['id', 'student_id', 'name'])], 201);
    }
}
