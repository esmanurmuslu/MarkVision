<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Obs\Student; // Öğrenci modelinin tam yolu (Namespace'e göre uyarla)

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
        'exam_id' => 'integer',
        'id' => 'integer', // Eksik olan ve senin eklediğin kısım
        
        // ÖNEMLİ DEĞİŞİKLİK: 'student_no' alanı integer idi, string olarak değiştirdim.
        'student_no' => 'string', 
    ];

    /**
     * Bu sınav sonucunun ait olduğu OBS öğrencisi.
     */
    public function student(): BelongsTo
    {
        // Model Sınıfı, Yabancı Anahtar (Foreign Key), Yerel Anahtar (Owner Key)
        return $this->belongsTo(Student::class, 'student_no', 'student_no');
    }

    /**
     * Bu sonucun ait olduğu sınav tablosu (Eğer Exams diye bir modelin varsa).
     */
    public function exam(): BelongsTo
    {
        // Exam modelin varsa, bu ilişkiyi de kurmak veritabanı sorgularını çok kolaylaştırır
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }
}