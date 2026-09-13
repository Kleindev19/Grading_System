<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GradeSheetController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

Route::middleware('auth.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/grade-sheets', [GradeSheetController::class, 'index']);
    Route::post('/grade-sheets', [GradeSheetController::class, 'store']);
    Route::patch('/grade-sheets/{gradeSheet}/status', [GradeSheetController::class, 'updateStatus']);
    Route::get('/published-grades', [GradeSheetController::class, 'published']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
});