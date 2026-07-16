<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessExamImageJob;

class ExamController extends Controller
{
    public function evaluate(Request $request)
    {
        // 1. Gelen verilerin doğrulanması
        $request->validate([
            'exam_id' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // 2. Fotoğrafı kaydet
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/exams');
            $absolutePath = storage_path('app/' . $imagePath);

            // 3. Kuyruğa at
            ProcessExamImageJob::dispatch($request->exam_id, $absolutePath);

            return response()->json(['status' => 'success', 'message' => 'İşlem kuyruğa alındı.'], 202);
        }

        return response()->json(['status' => 'error', 'message' => 'Dosya yüklenemedi.'], 400);
    }

    public function results($id)
    {
        // Burada sonuçları döndüreceğiz
        return response()->json(['status' => 'success', 'exam_id' => $id, 'data' => []]);
    }
}