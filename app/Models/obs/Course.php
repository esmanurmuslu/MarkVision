<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';

    public $timestamps = false;

    protected $fillable = [
        'course_name',
        'course_code',
        'teacher_id',
        'department_id'
    ];


    public function teacher()
    {
        return $this->belongsTo(
            Teacher::class,
            'teacher_id'
        );
    }


    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }


    public function exams()
    {
        return $this->hasMany(
            Exam::class,
            'course_id'
        );
    }
}