<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_sheet_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grade_sheet_id')->constrained()->cascadeOnDelete();
            $table->string('student_id', 80);
            $table->string('name');
            $table->string('midterm')->nullable();
            $table->string('finals')->nullable();
            $table->string('final_grade')->nullable();
            $table->string('grade_point')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['grade_sheet_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_sheet_students');
    }
};
