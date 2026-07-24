<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Obs\Exam;
use App\Models\Obs\Course;
use App\Models\Obs\Teacher;
use App\Models\Obs\Department;
use Illuminate\Http\Request;
use App\Jobs\ProcessExamImageJob;

class ExamController extends Controller
{

    // Optik değerlendirme işlemi
    public function evaluate(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $imagePath = $request->file('image')->store('public/exams');

        $absolutePath = storage_path('app/' . $imagePath);

        ProcessExamImageJob::dispatch(
            $request->exam_id,
            $absolutePath
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Optik değerlendirme kuyruğa alındı.'
        ], 202);
    }


    // Sınav Listesi
    public function index(Request $request)
    {
        $search = $request->search;

        $exams = Exam::with([
                'department',
                'teacher',
                'course'
            ])
            ->when($search, function ($query) use ($search) {

                $query->where('course_name', 'like', "%{$search}%")
                    ->orWhere('exam_type', 'like', "%{$search}%");

            })
            ->orderBy('id', 'desc')
            ->paginate(10);


        return view('obs.exams.index', compact(
            'exams',
            'search'
        ));
    }



    // Yeni sınav formu
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

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'teacher_id' => 'required|exists:teachers,id',
            'course_id' => 'required|exists:courses,id',
            'course_name' => 'required',
            'exam_type' => 'required',
            'total_questions' => 'required|integer|min:1',
        ]);


        Exam::create($request->all());


        return redirect()
            ->route('obs.exams.index')
            ->with('success','Sınav başarıyla oluşturuldu.');
    }



    // Sonuçlar
    public function results($id)
    {
        $exam = Exam::findOrFail($id);


        return view(
            'obs.exams.results',
            compact('exam')
        );
    }



    // Düzenleme formu
    public function edit($id)
    {
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
            ->with('success','Sınav güncellendi.');
    }




    // Sil
    public function destroy($id)
    {

        $exam = Exam::findOrFail($id);

        $exam->delete();


        return redirect()
            ->route('obs.exams.index')
            ->with('success','Sınav silindi.');
    }




    // Cevap anahtarı ekranı
    public function answerKey($id)
    {

        $exam = Exam::findOrFail($id);


        return view(
            'obs.exams.answerkey',
            compact('exam')
        );
    }




    // Cevap anahtarı kaydet
    public function saveAnswerKey(Request $request, $id)
    {

        $exam = Exam::findOrFail($id);


        $answers = [];


        for($i = 1; $i <= $exam->total_questions; $i++)
        {

            $answers[$i] = $request->input('q'.$i);

        }


        $exam->answer_key = $answers;

        $exam->save();



        return redirect()
            ->route('obs.exams.index')
            ->with(
                'success',
                'Cevap anahtarı kaydedildi.'
            );
    }

}