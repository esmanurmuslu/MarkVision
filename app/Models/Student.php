<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



class Student extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'student_no';
    public $incrementing = false; // Öğrenci numarası auto-increment olmadığı için false yaptık
    public $timestamps = false;    // Mevcut tablonda created_at/updated_at olmadığı için kapatıyoruz

    protected $fillable = ['student_no', 'student_name', 'student_surname', 'department_id'];

    // Öğrencinin birden fazla sınav sonucu olabilir
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_no', 'student_no');
    }
public function department(): BelongsTo
{
    return $this->belongsTo(Department::class, 'department_id');
}

}