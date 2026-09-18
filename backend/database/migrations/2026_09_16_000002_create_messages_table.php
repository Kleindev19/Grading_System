<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('grade_sheet_id')->nullable()->constrained('grade_sheets')->nullOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'professor_id', 'grade_sheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
