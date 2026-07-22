<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    // Liste
    public function index(Request $request)
    {
        $search = $request->search;

        $teachers = Teacher::when($search, function ($query) use ($search) {
            $query->where('tc_no', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%");
        })
        ->orderBy('name')
        ->paginate(10);

        return view('teachers.index', compact('teachers', 'search'));
    }

    // Ekleme Formu
    public function create()
    {
        return view('teachers.create');
    }

    // Kaydet
    public function store(Request $request)
    {
        $request->validate([
            'tc_no' => 'required|size:11|unique:teachers,tc_no',
            'name' => 'required',
            'surname' => 'required',
            'email' => 'nullable|email',
            'password' => 'required|min:6',
        ]);

        Teacher::create([
            'tc_no' => $request->tc_no,
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('teachers.index')
            ->with('success', 'Öğretmen başarıyla eklendi.');
    }

    // Düzenleme
    public function edit($id)
    {
        $teacher = Teacher::findOrFail($id);

        return view('teachers.edit', compact('teacher'));
    }

    // Güncelle
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $request->validate([
            'tc_no' => 'required|size:11|unique:teachers,tc_no,' . $teacher->id,
            'name' => 'required',
            'surname' => 'required',
            'email' => 'nullable|email',
        ]);

        $teacher->update([
            'tc_no' => $request->tc_no,
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
        ]);

        return redirect()->route('teachers.index')
            ->with('success', 'Öğretmen güncellendi.');
    }

    // Sil
    public function destroy($id)
    {
        Teacher::findOrFail($id)->delete();

        return redirect()->route('teachers.index')
            ->with('success', 'Öğretmen silindi.');
    }
}