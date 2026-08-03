<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $table = 'students';

    // Özel birincil anahtar (Primary Key)
    protected $primaryKey = 'student_no';

    // Birincil anahtar otomatik artan (auto-increment) değil
    public $incrementing = false;

    // ÖNEMLİ EKLENTİ: Eğer öğrenci numarasında harf varsa veya "0" ile başlıyorsa 
    // (Örn: 0551234), Laravel'in bunu tam sayıya çevirip bozmasını engeller.
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'student_no',
        'student_name',
        'student_surname',
        'department_id'
    ];

    /**
     * Öğrencinin bağlı olduğu bölüm.
     */
    public function department(): BelongsTo
    {
        // Model Sınıfı, Yabancı Anahtar (Foreign Key), Yerel Anahtar (Owner Key)
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Öğrenciye ait optik/sınav sonuçları.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_no', 'student_no');
    } 
}