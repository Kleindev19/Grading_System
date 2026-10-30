<?php

namespace App\Services;

use App\Models\GradingScale;
use InvalidArgumentException;

class GradingCalculator
{
    private ?array $gradingScaleRows = null;

    private const WEIGHTS = [
        'midterm' => [
            'quiz' => 15,
            'lab' => 25,
            'recitation' => 10,
            'assignment' => 10,
            'major_exam' => 40,
        ],
        'finals' => [
            'quiz' => 15,
            'lab' => 5,
            'recitation' => 20,
            'assignment' => 20,
            'major_exam' => 40,
        ],
    ];

    public function calculate(array $scores): array
    {
        $midterm = $this->calculatePeriod('midterm', $scores['midterm'] ?? []);
        $finals = $this->calculatePeriod('finals', $scores['finals'] ?? []);
        return $this->calculateFromTerms($midterm, $finals, $this->hasZeroScore($scores));
    }

    public function calculateFromTerms(float $midterm, float $finals, bool $hasZeroScore = false): array
    {
        $finalGrade = round(($midterm * 0.4) + ($finals * 0.6), 2);
        $roundedGrade = (int) round($finalGrade, 0, PHP_ROUND_HALF_UP);

        $this->gradingScaleRows ??= GradingScale::currentRows();
        $gradeRow = collect($this->gradingScaleRows)->first(
            fn (array $row): bool => $roundedGrade >= $row['minimum'] && $roundedGrade <= $row['maximum'],
        );
        if (!$gradeRow) {
            throw new InvalidArgumentException('The saved grading scale does not cover the calculated final grade.');
        }

        [$gradePoint, $remarks] = $hasZeroScore
            ? [5.00, 'FAILED']
            : [(float) $gradeRow['gradePoint'], $gradeRow['remarks']];

        return [
            'midterm' => number_format($midterm, 2, '.', ''),
            'finals' => number_format($finals, 2, '.', ''),
            'finalGrade' => number_format($finalGrade, 2, '.', ''),
            'gradePoint' => number_format($gradePoint, 2, '.', ''),
            'remarks' => $remarks,
        ];
    }

    public function hasZeroScore(array $scores): bool
    {
        foreach ($scores as $periodScores) {
            if (!is_array($periodScores)) {
                continue;
            }

            foreach ($periodScores as $score) {
                if ($score !== null && $score !== '' && is_numeric($score) && (float) $score === 0.0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function calculatePeriod(string $period, array $scores, ?array $criteria = null): float
    {
        if (!isset(self::WEIGHTS[$period])) {
            throw new InvalidArgumentException("Unknown grading period: {$period}.");
        }

        return $this->calculateTerm($scores, self::WEIGHTS[$period], $criteria);
    }

    private function calculateTerm(array $scores, array $weights, ?array $criteria = null): float
    {
        $prefixes = [
            'quiz' => ['Q'],
            'lab' => ['L'],
            'recitation' => ['R'],
            'assignment' => ['A'],
            'major_exam' => ['Mid Ex'],
        ];
        $usingDefaultCriteria = $criteria === null;
        if ($usingDefaultCriteria) {
            $criteria = array_map(
                fn (int|float $weight): array => ['weight' => $weight, 'maxScore' => 100],
                $weights,
            );
        }

        $criterionScores = [];
        foreach ($scores as $label => $score) {
            $key = $this->criterionKey((string) $label, $prefixes);
            if ($key !== null) {
                $criterionScores[$key][] = $score === null || $score === '' ? null : (float) $score;
            }
        }

        if ($usingDefaultCriteria) {
            foreach (array_keys($weights) as $category) {
                if (!isset($criterionScores[$category]) || in_array(null, $criterionScores[$category], true)) {
                    throw new InvalidArgumentException("A score is required for the {$category} category.");
                }
            }
        }

        $activeCriteria = array_intersect_key($criteria, $criterionScores);
        $weightTotal = array_sum(array_map(fn (array $criterion): float => (float) $criterion['weight'], $activeCriteria));
        if ($activeCriteria === [] || abs($weightTotal - 100) > 0.001) {
            throw new InvalidArgumentException('Grading criteria percentages must total 100%.');
        }

        $termGrade = 0.0;

        foreach ($activeCriteria as $category => $criterion) {
            $categoryScores = $criterionScores[$category] ?? [];
            if ($categoryScores === [] || in_array(null, $categoryScores, true)) {
                throw new InvalidArgumentException("A score is required for the {$category} category.");
            }

            $maxScore = (float) $criterion['maxScore'];
            if ($maxScore <= 0 || $maxScore > 100 || max($categoryScores) > $maxScore || max($categoryScores) > 100) {
                throw new InvalidArgumentException("Scores for the {$category} category exceed its maximum score.");
            }
            $averagePercent = (array_sum($categoryScores) / count($categoryScores) / $maxScore) * 100;
            $termGrade += $averagePercent * ((float) $criterion['weight'] / 100);
        }

        return round($termGrade, 2);
    }

    private function criterionKey(string $label, array $prefixes): ?string
    {
        if (preg_match('/^(?:Mid Ex|[QLAR])\d+:\s*(.+)$/i', $label, $matches) === 1) {
            $name = mb_strtolower(trim($matches[1]));
            $name = preg_replace('/\s+/', '_', $name);

            return 'custom:' . $name;
        }

        foreach ($prefixes as $category => $categoryPrefixes) {
            foreach ($categoryPrefixes as $prefix) {
                if (str_starts_with($label, $prefix)) {
                    return $category;
                }
            }
        }

        return null;
    }
}