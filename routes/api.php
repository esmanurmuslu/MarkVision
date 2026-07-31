<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarkVisionController;
use App\Http\Controllers\Obs\ExamController;

// Kullanıcı bilgisi
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// OBS modülü tarafındaki farklı akış (evaluate/results) — dokunulmadı
Route::post('/exams/evaluate', [ExamController::class, 'evaluate']);
Route::get('/exams/{id}/results', [ExamController::class, 'results']);

/*
|--------------------------------------------------------------------------
| MOBİL UYGULAMA (Flutter - main.dart) API ROTALARI
|--------------------------------------------------------------------------
| main.dart içinde API_URL = ".../api/v1" olarak tanımlı. Bu yüzden mobil
| tarafın çağırdığı HER uç burada, v1 prefix'i altında, main.dart'taki
| path'lerle BİREBİR aynı isimle tanımlanmalı. Prefix eksik/yanlış olursa
| mobil taraf 404 alır (önceki haliyle olan sorun buydu).
|
| ÖNEMLİ: Bu grup 'api' middleware'i kullanır → STATELESS, PHP session'ı
| YOKTUR. Bu yüzden MarkVisionController::optikOkut() ve ::saveAnswerKey()
| artık session'a zorunlu bağımlı değil; session yoksa request ile gelen
| sinav_id / exam_name ile de çalışabiliyor (bkz. controller). Web
| panelindeki (web.php) session tabanlı akış hiç değiştirilmedi.
|
| NOT: Eskiden burada var olmayan bir controller metodunu (register)
| çağıran ve tetiklenirse 500 hatası verecek olan kırık bir '/register'
| rotası vardı. Onun yerine gerçekten var olan registerApi() metoduna
| bağlandı.
|
| NOT 2: '/login' rotası eklendi. Mobil taraf şifreyi ne olursa olsun
| kabul ediyordu çünkü çağıracağı bir login endpoint'i hiç yoktu —
| controller'daki login() metodu session/Auth::login() kullandığı için
| stateless mobile'a uygun değildi, bu yüzden onun yerine yeni ve
| stateless olan loginApi() metoduna bağlandı (bkz. controller).
*/
Route::prefix('v1')->group(function () {

    Route::post('/register', [MarkVisionController::class, 'registerApi']);
    Route::post('/login', [MarkVisionController::class, 'loginApi']);

    Route::post('/optik-okut', [MarkVisionController::class, 'optikOkut']);
    Route::post('/optik-anahtar-oku', [MarkVisionController::class, 'anahtarOku']);
    Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey']);
    Route::get('/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey']);

    Route::get('/classes', [MarkVisionController::class, 'apiSiniflariGetir']);
    Route::post('/classes', [MarkVisionController::class, 'apiSinifKaydet']);

    Route::get('/students', [MarkVisionController::class, 'apiOgrencileriGetir']);
    Route::post('/students', [MarkVisionController::class, 'apiOgrenciKaydet']);

});