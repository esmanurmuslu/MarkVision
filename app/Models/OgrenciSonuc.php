<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgrenciSonuc extends Model
{
    protected $table = 'ogrenci_sonuclar';
    protected $fillable = [
        'sinav_id', 'ogrenci_no', 'ogrenci_ad_soyad', 'ogrenci_cevaplari',
        'dogru_sayisi', 'yanlis_sayisi', 'bos_sayisi', 'toplam_puan', 'gorsel_yolu',
        'obs_exam_result_id', 'obs_kayit_edildi',
    ];

    protected $casts = [
        'ogrenci_cevaplari' => 'array',
        'obs_kayit_edildi' => 'boolean',
    ];
}