<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarkVisionController;

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
| MARKVISION PANEL
|--------------------------------------------------------------------------
*/

Route::get('/', [MarkVisionController::class, 'index'])->name('panel.index');

Route::post('/ajax-login', [MarkVisionController::class, 'login'])
    ->name('panel.login');

// Optik okuma
Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut'])
    ->name('panel.okut');

// Geçmiş sonuçlar
Route::get('/gecmis-sonuclar', [MarkVisionController::class, 'gecmisSonuclar'])
    ->name('panel.gecmis');

// Cevap anahtarı
Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey'])
    ->name('panel.cevapkaydet');

Route::get('/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey'])
    ->name('panel.cevapgetir');

// OBS sınav listesi
Route::get('/obs-sinavlari', [MarkVisionController::class, 'obsSinavlariGetir'])
    ->name('panel.obssinavlari');

Route::post('/obs-kaydet', [MarkVisionController::class, 'obsKaydet'])
    ->name('panel.obskaydet');

// Kayıt
Route::get('/register', [MarkVisionController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [MarkVisionController::class, 'registerStore'])
    ->name('register.store');

// Şifre değiştir
Route::post('/panel/sifre-degistir', [MarkVisionController::class, 'sifreDegistir'])
    ->name('panel.sifredegistir');

/*
|--------------------------------------------------------------------------
| OBS MODÜLÜ
|--------------------------------------------------------------------------
*/

Route::prefix('obs')
    ->name('obs.')
    ->group(function () {

        // Giriş
        Route::get('/login', [AuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.post');

        Route::middleware('check.login')->group(function () {

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])
                ->name('dashboard');

            // Çıkış
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');

            // Resource Controllerlar
            Route::resource('students', StudentController::class);

            Route::resource('teachers', TeacherController::class)
                ->except(['create', 'store']);

            Route::resource('departments', DepartmentController::class);

            Route::resource('courses', CourseController::class);

            Route::resource('exams', ExamController::class);

            /*
            |--------------------------------------------------------------------------
            | Sonuçlar
            |--------------------------------------------------------------------------
            */

            Route::get('/results', [ResultController::class, 'index'])
                ->name('results.index');

            // EXPORT SHOW'DAN ÖNCE OLMALI
            Route::get('/results/export', [ResultController::class, 'export'])
                ->name('results.export');

            Route::get('/results/{result}', [ResultController::class, 'show'])
                ->name('results.show');

            // ONAYLA
            Route::post('/results/{result}/approve', [ResultController::class, 'approve'])
                ->name('results.approve');

            /*
            |--------------------------------------------------------------------------
            | Optik Tarama
            |--------------------------------------------------------------------------
            */

            Route::get('/tarama', [ObsController::class, 'index'])
                ->name('tarama');

            Route::get('/pending', [ObsController::class, 'pendingReviews'])
                ->name('pending');

            Route::post('/api/optical-scan', [ObsController::class, 'storeOpticalScan'])
                ->name('scan');

            /*
            |--------------------------------------------------------------------------
            | Cevap Anahtarı
            |--------------------------------------------------------------------------
            */

            Route::get('/exams/{exam}/answer-key', [ExamController::class, 'answerKey'])
                ->name('exams.answerkey');

            Route::post('/exams/{exam}/answer-key', [ExamController::class, 'saveAnswerKey'])
                ->name('exams.answerkey.save');

        });

    });