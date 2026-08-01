<?php

namespace App\Models;

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

    protected $casts = [
        'score' => 'float',
        'student_no' => 'integer',
        'exam_id' => 'integer',
        'id' => 'integer',         // EKSİK OLAN BUYDU
    ];
}