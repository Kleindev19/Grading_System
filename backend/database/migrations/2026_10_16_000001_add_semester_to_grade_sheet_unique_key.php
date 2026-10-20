<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingIndex = collect(Schema::getIndexes('grade_sheets'))->first(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['code', 'section', 'professor_id'],
        );

        if ($existingIndex) {
            Schema::table('grade_sheets', function (Blueprint $table) use ($existingIndex): void {
                $table->dropUnique($existingIndex['name']);
            });
        }

        Schema::table('grade_sheets', function (Blueprint $table): void {
            $table->unique(['code', 'section', 'professor_id', 'semester']);
        });
    }

    public function down(): void
    {
        $semesterIndex = collect(Schema::getIndexes('grade_sheets'))->first(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['code', 'section', 'professor_id', 'semester'],
        );

        if ($semesterIndex) {
            Schema::table('grade_sheets', function (Blueprint $table) use ($semesterIndex): void {
                $table->dropUnique($semesterIndex['name']);
            });
        }

        Schema::table('grade_sheets', function (Blueprint $table): void {
            $table->unique(['code', 'section', 'professor_id']);
        });
    }
};