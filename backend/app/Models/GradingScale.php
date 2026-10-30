<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingScale extends Model
{
    protected $fillable = ['rows'];

    protected function casts(): array
    {
        return ['rows' => 'array'];
    }

    public static function defaultRows(): array
    {
        return [
            ['minimum' => 98, 'maximum' => 100, 'gradePoint' => '1.00', 'remarks' => 'PASSED'],
            ['minimum' => 96, 'maximum' => 97, 'gradePoint' => '1.25', 'remarks' => 'PASSED'],
            ['minimum' => 93, 'maximum' => 95, 'gradePoint' => '1.50', 'remarks' => 'PASSED'],
            ['minimum' => 90, 'maximum' => 92, 'gradePoint' => '1.75', 'remarks' => 'PASSED'],
            ['minimum' => 87, 'maximum' => 89, 'gradePoint' => '2.00', 'remarks' => 'PASSED'],
            ['minimum' => 84, 'maximum' => 86, 'gradePoint' => '2.25', 'remarks' => 'PASSED'],
            ['minimum' => 81, 'maximum' => 83, 'gradePoint' => '2.50', 'remarks' => 'PASSED'],
            ['minimum' => 78, 'maximum' => 80, 'gradePoint' => '2.75', 'remarks' => 'PASSED'],
            ['minimum' => 75, 'maximum' => 77, 'gradePoint' => '3.00', 'remarks' => 'PASSED'],
            ['minimum' => 70, 'maximum' => 74, 'gradePoint' => '4.00', 'remarks' => 'FAILED'],
            ['minimum' => 0, 'maximum' => 69, 'gradePoint' => '5.00', 'remarks' => 'FAILED'],
        ];
    }

    public static function currentRows(): array
    {
        return self::query()->find(1)?->rows ?? self::defaultRows();
    }
}
