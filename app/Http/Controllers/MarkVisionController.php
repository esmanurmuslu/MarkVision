<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Sinav;
use App\Models\OgrenciSonuc;

class MarkVisionController extends Controller
{
    // Python'un tam yolu — kendi bilgisayarınızda where.exe/py -c ile bulduğumuz yol
   // Python'un tam yolu
    private string $pythonPath = 'C:\\Users\\SUDE\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

    public function index() // <-- 18. satır civarı
    {
        return view('markvision-panel');
    } // <-- 20. satır civarı
    public function login(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email', 'password' => 'required']);
            $teacher = DB::table('teachers')->where('email', $request->email)->first();

            if ($teacher && Hash::check($request->password, $teacher->password)) {
                $userModel = User::find($teacher->id);
                if (!$userModel) {
                    $userModel = new User();
                    $userModel->forceFill((array) $teacher);
                }
                Auth::login($userModel);

                return response()->json([
                    'success' => true,
                    'user' => [
                        'ad' => $teacher->name . ' ' . $teacher->surname,
                        'rol' => 'Öğretmen / Akademisyen',
                    ],
                ]);
            }
            return response()->json(['success' => false, 'message' => 'E-posta veya şifre hatalı!'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    // --- CEVAP ANAHTARI KAYDET ---
    public function saveAnswerKey(Request $request)
    {
        try {
            $request->validate([
                'exam_name' => 'required|string|max:255',
                'ders_kodu' => 'nullable|string|max:50',
                'answers'   => 'required|array|min:1',
                'answers.*' => 'required|string|in:A,B,C,D,E',
            ]);

            // DÜZELTME: 'ders_kodu' sütunu veritabanında NOT NULL ve varsayılan
            // değeri yok. Kullanıcı bu alanı boş bırakırsa sınav adından
            // otomatik bir kod türetiyoruz, böylece SQL hatası bir daha oluşmaz.
            $dersKodu = trim((string) $request->input('ders_kodu'));
            if ($dersKodu === '') {
                $dersKodu = strtoupper(Str::slug($request->input('exam_name'), '_'));
                if (strlen($dersKodu) > 30) {
                    $dersKodu = substr($dersKodu, 0, 30);
                }
                if ($dersKodu === '') {
                    $dersKodu = 'GENEL_' . time();
                }
            }

            $sinav = Sinav::create([
                'sinav_adi'      => $request->input('exam_name'),
                'ders_kodu'      => $dersKodu,
                'cevap_anahtari' => $request->input('answers'),
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Cevap anahtarı başarıyla kaydedildi.',
                'sinav_id' => $sinav->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // --- EN SON CEVAP ANAHTARINI GETİR (formu doldurmak için) ---
    public function getLatestAnswerKey()
    {
        try {
            $sinav = Sinav::latest()->first();
            if (!$sinav) {
                return response()->json(['success' => false]);
            }
            return response()->json([
                'success'   => true,
                'exam_name' => $sinav->sinav_adi,
                'ders_kodu' => $sinav->ders_kodu,
                'answers'   => $sinav->cevap_anahtari,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // --- OPTİK FORMU OKU (GERÇEK PYTHON PIPELINE ÇAĞRISI) ---
    public function optikOkut(Request $request)
    {
        try {
            // Frontend FormData ile gerçek dosya gönderiyor (base64 değil).
            $request->validate([
                'image' => 'required|file|image|max:10240', // max 10MB
            ]);

            // Her zaman EN SON kaydedilen cevap anahtarı kullanılır.
            // Yani okuma islemi ancak cevap anahtari basariyla kaydedildikten
            // sonra dogru calisir -- artik saveAnswerKey hata vermedigi icin
            // bu akis her zaman guncel anahtari kullanacak.
            $sinav = Sinav::latest()->first();
            if (!$sinav || empty($sinav->cevap_anahtari)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Önce "Cevap Anahtarı" bölümünden bir sınav cevap anahtarı kaydetmelisiniz.',
                ], 422);
            }

            $answerKey = $sinav->cevap_anahtari;
            $totalQuestions = count($answerKey);

            // 1. Yüklenen dosyayı diske kaydet
            $publicDir = storage_path('app/public/optik_forms');
            if (!is_dir($publicDir)) mkdir($publicDir, 0777, true);

            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
            $imageName = 'optik_' . time() . '_' . uniqid() . '.' . $extension;

            $uploadedFile->move($publicDir, $imageName);
            $imagePath = $publicDir . DIRECTORY_SEPARATOR . $imageName;

            // 2. Geçici sinav_bilgisi.json dosyasını oluştur
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $sinavBilgisi = [
                'exam_id'         => $sinav->id,
                'total_questions' => $totalQuestions,
                'answer_key'      => $answerKey,
            ];
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            // 3. Python pipeline'ını çalıştır
            $omrDir = base_path('omr_scripts');
            $pipelinePath = $omrDir . DIRECTORY_SEPARATOR . 'pipeline_main.py';
            $koordinatPath = $omrDir . DIRECTORY_SEPARATOR . 'koordinat_haritasi.json';

            $result = Process::path($omrDir)
                ->timeout(60)
                ->run([$this->pythonPath, $pipelinePath, $imagePath, $koordinatPath, $sinavPath]);

            $output = trim($result->output());
            $errorOutput = trim($result->errorOutput());

            @unlink($sinavPath);

            if (!$output) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python işlemi hiçbir çıktı üretmedi. Detay: ' . $errorOutput,
                ], 500);
            }

            $sonuc = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($sonuc['basarili'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python çıktısı okunamadı: ' . $output,
                ], 500);
            }

            if (!$sonuc['basarili']) {
                return response()->json([
                    'success' => false,
                    'message' => $sonuc['hata'] ?? 'Optik form okunamadı (anchor/köşe bulunamadı).',
                ], 422);
            }

            // 4. Sonucu ogrenci_sonuclar tablosuna kaydet
            // Bu, formu her hizala/okut dediginde -- okuma basarili oldugu
            // surece (anchor/kose bulunabildigi surece, cevaplar yanlis
            // olsa bile) -- Gecmis Sonuclar listesine bir kayit ekler.
            OgrenciSonuc::create([
                'sinav_id'          => $sinav->id,
                'ogrenci_no'        => $sonuc['student_no'] ?? null,
                'ogrenci_ad_soyad'  => null,
                'ogrenci_cevaplari' => $sonuc['student_answers'] ?? [],
                'dogru_sayisi'      => $sonuc['correct_count'] ?? 0,
                'yanlis_sayisi'     => $sonuc['wrong_count'] ?? 0,
                'bos_sayisi'        => $sonuc['blank_count'] ?? 0,
                'toplam_puan'       => $sonuc['score'] ?? 0,
                'gorsel_yolu'       => 'optik_forms/' . $imageName,
            ]);

            return response()->json([
                'success'    => true,
                'ogrenci_no' => $sonuc['student_no'] ?? 'Okunamadı',
                'dogru'      => $sonuc['correct_count'] ?? 0,
                'yanlis'     => $sonuc['wrong_count'] ?? 0,
                'bos'        => $sonuc['blank_count'] ?? 0,
                'puan'       => number_format($sonuc['score'] ?? 0, 2),
                'status'     => $sonuc['status'] ?? 'success',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()], 500);
        }
    }

    // --- GEÇMİŞ SONUÇLAR (veritabanından çeker) ---
    public function gecmisSonuclar()
    {
        try {
            // DÜZELTME: Daha önce OgrenciSonuc::with('sinav') kullanılıyordu.
            // OgrenciSonuc modelinde 'sinav' adinda bir iliski (relationship)
            // tanimli olmadigi icin bu satir Laravel'de bir istisna
            // (BadMethodCallException) firlatiyordu; bu istisna asagidaki
            // catch blogunda sessizce yakalanip 'success' => false donuyordu
            // ve frontend de bunu "henuz kayit yok" olarak gosteriyordu --
            // OYSA kayitlar veritabanina DOGRU sekilde dusuyordu, sadece bu
            // listeleme sorgusu patliyordu. Artik iliskiye bagli olmadan,
            // sinav_id uzerinden manuel eslestirme yapiyoruz.
            $sinavAdlari = Sinav::pluck('sinav_adi', 'id');

            $sonuclar = OgrenciSonuc::orderBy('id', 'desc')
                ->get()
                ->map(function ($s) use ($sinavAdlari) {
                    return [
                        'id'            => $s->id,
                        'exam_name'     => $sinavAdlari[$s->sinav_id] ?? 'Genel Optik Sınav',
                        'ogrenci_no'    => $s->ogrenci_no,
                        'correct_count' => $s->dogru_sayisi,
                        'wrong_count'   => $s->yanlis_sayisi,
                        'empty_count'   => $s->bos_sayisi,
                        'total_score'   => $s->toplam_puan,
                        'created_at'    => $s->created_at,
                    ];
                });

            return response()->json(['success' => true, 'data' => $sonuclar]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}