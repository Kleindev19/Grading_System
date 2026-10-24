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
            $table->date('submit_grade_deadline')->nullable()->after('finals_opens');
        });

        DB::table('grade_schedules')
            ->whereNotNull('finals_deadline')
            ->update(['submit_grade_deadline' => DB::raw('finals_deadline')]);

        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->dropColumn(['midterm_deadline', 'finals_deadline']);
        });
    }

    public function down(): void
    {
        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->date('midterm_deadline')->nullable()->after('midterm_opens');
            $table->date('finals_deadline')->nullable()->after('finals_opens');
        });

        DB::table('grade_schedules')
            ->whereNotNull('submit_grade_deadline')
            ->update(['finals_deadline' => DB::raw('submit_grade_deadline')]);

        Schema::table('grade_schedules', function (Blueprint $table): void {
            $table->dropColumn('submit_grade_deadline');
        });
    }
};
