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

    }
}
