<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Obs\ExamResult;
use App\Models\Obs\Student; // EKLENDİ: Öğrenciyi sorgulamak için gerekli

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $results = ExamResult::with(['student', 'exam'])
            ->when($search, function ($query) use ($search) {
                $query->where('student_no', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return view('obs.results.index', compact('results', 'search'));
    }

    public function show($id)
    {
        $result = ExamResult::with(['student', 'exam'])->findOrFail($id);

        return view('obs.results.show', compact('result'));
    }

    // YENİ EKLENEN METOT: Optikten gelen sonuçları kaydetme
    public function store(Request $request)
    {
        // Gelen verileri doğrula (Senin yapına göre doğru/yanlış/boş sayılarını da ekledik)
        $request->validate([
            'student_no'    => 'required|string',
            'exam_id'       => 'required|integer',
            'score'         => 'required|numeric',
            'correct_count' => 'nullable|integer',
            'wrong_count'   => 'nullable|integer',
            'blank_count'   => 'nullable|integer',
        ]);

        // Öğrenci numarasından OBS'deki öğrenciyi bul
        $student = Student::where('student_no', $request->student_no)->first();

        // Öğrenci sistemde varsa kaydı oluştur
        if ($student) {
            $student->examResults()->create([
                'exam_id'       => $request->exam_id,
                'score'         => $request->score,
                'correct_count' => $request->correct_count ?? 0,
                'wrong_count'   => $request->wrong_count ?? 0,
                'blank_count'   => $request->blank_count ?? 0,
                'status'        => $request->status ?? 'pending' // Başlangıç durumu, onaya düşmesi için
            ]);

            return response()->json(['mesaj' => 'Optik sonuç OBS sistemine başarıyla işlendi!'], 200);
        }

        return response()->json(['hata' => 'Öğrenci numarası OBS sisteminde bulunamadı.'], 404);
    }

    public function approve(ExamResult $result)
    {
        $result->update([
            'status' => 'success'
        ]);

        return redirect()
            ->route('obs.pending')
            ->with('success', 'Form başarıyla onaylandı.');
    }

    public function export()
    {
        // Çıktı tamponunu temizle
        if (ob_get_level()) {
            ob_end_clean();
        }

        $results = ExamResult::with(['student', 'exam'])->get();

        $fileName = 'sonuclar_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
        ];

        $callback = function () use ($results) {

            $file = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Öğrenci No',
                'Ad Soyad',
                'Ders',
                'Sınav',
                'Doğru',
                'Yanlış',
                'Boş',
                'Puan',
                'Durum'
            ], ';');

            foreach ($results as $result) {

                fputcsv($file, [

                    $result->student_no,

                    $result->student
                        ? $result->student->student_name . ' ' . $result->student->student_surname
                        : 'Bulunamadı',

                    $result->exam->course_name ?? '-',

                    $result->exam->exam_type ?? '-',

                    $result->correct_count,

                    $result->wrong_count,

                    $result->blank_count,

                    $result->score,

                    $result->status

                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}