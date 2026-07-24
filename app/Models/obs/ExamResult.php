<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $table = 'exam_results';

    protected $fillable = [
        'exam_id',
        'student_no',
        'score',
        'status'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_no', 'student_no');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
}