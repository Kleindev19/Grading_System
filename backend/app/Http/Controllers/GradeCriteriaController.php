<?php

namespace App\Http\Controllers;

use App\Models\GradeCriteria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GradeCriteriaController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can view grading criteria.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
        ]);
        $record = GradeCriteria::query()
            ->where([...$data, 'professor_id' => $user->id])
            ->first();

        return response()->json([
            'criteria' => $record?->criteria,
            'assessment_headers' => $record?->assessment_headers ?? [],
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user->role === 'professor', 403, 'Only professors can save grading criteria.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:80'],
            'semester' => ['required', 'string', 'max:80'],
            'criteria.midterm' => ['required', 'array', 'min:1'],
            'criteria.midterm.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'criteria.midterm.*.maxScore' => ['required', 'numeric', 'gt:0', 'max:100'],
            'criteria.finals' => ['required', 'array', 'min:1'],
            'criteria.finals.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'criteria.finals.*.maxScore' => ['required', 'numeric', 'gt:0', 'max:100'],
            'assessment_headers' => ['required', 'array'],
        ]);

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

        foreach (['midterm', 'finals'] as $period) {
            $weightTotal = array_sum(array_column($data['criteria'][$period], 'weight'));
            if (abs($weightTotal - 100) > 0.001) {
                throw ValidationException::withMessages([
                    "criteria.{$period}" => ucfirst($period) . ' criteria percentages must total 100%.',
                ]);
            }
        }

        $criteria = GradeCriteria::query()->updateOrCreate(
            [
                'professor_id' => $user->id,
                'code' => $data['code'],
                'section' => $data['section'],
                'semester' => $data['semester'],
            ],
            [
                'criteria' => $data['criteria'],
                'assessment_headers' => $data['assessment_headers'],
            ],
        );

        return response()->json([
            'criteria' => $criteria->criteria,
            'assessment_headers' => $criteria->assessment_headers,
        ]);
    }
}
