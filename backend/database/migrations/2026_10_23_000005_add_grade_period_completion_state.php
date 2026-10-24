<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->boolean('midterm_locked')->default(false)->after('submit_grade_deadline');
            $table->boolean('finals_locked')->default(false)->after('midterm_locked');
        });

        DB::table('grade_schedules')
            ->where('year_level', 'Grade Period')
            ->whereNotNull('finals_opens')
            ->update(['midterm_locked' => true]);

        DB::table('grade_schedules')
            ->where('year_level', 'Grade Period')
            ->whereNotNull('submit_grade_deadline')
            ->update(['finals_locked' => true]);
    }

    public function down(): void
    {
        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->dropColumn(['midterm_locked', 'finals_locked']);
        });
    }
};
