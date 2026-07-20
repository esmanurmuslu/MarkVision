<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $table = 'exams';

    public $timestamps = false;

    protected $fillable = [
        'department_id',
        'teacher_id',
        'course_id',
        'course_name',
        'exam_type',
        'total_questions',
        'answer_key'
    ];

    protected $casts = [
        'answer_key' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
public function results(): HasMany
{
    return $this->hasMany(ExamResult::class);
}

}