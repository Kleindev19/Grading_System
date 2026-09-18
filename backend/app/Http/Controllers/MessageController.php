<?php

namespace App\Http\Controllers;

use App\Models\GradeSheet;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(in_array($user->role, ['student', 'professor'], true), 403);

        $query = Message::with(['student:id,name,student_id', 'professor:id,name', 'sender:id,name', 'gradeSheet:id,code,subject,section']);
        $messages = $user->role === 'student'
            ? $query->where('student_id', $user->id)
            : $query->where('professor_id', $user->id);

        $records = $messages->latest()->get();
        if ($user->role === 'professor') {
            $records->filter(fn (Message $message): bool => !$message->read_at && (int) $message->sender_id !== (int) $user->id)->each(fn (Message $message) => $message->forceFill(['read_at' => now()])->save());
        }

        return response()->json(['messages' => $records->map(fn (Message $message) => $this->serialize($message))]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(in_array($user->role, ['student', 'professor'], true), 403);

        $data = $request->validate([
            'professor_id' => ['nullable', 'integer', 'exists:users,id'],
            'grade_sheet_id' => ['nullable', 'integer', 'exists:grade_sheets,id'],
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);
        abort_if(blank($data['body'] ?? null) && !$request->hasFile('attachment'), 422, 'Message or photo is required.');

        $sheet = !empty($data['grade_sheet_id']) ? GradeSheet::findOrFail($data['grade_sheet_id']) : null;
        abort_if($user->role === 'student' && empty($data['professor_id']), 422, 'Professor is required.');
        $professorId = $user->role === 'professor' ? $user->id : $data['professor_id'];
        if ($user->role === 'student') {
            abort_unless($sheet && (int) $sheet->professor_id === (int) $professorId, 403, 'This subject is not assigned to the professor.');
            abort_unless($sheet->studentsList()->where('student_id', $user->student_id)->exists(), 403, 'This student is not in the selected grade sheet.');
        }
        $studentId = $user->role === 'student' ? $user->id : $this->studentUserId($request, $professorId, $sheet);
        abort_if($sheet && (int) $sheet->professor_id !== (int) $professorId, 422, 'This grade sheet is not assigned to the professor.');

        $message = Message::create([
            'student_id' => $studentId,
            'professor_id' => $professorId,
            'grade_sheet_id' => $sheet?->id,
            'sender_id' => $user->id,
            'body' => $data['body'] ?? null,
            'attachment_path' => $request->hasFile('attachment') ? $request->file('attachment')->store('messages', 'public') : null,
        ]);

        return response()->json(['message' => $this->serialize($message->load(['student:id,name,student_id', 'professor:id,name', 'sender:id,name', 'gradeSheet:id,code,subject,section']))], 201);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(in_array($user->role, ['student', 'professor'], true), 403);
        abort_if((int) $message->sender_id !== (int) $user->id, 403, 'You can only unsend your own message.');

        if ($message->attachment_path) {
            $path = storage_path('app/public/' . $message->attachment_path);
            if (is_file($path)) {
                unlink($path);
            }
        }

        $message->delete();

        return response()->json(['message' => 'Message unsent successfully.']);
    }

    private function studentUserId(Request $request, int $professorId, ?GradeSheet $sheet): int
    {
        $studentId = $request->validate(['student_id' => ['required', 'integer', 'exists:users,id']])['student_id'];
        $student = User::findOrFail($studentId);

        $assigned = Message::where('professor_id', $professorId)->where('student_id', $studentId)->exists()
            || ($sheet && $sheet->studentsList()->where('student_id', $student->student_id)->exists());
        abort_unless($assigned, 403, 'This student is not assigned to the professor.');
        return (int) $studentId;
    }

    private function serialize(Message $message): array
    {
        return [
            'id' => $message->id,
            'studentId' => $message->student_id,
            'studentName' => $message->student?->name,
            'studentNumber' => $message->student?->student_id,
            'professorId' => $message->professor_id,
            'professorName' => $message->professor?->name,
            'senderId' => $message->sender_id,
            'body' => $message->body,
            'attachmentUrl' => $message->attachment_path ? asset('storage/' . $message->attachment_path) : null,
            'gradeSheet' => $message->gradeSheet ? ['id' => $message->gradeSheet->id, 'code' => $message->gradeSheet->code, 'subject' => $message->gradeSheet->subject, 'section' => $message->gradeSheet->section] : null,
            'createdAt' => $message->created_at?->toISOString(),
            'readAt' => $message->read_at?->toISOString(),
        ];
    }
}
