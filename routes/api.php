<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarkVisionController;
use App\Http\Controllers\Obs\ExamController;

// Kullanıcı bilgisi
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Mobil uygulamanın optik form fotoğrafını göndereceği uç
Route::post('/exams/evaluate', [ExamController::class, 'evaluate']);

// Belirli bir sınavın okunan sonuçlarını listelemek için kullanılacak uç
Route::get('/exams/{id}/results', [ExamController::class, 'results']);

// MarkVision API
Route::post('/optik-oku', [MarkVisionController::class, 'formuOkuAPI']);
Route::post('/register', [MarkVisionController::class, 'register']);