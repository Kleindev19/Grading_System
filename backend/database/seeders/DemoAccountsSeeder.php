<?php

namespace Database\Seeders;

use App\Models\Student;
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

        User::updateOrCreate(
            ['username' => 'mock.professor'],
            [
                'name' => 'Mock Professor',
                'email' => 'mock.professor@grading.test',
                'password' => $password,
                'role' => 'professor',
                'email_verified_at' => now(),
            ],
        );

        $student = User::updateOrCreate(
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

        Student::updateOrCreate(
            ['student_id' => $student->student_id],
            [
                'name' => $student->name,
                'institute' => 'IBE',
                'course' => 'BSBA',
                'year_level' => '1st Year',
                'section' => 'A',
                'status' => 'Active',
                'created_by' => $registrar->id,
            ],
        );
    }
}
