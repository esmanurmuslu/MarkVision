<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarkVisionController;
use App\Http\Controllers\Obs\ExamController;

// Kullanıcı bilgisi
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// OBS Sınav Rotaları
Route::post('/exams/evaluate', [ExamController::class, 'evaluate']);
Route::get('/exams/{id}/results', [ExamController::class, 'results']);

// =================================================================
// MARKVISION MOBİL UYGULAMA ROTALARI (v1)
// =================================================================
//
// main.dart içinde API_URL = ".../api/v1" olarak tanımlı. Mobil tarafın
// çağırdığı her uç bu grupta, main.dart'taki path'lerle birebir aynı
// isimle tanımlı olmalı; prefix eksik/yanlış olursa mobil 404 alır.
//
// ÖNEMLİ: Bu grup 'api' middleware'i kullanır -> STATELESS, session yok.
// MarkVisionController::optikOkut() ve ::saveAnswerKey() bu yüzden
// session'a zorunlu bağımlı değil; session yoksa request ile gelen
// sinav_id / exam_name ile de çalışır (bkz. controller). Web panelindeki
// (web.php) session tabanlı akış hiç değiştirilmedi.
//
// NOT: /v1/register artık registerStore() yerine registerApi()'ye
// bağlandı, çünkü registerStore() redirect() döndürüyor ve mobil
// tarafta bu 500/hatalı response olarak geri döner. registerApi()
// JSON döner ve Auth::login() çağırmaz (stateless tarafta oturum
// açmanın bir karşılığı yok).
//
// Geriye dönük uyumluluk için eski (v1 öneki olmayan) rotalar da
// aynen korunuyor; hiçbir eski path silinmedi.
// =================================================================

// 1. Giriş ve Kayıt İşlemleri
Route::post('/v1/login', [MarkVisionController::class, 'login']);
Route::post('/v1/register', [MarkVisionController::class, 'registerApi']);

// 2. Optik Okuma İşlemleri
Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut']);
Route::post('/v1/optik-okut', [MarkVisionController::class, 'optikOkut']);

Route::post('/optik-anahtar-oku', [MarkVisionController::class, 'anahtarOku']);
Route::post('/v1/optik-anahtar-oku', [MarkVisionController::class, 'anahtarOku']);

Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey']);
Route::post('/v1/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey']);

// Aktif/son cevap anahtarını geri döner (mobil tarafın senkronizasyonu için)
Route::get('/v1/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey']);

// 3. Veri Senkronizasyonu
Route::get('/v1/classes', [MarkVisionController::class, 'apiSiniflariGetir']);
Route::post('/v1/classes', [MarkVisionController::class, 'apiSinifKaydet']);

Route::get('/v1/students', [MarkVisionController::class, 'apiOgrencileriGetir']);
Route::post('/v1/students', [MarkVisionController::class, 'apiOgrenciKaydet']);
Route::patch('/v1/students/{id}', [MarkVisionController::class, 'apiOgrenciGuncelle']);

Route::delete('/v1/classes/{id}', [MarkVisionController::class, 'apiSinifSil']);
Route::get('/exams', [App\Http\Controllers\MarkVisionController::class, 'apiSinavlariGetir']);
Route::get('/v1/exams', [App\Http\Controllers\MarkVisionController::class, 'apiSinavlariGetir']);