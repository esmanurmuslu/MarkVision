<?php

namespace App\Http\Controllers\Obs;

use App\Http\Controllers\Controller;
use App\Models\Obs\Student;
use App\Models\Obs\Teacher;
use App\Models\Obs\Department;
use App\Models\Obs\Course;
use App\Models\Obs\Exam;
use App\Models\Obs\ExamResult;

class DashboardController extends Controller
{
    public function index()
    {
       return view('obs.dashboard.index', [

            // İstatistikler
            'studentCount' => Student::count(),
            'teacherCount' => Teacher::count(),
            'departmentCount' => Department::count(),
            'courseCount' => Course::count(),
            'examCount' => Exam::count(),
            'resultCount' => ExamResult::count(),

            'pendingCount' => ExamResult::where('status', 'pending_review')->count(),

            // Son Eklenen Öğrenciler
            'students' => Student::orderBy('student_no', 'desc')
                                 ->take(5)
                                 ->get(),

            // Son Okunan Sonuçlar
            'latestResults' => ExamResult::with('student')
                                         ->latest()
                                         ->take(5)
                                         ->get(),
        ]);
    }
}