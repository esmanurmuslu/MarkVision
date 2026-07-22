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

    $user = auth()->user(); // veya oturumdaki öğretmen

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

    // --- YENİ: KAYIT OL FORMUNU İŞLE ---
    // NOT: Gönderdiğin form verisine bakarak 'teachers' tablosunda
    // name, surname, email, tc_no, password kolonları olduğunu
    // varsaydım. Gerçek migration'ında kolon adları farklıysa
    // (örn. tc_no yerine tc_kimlik_no gibi) bana söyle, düzeltelim.
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

            // Kayıt olur olmaz otomatik giriş yaptırıyoruz.
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

    public function saveAnswerKey(Request $request)
    {
        try {
            $request->validate([
                'exam_name' => 'required|string|max:255',
                'ders_kodu' => 'nullable|string|max:50',
                'answers'   => 'required|array|min:1',
                'answers.*' => 'required|string|in:A,B,C,D,E',
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

            $sinav = Sinav::create([
                'sinav_adi'      => $request->input('exam_name'),
                'ders_kodu'      => $dersKodu,
                'cevap_anahtari' => $request->input('answers'),
            ]);

            $request->session()->put(self::AKTIF_SINAV_SESSION_KEY, $sinav->id);

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
            $sinavId = $request->session()->get(self::AKTIF_SINAV_SESSION_KEY);

            if (!$sinavId) {
                return response()->json(['success' => false, 'message' => 'Bu oturumda henüz bir cevap anahtarı girilmedi.']);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav) {
                $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
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

    public function optikOkut(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|image|max:10240',
            ]);

            $sinavId = $request->session()->get(self::AKTIF_SINAV_SESSION_KEY);

            if (!$sinavId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Önce "Cevap Anahtarı" bölümünden bu oturum için bir cevap anahtarı kaydetmelisiniz.',
                ], 422);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav || empty($sinav->cevap_anahtari)) {
                $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
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
            ];
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

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

    public function gecmisSonuclar()
    {
        try {
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