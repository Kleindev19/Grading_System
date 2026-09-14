<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('institute')->nullable()->after('name');
            $table->string('course')->nullable()->after('institute');
            $table->string('year_level', 40)->nullable()->after('course');
            $table->string('section', 80)->nullable()->after('year_level');
            $table->string('status', 40)->default('Active')->after('section');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['institute', 'course', 'year_level', 'section', 'status']);
        });
    }
};
