<?php

namespace App\Exports;

use App\Models\OgrenciSonuc;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Tüm geçmiş sonuçları en yeniden eskiye doğru çeker
        return OgrenciSonuc::orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Sonuç ID',
            'Öğrenci No',
            'Sınav ID',
            'Doğru',
            'Yanlış',
            'Boş',
            'Toplam Puan',
            'Okunma Tarihi',
            'OBS Durumu'
        ];
    }

    public function map($sonuc): array
    {
        return [
            $sonuc->id,
            $sonuc->ogrenci_no,
            $sonuc->sinav_id,
            $sonuc->dogru_sayisi,
            $sonuc->yanlis_sayisi,
            $sonuc->bos_sayisi,
            $sonuc->toplam_puan,
            $sonuc->created_at->format('d.m.Y H:i'),
            $sonuc->obs_kayit_edildi ? 'Kaydedildi' : 'Bekliyor'
        ];
    }
}