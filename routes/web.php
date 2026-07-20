<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ResultController;

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

Route::middleware('check.login')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Students
    Route::resource('students', StudentController::class);

    // Teachers
    Route::resource('teachers', TeacherController::class);

    // Departments
    Route::resource('departments', DepartmentController::class);

    // Courses
    Route::resource('courses', CourseController::class);

    // Exams
    Route::resource('exams', ExamController::class);

    // Answer Key
    Route::get('/exams/{exam}/answer-key', [ExamController::class, 'answerKey'])
        ->name('exams.answerkey');

    Route::post('/exams/{exam}/answer-key', [ExamController::class, 'saveAnswerKey'])
        ->name('exams.answerkey.save');

    // Results
    Route::get('/results', [ResultController::class, 'index'])
        ->name('results.index');

    Route::get('/results/{result}', [ResultController::class, 'show'])
        ->name('results.show');

    Route::get('/results/export', [ResultController::class, 'export'])
        ->name('results.export');

    // OBS
    Route::get('/obs', [ObsController::class, 'index'])
        ->name('obs.students');

    Route::get('/obs/pending', [ObsController::class, 'pendingReviews'])
        ->name('obs.pending');

    Route::post('/api/optical-scan', [ObsController::class, 'storeOpticalScan']);
});