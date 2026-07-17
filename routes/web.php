<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkVisionController;

Route::get('/', function () {
    return view('welcome');
});
use App\Jobs\ProcessExamImageJob;

Route::get('/test-gonder', function () {
    $path = base_path('omr_scripts/optik.png');
    ProcessExamImageJob::dispatch(1, $path);
    return "İş kuyruğa atıldı! queue:work terminalini kontrol et.";
});
Route::get('/', [MarkVisionController::class, 'index'])->name('panel.index');
Route::post('/ajax-login', [MarkVisionController::class, 'login'])->name('panel.login');
Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut'])->name('panel.okut');
Route::get('/gecmis-sonuclar', [MarkVisionController::class, 'gecmisSonuclar'])->name('panel.gecmis');

Route::get('/register', [MarkVisionController::class, 'showRegister'])->name('register');
Route::post('/register', [MarkVisionController::class, 'registerStore'])->name('register.store');
