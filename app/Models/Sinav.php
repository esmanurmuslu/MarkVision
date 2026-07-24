<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sinav extends Model
{
    protected $table = 'sinavlar';
    protected $fillable = ['sinav_adi', 'ders_kodu', 'cevap_anahtari', 'obs_exam_id'];

    protected $casts = [
        'cevap_anahtari' => 'array',
    ];
}