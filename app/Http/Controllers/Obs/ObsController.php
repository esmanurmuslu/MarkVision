<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use App\Models\Exam;

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
        $pendingResults = ExamResult::where('status', 'pending_review')
                                    ->whereNull('student_no')
                                    ->get();

        return view('obs.pending', compact('pendingResults'));
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
}