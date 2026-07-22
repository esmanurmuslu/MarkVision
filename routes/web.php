<?php

use App\Http\Controllers\Obs\AuthController;
use App\Http\Controllers\Obs\ObsController;
use App\Http\Controllers\Obs\DashboardController;
use App\Http\Controllers\Obs\StudentController;
use App\Http\Controllers\Obs\TeacherController;
use App\Http\Controllers\Obs\DepartmentController;
use App\Http\Controllers\Obs\CourseController;
use App\Http\Controllers\Obs\ExamController;
use App\Http\Controllers\Obs\ResultController;
/*
|--------------------------------------------------------------------------
| OBS MODÜLÜ
|--------------------------------------------------------------------------
| Tüm OBS sistemi artık /obs altında çalışacak.
| Örnek:
| /obs/login
| /obs/students
| /obs/results
| /obs/teachers
|--------------------------------------------------------------------------
*/

Route::prefix('obs')->group(function () {

    // Giriş
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post');

    // Giriş yapan kullanıcılar
    Route::middleware('check.login')->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Çıkış
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        // Öğrenciler
        Route::resource('students', StudentController::class);

        // Öğretmenler
        Route::resource('teachers', TeacherController::class);

        // Bölümler
        Route::resource('departments', DepartmentController::class);

        // Dersler
        Route::resource('courses', CourseController::class);

        // Sınavlar
        Route::resource('exams', ExamController::class);

        // Sonuçlar
        Route::get('/results', [ResultController::class, 'index'])
            ->name('results.index');

        Route::get('/results/{result}', [ResultController::class, 'show'])
            ->name('results.show');

        Route::get('/results/export', [ResultController::class, 'export'])
            ->name('results.export');

        // Optik Okuma
        Route::get('/tarama', [ObsController::class, 'index'])
            ->name('obs.students');

        Route::get('/pending', [ObsController::class, 'pendingReviews'])
            ->name('obs.pending');

        Route::post('/api/optical-scan', [ObsController::class, 'storeOpticalScan'])
            ->name('obs.scan');

        // Cevap Anahtarı
        Route::get('/exams/{exam}/answer-key', [ExamController::class, 'answerKey'])
            ->name('exams.answerkey');

        Route::post('/exams/{exam}/answer-key', [ExamController::class, 'saveAnswerKey'])
            ->name('exams.answerkey.save');
    });

});