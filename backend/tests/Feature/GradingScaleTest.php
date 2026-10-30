<?php

namespace Tests\Feature;

use App\Models\GradingScale;
use App\Models\User;
use App\Services\GradingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_scale_is_available_until_the_registrar_saves_a_custom_scale(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'grading-scale-professor-token',
        ]);

        $this->withToken($professor->api_token)
            ->getJson('/api/grading-scale')
            ->assertOk()
            ->assertJsonPath('rows.0.minimum', 98)
            ->assertJsonPath('rows.0.maximum', 100)
            ->assertJsonPath('rows.0.gradePoint', '1.00')
            ->assertJsonPath('isSaved', false)
            ->assertJsonCount(11, 'rows');
    }

    public function test_registrar_can_save_a_custom_scale_used_by_grade_calculations(): void
    {
        $registrar = User::factory()->create([
            'role' => 'registrar',
            'api_token' => 'grading-scale-registrar-token',
        ]);
        $rows = [
            ['minimum' => 80, 'maximum' => 100, 'gradePoint' => '1.00', 'remarks' => 'PASSED'],
            ['minimum' => 0, 'maximum' => 79, 'gradePoint' => '4.00', 'remarks' => 'FAILED'],
        ];

        $this->withToken($registrar->api_token)
            ->putJson('/api/grading-scale', ['rows' => $rows])
            ->assertOk()
            ->assertJsonPath('rows.1.remarks', 'FAILED')
            ->assertJsonPath('isSaved', true);

        $this->assertSame('1.00', app(GradingCalculator::class)->calculateFromTerms(80, 80)['gradePoint']);
        $this->assertSame('FAILED', app(GradingCalculator::class)->calculateFromTerms(79, 79)['remarks']);

        $this->withToken($registrar->api_token)
            ->getJson('/api/grading-scale')
            ->assertOk()
            ->assertJsonPath('rows.1.gradePoint', '4.00');
    }

    public function test_grading_scale_rejects_inc_remarks(): void
    {
        $registrar = User::factory()->create([
            'role' => 'registrar',
            'api_token' => 'grading-scale-remarks-token',
        ]);
        $rows = [
            ['minimum' => 80, 'maximum' => 100, 'gradePoint' => '1.00', 'remarks' => 'PASSED'],
            ['minimum' => 0, 'maximum' => 79, 'gradePoint' => '4.00', 'remarks' => 'INC'],
        ];

        $this->withToken($registrar->api_token)
            ->putJson('/api/grading-scale', ['rows' => $rows])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.1.remarks');

        $this->assertDatabaseCount('grading_scales', 0);
    }

    public function test_professors_can_read_but_cannot_change_the_grading_scale(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'grading-scale-readonly-token',
        ]);

        $this->withToken($professor->api_token)
            ->getJson('/api/grading-scale')
            ->assertOk();

        $this->withToken($professor->api_token)
            ->putJson('/api/grading-scale', ['rows' => GradingScale::defaultRows()])
            ->assertForbidden();
    }

    public function test_scale_rejects_gaps_and_overlapping_grade_ranges(): void
    {
        $registrar = User::factory()->create([
            'role' => 'registrar',
            'api_token' => 'grading-scale-validation-token',
        ]);

        $invalidScales = [
            [
                ['minimum' => 71, 'maximum' => 100, 'gradePoint' => '1.00', 'remarks' => 'PASSED'],
                ['minimum' => 0, 'maximum' => 69, 'gradePoint' => '5.00', 'remarks' => 'FAILED'],
            ],
            [
                ['minimum' => 70, 'maximum' => 100, 'gradePoint' => '1.00', 'remarks' => 'PASSED'],
                ['minimum' => 0, 'maximum' => 70, 'gradePoint' => '5.00', 'remarks' => 'FAILED'],
            ],
        ];

        foreach ($invalidScales as $rows) {
            $this->withToken($registrar->api_token)
                ->putJson('/api/grading-scale', ['rows' => $rows])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('rows');
        }

        $this->assertDatabaseCount('grading_scales', 0);
    }
}
