<?php

namespace App\Services;

use InvalidArgumentException;

class GradingCalculator
{
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
        return $this->calculateFromTerms($midterm, $finals);
    }

    public function calculateFromTerms(float $midterm, float $finals): array
    {
        $finalGrade = round(($midterm * 0.4) + ($finals * 0.6), 2);
        $roundedGrade = (int) round($finalGrade, 0, PHP_ROUND_HALF_UP);

        [$gradePoint, $remarks] = match (true) {
            $roundedGrade >= 98 => [1.00, 'PASSED'],
            $roundedGrade >= 96 => [1.25, 'PASSED'],
            $roundedGrade >= 93 => [1.50, 'PASSED'],
            $roundedGrade >= 90 => [1.75, 'PASSED'],
            $roundedGrade >= 87 => [2.00, 'PASSED'],
            $roundedGrade >= 84 => [2.25, 'PASSED'],
            $roundedGrade >= 81 => [2.50, 'PASSED'],
            $roundedGrade >= 78 => [2.75, 'PASSED'],
            $roundedGrade >= 75 => [3.00, 'PASSED'],
            $roundedGrade >= 70 => [4.00, 'INC'],
            default => [5.00, 'FAILED'],
        };

        return [
            'midterm' => number_format($midterm, 2, '.', ''),
            'finals' => number_format($finals, 2, '.', ''),
            'finalGrade' => number_format($finalGrade, 2, '.', ''),
            'gradePoint' => number_format($gradePoint, 2, '.', ''),
            'remarks' => $remarks,
        ];
    }

    public function calculatePeriod(string $period, array $scores): float
    {
        if (!isset(self::WEIGHTS[$period])) {
            throw new InvalidArgumentException("Unknown grading period: {$period}.");
        }

        return $this->calculateTerm($scores, self::WEIGHTS[$period]);
    }

    private function calculateTerm(array $scores, array $weights): float
    {
        $prefixes = [
            'quiz' => ['Q'],
            'lab' => ['L'],
            'recitation' => ['R'],
            'assignment' => ['A'],
            'major_exam' => ['Mid Ex'],
        ];
        $termGrade = 0.0;

        foreach ($weights as $category => $weight) {
            $categoryScores = [];
            foreach ($scores as $label => $score) {
                foreach ($prefixes[$category] as $prefix) {
                    if (str_starts_with((string) $label, $prefix) && $score !== null && $score !== '') {
                        $categoryScores[] = (float) $score;
                        break;
                    }
                }
            }

            if ($categoryScores === []) {
                throw new InvalidArgumentException("A score is required for the {$category} category.");
            }

            $termGrade += (array_sum($categoryScores) / count($categoryScores)) * ($weight / 100);
        }

        return round($termGrade, 2);
    }
}