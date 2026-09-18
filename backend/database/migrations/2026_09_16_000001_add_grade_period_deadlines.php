<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->date('midterm_deadline')->nullable()->after('midterm_opens');
            $table->date('finals_deadline')->nullable()->after('finals_opens');
        });
    }

    public function down(): void
    {
        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->dropColumn(['midterm_deadline', 'finals_deadline']);
        });
    }
};
