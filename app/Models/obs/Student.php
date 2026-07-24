<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $primaryKey = 'student_no';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_no',
        'student_name',
        'student_surname',
        'department_id'
    ];

    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }

    public function examResults()
    {
        return $this->hasMany(
            ExamResult::class,
            'student_no',
            'student_no'
        );
    }
}