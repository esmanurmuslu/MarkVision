<?php

use Illuminate\Support\Facades\Route;

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
|
| Tüm OBS sistemi /obs altında çalışır.
|
| Örnek:
| /obs/login
| /obs/students
| /obs/results
|
|--------------------------------------------------------------------------
*/

// Ana dizine girildiğinde direkt OBS logine yönlendir
Route::redirect('/', '/obs/login');

Route::prefix('obs')
    ->name('obs.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | AUTH (GİRİŞ EKRANI)
        |--------------------------------------------------------------------------
        */
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');

        /*
        |--------------------------------------------------------------------------
        | LOGIN GEREKTİREN ALANLAR
        |--------------------------------------------------------------------------
        */
        Route::middleware('check.login')->group(function () {

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Logout
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // OBS CRUD (Öğrenci, Öğretmen, Bölüm vb. Yönetimi)
            Route::resource('students', StudentController::class);
            Route::resource('teachers', TeacherController::class)
    ->except(['create', 'store']);
            Route::resource('departments', DepartmentController::class);
            Route::resource('courses', CourseController::class);
            Route::resource('exams', ExamController::class);

            // Results (Sonuçlar)
            Route::get('/results', [ResultController::class, 'index'])->name('results.index');
            Route::get('/results/{result}', [ResultController::class, 'show'])->name('results.show');
            Route::get('/results/export', [ResultController::class, 'export'])->name('results.export');

            // Optik Okuma İşlemleri
            Route::get('/tarama', [ObsController::class, 'index'])->name('tarama');
            Route::get('/pending', [ObsController::class, 'pendingReviews'])->name('pending');
            Route::post('/api/optical-scan', [ObsController::class, 'storeOpticalScan'])->name('scan');

            // Cevap Anahtarı İşlemleri
            Route::get('/exams/{exam}/answer-key', [ExamController::class, 'answerKey'])->name('exams.answerkey');
            Route::post('/exams/{exam}/answer-key', [ExamController::class, 'saveAnswerKey'])->name('exams.answerkey.save');
            
        });
    });