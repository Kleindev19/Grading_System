<?php

namespace Database\Seeders;

use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $registrar = User::updateOrCreate(
            ['username' => 'mock.registrar'],
            [
                'name' => 'Mock Registrar',
                'email' => 'mock.registrar@grading.test',
                'password' => Hash::make('Password123!'),
                'role' => 'registrar',
                'email_verified_at' => now(),
            ],
        );

        $professor = User::updateOrCreate(
            ['username' => 'mock.professor'],
            [
                'name' => 'Mock Professor',
                'email' => 'mock.professor@grading.test',
                'password' => Hash::make('Password123!'),
                'role' => 'professor',
                'email_verified_at' => now(),
            ],
        );

        $studentUser = User::updateOrCreate(
            ['username' => '23-00001'],
            [
                'name' => 'Mock Student',
                'email' => 'mock.student@grading.test',
                'password' => Hash::make('Password123!'),
                'role' => 'student',
                'student_id' => '23-00001',
                'email_verified_at' => now(),
            ],
        );

        Student::updateOrCreate(
            ['student_id' => '23-00001'],
            [
                'name' => $studentUser->name,
                'institute' => 'IBE',
                'course' => 'BSBA',
                'year_level' => '1st Year',
                'section' => 'A',
                'status' => 'Active',
                'created_by' => $registrar->id,
            ],
        );

        $sheet = GradeSheet::updateOrCreate(
            ['code' => 'MOCK101', 'section' => 'BSBA - 1A', 'professor_id' => $professor->id],
            [
                'subject' => 'Mock Business Law',
                'semester' => '1st Semester 2025-2026',
                'students' => 1,
                'units' => 3,
                'submitted' => now()->toDateString(),
                'status' => 'Published',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ],
        );

        $sheet->studentsList()->updateOrCreate(
            ['student_id' => '23-00001'],
            [
                'name' => $studentUser->name,
                'midterm' => '88',
                'finals' => '91',
                'final_grade' => '90',
                'grade_point' => '1.50',
                'remarks' => 'Passed',
                'assessment_scores' => ['Q1' => '18', 'Q2' => '19', 'A1' => '45'],
            ],
        );

        $workflowSheets = [];
        foreach ([
            'MOCK-APPROVAL' => ['Submitted', '88'],
            'MOCK-INCOMPLETE' => ['Submitted', ''],
            'MOCK-READY' => ['Approved', '86'],
            'MOCK-SCHEDULED' => ['Approved', '90'],
        ] as $code => [$status, $midterm]) {
            $workflowSheet = GradeSheet::updateOrCreate(
                ['code' => $code, 'section' => 'BSBA - 1A', 'professor_id' => $professor->id],
                [
                    'subject' => 'Mock Business Law - ' . str_replace('MOCK-', '', $code),
                    'semester' => '1st Semester 2026-2027',
                    'students' => 1,
                    'units' => 3,
                    'submitted' => now()->toDateString(),
                    'status' => $status,
                    'reviewed_by' => $status === 'Approved' ? $registrar->id : null,
                    'reviewed_at' => $status === 'Approved' ? now() : null,
                    'rejection_reason' => null,
                ],
            );

            $workflowSheet->studentsList()->updateOrCreate(
                ['student_id' => '23-00001'],
                [
                    'name' => $studentUser->name,
                    'midterm' => $midterm,
                    'finals' => '91',
                    'final_grade' => '90',
                    'grade_point' => '1.50',
                    'remarks' => 'Passed',
                    'assessment_scores' => ['Q1' => '18', 'Q2' => '19', 'A1' => '45'],
                ],
            );

            $workflowSheets[$code] = $workflowSheet;
        }

        GradeSchedule::updateOrCreate(
            ['school_year' => '2025-2026', 'semester' => '1st Semester', 'year_level' => '1st Year', 'created_by' => $registrar->id],
            [
                'courses' => ['MOCK101'],
                'released_date' => now()->toDateString(),
                'status' => 'Scheduled',
                'midterm_opens' => now()->toDateString(),
                'finals_opens' => now()->toDateString(),
            ],
        );

        GradeSchedule::updateOrCreate(
            ['school_year' => '2026-2027', 'semester' => '1st Semester', 'year_level' => '1st Year', 'created_by' => $registrar->id],
            [
                'courses' => ['sheet:' . $workflowSheets['MOCK-SCHEDULED']->id],
                'released_date' => now()->addDay()->toDateString(),
                'status' => 'Scheduled',
                'midterm_opens' => null,
                'midterm_deadline' => null,
                'finals_opens' => null,
                'finals_deadline' => null,
            ],
        );

    }
}
