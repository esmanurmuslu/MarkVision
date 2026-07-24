<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Jobs\ProcessExamImageJob;

class ExamController extends Controller
{
    public function evaluate(Request $request)
    // Sınav Listesi
    public function index(Request $request)
    {
        $search = $request->search;

        $exams = Exam::with(['department', 'teacher', 'course'])
            ->when($search, function ($query) use ($search) {
                $query->where('course_name', 'like', "%{$search}%")
                      ->orWhere('exam_type', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('obs.exams.index', compact('exams', 'search'));
    }

    // Yeni Sınav Formu
    public function create()
    {
        $departments = Department::orderBy('department_name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $courses = Course::orderBy('course_name')->get();

        return view('obs.exams.create', compact(
            'departments',
            'teachers',
            'courses'
        ));
    }

    // Kaydet
    public function store(Request $request)
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

<<<<<<< HEAD:app/Http/Controllers/ExamController.php
            // 3. Kuyruğa at
            ProcessExamImageJob::dispatch($request->exam_id, $absolutePath);

            return response()->json(['status' => 'success', 'message' => 'İşlem kuyruğa alındı.'], 202);
        }

        return response()->json(['status' => 'error', 'message' => 'Dosya yüklenemedi.'], 400);
=======
        return redirect()
            ->route('obs.exams.index')
            ->with('success', 'Sınav başarıyla oluşturuldu.');
>>>>>>> obs_entegrasyonu:app/Http/Controllers/Obs/ExamController.php
    }

    public function results($id)
    {
<<<<<<< HEAD:app/Http/Controllers/ExamController.php
        // Burada sonuçları döndüreceğiz
        return response()->json(['status' => 'success', 'exam_id' => $id, 'data' => []]);
    }
=======
        $exam = Exam::findOrFail($id);

        $departments = Department::orderBy('department_name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $courses = Course::orderBy('course_name')->get();

        return view('obs.exams.edit', compact(
            'exam',
            'departments',
            'teachers',
            'courses'
        ));
    }

    // Güncelle
    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'teacher_id' => 'required|exists:teachers,id',
            'course_id' => 'required|exists:courses,id',
            'course_name' => 'required',
            'exam_type' => 'required',
            'total_questions' => 'required|integer|min:1',
        ]);

        $exam->update($request->all());

        return redirect()
            ->route('obs.exams.index')
            ->with('success', 'Sınav güncellendi.');
    }

    // Sil
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);

        $exam->delete();

        return redirect()
            ->route('obs.exams.index')
            ->with('success', 'Sınav silindi.');
    }

    public function answerKey($id)
{
    $exam = Exam::findOrFail($id);

   return view('obs.exams.answerkey', compact('exam'));
}

// Cevap Anahtarını Kaydet
public function saveAnswerKey(Request $request, $id)
{
    $exam = Exam::findOrFail($id);

    $answers = [];

    for($i=1; $i <= $exam->total_questions; $i++){

        $answers[$i] = $request->input('q'.$i);

    }

    $exam->answer_key = $answers;

    $exam->save();

    return redirect()
            ->route('obs.exams.index')
            ->with('success','Cevap anahtarı kaydedildi.');
}

>>>>>>> obs_entegrasyonu:app/Http/Controllers/Obs/ExamController.php
}