<?php

namespace App\Http\Controllers;

use App\Models\GradeDraft;
use App\Services\GradeSubmissionLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeDraftController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can view grade drafts.');

        $course = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
        ]);

        $draft = GradeDraft::query()
            ->where([...$course, 'professor_id' => $user->id])
            ->first();

        return response()->json(['draft' => $draft]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can save grade drafts.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:0'],
            'assessment_headers' => ['required', 'array'],
            'student_records' => ['required', 'array'],
            'student_records.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'student_records.*.name' => ['required', 'string', 'max:255'],
            'student_records.*.scores.midterm' => ['required', 'array'],
            'student_records.*.scores.midterm.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'student_records.*.scores.finals' => ['required', 'array'],
            'student_records.*.scores.finals.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        GradeSubmissionLock::ensureUnlocked($data['semester']);

        if (array_is_list($data['assessment_headers'])) {
            $headers = Validator::make(
                ['headers' => $data['assessment_headers']],
                ['headers' => ['required', 'array'], 'headers.*' => ['required', 'string', 'max:80', 'distinct']],
            )->validate()['headers'];
            $data['assessment_headers'] = ['midterm' => $headers, 'finals' => $headers];
        } else {
            $data['assessment_headers'] = Validator::make($data['assessment_headers'], [
                'midterm' => ['required', 'array'],
                'midterm.*' => ['required', 'string', 'max:80', 'distinct'],
                'finals' => ['required', 'array'],
                'finals.*' => ['required', 'string', 'max:80', 'distinct'],
            ])->validate();
        }

        GradeDraft::query()->updateOrCreate(
            [
                'professor_id' => $user->id,
                'code' => $data['code'],
                'section' => $data['section'],
                'semester' => $data['semester'],
            ],
            [
                'subject' => $data['subject'],
                'units' => $data['units'],
                'assessment_headers' => $data['assessment_headers'],
                'student_records' => $data['student_records'],
            ],
        );

        return response()->json(['message' => 'Grade draft saved.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can delete grade drafts.');

        $course = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
        ]);
        GradeSubmissionLock::ensureUnlocked($course['semester']);

        GradeDraft::query()
            ->where([...$course, 'professor_id' => $user->id])
            ->delete();

        return response()->json(['message' => 'Grade draft deleted.']);
    }
}
