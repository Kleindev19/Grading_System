<?php

namespace Tests\Feature;

use App\Http\Controllers\GradeSheetController;
use App\Models\GradeSheet;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class GradeReleaseVisibilityTest extends TestCase
{
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
}
