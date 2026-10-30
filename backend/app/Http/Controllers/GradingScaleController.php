<?php

namespace App\Http\Controllers;

use App\Models\GradingScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradingScaleController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless(in_array($request->attributes->get('auth_user')->role, ['registrar', 'professor'], true), 403, 'Only registrars and professors can view the grading scale.');

        $scale = GradingScale::query()->find(1);

        return response()->json([
            'rows' => $scale?->rows ?? GradingScale::defaultRows(),
            'isSaved' => $scale !== null,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can update the grading scale.');

        $validator = Validator::make($request->all(), [
            'rows' => ['required', 'array', 'min:1', 'max:101'],
            'rows.*.minimum' => ['required', 'integer', 'min:0', 'max:100'],
            'rows.*.maximum' => ['required', 'integer', 'min:0', 'max:100'],
            'rows.*.gradePoint' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:5'],
            'rows.*.remarks' => ['required', 'string', 'in:PASSED,FAILED'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ranges = collect($request->input('rows'))
                ->map(fn (array $row): array => [
                    'minimum' => (int) $row['minimum'],
                    'maximum' => (int) $row['maximum'],
                ])
                ->sortBy('minimum')
                ->values();

            if ($ranges->first()['minimum'] !== 0) {
                $validator->errors()->add('rows', 'The grading scale must start at 0.');

                return;
            }

            $expectedMinimum = 0;
            foreach ($ranges as $range) {
                if ($range['minimum'] !== $expectedMinimum || $range['maximum'] < $range['minimum']) {
                    $validator->errors()->add('rows', 'Grade ranges must be valid, non-overlapping, and cover every grade from 0 to 100.');

                    return;
                }

                $expectedMinimum = $range['maximum'] + 1;
            }

            if ($expectedMinimum !== 101) {
                $validator->errors()->add('rows', 'The grading scale must cover every grade through 100.');
            }
        });

        $validated = $validator->validate();
        $scale = GradingScale::query()->find(1) ?? new GradingScale(['id' => 1]);
        $scale->rows = collect($validated['rows'])
            ->map(fn (array $row): array => [
                'minimum' => (int) $row['minimum'],
                'maximum' => (int) $row['maximum'],
                'gradePoint' => number_format((float) $row['gradePoint'], 2, '.', ''),
                'remarks' => trim($row['remarks']),
            ])
            ->values()
            ->all();
        $scale->save();

        return response()->json(['rows' => $scale->rows, 'isSaved' => true]);
    }
}
