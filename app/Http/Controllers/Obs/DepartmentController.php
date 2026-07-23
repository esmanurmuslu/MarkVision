<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // Liste
    public function index(Request $request)
    {
        $search = $request->search;

        $departments = Department::with('faculty')
            ->when($search, function ($query) use ($search) {
                $query->where('department_name', 'like', "%{$search}%");
            })
            ->orderBy('department_name')
            ->paginate(10);

        return view('obs.departments.index', compact('departments', 'search'));
    }

    // Ekleme Formu
    public function create()
    {
        $faculties = Faculty::orderBy('faculty_name')->get();

        return view('obs.departments.create', compact('faculties'));
    }

    // Kaydet
    public function store(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'department_name' => 'required',
            'degree_type' => 'required'
        ]);

        Department::create($request->all());

        return redirect()->route('obs.departments.index')
            ->with('success','Bölüm başarıyla eklendi.');
    }

    // Düzenleme Formu
    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $faculties = Faculty::orderBy('faculty_name')->get();

        return view('obs.departments.edit', compact('department','faculties'));
    }

    // Güncelle
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'department_name' => 'required',
            'degree_type' => 'required'
        ]);

        $department->update($request->all());

        return redirect()->route('obs.departments.index')
            ->with('success','Bölüm güncellendi.');
    }

    // Sil
    public function destroy($id)
    {
        Department::findOrFail($id)->delete();

        return redirect()->route('obs.departments.index')
            ->with('success','Bölüm silindi.');
    }
}