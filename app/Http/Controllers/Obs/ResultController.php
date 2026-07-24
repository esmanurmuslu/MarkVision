<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Obs\ExamResult;

class ResultController extends Controller
{
    public function index(Request $request)
{
    $search = $request->search;

    $results = ExamResult::with(['student','exam'])

        ->when($search, function ($query) use ($search) {

            $query->where('student_no','like',"%{$search}%");

        })

        ->latest()

        ->paginate(15);

    return view('obs.results.index', compact('results','search'));
}

public function show($id)
{
    $result = ExamResult::with(['student', 'exam'])->findOrFail($id);

    return view('obs.results.show', compact('result'));
}

public function export()
{

// Çıktı tamponunu temizle ki öncesinde gelen boşluklar dosyayı bozmasın
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

        // Türkçe karakterler için
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

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
                    ? $result->student->student_name.' '.$result->student->student_surname
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