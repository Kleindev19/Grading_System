<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\GradeSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_professor_messages_persist_in_database_and_are_visible_to_both_sides(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => 'S1001',
            'api_token' => 'student-token',
        ]);

        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'prof-token',
        ]);

        $studentMessage = Message::create([
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'sender_id' => $student->id,
            'body' => 'student to professor message',
            'grade_sheet_id' => null,
        ]);

        $professorMessage = Message::create([
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'sender_id' => $professor->id,
            'body' => 'professor to student reply',
            'grade_sheet_id' => null,
        ]);

        $this->assertDatabaseHas('messages', [
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'sender_id' => $student->id,
            'body' => 'student to professor message',
        ]);
        $this->assertDatabaseHas('messages', [
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'sender_id' => $professor->id,
            'body' => 'professor to student reply',
        ]);

        $studentInbox = $this->withHeaders([
            'Authorization' => 'Bearer student-token',
            'Accept' => 'application/json',
        ])->getJson('/api/messages');

        $professorInbox = $this->withHeaders([
            'Authorization' => 'Bearer prof-token',
            'Accept' => 'application/json',
        ])->getJson('/api/messages');

        $studentInbox->assertOk()->assertJsonFragment(['body' => 'student to professor message'])->assertJsonFragment(['body' => 'professor to student reply']);
        $professorInbox->assertOk()->assertJsonFragment(['body' => 'student to professor message'])->assertJsonFragment(['body' => 'professor to student reply']);
    }

    public function test_sender_can_unsend_a_message(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => 'S1001',
            'api_token' => 'student-token',
        ]);

        $professor = User::factory()->create([
            'role' => 'professor',
            'api_token' => 'prof-token',
        ]);

        $message = Message::create([
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'sender_id' => $student->id,
            'body' => 'delete me',
            'grade_sheet_id' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer student-token',
            'Accept' => 'application/json',
        ])->deleteJson('/api/messages/' . $message->id);

        $response->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_student_and_professor_can_send_small_images(): void
    {
        Storage::fake('public');
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-UPLOAD', 'api_token' => 'student-upload-token']);
        $professor = User::factory()->create(['role' => 'professor', 'api_token' => 'professor-upload-token']);
        $sheet = GradeSheet::create([
            'code' => 'UPLOAD101',
            'subject' => 'Upload Test',
            'section' => 'BSIT - 1A',
            'semester' => '1st Semester',
            'students' => 1,
            'units' => 3,
            'submitted' => now()->toDateString(),
            'status' => 'Submitted',
            'professor_id' => $professor->id,
        ]);
        $sheet->studentsList()->create(['student_id' => $student->student_id, 'name' => $student->name]);

        $studentResponse = $this->withToken($student->api_token)
            ->post('/api/messages', [
                'professor_id' => $professor->id,
                'grade_sheet_id' => $sheet->id,
                'body' => 'Student image',
                'attachment' => $this->pngUpload('student.png'),
            ], ['Accept' => 'application/json']);

        $studentResponse->assertCreated()->assertJsonPath('message.body', 'Student image');

        $professorResponse = $this->withToken($professor->api_token)
            ->post('/api/messages', [
                'student_id' => $student->id,
                'grade_sheet_id' => $sheet->id,
                'body' => 'Professor image',
                'attachment' => $this->pngUpload('professor.png'),
            ], ['Accept' => 'application/json']);

        $professorResponse->assertCreated()->assertJsonPath('message.body', 'Professor image');
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_student_and_professor_uploads_reject_files_over_one_megabyte(): void
    {
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-LARGE', 'api_token' => 'student-large-token']);
        $professor = User::factory()->create(['role' => 'professor', 'api_token' => 'professor-large-token']);

        $this->withToken($student->api_token)
            ->post('/api/messages', [
                'professor_id' => $professor->id,
                'body' => 'Too large',
                'attachment' => $this->pngUpload('large-student.png', 1025),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');

        $this->withToken($professor->api_token)
            ->post('/api/messages', [
                'student_id' => $student->id,
                'body' => 'Too large',
                'attachment' => $this->pngUpload('large-professor.png', 1025),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_upload_rejects_non_image_files(): void
    {
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'S-NONIMAGE', 'api_token' => 'student-nonimage-token']);
        $professor = User::factory()->create(['role' => 'professor']);

        $this->withToken($student->api_token)
            ->post('/api/messages', [
                'professor_id' => $professor->id,
                'attachment' => UploadedFile::fake()->create('archive.zip', 100, 'application/zip'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('messages', 0);
    }

    private function pngUpload(string $name, int $sizeKilobytes = 1): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');
        $targetBytes = $sizeKilobytes * 1024;
        $content = $png . str_repeat("\0", max(0, $targetBytes - strlen($png)));

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
