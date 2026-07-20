<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; // <-- Flask motoruna istek atabilmek için eklendi
use App\Models\User;

class MarkVisionController extends Controller
{
    public function index()
    {
        return view('markvision-panel');
    }

    public function login(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email', 'password' => 'required']);

            // E-posta karşılaştırmasını baş/son boşluk ve büyük/küçük harf
            // farkına karşı dayanıklı yapıyoruz (kopyala-yapıştırdan gelen
            // gizli boşluklar "yanlış şifre" gibi görünen sahte hatalara yol açabiliyordu).
            $email = trim(strtolower($request->email));
            $teacher = DB::table('teachers')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($teacher && Hash::check($request->password, $teacher->password)) {
                $userModel = User::find($teacher->id);
                if (!$userModel) {
                    $userModel = new User();
                    $userModel->forceFill((array)$teacher);
                }
                Auth::login($userModel);

                return response()->json([
                    'success' => true,
                    'user' => [
                        'ad' => $teacher->name . ' ' . $teacher->surname,
                        'rol' => 'Öğretmen / Akademisyen'
                    ]
                ]);
            }
            return response()->json(['success' => false, 'message' => 'E-posta veya şifre hatalı!'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function optikOkut(Request $request)
    {
        // HATA AYIKLAMA: PHP'nin kendi dosya yükleme hata kodunu yakalayalım
        if (!$request->hasFile('image')) {
            // Dosya PHP'ye hiç ulaşmadıysa veya reddedildiyse gerçek hata kodunu al:
            $hataKodu = isset($_FILES['image']['error']) ? $_FILES['image']['error'] : 'Dosya gönderilmedi (Frontend Form Hatası)';
            
            return response()->json([
                'success' => false,
                'message' => 'PHP dosyayı kabul etmedi! Hata Kodu: ' . $hataKodu
            ], 400);
        }
        
        

        try {
            // 1. Hangi sınav okutulacak? Frontend exam_id gönderiyorsa onu kullan,
            //    göndermiyorsa (eski davranışla uyumlu olsun diye) ilk sınavı al.
            $examId = $request->input('exam_id');
            $exam = $examId
                ? DB::table('exams')->where('id', $examId)->first()
                : DB::table('exams')->first();

            if (!$exam) {
                return response()->json(['success' => false, 'message' => 'Sınav bulunamadı.'], 404);
            }
            $examId = $exam->id;

           // 2. Arayüzden Gelen Görseli Al ve Basit Bir İsimle Kaydet
            // OpenCV'nin Türkçe karakterli (MUŞLU) yollarda çökmesini önlemek için
            // resmi doğrudan Python scriptinin yanına (omr_scripts) basit bir isimle taşıyoruz.
            $imageName = 'okunacak_form_' . time() . '.png';
            $request->file('image')->move(base_path('omr_scripts'), $imageName);

            // 3. Python motorunun okuyacağı sinav_bilgisi.json dosyasını dinamik olarak yazıyoruz
            $sinavBilgisi = [
                'exam_id' => $exam->id,
                'total_questions' => (int) $exam->total_questions,
                'answer_key' => json_decode($exam->answer_key, true),
            ];
            $sinavJson = base_path('omr_scripts/sinav_bilgisi.json');
            file_put_contents($sinavJson, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            // 4. PYTHON MOTORUNU ÇALIŞTIR
            $scriptPath = base_path('omr_scripts/pipeline_main.py');
            $koordinatJson = base_path('omr_scripts/koordinat_haritasi.json');

            putenv('TMP=' . storage_path('app'));
            putenv('TEMP=' . storage_path('app'));

            // DİKKAT: Artık Python'a uzun ve sorunlu absolute path yerine SADECE dosyanın adını ($imageName) gönderiyoruz!
            // pipeline_main.py zaten os.chdir ile kendi klasöründe (omr_scripts) arama yapacak.
            $process = new \Symfony\Component\Process\Process([
                'python', 
                $scriptPath, 
                $imageName, 
                $koordinatJson, 
                $sinavJson
            ]);

            $process->run();

            // HATA AYIKLAMA İÇİN:
            if (!$process->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'PYTHON DETAYI: ' . $process->getErrorOutput() 
                ], 500);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            $process->run();

            // HATA AYIKLAMA İÇİN:
          // HATA AYIKLAMA İÇİN:
            if (!$process->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    // Hatayı doğrudan ekrana yansıtıyoruz:
                    'message' => 'PYTHON DETAYI: ' . $process->getErrorOutput() 
                ], 500);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);
            // 5. PYTHON'DAN GELEN VERİLER
            $ogrenciNo = $result['student_no'] ?? null; // null => numara okunamadı
            $dogru = $result['correct_count'] ?? 0;
            $yanlis = $result['wrong_count'] ?? 0;
            $bos = $result['blank_count'] ?? 0;
            $puan = $result['score'] ?? 0;
            $status = $result['status'] ?? 'pending_review';

            // 6. İSMİ BUL
            $secilenIsim = 'Bilinmeyen Öğrenci';
            if ($ogrenciNo !== null) {
                $ogrenci = DB::table('students')->where('student_no', $ogrenciNo)->first();
                if ($ogrenci) {
                    $secilenIsim = $ogrenci->student_name . ' ' . $ogrenci->student_surname;
                }
            }

            // 7. VERİTABANINA KAYDET (exam_results tablosunun GERÇEK kolonlarıyla birebir)
            $insertData = [
                'exam_id'            => $examId,
                'student_answers'    => json_encode($result['student_answers'] ?? [], JSON_UNESCAPED_UNICODE),
                'correct_count'      => $dogru,
                'wrong_count'        => $yanlis,
                'blank_count'        => $bos,
                'score'              => $puan,
                'status'             => $status,
                'optical_image_url'  => $imageName,
                'updated_at'         => now(),
            ];

            // exam_results tablosunda (student_no, exam_id) UNIQUE kısıtlaması var.
            // Aynı öğrenci aynı sınav için tekrar okutulursa düz insert() "Duplicate entry"
            // hatası fırlatır; bu yüzden updateOrInsert ile "varsa güncelle, yoksa ekle" yapıyoruz.
            if ($ogrenciNo !== null) {
                DB::table('exam_results')->updateOrInsert(
                    ['student_no' => $ogrenciNo, 'exam_id' => $examId],
                    $insertData + ['created_at' => now()]
                );
            } else {
                // Numara okunamadıysa unique kısıtlamaya takılmadan yeni bir satır olarak ekle
                $insertData['student_no'] = null;
                $insertData['created_at'] = now();
                DB::table('exam_results')->insert($insertData);
            }

            // 8. EKRANA (YANDAKİ GÜZEL TASARIMA) GÖNDER
            return response()->json([
                'success'    => true,
                'ogrenci_no' => $ogrenciNo ?? 'Okunamadı',
                'ad_soyad'   => $secilenIsim,
                'dogru'      => $dogru,
                'yanlis'     => $yanlis,
                'bos'        => $bos,
                'puan'       => number_format($puan, 2),
                'durum'      => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PHP HATA DETAYI: ' . $e->getMessage() . ' | Dosya: ' . $e->getFile() . ' | Satır: ' . $e->getLine()
            ], 500);
        }
    }

    public function showRegister()
    {
        return view('auth.register'); // resources/views/auth/register.blade.php dosyan olmalı
    }

    // OLUŞTURULAN YENİ METOT: Veri Tabanındaki Geçmiş Sonuçları Çeker
    public function gecmisSonuclar()
    {
        try {
            // NOT: exams tablosunda 'exam_name' diye bir kolon yok
            // (gerçek kolonlar: course_name, exam_type) -- eskiden burada
            // "Unknown column 'exams.exam_name'" hatası alınıyordu.
            $sonuclar = DB::table('exam_results')
                ->leftJoin('exams', 'exam_results.exam_id', '=', 'exams.id')
                ->select(
                    'exam_results.*',
                    'exams.course_name as exam_name',
                    'exams.exam_type'
                )
                ->orderBy('exam_results.id', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $sonuclar]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* * ENTEGRE EDİLEN YENİ MOBİL API METODU
     * Flutter'dan gelen Base64 görseli alır, Flask motorunda işler ve veritabanına yazar.
     */
    public function formuOkuAPI(Request $request)
    {
        // 1. Flutter'dan gelen Base64 resim verisini alıyoruz
        $base64Data = $request->input('image'); 

        if (!$base64Data) {
            return response()->json(['success' => false, 'message' => 'Görsel verisi eksik.'], 400);
        }

        try {
            // 2. Resmi arkadaşının hazırladığı Flask (Python) OpenCV motoruna gönderiyoruz
            $pythonResponse = Http::post('http://127.0.0.1:5000/predict-omr', [
                'image' => $base64Data
            ]);
            
            $result = $pythonResponse->json();

            // Görüntü işleme motorundan hata döndüyse yakalıyoruz
            if (isset($result['error']) || (isset($result['status']) && $result['status'] == 'fail')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Optik form hizalanamadı veya okunamadı. Lütfen tekrar deneyin.'
                ], 422);
            }

            // 3. Python'dan gelen gerçek sonuçları senin dinamik DB yapın için hazırlıyoruz
            $exam = DB::table('exams')->first();
            $examId = $exam ? $exam->id : 1;

            // Python motorundan dönen veya dönmediğinde varsayılan atanacak veriler
            $studentNoFromOMR = $result['student_no'] ?? $result['student_id'] ?? null;
            $dogru = $result['correct_count'] ?? 0;
            $yanlis = $result['wrong_count'] ?? 0;
            $puan = $result['score'] ?? ($dogru * 5);
            $bos = max(0, 20 - ($dogru + $yanlis));
            $status = $result['status'] ?? 'pending_review';

            // exam_results tablosunun GERÇEK kolonlarıyla birebir (kolon adı tahmini kaldırıldı,
            // optikOkut() ile aynı mantık: bkz. markvision.sql şeması)
            $insertData = [
                'exam_id'            => $examId,
                'student_answers'    => json_encode($result['answers'] ?? $result['student_answers'] ?? [], JSON_UNESCAPED_UNICODE),
                'correct_count'      => $dogru,
                'wrong_count'        => $yanlis,
                'blank_count'        => $bos,
                'score'              => $puan,
                'status'             => $status,
                'optical_image_url'  => 'optik_forms/mobile_' . time() . '.png',
                'updated_at'         => now(),
            ];

            // (student_no, exam_id) UNIQUE kısıtlaması nedeniyle updateOrInsert kullanıyoruz.
            if ($studentNoFromOMR !== null) {
                DB::table('exam_results')->updateOrInsert(
                    ['student_no' => $studentNoFromOMR, 'exam_id' => $examId],
                    $insertData + ['created_at' => now()]
                );
            } else {
                $insertData['student_no'] = null;
                $insertData['created_at'] = now();
                DB::table('exam_results')->insert($insertData);
            }

            // 4. Flutter uygulamana başarı çıktısını ve analizleri dönüyoruz
            return response()->json([
                'success' => true,
                'ogrenci_no' => $studentNoFromOMR ?? 'Okunamadı',
                'dogru' => $dogru,
                'yanlis' => $yanlis,
                'bos' => $bos,
                'puan' => number_format($puan, 2)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Sistem Hatası veya Python Motoru Bağlantı Kesintisi: ' . $e->getMessage()
            ], 500);
        }
    }
}