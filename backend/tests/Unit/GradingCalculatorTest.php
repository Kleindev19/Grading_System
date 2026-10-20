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
            'inc range' => [72, '4.00', 'INC'],
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

    private function scoresForFinalGrade(float $finalGrade): array
    {
        $termScores = ['Q1' => $finalGrade, 'L1' => $finalGrade, 'R1' => $finalGrade, 'A1' => $finalGrade, 'Mid Ex' => $finalGrade];

        return ['midterm' => $termScores, 'finals' => $termScores];
    }
}