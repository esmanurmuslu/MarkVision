<?php

namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResultsExport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Sinav;
use App\Models\OgrenciSonuc;
use App\Models\Obs\Exam as ObsExam;
use App\Models\Obs\Student as ObsStudent;
use App\Models\Obs\ExamResult as ObsExamResult;

class MarkVisionController extends Controller
{
   private string $pythonPath = 'python';

    private const AKTIF_SINAV_SESSION_KEY = 'aktif_sinav_id';

    public function index()
    {
        return view('markvision-panel');
    }

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

                $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);

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

    public function sifreDegistir(Request $request)
    {
        $request->validate([
            'eski_sifre' => 'required',
            'yeni_sifre' => 'required|min:6',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->eski_sifre, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mevcut şifre yanlış.']);
        }

        $user->password = Hash::make($request->yeni_sifre);
        $user->save();

        return response()->json(['success' => true]);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function registerStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:100',
                'surname'  => 'required|string|max:100',
                'email'    => 'required|email|max:150|unique:teachers,email',
                'tc_no'    => 'required|string|max:11|unique:teachers,tc_no',
                'password' => 'required|string|min:6',
            ], [
                'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
                'tc_no.unique' => 'Bu TC kimlik numarası zaten kayıtlı.',
            ]);

            $teacherId = DB::table('teachers')->insertGetId([
                'name'       => $validated['name'],
                'surname'    => $validated['surname'],
                'email'      => $validated['email'],
                'tc_no'      => $validated['tc_no'],
                'password'   => Hash::make($validated['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teacher = DB::table('teachers')->find($teacherId);
            $userModel = User::find($teacher->id);
            if (!$userModel) {
                $userModel = new User();
                $userModel->forceFill((array) $teacher);
            }
            Auth::login($userModel);

            return redirect()->route('panel.index')->with('success', 'Kayıt başarılı! Hoş geldiniz.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->except('password'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['general' => 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage()])
                ->withInput($request->except('password'));
        }
    }

    // Mobil (Flutter) uygulamadan kayıt için JSON dönen sürüm.
    // registerStore() ile aynı validasyonu kullanır ama redirect yerine
    // JSON döner ve Auth::login() çağırmaz (api.php rotaları stateless,
    // session/cookie tabanlı oturum açmanın mobilde bir karşılığı yok).
    public function registerApi(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:100',
                'surname'  => 'required|string|max:100',
                'email'    => 'required|email|max:150|unique:teachers,email',
                'tc_no'    => 'required|string|max:11|unique:teachers,tc_no',
                'password' => 'required|string|min:6',
            ], [
                'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
                'tc_no.unique' => 'Bu TC kimlik numarası zaten kayıtlı.',
            ]);

            $teacherId = DB::table('teachers')->insertGetId([
                'name'       => $validated['name'],
                'surname'    => $validated['surname'],
                'email'      => $validated['email'],
                'tc_no'      => $validated['tc_no'],
                'password'   => Hash::make($validated['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teacher = DB::table('teachers')->find($teacherId);

            return response()->json([
                'success' => true,
                'message' => 'Kayıt başarılı.',
                'user'    => [
                    'id'  => $teacher->id,
                    'ad'  => $teacher->name . ' ' . $teacher->surname,
                    'rol' => 'Öğretmen / Akademisyen',
                ],
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

    public function saveAnswerKey(Request $request)
    {
        try {
            $request->validate([
                'exam_name'   => 'required|string|max:255',
                'ders_kodu'   => 'nullable|string|max:50',
                // Mobilde boş string veya null gelebileceği için nullable ve integer olmasını sağlıyoruz
                'obs_exam_id' => 'nullable', 
                'answers'     => 'required|array|min:1',
                'answers.*'   => 'required|string|in:A,B,C,D,E',
            ]);

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

            // OBS sınav ID boş veya "seçilmedi" ise null yapalım
            $obsExamId = $request->input('obs_exam_id');
            if (empty($obsExamId) || $obsExamId === 'null' || $obsExamId === '0') {
                $obsExamId = null;
            }

            
            
            $sinav = new Sinav();
            $sinav->sinav_adi = $request->input('exam_name');
            $sinav->ders_kodu = $dersKodu;
            $sinav->cevap_anahtari = $request->input('answers');
            $sinav->obs_exam_id = $obsExamId;
            $sinav->question_weights = $request->input('question_weights'); // YENİ EKLENDİ
            $sinav->save();

            // Web panelinde (session var) eskisi gibi "aktif sınav" session'a yazılır.
            // Mobil/api.php üzerinden gelen isteklerde session hiç yoktur (stateless),
            // bu durumda hasSession() false döner ve burada patlamadan geçilir.
            // Mobil taraf aktif sınavı bu response'taki 'sinav_id' değeriyle takip eder.
            if ($request->hasSession()) {
                $request->session()->put(self::AKTIF_SINAV_SESSION_KEY, $sinav->id);
            }

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

    public function getLatestAnswerKey(Request $request)
    {
        try {
            // Session yerine son eklenen sınavı alalım ki hata vermesin:
            $sinav = Sinav::latest()->first();
            $sinavId = $sinav ? $sinav->id : null;

            if (!$sinavId) {
                return response()->json(['success' => false, 'message' => 'Bu oturumda henüz bir cevap anahtarı girilmedi.']);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav) {
                if ($request->hasSession()) {
                    $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
                }
                return response()->json(['success' => false, 'message' => 'Aktif cevap anahtarı bulunamadı.']);
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

    // OBS'de kayıtlı sınavları listeler (Cevap Anahtarı ekranındaki dropdown için)
    public function obsSinavlariGetir(Request $request)
    {
        // DİKKAT: header kodu süslü parantezin İÇİNDE olmalı!
        header('ngrok-skip-browser-warning: true');
        
        try {
            $sinavlar = ObsExam::with('teacher')
                ->orderBy('id', 'desc')
                ->get(['id', 'teacher_id', 'course_name', 'exam_type', 'total_questions'])
                ->map(function ($sinav) {
                    return [
                        'id'              => $sinav->id,
                        'course_name'     => $sinav->course_name,
                        'exam_type'       => $sinav->exam_type,
                        'total_questions' => $sinav->total_questions,
                        'teacher_name'    => $sinav->teacher->name ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $sinavlar,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function optikOkut(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|image|max:10240',
            ]);

            // Aktif sınavı bulma sırası:
            // 1) İstekle birlikte doğrudan sinav_id gelmiş mi (mobil bunu kullanacak,
            //    saveAnswerKey()'in döndürdüğü sinav_id'yi saklayıp burada geri gönderir)
            // 2) Session'da bir aktif sınav var mı (web paneli - eskisi gibi çalışır)
            // 3) exam_name gelmiş mi, o isme ait en güncel sınavı bul (mobil için
            //    sinav_id'yi saklamadıysa yedek yol)
            $sinavId = $request->input('sinav_id');

            if (!$sinavId && $request->hasSession()) {
                $sinavId = $request->session()->get(self::AKTIF_SINAV_SESSION_KEY);
            }

            if (!$sinavId && $request->filled('exam_name')) {
                $sinavByName = Sinav::where('sinav_adi', $request->input('exam_name'))
                    ->latest()
                    ->first();
                $sinavId = $sinavByName->id ?? null;
            }

            if (!$sinavId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Önce "Cevap Anahtarı" bölümünden bu sınav için bir cevap anahtarı kaydetmelisiniz.',
                ], 422);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav || empty($sinav->cevap_anahtari)) {
                if ($request->hasSession()) {
                    $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Aktif cevap anahtarı bulunamadı. Lütfen "Cevap Anahtarı" bölümünden yeniden kaydedin.',
                ], 422);
            }

            $answerKey = $sinav->cevap_anahtari;
            $totalQuestions = count($answerKey);

            $publicDir = storage_path('app/public/optik_forms');
            if (!is_dir($publicDir)) mkdir($publicDir, 0777, true);

            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
            $imageName = 'optik_' . time() . '_' . uniqid() . '.' . $extension;

            $uploadedFile->move($publicDir, $imageName);
            $imagePath = $publicDir . DIRECTORY_SEPARATOR . $imageName;

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $sinavBilgisi = [
                'exam_id'         => $sinav->id,
                'total_questions' => $totalQuestions,
                'answer_key'      => $answerKey,
                'question_weights'=> $sinav->question_weights ?? [], // YENİ EKLENDİ
            ];
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            $omrDir = base_path('omr_scripts');
            $pipelinePath = $omrDir . DIRECTORY_SEPARATOR . 'pipeline_main.py';
            $koordinatPath = $omrDir . DIRECTORY_SEPARATOR . 'koordinat_haritasi.json';

            // Windows izin sorununu tamamen ortadan kaldıran saf exec yöntemi
            $command = sprintf(
                '"%s" "%s" "%s" "%s" "%s" 2>&1',
                $this->pythonPath,
                $pipelinePath,
                $imagePath,
                $koordinatPath,
                $sinavPath
            );

            exec($command, $outputArray, $resultCode);
            $output = trim(implode("\n", $outputArray));
            $errorOutput = $resultCode !== 0 ? $output : '';

            @unlink($sinavPath);

            if (!$output) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python işlemi hiçbir çıktı üretmedi. Detay: ' . $errorOutput,
                ], 500);
            }

            // ÇÖZÜM 2: Python fazladan hata metni bassa bile sadece saf JSON kısmını cımbızla çekiyoruz
            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $cleanJson = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
                $sonuc = json_decode($cleanJson, true);
            } else {
                $sonuc = json_decode($output, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE || !isset($sonuc['basarili'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kağıt algılanamadı. Lütfen kamerayı kağıda tam tepeden (dik) ve yeşil köşelere oturacak şekilde tutun.',
                ], 422);
            }

            if (!$sonuc['basarili']) {
                return response()->json([
                    'success' => false,
                    'message' => $sonuc['hata'] ?? 'Optik form okunamadı (Köşeler bulunamadı veya açı çok yamuk).',
                ], 422);
            }

            // ÇÖZÜM 1: Öğrenci numarası boş okunursa veritabanını çökertmek yerine kullanıcıyı uyar
            $ogrenciNo = $sonuc['student_no'] ?? null;
            if (empty($ogrenciNo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hata: Öğrenci numarası okunamadı! Lütfen kodlamayı kontrol edip formu tekrar net bir şekilde okutun.',
                ], 422);
            }

            $ogrenciSonuc = OgrenciSonuc::create([
                'sinav_id'          => $sinav->id,
                'ogrenci_no'        => $ogrenciNo,
                'ogrenci_ad_soyad'  => null,
                'ogrenci_cevaplari' => $sonuc['student_answers'] ?? [],
                'dogru_sayisi'      => $sonuc['correct_count'] ?? 0,
                'yanlis_sayisi'     => $sonuc['wrong_count'] ?? 0,
                'bos_sayisi'        => $sonuc['blank_count'] ?? 0,
                'toplam_puan'       => $sonuc['score'] ?? 0,
                'gorsel_yolu'       => 'optik_forms/' . $imageName,
            ]);

            return response()->json([
                'success'          => true,
                'ogrenci_sonuc_id' => $ogrenciSonuc->id,
                'obs_hazir'        => !empty($sinav->obs_exam_id),
                'ogrenci_no'       => $ogrenciNo,
                'dogru'            => $sonuc['correct_count'] ?? 0,
                'yanlis'           => $sonuc['wrong_count'] ?? 0,
                'bos'              => $sonuc['blank_count'] ?? 0,
                'puan'             => number_format($sonuc['score'] ?? 0, 2),
                'status'           => $sonuc['status'] ?? 'success',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()], 500);
        }
    }

    public function gecmisSonuclar()
    {
        try {
            $sinavlar = Sinav::all()->keyBy('id');

            $sonuclar = OgrenciSonuc::orderBy('id', 'desc')
                ->get()
                ->map(function ($s) use ($sinavlar) {
                    $sinav = $sinavlar[$s->sinav_id] ?? null;

                    return [
                        'id'               => $s->id,
                        'exam_name'        => $sinav->sinav_adi ?? 'Genel Optik Sınav',
                        'ogrenci_no'       => $s->ogrenci_no,
                        'correct_count'    => $s->dogru_sayisi,
                        'wrong_count'      => $s->yanlis_sayisi,
                        'empty_count'      => $s->bos_sayisi,
                        'total_score'      => $s->toplam_puan,
                        'created_at'       => $s->created_at,
                        'obs_kayit_edildi' => (bool) $s->obs_kayit_edildi,
                        'obs_hazir'        => (bool) ($sinav->obs_exam_id ?? false),
                    ];
                });

            return response()->json(['success' => true, 'data' => $sonuclar]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Taranan sonucu OBS'ye (exam_results tablosuna) kaydeder
    public function obsKaydet(Request $request)
    {
        try {
            $request->validate([
                'ogrenci_sonuc_id' => 'required|integer',
            ]);

            $ogrenciSonuc = OgrenciSonuc::find($request->ogrenci_sonuc_id);

            if (!$ogrenciSonuc) {
                return response()->json(['success' => false, 'message' => 'Sonuç bulunamadı.'], 404);
            }

            if ($ogrenciSonuc->obs_kayit_edildi) {
                return response()->json(['success' => false, 'message' => 'Bu sonuç zaten OBS\'ye kaydedilmiş.'], 422);
            }

            $sinav = Sinav::find($ogrenciSonuc->sinav_id);

            if (!$sinav || !$sinav->obs_exam_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu sınav için OBS eşleştirmesi yapılmamış. Lütfen "Cevap Anahtarı" ekranından bu sınavı OBS sınavıyla eşleştirin.',
                ], 422);
            }

            if (!$ogrenciSonuc->ogrenci_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenci numarası okunamadığı için OBS\'ye kaydedilemiyor.',
                ], 422);
            }

            $obsOgrenci = ObsStudent::find($ogrenciSonuc->ogrenci_no);

            if (!$obsOgrenci) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenci numarası "' . $ogrenciSonuc->ogrenci_no . '" OBS\'de kayıtlı değil.',
                ], 422);
            }

            // Aynı öğrenci ve sınav için kayıt varsa güncelle, yoksa yeni oluştur
            $examResult = ObsExamResult::updateOrCreate(
                [
                    'exam_id'    => $sinav->obs_exam_id,
                    'student_no' => $ogrenciSonuc->ogrenci_no,
                ],
                [
                    'score'           => $ogrenciSonuc->toplam_puan,
                    'correct_count'   => $ogrenciSonuc->dogru_sayisi,
                    'wrong_count'     => $ogrenciSonuc->yanlis_sayisi,
                    'blank_count'     => $ogrenciSonuc->bos_sayisi,
                    'student_answers' => $ogrenciSonuc->ogrenci_cevaplari,
                ]
            );

            $ogrenciSonuc->obs_exam_result_id = $examResult->id;
            $ogrenciSonuc->obs_kayit_edildi = true;
            $ogrenciSonuc->save();

            return response()->json([
                'success' => true,
                'message' => 'Sonuç OBS\'ye başarıyla kaydedildi.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function exportExcel()
{
    return Excel::download(new ResultsExport, 'zipgrade_sonuclar.xlsx');
}
public function anahtarOku(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|image|max:10240',
            ]);

            $publicDir = storage_path('app/public/optik_forms');
            if (!is_dir($publicDir)) mkdir($publicDir, 0777, true);

            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
            $imageName = 'anahtar_' . time() . '_' . uniqid() . '.' . $extension;

            $uploadedFile->move($publicDir, $imageName);
            $imagePath = $publicDir . DIRECTORY_SEPARATOR . $imageName;

            // Cevap anahtarı okuma için geçici boş bir sınav şablonu verisi oluşturuyoruz
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $sinavBilgisi = [
                'exam_id' => 0,
                'total_questions' => 20, // Formunuza göre soru sayısı (örn: 20, 50 vb.)
                'answer_key' => [],
            ];
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_anahtar_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            $omrDir = base_path('omr_scripts');
            $pipelinePath = $omrDir . DIRECTORY_SEPARATOR . 'pipeline_main.py';
            $koordinatPath = $omrDir . DIRECTORY_SEPARATOR . 'koordinat_haritasi.json';

            $command = sprintf(
                '"%s" "%s" "%s" "%s" "%s" 2>&1',
                $this->pythonPath,
                $pipelinePath,
                $imagePath,
                $koordinatPath,
                $sinavPath
            );

            exec($command, $outputArray, $resultCode);
            $output = trim(implode("\n", $outputArray));

            @unlink($sinavPath);

            if (!$output) {
                return response()->json(['success' => false, 'message' => 'Python işlemi çıktı üretmedi.'], 500);
            }

            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $cleanJson = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
                $sonuc = json_decode($cleanJson, true);
            } else {
                $sonuc = json_decode($output, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE || !isset($sonuc['basarili']) || !$sonuc['basarili']) {
                return response()->json([
                    'success' => false,
                    'message' => $sonuc['hata'] ?? 'Cevap anahtarı formu okunamadı.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'cevaplar' => $sonuc['student_answers'] ?? [],
                'message' => 'Cevap anahtarı başarıyla tarandı.',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()], 500);
        }
    }
    // --- TELEFON UYGULAMASI İÇİN SENKRONİZASYON API METOTLARI ---

    public function apiSiniflariGetir(Request $request)
    {
        // Veritabanındaki sınıfları telefona JSON olarak döndürür
        $siniflar = DB::table('siniflar')->orderBy('class_name')->get();
        return response()->json(['success' => true, 'data' => $siniflar]);
    }

    public function apiSinifKaydet(Request $request)
    {
        try {
            $validated = $request->validate([
                'class_name' => 'required|string|max:100',
            ]);

            // Telefonda eklenen sınıfı veritabanına kaydeder
            $id = DB::table('siniflar')->insertGetId([
                'class_name' => $validated['class_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'Sınıf eklendi']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiOgrencileriGetir(Request $request)
    {
        $ogrenciler = DB::table('students')->get();
        return response()->json(['success' => true, 'data' => $ogrenciler]);
    }

    // Telefonda eklenen öğrenciyi veritabanına kaydeder (apiOgrencileriGetir'in eşi).
    // DİKKAT: 'students' tablosunun gerçek kolon adlarını (name/student_no vb.)
    // migration dosyanızdan teyit edip gerekirse burayı güncelleyin — bu dosya
    // bende yoktu, bu yüzden en olası isimlerle yazıldı.
    public function apiOgrenciKaydet(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'       => 'required|string|max:150',
                'student_no' => 'required|string|max:50',
                'class_id'   => 'nullable|integer',
            ]);

            $id = DB::table('students')->insertGetId([
                'name'       => $validated['name'],
                'student_no' => $validated['student_no'],
                'class_id'   => $validated['class_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id, 'message' => 'Öğrenci eklendi']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}