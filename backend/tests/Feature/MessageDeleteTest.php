<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
