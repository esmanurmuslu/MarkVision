<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login ekranı
    public function showLogin()
    {
        return view('auth.login');
    }

    // Giriş işlemi
    public function login(Request $request)
    {
        $request->validate([
            'tc_no' => 'required',
            'password' => 'required'
        ]);

        $teacher = Teacher::where('tc_no', $request->tc_no)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return back()
                ->withErrors([
                    'login' => 'TC Kimlik No veya şifre hatalı.'
                ])
                ->withInput();
        }

        session([
            'teacher_id' => $teacher->id,
            'teacher_name' => $teacher->name . ' ' . $teacher->surname
        ]);

        return redirect()->route('dashboard');
    }

    // Çıkış
    public function logout()
    {
        session()->flush();

        return redirect()->route('login');
    }
}