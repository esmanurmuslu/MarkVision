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

// Cevap anahtarı KAMERAYLA tarama (YENİ EKLENDİ)
Route::post('/optik-anahtar-oku', [MarkVisionController::class, 'anahtarOku'])
    ->name('panel.anahtarokut');

// Geçmiş sonuçlar
Route::get('/gecmis-sonuclar', [MarkVisionController::class, 'gecmisSonuclar'])
    ->name('panel.gecmis');

// --- EXCEL DOSYASI İNDİRME ROTASI ---
Route::get('/gecmis-sonuclar/export', [MarkVisionController::class, 'exportExcel'])
    ->name('panel.export');

Route::get('/gecmis-sonuclar/{id}', [MarkVisionController::class, 'gecmisSonucDetay'])
    ->name('panel.gecmisdetay');
// -----------------------------------

// C Kişisi Görevi: Cevap anahtarı, soru ağırlıkları ve ceza katsayısı destekli kayıt
Route::post('/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKey'])
    ->name('panel.cevapkaydet');

Route::get('/cevap-anahtari-getir', [MarkVisionController::class, 'getLatestAnswerKey'])
    ->name('panel.cevapgetir');

// OBS sınav listesi
Route::get('/obs-sinavlari', [MarkVisionController::class, 'obsSinavlariGetir'])
    ->name('panel.obssinavlari');

Route::post('/obs-kaydet', [MarkVisionController::class, 'obsKaydet'])
    ->name('panel.obskaydet');

/*
|--------------------------------------------------------------------------
| PANEL: SINAVLAR / SINIFLAR / ÖĞRENCİLER (kullanıcıya özel — YENİ EKLENDİ)
|--------------------------------------------------------------------------
| Öncesinde bu üç sekme tamamen HTML'e gömülü sahte veriydi. Artık her
| kayıt Auth::id() (giriş yapan öğretmen) ile filtreleniyor.
*/

// Sınavlar
Route::get('/panel/sinavlar', [MarkVisionController::class, 'panelSinavlariGetir'])
    ->name('panel.sinavlar.listele');
Route::post('/panel/sinavlar/sil', [MarkVisionController::class, 'panelSinavSil'])
    ->name('panel.sinavlar.sil');

// Sınıflar
Route::get('/panel/siniflar', [MarkVisionController::class, 'panelSiniflariGetir'])
    ->name('panel.siniflar.listele');
Route::post('/panel/siniflar', [MarkVisionController::class, 'panelSinifEkle'])
    ->name('panel.siniflar.ekle');
Route::put('/panel/siniflar/{id}', [MarkVisionController::class, 'panelSinifGuncelle'])
    ->name('panel.siniflar.guncelle');
Route::delete('/panel/siniflar/{id}', [MarkVisionController::class, 'panelSinifSil'])
    ->name('panel.siniflar.sil');

// Öğrenciler
Route::get('/panel/ogrenciler', [MarkVisionController::class, 'panelOgrencileriGetir'])
    ->name('panel.ogrenciler.listele');
Route::post('/panel/ogrenciler', [MarkVisionController::class, 'panelOgrenciEkle'])
    ->name('panel.ogrenciler.ekle');
Route::put('/panel/ogrenciler/{id}', [MarkVisionController::class, 'panelOgrenciGuncelle'])
    ->name('panel.ogrenciler.guncelle');
Route::delete('/panel/ogrenciler/{id}', [MarkVisionController::class, 'panelOgrenciSil'])
    ->name('panel.ogrenciler.sil');

// Kayıt
Route::get('/register', [MarkVisionController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [MarkVisionController::class, 'registerStore'])
    ->name('register.store');

// Şifre değiştir
Route::post('/panel/sifre-degistir', [MarkVisionController::class, 'sifreDegistir'])
    ->name('panel.sifredegistir');

// Hesabı Sil (YENİ EKLENDİ)
Route::post('/panel/hesabi-sil', [MarkVisionController::class, 'hesabiSil'])
    ->name('panel.hesabisil');

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

            // YENİ EKLENEN ROTA: Optik sonuçlarını web paneli/cihazlar üzerinden doğrudan OBS'ye işler
            Route::post('/optik-kaydet', [ResultController::class, 'store'])
                ->name('optik.kaydet');

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

/*
|--------------------------------------------------------------------------
| MARKVISION MOBİL MODÜLÜ (YENİ EKLENDİ — üstteki hiçbir rota değiştirilmedi)
|--------------------------------------------------------------------------
| Telefon görünümlü tek sayfa mobil uygulama: şık doldurma (çoklu seçim),
| kamera ile optik okutma ve geçmiş sonuçlar burada aynı ekranda akar.
*/

Route::get('/mobil', [MarkVisionController::class, 'mobileIndex'])
    ->name('mobil.index');

// Mobilde çoklu şık (istenildiği kadar seçenek) destekli cevap anahtarı kaydı
Route::post('/mobil/cevap-anahtari-kaydet', [MarkVisionController::class, 'saveAnswerKeyMobile'])
    ->name('mobil.cevapkaydet');