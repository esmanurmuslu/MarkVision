<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Ders Listesi
    public function index(Request $request)
    {
        $search = $request->search;

        $courses = Course::with('department')
            ->when($search, function ($query) use ($search) {
                $query->where('course_code', 'like', "%{$search}%")
                      ->orWhere('course_name', 'like', "%{$search}%");
            })
            ->orderBy('course_code')
            ->paginate(10);

        return view('courses.index', compact('courses', 'search'));
    }

    // Ders Ekleme Formu
    public function create()
    {
        $departments = Department::orderBy('department_name')->get();

        return view('courses.create', compact('departments'));
    }

    // Ders Kaydet
    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'course_code'   => 'required|unique:courses,course_code',
            'course_name'   => 'required'
        ]);

        Course::create($request->all());

        return redirect()->route('courses.index')
            ->with('success', 'Ders başarıyla eklendi.');
    }

    // Düzenleme Formu
    public function edit($id)
    {
        $course = Course::findOrFail($id);
        $departments = Department::orderBy('department_name')->get();

        return view('courses.edit', compact('course', 'departments'));
    }

    // Güncelle
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'course_code'   => 'required|unique:courses,course_code,' . $course->id,
            'course_name'   => 'required'
        ]);

        $course->update($request->all());

        return redirect()->route('courses.index')
            ->with('success', 'Ders başarıyla güncellendi.');
    }

    // Sil
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Ders başarıyla silindi.');
    }
}