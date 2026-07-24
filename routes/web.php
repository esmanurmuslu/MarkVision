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

Route::get('/', [MarkVisionController::class, 'index'])
    ->name('panel.index');

Route::post('/ajax-login', [MarkVisionController::class, 'login'])
    ->name('panel.login');


Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut'])
    ->name('panel.okut');


Route::get('/gecmis-sonuclar', [MarkVisionController::class, 'gecmisSonuclar'])
    ->name('panel.gecmis');


Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey'])
    ->name('panel.cevapkaydet');


Route::get('/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey'])
    ->name('panel.cevapgetir');


Route::get('/register', [MarkVisionController::class, 'showRegister'])
    ->name('register');


Route::post('/register', [MarkVisionController::class, 'registerStore'])
    ->name('register.store');


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


        /*
        |--------------------------------------------------------------------------
        | Login
        |--------------------------------------------------------------------------
        */

        Route::get('/login', [AuthController::class, 'showLogin'])
            ->name('login');


        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.post');



        /*
        |--------------------------------------------------------------------------
        | Auth gerekli
        |--------------------------------------------------------------------------
        */

        Route::middleware('check.login')->group(function () {


            // Dashboard

            Route::get('/', [DashboardController::class, 'index'])
                ->name('dashboard');



            // Logout

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');



            // Öğrenci

            Route::resource('students', StudentController::class);



            // Öğretmen

            Route::resource('teachers', TeacherController::class)
                ->except(['create','store']);



            // Bölüm

            Route::resource('departments', DepartmentController::class);



            // Ders

            Route::resource('courses', CourseController::class);



            // Sınav

            Route::resource('exams', ExamController::class);



            /*
            |--------------------------------------------------------------------------
            | Sonuçlar
            |--------------------------------------------------------------------------
            */

            Route::get('/results', [ResultController::class, 'index'])
                ->name('results.index');


            // EXPORT MUTLAKA ÖNCE

            Route::get('/results/export', [ResultController::class, 'export'])
                ->name('results.export');


            Route::get('/results/{result}', [ResultController::class, 'show'])
                ->name('results.show');




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

            Route::get('/exams/{exam}/answer-key',
                [ExamController::class, 'answerKey']
            )->name('exams.answerkey');


            Route::post('/exams/{exam}/answer-key',
                [ExamController::class, 'saveAnswerKey']
            )->name('exams.answerkey.save');

        });

    });