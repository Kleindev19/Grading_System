<?php

namespace Tests\Feature;

use App\Http\Controllers\GradeSheetController;
use App\Http\Controllers\GradeScheduleController;
use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GradeReleaseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_response_does_not_expose_api_token(): void
    {
        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'private-professor-token',
        ]);

        $this->withToken($professor->api_token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $professor->id)
            ->assertJsonPath('user.role', 'professor')
            ->assertJsonMissingPath('user.api_token');
    }

    public function test_student_sees_grade_immediately_after_it_is_published(): void
    {
        $registrarUsername = 'registrar_' . uniqid();
        $professorUsername = 'prof_' . uniqid();
        $studentUsername = 'student_' . uniqid();

        $registrar = User::create([
            'name' => 'Registrar',
            'username' => $registrarUsername,
            'email' => $registrarUsername . '@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'registrar',
            'student_id' => null,
            'email_verified_at' => now(),
        ]);

        $professor = User::create([
            'name' => 'Professor',
            'username' => $professorUsername,
            'email' => $professorUsername . '@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'professor',
            'student_id' => null,
            'email_verified_at' => now(),
        ]);

        $student = User::create([
            'name' => 'Student One',
            'username' => $studentUsername,
            'email' => $studentUsername . '@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'student',
            'student_id' => 'S-001',
            'email_verified_at' => now(),
        ]);

        $sheet = GradeSheet::create([
            'code' => 'IT101',
            'subject' => 'Programming 1',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Approved',
            'professor_id' => $professor->id,
            'reviewed_by' => $registrar->id,
            'reviewed_at' => now(),
        ]);

        $sheet->studentsList()->create([
            'student_id' => 'S-001',
            'name' => 'Student One',
            'midterm' => '84',
            'finals' => '88',
            'final_grade' => '86',
            'grade_point' => '1.25',
            'remarks' => 'INC',
        ]);

        $registrarRequest = new Request();
        $registrarRequest->attributes->set('auth_user', $registrar);
        $registrarRequest->merge(['status' => 'Published']);

        $response = (new GradeSheetController())->updateStatus($registrarRequest, $sheet);
        $payload = $response->getData(true);

        $this->assertSame('Published', $sheet->fresh()->status);
        $this->assertSame('IT101', $payload['grade_sheet']['code']);

        $studentRequest = new Request();
        $studentRequest->attributes->set('auth_user', $student);

        $studentResponse = (new GradeSheetController())->published($studentRequest);
        $studentPayload = $studentResponse->getData(true);

        $this->assertNotEmpty($studentPayload['grade_sheets']);
        $this->assertSame('IT101', $studentPayload['grade_sheets'][0]['code']);
        $this->assertSame('FAILED', $studentPayload['grade_sheets'][0]['student_records'][0]['remarks']);
    }

    public function test_professor_cannot_submit_incomplete_grades(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $request = Request::create('/api/grade-sheets', 'POST', [
            'code' => 'IT101',
            'subject' => 'Programming 1',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'student_records' => [[
                'id' => 'S-001',
                'name' => 'Student One',
                'midterm' => '',
                'finals' => '',
                'finalGrade' => '',
                'gradePoint' => '',
            ]],
        ]);
        $request->attributes->set('auth_user', $professor);

        $this->expectException(ValidationException::class);
        (new GradeSheetController())->store($request);
    }

    public function test_professor_submission_flows_through_registrar_to_student_with_computed_grades(): void
    {
        $professor = User::factory()->create(['role' => 'professor', 'api_token' => 'professor-flow-token']);
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'registrar-flow-token']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-FLOW-001', 'api_token' => 'student-flow-token']);
        $termScores = ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100];
        $submissionData = [
            'code' => 'FLOW101',
            'subject' => 'Workflow Test',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'student_records' => [[
                'id' => 'S-FLOW-001',
                'name' => $student->name,
                'scores' => [
                    'midterm' => [...$termScores, 'Mid Ex' => 80],
                    'finals' => $termScores,
                ],
            ]],
        ];
        $submissionResponse = $this->withToken($professor->api_token)->postJson('/api/grade-sheets', $submissionData)->assertCreated();
        $submission = $submissionResponse->json('grade_sheet');

        $this->assertSame('92.00', $submission['student_records'][0]['midterm']);
        $this->assertSame('100.00', $submission['student_records'][0]['finals']);
        $this->assertSame('96.80', $submission['student_records'][0]['finalGrade']);
        $this->assertSame('1.25', $submission['student_records'][0]['gradePoint']);
        $this->assertSame('PASSED', $submission['student_records'][0]['remarks']);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-sheets/' . $submission['id'] . '/status', [
                'status' => 'Submitted',
                'rejection_reason' => 'Please review the quiz scores.',
            ])
            ->assertOk()
            ->assertJsonPath('grade_sheet.rejection_reason', 'Please review the quiz scores.');

        $this->withToken($professor->api_token)
            ->getJson('/api/grade-sheets')
            ->assertOk()
            ->assertJsonPath('grade_sheets.0.rejection_reason', 'Please review the quiz scores.');

        $resubmission = $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', $submissionData)
            ->assertCreated()
            ->json('grade_sheet');
        $this->assertNull($resubmission['rejection_reason']);

        foreach (['Approved', 'Published'] as $status) {
            $this->withToken($registrar->api_token)
                ->patchJson('/api/grade-sheets/' . $resubmission['id'] . '/status', ['status' => $status])
                ->assertOk()
                ->assertJsonPath('grade_sheet.status', $status);
        }

        $published = $this->withToken($student->api_token)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->json('grade_sheets');

        $this->assertCount(1, $published);
        $this->assertSame('FLOW101', $published[0]['code']);
        $this->assertSame('96.80', $published[0]['student_records'][0]['finalGrade']);
        $this->assertSame('1.25', $published[0]['student_records'][0]['gradePoint']);
        $this->assertSame('PASSED', $published[0]['student_records'][0]['remarks']);
    }

    public function test_final_grade_submission_is_allowed_through_its_deadline_and_blocked_after_it(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'deadline-registrar-token']);
        $professor = User::factory()->create(['role' => 'professor', 'api_token' => 'deadline-professor-token']);

        $gradePeriod = GradeSchedule::create([
            'school_year' => '2025-2026',
            'semester' => '1st Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'status' => 'Released',
            'midterm_opens' => now()->subDays(14)->toDateString(),
            'finals_opens' => now()->subDays(7)->toDateString(),
            'submit_grade_deadline' => today()->toDateString(),
            'midterm_locked' => true,
            'finals_locked' => true,
            'created_by' => $registrar->id,
        ]);

        $submission = [
            'code' => 'DEADLINE101',
            'subject' => 'Deadline Test',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester 2025-2026',
            'students' => 1,
            'units' => 3,
            'student_records' => [[
                'id' => 'S-DEADLINE-001',
                'name' => 'Deadline Student',
                'scores' => [
                    'midterm' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100],
                    'finals' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100],
                ],
            ]],
        ];

        $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', $submission)
            ->assertCreated();

        $this->travelTo(now()->addDay());

        $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', $submission)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The final-grade submission deadline has passed.');

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/reopen', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', false)
            ->assertJsonPath('schedule.finalsLocked', true);

        $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', $submission)
            ->assertCreated();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/complete', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', true)
            ->assertJsonPath('schedule.finalsLocked', true);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/reopen', ['period' => 'finals'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', true)
            ->assertJsonPath('schedule.finalsLocked', false);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/reopen', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', false)
            ->assertJsonPath('schedule.finalsLocked', false);

        $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', [
                ...$submission,
                'period' => 'finals',
                'student_records' => [[
                    'id' => 'S-DEADLINE-001',
                    'name' => 'Deadline Student',
                    'scores' => ['finals' => $submission['student_records'][0]['scores']['finals']],
                ]],
            ])
            ->assertCreated();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/complete', ['period' => 'finals'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', false)
            ->assertJsonPath('schedule.finalsLocked', true);
    }

    public function test_grade_period_uses_a_single_final_grade_submission_deadline(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'period-registrar-token']);
        $period = [
            'schoolYear' => now()->year . '-' . (now()->year + 1),
            'semester' => '1st Semester',
            'midtermOpens' => now()->toDateString(),
        ];

        $created = $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $period)
            ->assertCreated()
            ->assertJsonPath('schedule.midtermOpens', $period['midtermOpens'])
            ->assertJsonPath('schedule.finalsOpens', null)
            ->assertJsonPath('schedule.submitGradeDeadline', null)
            ->assertJsonPath('schedule.midtermLocked', false)
            ->assertJsonPath('schedule.finalsLocked', false)
            ->assertJsonMissingPath('schedule.midtermDeadline')
            ->assertJsonMissingPath('schedule.finalsDeadline')
            ->json('schedule');

        $this->assertDatabaseHas('grade_schedules', [
            'school_year' => $period['schoolYear'],
            'semester' => $period['semester'],
        ]);
        $this->assertSame(
            null,
            GradeSchedule::query()->whereKey($created['id'])->firstOrFail()->submit_grade_deadline?->toDateString(),
        );

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $created['id'], [
                ...$period,
                'finalsOpens' => now()->addWeek()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Mark Midterm complete before setting the Finals opening date.');

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $created['id'] . '/complete', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', true);

        $finalsOpens = now()->addWeek()->toDateString();
        $updated = $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $created['id'], [
                ...$period,
                'finalsOpens' => $finalsOpens,
            ])
            ->assertOk()
            ->assertJsonPath('schedule.finalsOpens', $finalsOpens)
            ->json('schedule');

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $period)
            ->assertUnprocessable()
            ->assertJsonPath('message', "A Grade Period already exists for 1st Semester, A.Y. {$period['schoolYear']}. Edit the existing schedule instead.");

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', [
                ...$period,
                'schoolYear' => (now()->year - 1) . '-' . now()->year,
                'semester' => '2nd Semester',
            ])
            ->assertUnprocessable();

        $deadline = now()->addWeeks(2)->toDateString();
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $updated['id'], [
                ...$period,
                'finalsOpens' => $finalsOpens,
                'submitGradeDeadline' => $deadline,
            ])
            ->assertOk()
            ->assertJsonPath('schedule.submitGradeDeadline', $deadline);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $updated['id'] . '/complete', ['period' => 'finals'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Finals cannot be completed before it opens.');

        $this->travelTo(now()->addWeek());
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $updated['id'] . '/complete', ['period' => 'finals'])
            ->assertOk()
            ->assertJsonPath('schedule.finalsLocked', true);

        $existing = GradeSchedule::query()->where('school_year', $period['schoolYear'])->firstOrFail();
        $updatedDeadline = now()->addWeeks(3)->toDateString();
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $existing->id, [
                ...$period,
                'finalsOpens' => $finalsOpens,
                'submitGradeDeadline' => $updatedDeadline,
            ])
            ->assertOk()
            ->assertJsonPath('schedule.submitGradeDeadline', $updatedDeadline);
    }

    public function test_second_semester_cannot_be_scheduled_until_first_semester_is_complete_and_deadline_passed(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'second-semester-registrar-token']);
        $schoolYear = now('Asia/Manila')->year . '-' . (now('Asia/Manila')->year + 1);
        $firstSemester = GradeSchedule::create([
            'school_year' => $schoolYear,
            'semester' => '1st Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => now()->subDays(10)->toDateString(),
            'finals_opens' => now()->subDays(2)->toDateString(),
            'submit_grade_deadline' => now()->addDay()->toDateString(),
            'midterm_locked' => true,
            'status' => 'Released',
            'created_by' => $registrar->id,
        ]);
        $secondSemester = [
            'schoolYear' => $schoolYear,
            'semester' => '2nd Semester',
            'midtermOpens' => now()->toDateString(),
        ];

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $secondSemester)
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                "The 2nd Semester for A.Y. {$schoolYear} can be scheduled after the 1st Semester Finals are complete and its Submit of Grade Deadline has passed.",
            );

        $firstSemester->update([
            'finals_locked' => true,
            'submit_grade_deadline' => now()->subDay()->toDateString(),
        ]);

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $secondSemester)
            ->assertCreated()
            ->assertJsonPath('schedule.semester', '2nd Semester');
    }

    public function test_next_school_year_opens_after_previous_second_semester_finals_are_locked(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'next-year-registrar-token']);
        $currentYear = now('Asia/Manila')->year;
        $currentSchoolYear = "{$currentYear}-" . ($currentYear + 1);
        $nextSchoolYear = ($currentYear + 1) . '-' . ($currentYear + 2);
        $nextYearPeriod = [
            'schoolYear' => $nextSchoolYear,
            'semester' => '1st Semester',
            'midtermOpens' => now()->toDateString(),
        ];

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $nextYearPeriod)
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                "The {$nextSchoolYear} school year can be scheduled after Finals for the 2nd Semester of A.Y. {$currentSchoolYear} is marked complete.",
            );

        GradeSchedule::create([
            'school_year' => $currentSchoolYear,
            'semester' => '2nd Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => now()->subMonths(2)->toDateString(),
            'finals_opens' => now()->subMonth()->toDateString(),
            'submit_grade_deadline' => today()->toDateString(),
            'midterm_locked' => true,
            'finals_locked' => true,
            'status' => 'Released',
            'created_by' => $registrar->id,
        ]);

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $nextYearPeriod)
            ->assertCreated()
            ->assertJsonPath('schedule.schoolYear', $nextSchoolYear)
            ->json('schedule');

        $futurePeriod = GradeSchedule::query()->where('school_year', $nextSchoolYear)->firstOrFail()->toArray();
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $futurePeriod['id'] . '/complete', ['period' => 'midterm'])
            ->assertOk();

        $finalsOpens = today()->toDateString();
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $futurePeriod['id'], [
                ...$nextYearPeriod,
                'finalsOpens' => $finalsOpens,
            ])
            ->assertOk();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $futurePeriod['id'] . '/complete', ['period' => 'finals'])
            ->assertOk();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $futurePeriod['id'], [
                ...$nextYearPeriod,
                'finalsOpens' => $finalsOpens,
                'submitGradeDeadline' => now()->addYear()->addMonths(3)->toDateString(),
            ])
            ->assertOk();
    }

    public function test_next_school_year_is_available_as_soon_as_previous_second_semester_finals_are_locked(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'calendar-year-registrar-token']);
        $currentYear = now('Asia/Manila')->year;
        $currentSchoolYear = "{$currentYear}-" . ($currentYear + 1);
        $nextSchoolYear = ($currentYear + 1) . '-' . ($currentYear + 2);
        $previousDeadline = now()->addYear()->addDays(2)->toDateString();

        GradeSchedule::create([
            'school_year' => $currentSchoolYear,
            'semester' => '2nd Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => now()->addMonths(10)->toDateString(),
            'finals_opens' => now()->addMonths(11)->toDateString(),
            'submit_grade_deadline' => $previousDeadline,
            'midterm_locked' => true,
            'finals_locked' => true,
            'status' => 'Released',
            'created_by' => $registrar->id,
        ]);

        $this->travelTo(now()->addYear());
        $nextYearPeriod = [
            'schoolYear' => $nextSchoolYear,
            'semester' => '1st Semester',
            'midtermOpens' => now()->addMonth()->toDateString(),
        ];

        $this->withToken($registrar->api_token)
            ->postJson('/api/grade-periods', $nextYearPeriod)
            ->assertCreated()
            ->assertJsonPath('schedule.schoolYear', $nextSchoolYear);
    }

    public function test_grade_period_stages_unlock_only_after_registrar_marks_each_period_complete(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(12, 0));
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'locked-period-registrar-token']);
        $period = GradeSchedule::create([
            'school_year' => now()->year . '-' . (now()->year + 1),
            'semester' => '1st Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => today()->toDateString(),
            'status' => 'Released',
            'created_by' => $registrar->id,
        ]);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id, [
                'schoolYear' => $period->school_year,
                'semester' => $period->semester,
                'midtermOpens' => $period->midterm_opens->toDateString(),
                'finalsOpens' => today()->toDateString(),
                'submitGradeDeadline' => today()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Mark Midterm complete before setting the Finals opening date.');

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id . '/complete', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', true);

        $finalsOpen = now()->addDay()->toDateString();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id, [
                'schoolYear' => $period->school_year,
                'semester' => $period->semester,
                'midtermOpens' => $period->midterm_opens->toDateString(),
                'finalsOpens' => $finalsOpen,
            ])
            ->assertOk()
            ->assertJsonPath('schedule.finalsOpens', $finalsOpen);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id, [
                'schoolYear' => $period->school_year,
                'semester' => $period->semester,
                'midtermOpens' => $period->midterm_opens->toDateString(),
                'finalsOpens' => $finalsOpen,
                'submitGradeDeadline' => now()->addWeeks(2)->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('schedule.submitGradeDeadline', now()->addWeeks(2)->toDateString());

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id . '/complete', ['period' => 'finals'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Finals cannot be completed before it opens.');

        $this->travelTo(now()->addDay());
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id . '/complete', ['period' => 'finals'])
            ->assertOk()
            ->assertJsonPath('schedule.finalsLocked', true);

        $deadline = now()->addDay()->toDateString();
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id, [
                'schoolYear' => $period->school_year,
                'semester' => $period->semester,
                'midtermOpens' => $period->midterm_opens->toDateString(),
                'finalsOpens' => $finalsOpen,
                'submitGradeDeadline' => $deadline,
            ])
            ->assertOk()
            ->assertJsonPath('schedule.submitGradeDeadline', $deadline);

        $this->travelTo(now()->addDays(2));
        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $period->id, [
                'schoolYear' => $period->school_year,
                'semester' => $period->semester,
                'midtermOpens' => $period->midterm_opens->toDateString(),
                'finalsOpens' => $finalsOpen,
                'submitGradeDeadline' => now()->addWeek()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Completed academic years cannot be edited.');

        $this->withToken($registrar->api_token)
            ->deleteJson('/api/grade-periods/' . $period->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Completed academic years cannot be edited.');

        $historicalPeriod = GradeSchedule::create([
            'school_year' => (now()->year - 1) . '-' . now()->year,
            'semester' => '2nd Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'midterm_opens' => now()->subMonths(8)->toDateString(),
            'finals_opens' => now()->subMonths(6)->toDateString(),
            'submit_grade_deadline' => now()->subMonths(5)->toDateString(),
            'midterm_locked' => true,
            'finals_locked' => true,
            'status' => 'Released',
            'created_by' => $registrar->id,
        ]);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $historicalPeriod->id, [
                'schoolYear' => $historicalPeriod->school_year,
                'semester' => $historicalPeriod->semester,
                'midtermOpens' => now()->subMonths(7)->toDateString(),
                'finalsOpens' => $historicalPeriod->finals_opens->toDateString(),
                'submitGradeDeadline' => $historicalPeriod->submit_grade_deadline->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Completed academic years cannot be edited.');
    }

    public function test_midterm_and_finals_can_be_submitted_separately_and_second_semester_stays_separate(): void
    {
        $professor = User::factory()->create(['role' => 'professor', 'api_token' => 'separate-period-professor-token']);
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'separate-period-registrar-token']);
        $termScores = ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 80];
        $sheetData = [
            'code' => 'TWOTERM101',
            'subject' => 'Two Term Course',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester 2025-2026',
            'students' => 1,
            'units' => 3,
            'student_records' => [[
                'id' => 'S-TWO-TERM',
                'name' => 'Two Term Student',
                'scores' => ['midterm' => $termScores],
            ]],
        ];

        $gradePeriod = GradeSchedule::create([
            'school_year' => '2025-2026',
            'semester' => '1st Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'status' => 'Released',
            'midterm_opens' => now()->subDay()->toDateString(),
            'finals_opens' => now()->subDay()->toDateString(),
            'submit_grade_deadline' => now()->addWeek()->toDateString(),
            'created_by' => $registrar->id,
        ]);

        $midtermSubmission = $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', [...$sheetData, 'period' => 'midterm'])
            ->assertCreated()
            ->json('grade_sheet');

        $this->assertSame('92.00', $midtermSubmission['student_records'][0]['midterm']);
        $this->assertSame('', $midtermSubmission['student_records'][0]['finals']);
        $this->assertSame('', $midtermSubmission['student_records'][0]['finalGrade']);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-sheets/' . $midtermSubmission['id'] . '/status', ['status' => 'Approved'])
            ->assertUnprocessable();

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-periods/' . $gradePeriod->id . '/complete', ['period' => 'midterm'])
            ->assertOk()
            ->assertJsonPath('schedule.midtermLocked', true);

        $finalsSubmission = $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', [
                ...$sheetData,
                'period' => 'finals',
                'student_records' => [[
                    'id' => 'S-TWO-TERM',
                    'name' => 'Two Term Student',
                    'scores' => ['finals' => ['Q1' => 100, 'L1' => 100, 'R1' => 100, 'A1' => 100, 'Mid Ex' => 100]],
                ]],
            ])
            ->assertCreated()
            ->json('grade_sheet');

        $this->assertSame($midtermSubmission['id'], $finalsSubmission['id']);
        $this->assertSame('92.00', $finalsSubmission['student_records'][0]['midterm']);
        $this->assertSame('100.00', $finalsSubmission['student_records'][0]['finals']);
        $this->assertSame('96.80', $finalsSubmission['student_records'][0]['finalGrade']);
        $this->assertSame('1.25', $finalsSubmission['student_records'][0]['gradePoint']);

        GradeSchedule::create([
            'school_year' => '2025-2026',
            'semester' => '2nd Semester',
            'year_level' => 'Grade Period',
            'courses' => [],
            'status' => 'Released',
            'finals_locked' => true,
            'submit_grade_deadline' => now()->addDays(7)->toDateString(),
            'created_by' => $registrar->id,
        ]);

        $secondSemester = $this->withToken($professor->api_token)
            ->postJson('/api/grade-sheets', [
                ...$sheetData,
                'semester' => '2nd Semester 2025-2026',
                'period' => 'both',
                'student_records' => [[
                    'id' => 'S-TWO-TERM',
                    'name' => 'Two Term Student',
                    'scores' => ['midterm' => $termScores, 'finals' => $termScores],
                ]],
            ])
            ->assertCreated()
            ->json('grade_sheet');

        $this->assertNotSame($finalsSubmission['id'], $secondSemester['id']);
        $this->assertSame('2nd Semester 2025-2026', $secondSemester['semester']);
        $this->assertDatabaseCount('grade_sheets', 2);
    }

    public function test_student_receives_only_scheduled_courses_they_are_enrolled_in(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $professor = User::factory()->create(['role' => 'professor']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => 'S-ENROLLED-001',
            'api_token' => 'student-enrollment-schedules-token',
        ]);
        $otherStudent = User::factory()->create(['role' => 'student', 'student_id' => 'S-OTHER-002']);

        $ownSheet = GradeSheet::create([
            'code' => 'OWN101', 'subject' => 'Own Course', 'section' => 'BSIT - 1A',
            'semester' => '1st Semester', 'students' => 1, 'units' => 3,
            'submitted' => now()->toDateString(), 'status' => 'Approved', 'professor_id' => $professor->id,
        ]);
        $ownSheet->studentsList()->create(['student_id' => $student->student_id, 'name' => $student->name]);

        $otherSheet = GradeSheet::create([
            'code' => 'OTHER101', 'subject' => 'Other Course', 'section' => 'BSIT - 1B',
            'semester' => '1st Semester', 'students' => 1, 'units' => 3,
            'submitted' => now()->toDateString(), 'status' => 'Approved', 'professor_id' => $professor->id,
        ]);
        $otherSheet->studentsList()->create(['student_id' => $otherStudent->student_id, 'name' => $otherStudent->name]);

        GradeSchedule::create([
            'school_year' => '2025-2026', 'semester' => '1st Semester', 'year_level' => '1st Year',
            'courses' => ['sheet:' . $ownSheet->id, 'sheet:' . $otherSheet->id],
            'status' => 'Scheduled', 'created_by' => $registrar->id,
        ]);
        GradeSchedule::create([
            'school_year' => '2025-2026', 'semester' => '1st Semester', 'year_level' => 'Grade Period',
            'courses' => [], 'status' => 'Released', 'midterm_opens' => now()->addDay()->toDateString(), 'created_by' => $registrar->id,
        ]);

        $this->withToken($student->api_token)
            ->getJson('/api/grade-schedules')
            ->assertOk()
            ->assertJsonCount(2, 'schedules')
            ->assertJsonFragment(['yearLevel' => 'Grade Period', 'courses' => []])
            ->assertJsonFragment(['midtermOpens' => now()->addDay()->toDateString()])
            ->assertJsonFragment(['courses' => ['sheet:' . $ownSheet->id]])
            ->assertJsonMissing(['courses' => ['sheet:' . $otherSheet->id]]);
    }

    public function test_published_grade_lookup_supports_student_records_keyed_by_username(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => 'S-MOTHER-001',
            'username' => 'mother-user-001',
            'api_token' => 'mother-student-token',
        ]);
        $sheet = GradeSheet::create([
            'code' => 'MOTHER101',
            'subject' => 'Mother System Course',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester 2025-2026',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Published',
            'professor_id' => $professor->id,
        ]);
        $sheet->studentsList()->create([
            'student_id' => $student->username,
            'name' => $student->name,
            'midterm' => '88',
            'finals' => '91',
            'final_grade' => '90',
            'grade_point' => '1.50',
            'remarks' => 'PASSED',
        ]);

        $this->withToken($student->api_token)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->assertJsonPath('grade_sheets.0.student_records.0.id', $student->username)
            ->assertJsonPath('grade_sheets.0.student_records.0.finalGrade', '90');
    }

    public function test_registrar_cannot_publish_incomplete_grades(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $professor = User::factory()->create(['role' => 'professor']);
        $sheet = GradeSheet::create([
            'code' => 'IT101',
            'subject' => 'Programming 1',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Approved',
            'professor_id' => $professor->id,
        ]);
        $sheet->studentsList()->create([
            'student_id' => 'S-001',
            'name' => 'Student One',
            'midterm' => '',
            'finals' => '88',
            'final_grade' => '86',
            'grade_point' => '1.25',
        ]);

        $request = Request::create('/api/grade-sheets/' . $sheet->id . '/status', 'PATCH', ['status' => 'Published']);
        $request->attributes->set('auth_user', $registrar);

        $response = (new GradeSheetController())->updateStatus($request, $sheet);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Approved', $sheet->fresh()->status);
    }

    public function test_registrar_can_return_incomplete_approved_sheet_but_not_complete_approved_sheet(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'registrar-recovery-token']);
        $professor = User::factory()->create(['role' => 'professor']);
        $incompleteSheet = GradeSheet::create([
            'code' => 'RECOVER101', 'subject' => 'Recovery Subject', 'section' => 'BSIT - 1A',
            'semester' => '1st Semester', 'students' => 1, 'units' => 3,
            'submitted' => now()->toDateString(), 'status' => 'Approved', 'professor_id' => $professor->id,
        ]);
        $incompleteSheet->studentsList()->create([
            'student_id' => 'S-RECOVER', 'name' => 'Recovery Student', 'midterm' => '',
            'finals' => '90', 'final_grade' => '90', 'grade_point' => '1.50',
        ]);
        $completeSheet = GradeSheet::create([
            'code' => 'COMPLETE101', 'subject' => 'Complete Subject', 'section' => 'BSIT - 1A',
            'semester' => '1st Semester', 'students' => 1, 'units' => 3,
            'submitted' => now()->toDateString(), 'status' => 'Approved', 'professor_id' => $professor->id,
        ]);
        $completeSheet->studentsList()->create([
            'student_id' => 'S-COMPLETE', 'name' => 'Complete Student', 'midterm' => '90',
            'finals' => '90', 'final_grade' => '90', 'grade_point' => '1.50',
        ]);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-sheets/' . $incompleteSheet->id . '/status', [
                'status' => 'Submitted', 'rejection_reason' => 'Please complete the midterm grade.',
            ])
            ->assertOk()
            ->assertJsonPath('grade_sheet.status', 'Submitted')
            ->assertJsonPath('grade_sheet.rejection_reason', 'Please complete the midterm grade.');

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-sheets/' . $completeSheet->id . '/status', [
                'status' => 'Submitted', 'rejection_reason' => 'Return this grade.',
            ])
            ->assertUnprocessable();

        $this->assertSame('Approved', $completeSheet->fresh()->status);
    }

    public function test_incomplete_published_grades_are_returned_and_hidden_from_student(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-001']);
        $sheet = GradeSheet::create([
            'code' => 'IT101',
            'subject' => 'Programming 1',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Published',
            'professor_id' => $professor->id,
        ]);
        $sheet->studentsList()->create([
            'student_id' => 'S-001',
            'name' => 'Student One',
            'midterm' => '',
            'finals' => '88',
            'final_grade' => '86',
            'grade_point' => '1.25',
        ]);

        $request = Request::create('/api/published-grades', 'GET');
        $request->attributes->set('auth_user', $student);

        $response = (new GradeSheetController())->published($request);

        $this->assertSame([], $response->getData(true)['grade_sheets']);
        $this->assertSame('Submitted', $sheet->fresh()->status);
    }

    public function test_due_schedule_publishes_approved_grades_when_a_student_checks_them(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $professor = User::factory()->create(['role' => 'professor']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-DUE', 'api_token' => 'due-student-token']);

        $sheet = GradeSheet::create([
            'code' => 'DUE101',
            'subject' => 'Due Subject',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Approved',
            'professor_id' => $professor->id,
        ]);
        $sheet->studentsList()->create([
            'student_id' => 'S-DUE',
            'name' => 'Due Student',
            'midterm' => '85',
            'finals' => '90',
            'final_grade' => '88',
            'grade_point' => '1.50',
        ]);

        $schedule = GradeSchedule::create([
            'school_year' => '2025-2026',
            'semester' => '1st Semester',
            'year_level' => '1st Year',
            'courses' => ['sheet:' . $sheet->id],
            'released_date' => now()->subDay()->toDateString(),
            'status' => 'Scheduled',
            'created_by' => $registrar->id,
        ]);

        $this->withToken($student->api_token)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->assertJsonPath('grade_sheets.0.code', 'DUE101');

        $this->assertSame('Released', $schedule->fresh()->status);
        $this->assertSame('Published', $sheet->fresh()->status);
    }

    public function test_schedule_release_publishes_only_complete_approved_grade_sheets(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar', 'api_token' => 'schedule-registrar-token']);
        $professor = User::factory()->create(['role' => 'professor']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-COMPLETE', 'api_token' => 'schedule-student-token']);

        $completeSheet = GradeSheet::create([
            'code' => 'COMPLETE101',
            'subject' => 'Complete Subject',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Approved',
            'professor_id' => $professor->id,
        ]);
        $completeSheet->studentsList()->create([
            'student_id' => 'S-COMPLETE',
            'name' => 'Complete Student',
            'midterm' => '85',
            'finals' => '90',
            'final_grade' => '88',
            'grade_point' => '1.50',
        ]);

        $incompleteSheet = GradeSheet::create([
            'code' => 'INCOMPLETE101',
            'subject' => 'Incomplete Subject',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Approved',
            'professor_id' => $professor->id,
        ]);
        $incompleteSheet->studentsList()->create([
            'student_id' => 'S-INCOMPLETE',
            'name' => 'Incomplete Student',
            'midterm' => '',
            'finals' => '90',
            'final_grade' => '88',
            'grade_point' => '1.50',
        ]);

        $schedule = GradeSchedule::create([
            'school_year' => '2025-2026',
            'semester' => '1st Semester',
            'year_level' => '1st Year',
            'courses' => ['sheet:' . $completeSheet->id, 'sheet:' . $incompleteSheet->id],
            'status' => 'Scheduled',
            'created_by' => $registrar->id,
        ]);

        $this->withToken($registrar->api_token)
            ->patchJson('/api/grade-schedules/' . $schedule->id . '/release')
            ->assertOk()
            ->assertJsonPath('schedule.status', 'Released');

        $this->assertSame('Published', $completeSheet->fresh()->status);
        $this->assertSame('Approved', $incompleteSheet->fresh()->status);

        $this->withToken($student->api_token)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->assertJsonPath('grade_sheets.0.code', 'COMPLETE101');
    }
}
