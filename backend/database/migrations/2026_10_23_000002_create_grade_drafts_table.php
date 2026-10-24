<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('section', 80);
            $table->string('semester', 80);
            $table->string('subject');
            $table->unsignedInteger('units')->default(0);
            $table->json('assessment_headers');
            $table->json('student_records');
            $table->timestamps();
            $table->unique(['professor_id', 'code', 'section', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_drafts');
    }
};
