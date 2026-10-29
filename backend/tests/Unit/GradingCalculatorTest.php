<?php

namespace Tests\Unit;

use App\Services\GradingCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GradingCalculatorTest extends TestCase
{
    public function test_it_applies_period_weights_and_the_40_60_final_composition(): void
    {
        $scores = [
            'midterm' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 80],
            'finals' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100],
        ];

        $grades = (new GradingCalculator())->calculate($scores);

        $this->assertSame('92.00', $grades['midterm']);
        $this->assertSame('100.00', $grades['finals']);
        $this->assertSame('96.80', $grades['finalGrade']);
        $this->assertSame('1.25', $grades['gradePoint']);
        $this->assertSame('PASSED', $grades['remarks']);
    }

    public function test_any_zero_assessment_score_immediately_fails_the_student(): void
    {
        $scores = [
            'midterm' => ['Q1' => 0, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100],
            'finals' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100],
        ];

        $grades = (new GradingCalculator())->calculate($scores);

        $this->assertSame('5.00', $grades['gradePoint']);
        $this->assertSame('FAILED', $grades['remarks']);
    }

    #[DataProvider('gradeScaleCases')]
    public function test_it_maps_final_percentages_to_the_grade_scale(float $finalGrade, string $gradePoint, string $remarks): void
    {
        $scores = $this->scoresForFinalGrade($finalGrade);

        $grades = (new GradingCalculator())->calculate($scores);

        $this->assertSame($gradePoint, $grades['gradePoint']);
        $this->assertSame($remarks, $grades['remarks']);
    }

    public static function gradeScaleCases(): array
    {
        return [
            'excellent' => [98, '1.00', 'PASSED'],
            'very good' => [96, '1.25', 'PASSED'],
            'below passing threshold' => [72, '4.00', 'FAILED'],
            'failed range' => [69, '5.00', 'FAILED'],
        ];
    }

    public function test_it_rejects_terms_missing_a_weighted_category(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lab category');

        (new GradingCalculator())->calculate([
            'midterm' => ['Q1' => 90, 'R1' => 90, 'A1' => 90, 'Mid Ex' => 90],
            'finals' => ['Q1' => 90, 'L1' => 90, 'R1' => 90, 'A1' => 90, 'Mid Ex' => 90],
        ]);
    }

    public function test_it_applies_saved_weights_and_maximum_scores_to_custom_assessments(): void
    {
        $criteria = [
            'quiz' => ['weight' => 15, 'maxScore' => 100],
            'lab' => ['weight' => 25, 'maxScore' => 100],
            'recitation' => ['weight' => 10, 'maxScore' => 100],
            'assignment' => ['weight' => 0, 'maxScore' => 100],
            'major_exam' => ['weight' => 40, 'maxScore' => 100],
            'custom:project' => ['weight' => 10, 'maxScore' => 50],
        ];

        $grade = (new GradingCalculator())->calculatePeriod('midterm', [
            'Q1' => 100,
            'L1' => 100,
            'R1' => 100,
            'A1' => 100,
            'Mid Ex' => 100,
            'A2: Project' => 25,
        ], $criteria);

        $this->assertSame(95.0, $grade);
    }

    public function test_it_rejects_scores_above_one_hundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceed its maximum score');

        (new GradingCalculator())->calculatePeriod('midterm', [
            'Q1' => 101,
            'L1' => 100,
            'R1' => 100,
            'A1' => 100,
            'Mid Ex' => 100,
        ]);
    }

    public function test_it_uses_the_maximum_score_saved_in_category_criteria(): void
    {
        $criteria = [
            'quiz' => ['weight' => 15, 'maxScore' => 100],
            'lab' => ['weight' => 25, 'maxScore' => 100],
            'recitation' => ['weight' => 10, 'maxScore' => 100],
            'assignment' => ['weight' => 10, 'maxScore' => 100],
            'major_exam' => ['weight' => 40, 'maxScore' => 100],
        ];

        $grade = (new GradingCalculator())->calculatePeriod('midterm', [
            'Q1' => 100,
            'L1' => 100,
            'R1' => 100,
            'A1' => 100,
            'Mid Ex' => 100,
        ], $criteria);

        $this->assertSame(100.0, $grade);
    }

    private function scoresForFinalGrade(float $finalGrade): array
    {
        $termScores = ['Q1' => $finalGrade, 'L1' => $finalGrade, 'R1' => $finalGrade, 'A1' => $finalGrade, 'Mid Ex' => $finalGrade];

        return ['midterm' => $termScores, 'finals' => $termScores];
    }
}