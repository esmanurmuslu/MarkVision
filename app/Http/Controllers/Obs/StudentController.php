<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Obs\Student;
use App\Models\Obs\Department;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // Öğrenci Listesi
    public function index(Request $request)
    {
        $search = $request->search;

        $students = Student::with('department')
            ->when($search, function ($query) use ($search) {
                $query->where('student_no', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%")
                    ->orWhere('student_surname', 'like', "%{$search}%");
            })
            ->orderBy('student_no')
            ->paginate(10);

        return view('obs.students.index', compact('students', 'search'));
    }

    // Öğrenci Ekleme Formu
    public function create()
    {
        $departments = Department::orderBy('department_name')->get();

        return view('obs.students.create', compact('departments'));
    }

    // Öğrenci Kaydet
    public function store(Request $request)
    {
        $request->validate([
            'student_no' => 'required|unique:students,student_no',
            'student_name' => 'required',
            'student_surname' => 'required',
            'department_id' => 'required|exists:departments,id',
        ]);

        Student::create($request->all());

        return redirect()->route('obs.students.index')
            ->with('success', 'Öğrenci başarıyla eklendi.');
    }

    // Düzenleme Formu
    public function edit($student_no)
    {
        $student = Student::findOrFail($student_no);
        $departments = Department::orderBy('department_name')->get();

        return view('obs.students.edit', compact('student', 'departments'));
    }

    // Güncelle
    public function update(Request $request, $student_no)
    {
        $student = Student::findOrFail($student_no);

        $request->validate([
            'student_name' => 'required',
            'student_surname' => 'required',
            'department_id' => 'required|exists:departments,id',
        ]);

        $student->update($request->all());

        return redirect()->route('obs.students.index')
            ->with('success', 'Öğrenci başarıyla güncellendi.');
    }

    // Sil
    public function destroy($student_no)
    {
        $student = Student::findOrFail($student_no);

        $student->delete();

        return redirect()->route('obs.students.index')
            ->with('success', 'Öğrenci başarıyla silindi.');
    }
}