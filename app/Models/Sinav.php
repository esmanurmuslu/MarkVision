<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sinav extends Model
{
    use HasFactory;

    protected $table = 'sinavlar';

    protected $fillable = [
        'sinav_adi',
        'ders_kodu',
        'cevap_anahtari',
        'obs_exam_id',
        'question_weights'
    ];

    // --- BU KISMI EKLE ---
    protected $casts = [
        'cevap_anahtari' => 'array',
        'question_weights' => 'array',
    ];
}