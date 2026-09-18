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
            'remarks' => 'Passed',
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

    public function test_schedule_release_publishes_only_complete_approved_grade_sheets(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $professor = User::factory()->create(['role' => 'professor']);

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

        $request = Request::create('/api/grade-schedules/' . $schedule->id . '/release', 'PATCH');
        $request->attributes->set('auth_user', $registrar);

        (new GradeScheduleController())->release($request, $schedule);

        $this->assertSame('Published', $completeSheet->fresh()->status);
        $this->assertSame('Approved', $incompleteSheet->fresh()->status);
    }
}
