<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Obs\Student;
use App\Models\Obs\ExamResult;
use Illuminate\Http\Request;
use App\Models\Obs\Exam;

class ObsController extends Controller
{
    // OBS Ana Sayfası: Öğrenci Listesi
    public function index()
    {
        $students = Student::orderBy('student_no', 'asc')->paginate(15);
        return view('obs.index', compact('students'));
    }

    // Numarası okunamayan ve onay bekleyen (pending_review) formlar
    public function pendingReviews()
    {
        $pendingResults = ExamResult::with(['student', 'exam'])
            ->where('status', 'pending_review')
            ->get();

        // Blade dosyamızda $bekleyenler yazdığımız için eşleştirme yapıyoruz
        $bekleyenler = $pendingResults;

        return view('obs.pending', compact('pendingResults', 'bekleyenler'));
    }

    // Optik okuyucudan gelen veriyi veritabanına kaydeden API fonksiyonu
    public function storeOpticalScan(Request $request)
    {
        $studentNo = $request->input('student_no');
        $examId = $request->input('exam_id');
        $studentAnswers = $request->input('answers', []);

        // Sınavı bul
        $exam = Exam::findOrFail($examId);

        $answerKey = $exam->answer_key ?? [];

        $correct = 0;
        $wrong = 0;
        $blank = 0;

        foreach ($answerKey as $question => $correctAnswer) {
            $studentAnswer = $studentAnswers[$question] ?? null;

            if ($studentAnswer === null || $studentAnswer === '') {
                $blank++;
            } elseif ($studentAnswer == $correctAnswer) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        // 100'lük sistem
        $score = 0;
        if ($exam->total_questions > 0) {
            $score = round(($correct / $exam->total_questions) * 100, 2);
        }

        $result = ExamResult::create([
            'student_no' => $studentNo,
            'exam_id' => $examId,
            'student_answers' => $studentAnswers,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'blank_count' => $blank,
            'score' => $score,
            'status' => $studentNo ? 'success' : 'pending_review',
            'optical_image_url' => $request->input(
                'image_url',
                'assets/scanned_forms/default.jpg'
            )
        ]);

        return response()->json([
            'status' => 'success',
            'correct' => $correct,
            'wrong' => $wrong,
            'blank' => $blank,
            'score' => $score,
            'result_id' => $result->id
        ], 201);
    }

    // YENİ EKLENEN FONKSİYON: JavaScript'ten (AJAX) gelen öğrenci numarasını günceller
    public function numarayiKurtar(Request $request)
    {
        $request->validate([
            'sonuc_id' => 'required|exists:exam_results,id',
            'ogrenci_no' => 'required|string|max:15'
        ]);

        try {
            $sonuc = ExamResult::findOrFail($request->sonuc_id);
            
            $sonuc->student_no = $request->ogrenci_no; 
            
            // Senin yukarıdaki 'storeOpticalScan' mantığına uyarak 'completed' yerine 'success' yapıyoruz
            $sonuc->status = 'success'; 
            $sonuc->save();

            return response()->json([
                'success' => true,
                'message' => 'Öğrenci numarası başarıyla güncellendi!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ], 500);
        }
    }
}