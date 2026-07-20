<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkVisionController;

Route::get('/', [MarkVisionController::class, 'index'])->name('panel.index');
Route::post('/ajax-login', [MarkVisionController::class, 'login'])->name('panel.login');

// --- Optik okuma ---
Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut'])->name('panel.okut');

// --- Geçmiş sonuçlar ---
Route::get('/gecmis-sonuclar', [MarkVisionController::class, 'gecmisSonuclar'])->name('panel.gecmis');

// --- YENİ: Cevap anahtarı kaydet / getir ---
Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey'])->name('panel.cevapkaydet');
Route::get('/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey'])->name('panel.cevapgetir');

// --- Kayıt ---
Route::get('/register', [MarkVisionController::class, 'showRegister'])->name('register');
Route::post('/register', [MarkVisionController::class, 'registerStore'])->name('register.store');
