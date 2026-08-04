"""
omr_form_geometry.py
----------------------
MARKVISION optik form sisteminin TEK GERCEK KAYNAGI (single source of truth)
geometri modulu.

Bu dosya: "5 sikli 50 soru", "3 sikli 20 soru", "5 sikli 100 soru 9 haneli numara"
gibi HERHANGI BIR (soru_sayisi, sik_harfleri, hane_sayisi) kombinasyonu icin:

  1) PDF/PNG olarak basilacak formun UZERINDEKI her kabarcigin TAM MM konumunu,
  2) Bu form taranip duzlestirildikten SONRA (1000x1400 piksellik cikti uzerinde)
     ayni kabarciklarin TAM PIKSEL konumunu

matematiksel olarak hesaplar.
"""

from __future__ import annotations
import math


# =====================================================================
# 1) FIZIKSEL SAYFA DUZENI (mm)
# =====================================================================
PAGE_W_MM = 210.0      # A4 genislik
PAGE_H_MM = 297.0      # A4 yukseklik
MARGIN_MM = 14.0       # sayfa kenarindan anchor karesine bosluk
ANCHOR_SIZE_MM = 9.0   # anchor (kose) karesinin kenar uzunlugu

# Anchor karelerinin MERKEZ konumlari (mm)
ANCHOR_TL = (MARGIN_MM + ANCHOR_SIZE_MM / 2, MARGIN_MM + ANCHOR_SIZE_MM / 2)
ANCHOR_TR = (PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2, MARGIN_MM + ANCHOR_SIZE_MM / 2)
ANCHOR_BL = (MARGIN_MM + ANCHOR_SIZE_MM / 2, PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2)
ANCHOR_BR = (PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2, PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2)


# =====================================================================
# 2) DUZLESTIRME CIKTISI (px)
# =====================================================================
OMR_OUT_W = 1000
OMR_OUT_H = 1400
OMR_PAD = 40  # warp hedefindeki kenar_bosluk


def mm_to_px(xmm: float, ymm: float) -> tuple[int, int]:
    """
    Sayfa uzerindeki mm konumunu piksele çevirir.
    Capraz oranti mantigi ile calisir.
    """
    px = OMR_PAD + (xmm - ANCHOR_TL[0]) / (ANCHOR_TR[0] - ANCHOR_TL[0]) * (OMR_OUT_W - 2 * OMR_PAD)
    py = OMR_PAD + (ymm - ANCHOR_TL[1]) / (ANCHOR_BL[1] - ANCHOR_TL[1]) * (OMR_OUT_H - 2 * OMR_PAD)
    return (int(round(px)), int(round(py)))


# =====================================================================
# 3) SABIT UST BOLUM (Ad Soyad / Tarih / Sinif / Sinav Adi kutusu)
# =====================================================================
CONTENT_LEFT_MM = 22.0
CONTENT_RIGHT_MM = PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM  
HEADER_TOP_MM = 22.0
HEADER_BOTTOM_MM = 36.0

ID_LABEL_Y_MM = 42.0           
ID_BOXES_TOP_MM = 45.0          
ID_BOXES_HEIGHT_MM = 6.0        
# Kutular 45.0'da baslar, 6.0 boyundadir (51.0'da biter). 
# Kabarciklar 55.0'dan baslar (Arada tam 4mm bosluk var, ASLA UST USTE BINMEZ).
ID_BUBBLES_TOP_MM = 55.0        


# =====================================================================
# 4) OGRENCI NO KABARCIK HARITASI
# =====================================================================
ID_ROW_SPACING_MM = 4.5         
ID_COL_SPACING_MM = 9.0        
ID_BUBBLE_RADIUS_MM = 1.9       


def ogrenci_no_plani(hane_sayisi: int) -> dict:
    if hane_sayisi < 1:
        raise ValueError("Ogrenci no hane sayisi en az 1 olmali.")

    plan = {
        "hane_sayisi": hane_sayisi,
        "col_spacing_mm": ID_COL_SPACING_MM,
        "bubble_radius_mm": ID_BUBBLE_RADIUS_MM,
        "basamaklar": {},
    }

    for basamak_idx in range(hane_sayisi):
        col_x = CONTENT_LEFT_MM + (ID_COL_SPACING_MM / 2.0) + (basamak_idx * ID_COL_SPACING_MM)
        basamak_adi = f"basamak_{basamak_idx + 1}"
        plan["basamaklar"][basamak_adi] = {}

        for rakam in range(10):
            y = ID_BUBBLES_TOP_MM + rakam * ID_ROW_SPACING_MM
            plan["basamaklar"][basamak_adi][str(rakam)] = (col_x, y)

    # 10 satir (0-9) => 55.0 + 9 * 4.5 = 95.5 mm'de biter.
    plan["bubbles_bottom_mm"] = ID_BUBBLES_TOP_MM + 9 * ID_ROW_SPACING_MM
    return plan


# =====================================================================
# 5) SORU / SIK KABARCIK HARITASI
# =====================================================================
ANSWER_BOTTOM_MARGIN_MM = 10.0  # Alttaki siyah karelere (274mm) ASLA degmemesi icin devasa pay
ANSWER_Y_TRIM_MM = 1.0
FIRST_SIK_OFFSET_MM = 9.0

# HOCANIN ISTEDIGI FERAHLIK VE BUYUKLUK DEGERLERI
SIK_SPACING_MM = 6.5           
COLUMN_GUTTER_MM = 12.0        


class FormSigmayaSigmiyor(Exception):
    """Istenen soru/sik kombinasyonu tek A4 sayfaya sigmiyor."""
    pass


def _kolon_genisligi(sik_sayisi: int) -> float:
    return FIRST_SIK_OFFSET_MM + (sik_sayisi - 1) * SIK_SPACING_MM + COLUMN_GUTTER_MM


def sorular_plani(soru_sayisi: int, sik_harfleri: str, id_plani: dict) -> dict:
    sik_sayisi = len(sik_harfleri)
    if sik_sayisi < 2:
        raise ValueError("En az 2 sik gerekli.")
    if soru_sayisi < 1:
        raise ValueError("En az 1 soru gerekli.")
    if len(set(sik_harfleri)) != sik_sayisi:
        raise ValueError("Sik harfleri icinde tekrar eden harf var.")

    id_var = id_plani["hane_sayisi"] > 0
    id_bottom = id_plani.get("bubbles_bottom_mm", 95.5) if id_var else 38.0

    # SORULARIN AŞAĞI TAŞMASINI ENGELLEYEN YENİ SINIRLAR VE GÜVENLİK
    if soru_sayisi <= 25:
        start_y = 125.0 if id_var else 50.0 
        row_height = 6.0 
        maks_satir = 25
    elif soru_sayisi <= 50:
        start_y = 105.0 if id_var else 45.0
        row_height = 6.2
        maks_satir = 25
    else:
        start_y = id_bottom + 8.0 if id_var else 38.0
        row_height = 4.8  
        maks_satir = 34

    kolon_genislik = _kolon_genisligi(sik_sayisi)

    yatay_alan = CONTENT_RIGHT_MM - CONTENT_LEFT_MM
    maks_kolon = max(1, int(yatay_alan // kolon_genislik))
    gereken_kolon = math.ceil(soru_sayisi / maks_satir)
    kullanilan_kolon = min(maks_kolon, gereken_kolon)

    if (maks_kolon * maks_satir) < soru_sayisi:
        raise FormSigmayaSigmiyor(f"{soru_sayisi} soru x {sik_sayisi} sik tek A4 sayfaya sigmiyor.")

    fiili_maks_satir = math.ceil(soru_sayisi / kullanilan_kolon)
    fiili_maks_satir = min(fiili_maks_satir, maks_satir)

    # TAŞMA GÜVENLİK KİLİDİ: Eğer hesaplanan en alt sınır siyah karelere yaklaşıyorsa satır aralığını otomatik kıs.
    max_allowed_y = PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM - ANSWER_BOTTOM_MARGIN_MM  # 264.0 mm
    while (start_y + (fiili_maks_satir - 1) * row_height) > max_allowed_y:
        row_height -= 0.1
        if row_height < 3.5:
            break  # Güvenlik sınırı

    sorular = []
    for i in range(1, soru_sayisi + 1):
        kolon_idx = (i - 1) // fiili_maks_satir
        satir_idx = (i - 1) % fiili_maks_satir

        # Sola hizalı düzen: Her durumda CONTENT_LEFT_MM'den başlar (Öğrenci numarasıyla aynı hizadan)
        qx = CONTENT_LEFT_MM + (kolon_idx * kolon_genislik)
        qy = start_y + satir_idx * row_height

        soru = {"soru_no": i}
        for idx, harf in enumerate(sik_harfleri):
            bx = qx + FIRST_SIK_OFFSET_MM + idx * SIK_SPACING_MM
            by = qy - ANSWER_Y_TRIM_MM
            soru[harf] = (bx, by)
        sorular.append(soru)

    return {
        "soru_sayisi": soru_sayisi,
        "sik_harfleri": sik_harfleri,
        "row_height_mm": row_height,
        "sik_araligi_mm": SIK_SPACING_MM,
        "bubble_radius_mm": 2.1,  # HOCANIN ISTEDIGI BUYUK BALONCUKLAR
        "maks_satir": fiili_maks_satir,
        "kullanilan_kolon": kullanilan_kolon,
        "kolon_genislik_mm": kolon_genislik,
        "sorular": sorular,
    }


def form_planla(soru_sayisi: int, sik_harfleri: str, hane_sayisi: int) -> dict:
    """Tum formun (ogrenci no + sorular) TEK cagriyla planini cikarir."""
    id_plani = ogrenci_no_plani(hane_sayisi)
    soru_plani = sorular_plani(soru_sayisi, sik_harfleri, id_plani)
    return {"ogrenci_no": id_plani, "sorular": soru_plani}