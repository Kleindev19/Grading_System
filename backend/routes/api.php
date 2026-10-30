<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GradeSheetController;
use App\Http\Controllers\GradeCriteriaController;
use App\Http\Controllers\GradeDraftController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradeScheduleController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\GradingScaleController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

Route::middleware('auth.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/grade-sheets', [GradeSheetController::class, 'index']);
    Route::post('/grade-sheets', [GradeSheetController::class, 'store']);
    Route::get('/grade-criteria', [GradeCriteriaController::class, 'show']);
    Route::put('/grade-criteria', [GradeCriteriaController::class, 'save']);
    Route::get('/grading-scale', [GradingScaleController::class, 'show']);
    Route::put('/grading-scale', [GradingScaleController::class, 'save']);
    Route::get('/grade-drafts', [GradeDraftController::class, 'show']);
    Route::put('/grade-drafts', [GradeDraftController::class, 'save']);
    Route::delete('/grade-drafts', [GradeDraftController::class, 'destroy']);
    Route::patch('/grade-sheets/{gradeSheet}/status', [GradeSheetController::class, 'updateStatus']);
    Route::get('/published-grades', [GradeSheetController::class, 'published']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{studentId}/grades', [StudentController::class, 'grades']);
    Route::get('/professors', [ProfessorController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/grade-schedules', [GradeScheduleController::class, 'index']);
    Route::post('/grade-schedules', [GradeScheduleController::class, 'store']);
    Route::patch('/grade-schedules/{schedule}/release', [GradeScheduleController::class, 'release']);
    Route::post('/grade-periods', [GradeScheduleController::class, 'storePeriod']);
    Route::patch('/grade-periods/{schedule}', [GradeScheduleController::class, 'updatePeriod']);
    Route::patch('/grade-periods/{schedule}/lock-submission', [GradeScheduleController::class, 'lockSubmission']);
    Route::delete('/grade-periods/{schedule}', [GradeScheduleController::class, 'destroyPeriod']);
});