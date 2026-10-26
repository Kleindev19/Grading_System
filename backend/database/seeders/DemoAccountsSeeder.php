<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\GradeSchedule;
use App\Models\GradeSheet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (config('app.env') === 'production' && ! config('app.seed_demo_accounts')) {
            throw new \RuntimeException('Set SEED_DEMO_ACCOUNTS=true before creating demo accounts in production.');
        }

        $demoProfessor = User::query()->where('username', 'mock.professor')->first();
        $demoRegistrar = User::query()->where('username', 'mock.registrar')->first();
        $demoStudent = User::query()->where('username', '23-00001')->first();
        $hasCompleteFixture = $demoProfessor
            && $demoRegistrar
            && $demoStudent
            && GradeSheet::query()
                ->where('professor_id', $demoProfessor->id)
                ->whereIn('code', ['MOCK101', 'MOCK-INCOMPLETE', 'MOCK-READY', 'MOCK-SCHEDULED', 'MOCK-PUBLISHED'])
                ->count() === 5
            && GradeSheet::query()
                ->where('code', 'MOCK101')
                ->whereHas('studentsList', fn ($query) => $query->where('student_id', $demoStudent->student_id))
                ->exists()
            && GradeSchedule::query()
                ->where('created_by', $demoRegistrar->id)
                ->where('year_level', 'Grade Period')
                ->exists();

        if ($hasCompleteFixture) {
            return;
        }

        $password = Hash::make('Password123!');

        $registrar = User::updateOrCreate(
            ['username' => 'mock.registrar'],
            [
                'name' => 'Mock Registrar',
                'email' => 'mock.registrar@grading.test',
                'password' => $password,
                'role' => 'registrar',
                'email_verified_at' => now(),
            ],
        );

        $professor = User::updateOrCreate(
            ['username' => 'mock.professor'],
            [
                'name' => 'Mock Professor',
                'email' => 'mock.professor@grading.test',
                'password' => $password,
                'role' => 'professor',
                'email_verified_at' => now(),
            ],
        );

        $students = collect([
            ['id' => '23-00001', 'name' => 'Mock Student'],
            ['id' => '23-00002', 'name' => 'Alex Sample'],
            ['id' => '23-00003', 'name' => 'Jamie Example'],
        ]);

        User::updateOrCreate(
            ['username' => '23-00001'],
            [
                'name' => 'Mock Student',
                'email' => 'mock.student@grading.test',
                'password' => $password,
                'role' => 'student',
                'student_id' => '23-00001',
                'email_verified_at' => now(),
            ],
        );

        foreach ($students as $rosterStudent) {
            Student::updateOrCreate(
                ['student_id' => $rosterStudent['id']],
                [
                    'name' => $rosterStudent['name'],
                    'institute' => 'IBE',
                    'course' => 'BSBA',
                    'year_level' => '1st Year',
                    'section' => 'A',
                    'status' => 'Active',
                    'created_by' => $registrar->id,
                ],
            );
        }

        $schoolYearStart = now('Asia/Manila')->year;
        $schoolYear = "{$schoolYearStart}-" . ($schoolYearStart + 1);
        $semester = "1st Semester {$schoolYear}";
        $submittedScores = [
            'midterm' => ['Q1' => 18, 'Q2' => 17, 'Q3' => 19, 'Q4' => 18, 'Q5' => 20, 'L1' => 45, 'A1' => 48, 'A2' => 46, 'A3' => 50, 'A4' => 47, 'R1' => 88, 'Mid Ex' => 90],
            'finals' => ['Q1' => 19, 'Q2' => 18, 'Q3' => 20, 'Q4' => 19, 'Q5' => 18, 'L1' => 46, 'A1' => 49, 'A2' => 47, 'A3' => 50, 'A4' => 48, 'R1' => 90, 'Mid Ex' => 92],
        ];

        $scenarios = [
            'MOCK101' => ['Mock Business Law', 'Submitted', true],
            'MOCK-INCOMPLETE' => ['Mock Incomplete Grades', 'Submitted', false],
            'MOCK-READY' => ['Mock Approved Grades', 'Approved', true],
            'MOCK-SCHEDULED' => ['Mock Scheduled Release', 'Approved', true],
            'MOCK-PUBLISHED' => ['Mock Published Grades', 'Published', true],
        ];
        $sheets = [];

        foreach ($scenarios as $code => [$subject, $status, $complete]) {
            $sheet = GradeSheet::updateOrCreate(
                [
                    'code' => $code,
                    'section' => 'BSBA - 1A',
                    'professor_id' => $professor->id,
                    'semester' => $semester,
                ],
                [
                    'subject' => $subject,
                    'students' => $students->count(),
                    'units' => 3,
                    'submitted' => now('Asia/Manila')->toDateString(),
                    'status' => $status,
                    'reviewed_by' => in_array($status, ['Approved', 'Published'], true) ? $registrar->id : null,
                    'reviewed_at' => in_array($status, ['Approved', 'Published'], true) ? now() : null,
                    'rejection_reason' => null,
                ],
            );

            $sheet->studentsList()->delete();
            foreach ($students as $index => $rosterStudent) {
                $hasGrades = $complete || $index === 0;
                $midterm = $hasGrades ? '88' : null;
                $finals = $hasGrades ? '91' : null;
                $sheet->studentsList()->create([
                    'student_id' => $rosterStudent['id'],
                    'name' => $rosterStudent['name'],
                    'midterm' => $midterm,
                    'finals' => $finals,
                    'final_grade' => $hasGrades ? '89.80' : null,
                    'grade_point' => $hasGrades ? '1.75' : null,
                    'remarks' => $hasGrades ? 'PASSED' : null,
                    'assessment_scores' => $hasGrades ? $submittedScores : [],
                ]);
            }

            $sheets[$code] = $sheet;
        }

        GradeSchedule::updateOrCreate(
            [
                'school_year' => $schoolYear,
                'semester' => '1st Semester',
                'year_level' => 'Grade Period',
                'created_by' => $registrar->id,
            ],
            [
                'courses' => [],
                'status' => 'Released',
                'submit_grade_deadline' => now('Asia/Manila')->addDays(30)->toDateString(),
                'submit_grade_locked' => false,
            ],
        );

        $this->seedCourseReleaseSchedule(
            $registrar,
            $schoolYear,
            'sheet:' . $sheets['MOCK-PUBLISHED']->id,
            now('Asia/Manila')->subDay()->toDateString(),
            'Released',
        );
        $this->seedCourseReleaseSchedule(
            $registrar,
            $schoolYear,
            'sheet:' . $sheets['MOCK-SCHEDULED']->id,
            now('Asia/Manila')->addDays(7)->toDateString(),
            'Scheduled',
        );
    }

    private function seedCourseReleaseSchedule(User $registrar, string $schoolYear, string $course, string $releasedDate, string $status): void
    {
        $schedule = GradeSchedule::query()
            ->where('school_year', $schoolYear)
            ->where('semester', '1st Semester')
            ->where('year_level', '1st Year')
            ->where('created_by', $registrar->id)
            ->get()
            ->first(fn (GradeSchedule $candidate): bool => $candidate->courses === [$course]);

        if (!$schedule) {
            $schedule = new GradeSchedule([
                'school_year' => $schoolYear,
                'semester' => '1st Semester',
                'year_level' => '1st Year',
                'courses' => [$course],
                'created_by' => $registrar->id,
            ]);
        }

        $schedule->fill([
            'released_date' => $releasedDate,
            'status' => $status,
        ])->save();
    }
}
