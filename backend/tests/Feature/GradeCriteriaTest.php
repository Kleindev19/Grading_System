<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeCriteriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_save_and_load_course_criteria(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'criteria-professor-token',
        ]);
        $criteria = [
            'midterm' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 25, 'maxScore' => 100],
                'recitation' => ['weight' => 10, 'maxScore' => 100],
                'assignment' => ['weight' => 0, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
                'custom:project' => ['weight' => 10, 'maxScore' => 50],
            ],
            'finals' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 5, 'maxScore' => 100],
                'recitation' => ['weight' => 20, 'maxScore' => 100],
                'assignment' => ['weight' => 20, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
            ],
        ];

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-criteria', [
                'code' => 'CRIT101',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'criteria' => $criteria,
                'assessment_headers' => ['Q1', 'A2: Project'],
            ])
            ->assertOk()
            ->assertJsonPath('criteria.midterm.custom:project.weight', 10);

        $this->withToken($professor->api_token)
            ->getJson('/api/grade-criteria?code=CRIT101&section=BSIT%20-%201A&semester=1st%20Semester')
            ->assertOk()
            ->assertJsonPath('criteria.midterm.custom:project.maxScore', 50)
            ->assertJsonPath('assessment_headers.midterm.1', 'A2: Project')
            ->assertJsonPath('assessment_headers.finals.1', 'A2: Project');
    }

    public function test_professor_can_save_separate_midterm_and_finals_assessments(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'separate-criteria-token',
        ]);
        $criteria = [
            'midterm' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 25, 'maxScore' => 100],
                'recitation' => ['weight' => 10, 'maxScore' => 100],
                'assignment' => ['weight' => 10, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
                'custom:project' => ['weight' => 0, 'maxScore' => 100],
            ],
            'finals' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 5, 'maxScore' => 100],
                'recitation' => ['weight' => 20, 'maxScore' => 100],
                'assignment' => ['weight' => 20, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
            ],
        ];

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-criteria', [
                'code' => 'SEPARATE101',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'criteria' => $criteria,
                'assessment_headers' => [
                    'midterm' => ['Q1', 'A2: Project'],
                    'finals' => ['Q1'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('assessment_headers.midterm.1', 'A2: Project')
            ->assertJsonPath('assessment_headers.finals', ['Q1']);
    }

    public function test_professor_can_save_restore_and_delete_a_grade_draft(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'grade-draft-token',
        ]);
        $course = [
            'code' => 'DRAFT101',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
        ];
        $draft = [
            ...$course,
            'subject' => 'Draft Course',
            'units' => 3,
            'assessment_headers' => [
                'midterm' => ['Q1', 'A2: Project'],
                'finals' => ['Q1'],
            ],
            'student_records' => [[
                'id' => 'DRAFT-STUDENT',
                'name' => 'Draft Student',
                'scores' => [
                    'midterm' => ['Q1' => 85, 'A2: Project' => 40],
                    'finals' => ['Q1' => 90],
                ],
            ]],
        ];

        $this->withToken($professor->api_token)
            ->getJson('/api/grade-drafts?' . http_build_query($course))
            ->assertOk()
            ->assertJsonPath('draft', null);

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-drafts', $draft)
            ->assertOk()
            ->assertJsonPath('message', 'Grade draft saved.');

        $this->withToken($professor->api_token)
            ->getJson('/api/grade-drafts?' . http_build_query($course))
            ->assertOk()
            ->assertJsonPath('draft.assessment_headers.midterm.1', 'A2: Project')
            ->assertJsonPath('draft.student_records.0.scores.midterm.Q1', 85);

        $this->assertDatabaseCount('grade_sheets', 0);

        $this->withToken($professor->api_token)
            ->deleteJson('/api/grade-drafts?' . http_build_query($course))
            ->assertOk();

        $this->withToken($professor->api_token)
            ->getJson('/api/grade-drafts?' . http_build_query($course))
            ->assertOk()
            ->assertJsonPath('draft', null);
    }

    public function test_criteria_must_total_one_hundred_percent(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'criteria-invalid-token',
        ]);

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-criteria', [
                'code' => 'CRIT102',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'criteria' => [
                    'midterm' => ['quiz' => ['weight' => 90, 'maxScore' => 100]],
                    'finals' => ['quiz' => ['weight' => 100, 'maxScore' => 100]],
                ],
                'assessment_headers' => ['Q1'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['criteria.midterm']);
    }

    public function test_assessment_max_score_cannot_exceed_one_hundred(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'criteria-max-token',
        ]);

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-criteria', [
                'code' => 'CRIT-MAX',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'criteria' => [
                    'midterm' => ['quiz' => ['weight' => 100, 'maxScore' => 101]],
                    'finals' => ['quiz' => ['weight' => 100, 'maxScore' => 100]],
                ],
                'assessment_headers' => ['Q1'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('criteria.midterm.quiz.maxScore');
    }

    public function test_grade_drafts_reject_scores_above_one_hundred(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'draft-max-token',
        ]);

        $this->withToken($professor->api_token)
            ->putJson('/api/grade-drafts', [
                'code' => 'DRAFT-MAX',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'subject' => 'Draft Maximum Test',
                'units' => 3,
                'assessment_headers' => ['Q1'],
                'student_records' => [[
                    'id' => 'MAX-STUDENT',
                    'name' => 'Maximum Student',
                    'scores' => [
                        'midterm' => ['Q1' => 101],
                        'finals' => ['Q1' => 100],
                    ],
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('student_records.0.scores.midterm.Q1');

        $this->assertDatabaseCount('grade_drafts', 0);
    }

    public function test_grade_submissions_reject_scores_above_one_hundred(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'submission-max-token',
        ]);

        $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', [
                'code' => 'SHEET-MAX',
                'subject' => 'Submission Maximum Test',
                'section' => 'BSIT - 1A',
                'semester' => '1st Semester',
                'students' => 1,
                'units' => 3,
                'student_records' => [[
                    'id' => 'MAX-STUDENT',
                    'name' => 'Maximum Student',
                    'scores' => [
                        'midterm' => ['Q1' => 101],
                        'finals' => ['Q1' => 100],
                    ],
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('student_records.0.scores.midterm.Q1');

        $this->assertDatabaseCount('grade_sheets', 0);
    }

    public function test_submitted_grades_use_the_saved_weights_and_max_scores(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'criteria-submit-token',
        ]);
        $criteria = [
            'midterm' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 25, 'maxScore' => 100],
                'recitation' => ['weight' => 10, 'maxScore' => 100],
                'assignment' => ['weight' => 0, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
                'custom:project' => ['weight' => 10, 'maxScore' => 50],
            ],
            'finals' => [
                'quiz' => ['weight' => 15, 'maxScore' => 100],
                'lab' => ['weight' => 5, 'maxScore' => 100],
                'recitation' => ['weight' => 20, 'maxScore' => 100],
                'assignment' => ['weight' => 20, 'maxScore' => 100],
                'major_exam' => ['weight' => 40, 'maxScore' => 100],
            ],
        ];
        $this->withToken($professor->api_token)->putJson('/api/grade-criteria', [
            'code' => 'PROJECT101',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'criteria' => $criteria,
            'assessment_headers' => ['Q1', 'L1', 'R1', 'A1', 'Mid Ex', 'A2: Project'],
        ])->assertOk();

        $this->withToken($professor->api_token)->putJson('/api/grade-drafts', [
            'code' => 'PROJECT101',
            'subject' => 'Project Criteria Test',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'units' => 3,
            'assessment_headers' => [
                'midterm' => ['Q1', 'L1', 'R1', 'A1', 'Mid Ex', 'A2: Project'],
                'finals' => ['Q1', 'L1', 'R1', 'A1', 'Mid Ex'],
            ],
            'student_records' => [[
                'id' => 'PROJECT-STUDENT',
                'name' => 'Project Student',
                'scores' => [
                    'midterm' => ['Q1' => 100],
                    'finals' => ['Q1' => 100],
                ],
            ]],
        ])->assertOk();

        $response = $this->withToken($professor->api_token)->postJson('/api/grade-sheets', [
            'code' => 'PROJECT101',
            'subject' => 'Project Criteria Test',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'student_records' => [[
                'id' => 'PROJECT-STUDENT',
                'name' => 'Project Student',
                'scores' => [
                    'midterm' => [
                        'Q1' => 100,
                        'L1' => 100,
                        'R1' => 100,
                        'A1' => 100,
                        'Mid Ex' => 100,
                        'A2: Project' => 25,
                    ],
                    'finals' => [
                        'Q1' => 100,
                        'L1' => 100,
                        'R1' => 100,
                        'A1' => 100,
                        'Mid Ex' => 100,
                    ],
                ],
            ]],
        ])->assertCreated();

        $response->assertJsonPath('grade_sheet.student_records.0.midterm', '95.00')
            ->assertJsonPath('grade_sheet.student_records.0.finals', '100.00')
            ->assertJsonPath('grade_sheet.student_records.0.finalGrade', '98.00');
        $this->assertDatabaseMissing('grade_drafts', ['code' => 'PROJECT101', 'professor_id' => $professor->id]);
    }
}
