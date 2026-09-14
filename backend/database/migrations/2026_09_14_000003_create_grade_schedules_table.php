<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('school_year', 20);
            $table->string('semester', 40);
            $table->string('year_level', 40);
            $table->json('courses');
            $table->date('released_date')->nullable();
            $table->enum('status', ['Scheduled', 'Released'])->default('Scheduled');
            $table->date('midterm_opens')->nullable();
            $table->date('finals_opens')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_schedules');
    }
};