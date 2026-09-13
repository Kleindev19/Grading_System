<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_sheets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80);
            $table->string('subject');
            $table->string('section', 80);
            $table->string('semester', 80);
            $table->unsignedInteger('students')->default(0);
            $table->date('submitted');
            $table->enum('status', ['Submitted', 'Approved', 'Published'])->default('Submitted');
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['code', 'section', 'professor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_sheets');
    }
};
