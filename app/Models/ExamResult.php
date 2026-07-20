<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $table = 'exam_results';

    protected $fillable = [
        'student_no',
        'exam_id',
        'student_answers',
        'correct_count',
        'wrong_count',
        'blank_count',
        'score',
        'status',
        'optical_image_url'
    ];

    protected $casts = [
        'student_answers' => 'array',
        'score' => 'decimal:2',
    ];

    // Sonuç bir öğrenciye aittir
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_no', 'student_no');
    }

    // Sonuç bir sınava aittir
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
}