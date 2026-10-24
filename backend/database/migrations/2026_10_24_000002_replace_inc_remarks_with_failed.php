<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('grade_sheet_students')
            ->whereRaw('LOWER(TRIM(remarks)) = ?', ['inc'])
            ->update(['remarks' => 'FAILED']);
    }

    public function down(): void
    {
    }
};
