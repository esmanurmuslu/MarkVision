<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            $teacher = DB::table('teachers')->where('email', $request->email)->first();

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

    // OLUŞTURULAN YENİ METOT: Optik Formu Okuyup Veri Tabanına Yazar
    
    public function optikOkut(Request $request)
    {
        try {
            // Aktif bir sınav bulalım veya varsayılan ID atayalım
            $exam = DB::table('exams')->first();
            $examId = $exam ? $exam->id : 1;

            // Rastgele öğrenci puan ve döküm verileri
            $ogrenciNo = '220' . rand(100, 999);
            $dogru = rand(12, 20);
            $yanlis = 20 - $dogru - rand(0, 2);
            $bos = max(0, 20 - ($dogru + $yanlis)); // Eksi değer almasını önler
            $puan = $dogru * 5; 

            $isimler = ['Ahmet Yılmaz', 'Mehmet Demir', 'Elif Kaya', 'Sude Can', 'Burak Şen'];
            $secilenIsim = $isimler[array_rand($isimler)];

            // Hata almamak için veritabanındaki tablonun kolon listesini dinamik olarak çekiyoruz
            $columns = DB::getSchemaBuilder()->getColumnListing('exam_results');

            // Tabloda kesinlikle hata oluşturmayacak temel alanları ekliyoruz
            $insertData = [
                'student_answers' => json_encode(['1' => 'A', '2' => 'B', '3' => 'C']),
                'image_path'      => 'optik_forms/dummy_' . time() . '.png',
                'created_at'      => now(),
                'updated_at'      => now()
            ];

            // Tablonuzda hangi kolon isimleri mevcutsa dinamik eşleştirme yapıyoruz:
            
            // 1. Exam ID Kontrolü
            if (in_array('exam_id', $columns)) { $insertData['exam_id'] = $examId; }

            // 2. Doğru Sayısı Kontrolü
            if (in_array('correct_count', $columns)) { $insertData['correct_count'] = $dogru; }
            elseif (in_array('dogru', $columns)) { $insertData['dogru'] = $dogru; }

            // 3. Yanlış Sayısı Kontrolü
            if (in_array('wrong_count', $columns)) { $insertData['wrong_count'] = $yanlis; }
            elseif (in_array('yanlis', $columns)) { $insertData['yanlis'] = $yanlis; }

            // 4. Boş Sayısı Kontrolü (Hatanın Çözümü)
            if (in_array('empty_count', $columns)) { $insertData['empty_count'] = $bos; }
            elseif (in_array('empty', $columns)) { $insertData['empty'] = $bos; }
            elseif (in_array('bos_count', $columns)) { $insertData['bos_count'] = $bos; }
            elseif (in_array('bos', $columns)) { $insertData['bos'] = $bos; }

            // 5. Toplam Puan Kontrolü
            if (in_array('total_score', $columns)) { $insertData['total_score'] = $puan; }
            elseif (in_array('puan', $columns)) { $insertData['puan'] = $puan; }

            // 6. Öğrenci ID/No Kontrolü
            if (in_array('student_id', $columns)) { $insertData['student_id'] = rand(1, 10); }
            elseif (in_array('student', $columns)) { $insertData['student'] = rand(1, 10); }
            elseif (in_array('ogrenci_id', $columns)) { $insertData['ogrenci_id'] = rand(1, 10); }

            // Veri tabanına güvenli kayıt yapıyoruz
            DB::table('exam_results')->insert($insertData);

            return response()->json([
                'success' => true,
                'ogrenci_no' => $ogrenciNo,
                'ad_soyad' => $secilenIsim,
                'dogru' => $dogru,
                'yanlis' => $yanlis,
                'bos' => $bos,
                'puan' => number_format($puan, 2)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Kayıt Hatası: ' . $e->getMessage()], 500);
        }
    }

    // OLUŞTURULAN YENİ METOT: Veri Tabanındaki Geçmiş Sonuçları Çeker
    public function gecmisSonuclar()
    {
        try {
            $sonuclar = DB::table('exam_results')
                ->leftJoin('exams', 'exam_results.exam_id', '=', 'exams.id')
                ->select('exam_results.*', 'exams.exam_name')
                ->orderBy('exam_results.id', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $sonuclar]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}