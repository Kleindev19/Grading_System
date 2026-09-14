<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_sheet_students', function (Blueprint $table): void {
            $table->json('assessment_scores')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('grade_sheet_students', function (Blueprint $table): void {
            $table->dropColumn('assessment_scores');
        });
    }
};