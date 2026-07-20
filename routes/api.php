<?php
use App\Http\Controllers\MarkVisionController;
Route::post('/optik-oku', [MarkVisionController::class, 'formuOkuAPI']);
Route::post('/register', [App\Http\Controllers\MarkVisionController::class, 'register']);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Mobil uygulamanın optik form fotoğrafını göndereceği uç
Route::post('/exams/evaluate', [ExamController::class, 'evaluate']);

// Belirli bir sınavın okunan sonuçlarını listelemek için kullanılacak uç
Route::get('/exams/{id}/results', [ExamController::class, 'results']);
Route::post('/optik-oku', [MarkVisionController::class, 'formuOkuAPI']);
Route::post('/register', [App\Http\Controllers\MarkVisionController::class, 'register']);
