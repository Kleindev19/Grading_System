<?php

namespace Tests\Feature;

use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_can_log_in_after_seeding(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        foreach (['23-00001', 'mock.professor', 'mock.registrar'] as $username) {
            $this->postJson('/api/login', [
                'username' => $username,
                'password' => 'Password123!',
            ])->assertOk()
                ->assertJsonPath('user.username', $username);
        }
    }

    public function test_demo_data_connects_the_student_to_the_professor_and_workflow(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        $student = User::where('username', '23-00001')->firstOrFail();
        $professor = User::where('username', 'mock.professor')->firstOrFail();
        $registrar = User::where('username', 'mock.registrar')->firstOrFail();
        $professorToken = $this->postJson('/api/login', [
            'username' => $professor->username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');
        $registrarToken = $this->postJson('/api/login', [
            'username' => $registrar->username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');
        $studentToken = $this->postJson('/api/login', [
            'username' => $student->username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');

        $this->withToken($professorToken)
            ->getJson('/api/grade-sheets')
            ->assertOk()
            ->assertJsonFragment(['code' => 'MOCK101', 'status' => 'Submitted'])
            ->assertJsonFragment(['code' => 'MOCK-READY', 'status' => 'Approved'])
            ->assertJsonFragment(['code' => 'MOCK-PUBLISHED', 'status' => 'Published']);

        $this->withToken($registrarToken)
            ->getJson('/api/grade-sheets')
            ->assertOk()
            ->assertJsonFragment(['code' => 'MOCK-INCOMPLETE', 'status' => 'Submitted']);

        $this->withToken($studentToken)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->assertJsonPath('grade_sheets.0.code', 'MOCK-PUBLISHED')
            ->assertJsonPath('grade_sheets.0.student_records.0.id', '23-00001');

        $this->assertDatabaseHas('grade_sheet_students', [
            'student_id' => $student->student_id,
            'name' => $student->name,
        ]);
        $this->assertDatabaseHas('grade_schedules', [
            'year_level' => 'Grade Period',
            'semester' => '1st Semester',
            'submit_grade_locked' => false,
        ]);
        $this->assertSame(5, GradeSheet::where('professor_id', $professor->id)->count());
        $this->assertGreaterThanOrEqual(2, GradeSchedule::where('created_by', $registrar->id)->count());
    }

    public function test_registrar_student_grade_lookup_returns_only_the_selected_students_published_record(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        $registrar = User::where('username', 'mock.registrar')->firstOrFail();
        $token = $this->postJson('/api/login', [
            'username' => $registrar->username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');

        $sheet = GradeSheet::where('code', 'MOCK-PUBLISHED')->firstOrFail();
        $sheet->studentsList()->where('student_id', '23-00002')->update([
            'midterm' => '82',
            'finals' => '86',
            'final_grade' => '84.40',
            'grade_point' => '2.25',
            'remarks' => 'PASSED',
        ]);

        $this->withToken($token)
            ->getJson('/api/students/23-00002/grades')
            ->assertOk()
            ->assertJsonCount(1, 'grades')
            ->assertJsonPath('grades.0.code', 'MOCK-PUBLISHED')
            ->assertJsonPath('grades.0.midterm', '82')
            ->assertJsonPath('grades.0.finals', '86')
            ->assertJsonPath('grades.0.finalGrade', '84.40')
            ->assertJsonPath('grades.0.gradePoint', '2.25');
    }

    public function test_registrar_student_grade_lookup_supports_grade_records_keyed_by_student_username(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        $registrar = User::where('username', 'mock.registrar')->firstOrFail();
        $token = $this->postJson('/api/login', [
            'username' => $registrar->username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');

        User::factory()->create([
            'role' => 'student',
            'student_id' => '23-00002',
            'username' => 'alex.sample.account',
        ]);
        $sheet = GradeSheet::where('code', 'MOCK-PUBLISHED')->firstOrFail();
        $sheet->studentsList()->where('student_id', '23-00002')->update(['student_id' => 'alex.sample.account']);

        $this->withToken($token)
            ->getJson('/api/students/23-00002/grades')
            ->assertOk()
            ->assertJsonCount(1, 'grades')
            ->assertJsonPath('grades.0.midterm', '88');
    }

    public function test_demo_professor_can_submit_registrar_can_approve_and_publish_student_grades(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        $professor = User::where('username', 'mock.professor')->firstOrFail();
        $registrar = User::where('username', 'mock.registrar')->firstOrFail();
        $student = User::where('username', '23-00001')->firstOrFail();

        $login = fn (string $username): string => $this->postJson('/api/login', [
            'username' => $username,
            'password' => 'Password123!',
        ])->assertOk()->json('token');

        $professorToken = $login($professor->username);
        $registrarToken = $login($registrar->username);
        $studentToken = $login($student->username);
        $scores = [
            'midterm' => ['Q1' => 18, 'Q2' => 17, 'Q3' => 19, 'Q4' => 18, 'Q5' => 20, 'L1' => 45, 'A1' => 48, 'A2' => 46, 'A3' => 50, 'A4' => 47, 'R1' => 88, 'Mid Ex' => 90],
            'finals' => ['Q1' => 19, 'Q2' => 18, 'Q3' => 20, 'Q4' => 19, 'Q5' => 18, 'L1' => 46, 'A1' => 49, 'A2' => 47, 'A3' => 50, 'A4' => 48, 'R1' => 90, 'Mid Ex' => 92],
        ];
        $records = [
            ['id' => '23-00001', 'name' => 'Mock Student', 'scores' => $scores],
            ['id' => '23-00002', 'name' => 'Alex Sample', 'scores' => $scores],
            ['id' => '23-00003', 'name' => 'Jamie Example', 'scores' => $scores],
        ];

        $sheet = $this->withToken($professorToken)
            ->postJson('/api/grade-sheets', [
                'code' => 'MOCK101',
                'subject' => 'Mock Business Law',
                'section' => 'BSBA - 1A',
                'semester' => '1st Semester ' . now('Asia/Manila')->year . '-' . (now('Asia/Manila')->year + 1),
                'students' => count($records),
                'units' => 3,
                'student_records' => $records,
            ])
            ->assertCreated()
            ->assertJsonPath('grade_sheet.status', 'Submitted')
            ->json('grade_sheet');

        $this->withToken($registrarToken)
            ->patchJson("/api/grade-sheets/{$sheet['id']}/status", ['status' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('grade_sheet.status', 'Approved');

        $schedule = $this->withToken($registrarToken)
            ->postJson('/api/grade-schedules', [
                'schoolYear' => now('Asia/Manila')->year . '-' . (now('Asia/Manila')->year + 1),
                'semester' => '1st Semester',
                'yearLevel' => '1st Year',
                'courses' => ['sheet:' . $sheet['id']],
                'releasedDate' => now('Asia/Manila')->toDateString(),
            ])
            ->assertCreated()
            ->json('schedule');

        $this->withToken($registrarToken)
            ->patchJson("/api/grade-schedules/{$schedule['id']}/release")
            ->assertOk()
            ->assertJsonPath('schedule.status', 'Released');

        $this->withToken($studentToken)
            ->getJson('/api/published-grades')
            ->assertOk()
            ->assertJsonFragment(['code' => 'MOCK101'])
            ->assertJsonFragment(['id' => '23-00001', 'name' => 'Mock Student']);
    }

    public function test_reseeding_demo_data_does_not_reset_a_tested_grade_workflow(): void
    {
        $this->seed(DemoAccountsSeeder::class);
        $professor = User::where('username', 'mock.professor')->firstOrFail();
        $sheet = GradeSheet::where('code', 'MOCK101')->where('professor_id', $professor->id)->firstOrFail();
        $sheet->update(['status' => 'Approved']);

        $this->seed(DemoAccountsSeeder::class);

        $this->assertSame('Approved', $sheet->fresh()->status);
        $this->assertSame(5, GradeSheet::where('professor_id', $professor->id)->count());
    }

    public function test_vercel_origin_is_allowed_for_api_preflight_requests(): void
    {
        config()->set('cors.allowed_origins', ['https://gradingsystem05.vercel.app']);

        $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://gradingsystem05.vercel.app',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])->assertNoContent()
            ->assertHeader('access-control-allow-origin', 'https://gradingsystem05.vercel.app');
    }

    public function test_known_vercel_origins_remain_in_the_default_cors_allowlist(): void
    {
        $this->assertContains('https://gradingsystem05.vercel.app', config('cors.allowed_origins'));
        $this->assertContains('https://grading-system-gamma-six.vercel.app', config('cors.allowed_origins'));
        $this->assertContains(
        'https://grading-system-cdm-kleins-projects-246b3510.vercel.app',
        config('cors.allowed_origins'),
        );
    }

    public function test_demo_accounts_seeding_is_disabled_in_production_by_default(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.seed_demo_accounts', false);

        $this->expectException(\RuntimeException::class);

        $this->seed(DemoAccountsSeeder::class);
    }
}
