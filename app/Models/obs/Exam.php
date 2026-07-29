<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;
use App\Models\Obs\Department;

class Exam extends Model
{
    protected $table = 'exams';

    protected $fillable = [
        'name',
        'course_id',
        'department_id',
        'teacher_id',
        'exam_date',
        'total_questions',
        'answer_key',
    ];

    protected $casts = [
        'answer_key' => 'array',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function results()
    {
        return $this->hasMany(ExamResult::class, 'exam_id');
    }
}