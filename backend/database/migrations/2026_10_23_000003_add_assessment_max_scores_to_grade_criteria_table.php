<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_criteria', function (Blueprint $table): void {
            $table->json('assessment_max_scores')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('grade_criteria', function (Blueprint $table): void {
            $table->dropColumn('assessment_max_scores');
        });
    }
};
