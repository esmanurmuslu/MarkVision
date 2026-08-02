"""
omr_form_geometry.py
----------------------
MARKVISION optik form sisteminin TEK GERCEK KAYNAGI (single source of truth)
geometri modulu.

Bu dosyanin amaci: "5 sikli 50 soru", "8 sikli 10 soru", "5 sikli 100 soru
9 haneli numara" gibi HERHANGI BIR (soru_sayisi, sik_harfleri, hane_sayisi)
kombinasyonu icin:

  1) PDF/PNG olarak basilacak formun UZERINDEKI her kabarcigin TAM MM
     konumunu,
  2) Bu form taranip anchor_detect.belgeyi_duzlestir() ile duzlestirildikten
     SONRA (1000x1400 piksellik cikti uzerinde) ayni kabarciklarin TAM
     PIKSEL konumunu

matematiksel olarak, ELLE KALIBRASYON YAPMADAN, hesaplar.

NEDEN BOYLE YAPILDI: Eski yontemde (kalibrasyon_araci.py) HER form boyutu
icin ayri ayri, elle, yuzlerce nokta tiklamak gerekiyordu -- hem cok yavas
hem de en ufak bir yazici/tarama farkinda tum harita gecersiz oluyordu.
Bu modul yerine SABIT bir fiziksel sayfa duzeni (A4, 14mm kenar bosluklu,
9mm'lik 4 kose anchor karesi) tanimlar ve butun elemanlarin konumunu bu
sabit duzenden ORANTILI olarak turetir. Boylece:

  - Form Uretici (sablon_uret.py) bu modulden mm konumlarini alip sayfaya
    cizer,
  - Koordinat Uretici (koordinat_uretici.py) AYNI modulden ayni mm
    konumlarini alip mm_to_px() ile piksele cevirir,

ve ikisi HER ZAMAN birebir ortusur -- cunku ikisi de ayni fonksiyonlardan
besleniyor. Elle kalibrasyona hicbir zaman gerek kalmaz.

KRITIK KURAL: anchor_detect.py'nin belgeyi_duzlestir() fonksiyonu (varsayilan
parametrelerle) HER ZAMAN 1000x1400 piksellik, 40 piksel kenar boslukli bir
cikti uretir. Bu modul de mm_to_px() icinde AYNI 1000/1400/40 degerlerini
kullanir. Biri degisirse OTEKI DE DEGISMELI (asagidaki OMR_OUT_* sabitlerine
bakin) -- yoksa uretilen koordinatlar taranan gorseldeki kabarciklarla
ORTUSMEZ.
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

# Anchor karelerinin MERKEZ konumlari (mm). anchor_detect.py bu kareleri
# resimde ARAR (konumlarini varsaymaz) -- biz sadece PDF'ye BU konumlara
# ciziyoruz, boylece tarama sirasinda anchor_detect onlari bulabiliyor.
ANCHOR_TL = (MARGIN_MM + ANCHOR_SIZE_MM / 2, MARGIN_MM + ANCHOR_SIZE_MM / 2)
ANCHOR_TR = (PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2, MARGIN_MM + ANCHOR_SIZE_MM / 2)
ANCHOR_BL = (MARGIN_MM + ANCHOR_SIZE_MM / 2, PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2)
ANCHOR_BR = (PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2, PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM / 2)


# =====================================================================
# 2) DUZLESTIRME CIKTISI (px) -- anchor_detect.belgeyi_duzlestir() ile
#    BIREBIR AYNI OLMALI (varsayilan parametreleri degistirmediyseniz
#    dokunmayin).
# =====================================================================
OMR_OUT_W = 1000
OMR_OUT_H = 1400
OMR_PAD = 40  # warp hedefindeki kenar_bosluk


def mm_to_px(xmm: float, ymm: float) -> tuple[int, int]:
    """
    Sayfa uzerindeki bir mm konumunu, duzlestirilmis (warp edilmis) goruntu
    uzerindeki piksel konumuna cevirir. anchor_detect.belgeyi_duzlestir()
    anchor merkezlerini TAM OLARAK (PAD,PAD)-(W-PAD,PAD)-(W-PAD,H-PAD)-
    (PAD,H-PAD) noktalarina esler; aradaki her nokta da orantili olarak
    esitlenir.
    """
    px = OMR_PAD + (xmm - ANCHOR_TL[0]) / (ANCHOR_TR[0] - ANCHOR_TL[0]) * (OMR_OUT_W - 2 * OMR_PAD)
    py = OMR_PAD + (ymm - ANCHOR_TL[1]) / (ANCHOR_BL[1] - ANCHOR_TL[1]) * (OMR_OUT_H - 2 * OMR_PAD)
    return (int(round(px)), int(round(py)))


# =====================================================================
# 3) SABIT UST BOLUM (Ad Soyad / Tarih / Sinif / Sinav Adi kutusu).
#    Bu alan OMR ile okunmaz (ogretmen elle okur), bu yuzden soru/sik/hane
#    sayisindan BAGIMSIZ, sabit.
# =====================================================================
CONTENT_LEFT_MM = 18.0
CONTENT_RIGHT_MM = PAGE_W_MM - MARGIN_MM - ANCHOR_SIZE_MM  # 187.0
HEADER_TOP_MM = 22.0
HEADER_BOTTOM_MM = 40.0

ID_LABEL_Y_MM = HEADER_BOTTOM_MM + 4.0        # "Ogrenci No (Student ID)" yazisi
ID_BOXES_TOP_MM = ID_LABEL_Y_MM + 4.0         # el yazisiyla rakam yazilacak kutucuklar
ID_BOXES_HEIGHT_MM = 7.0
ID_BUBBLES_TOP_MM = ID_BOXES_TOP_MM + ID_BOXES_HEIGHT_MM + 2.0


# =====================================================================
# 4) OGRENCI NO KABARCIK HARITASI (dinamik hane sayisina gore)
# =====================================================================
ID_ROW_SPACING_MM = 4.6
ID_COL_SPACING_MIN_MM = 7.0
ID_COL_SPACING_MAX_MM = 12.0
ID_BUBBLE_RADIUS_MM = 1.6


def ogrenci_no_plani(hane_sayisi: int) -> dict:
    """
    Öğrenci numarası sütunlarının x koordinatlarını piksel kayması 
    yaşanmayacak şekilde sabit milimetre aralıklarına sabitler.
    """
    if hane_sayisi < 1:
        raise ValueError("Ogrenci no hane sayisi en az 1 olmali.")

    # Sütunlar arasındaki mesafeyi ve sol başlangıcı formdaki kutulara tam oturacak şekilde sabitliyoruz
    sabit_sol_baslangic = CONTENT_LEFT_MM + 2.0
    sabit_col_spacing = 8.5  # Her bir hane sütununun arasındaki mm mesafesi (kesin uyumlu değer)

    plan = {
        "hane_sayisi": hane_sayisi,
        "col_spacing_mm": sabit_col_spacing,
        "bubble_radius_mm": ID_BUBBLE_RADIUS_MM,
        "basamaklar": {},
    }

    for basamak_idx in range(hane_sayisi):
        col_x = sabit_sol_baslangic + basamak_idx * sabit_col_spacing
        basamak_adi = f"basamak_{basamak_idx + 1}"
        plan["basamaklar"][basamak_adi] = {}
        for rakam in range(10):
            y = ID_BUBBLES_TOP_MM + rakam * ID_ROW_SPACING_MM
            plan["basamaklar"][basamak_adi][str(rakam)] = (col_x, y)

    plan["bubbles_bottom_mm"] = ID_BUBBLES_TOP_MM + 10 * ID_ROW_SPACING_MM
    return plan

# =====================================================================
# 5) SORU/SIK KABARCIK HARITASI -- bu modulun kalbi burasi. Dinamik soru
#    sayisi + sik sayisina gore OTOMATIK sutun/satir/aralik hesabi yapar.
# =====================================================================
ANSWER_BOTTOM_MARGIN_MM = 6.0  # alt anchor'a carpmamak icin bosluk

ROW_HEIGHT_DEFAULT_MM = 5.3
ROW_HEIGHT_MIN_MM = 3.6

SIK_SPACING_DEFAULT_MM = 7.0
SIK_SPACING_MIN_MM = 2.9

FIRST_SIK_OFFSET_MM = 9.0   # soru numarasi ile ilk sikkin arasi bosluk
COLUMN_GUTTER_MM = 6.0      # bir sutunla digeri arasi bosluk


class FormSigmayaSigmiyor(Exception):
    """Istenen soru/sik kombinasyonu, minimum boyutlarda bile tek A4 sayfaya sigmiyor."""
    pass


def _kolon_genisligi(sik_sayisi: int, sik_araligi: float) -> float:
    return FIRST_SIK_OFFSET_MM + (sik_sayisi - 1) * sik_araligi + COLUMN_GUTTER_MM


def sorular_plani(soru_sayisi: int, sik_harfleri: str, id_plani: dict) -> dict:
    """
    soru_sayisi ve sik_harfleri (orn. "ABCDE", "ABCDEFGH") icin, sayfaya
    sigacak sekilde OTOMATIK sutun sayisi / satir yuksekligi / sik araligi
    hesaplar.

    Once varsayilan (rahat) boyutlarla dener. Sigmiyorsa kademeli olarak
    ONCE satir yuksekligini, sonra sik araligini, minimum degerlere kadar
    kucultur (0.1mm adimlarla). O da yetmezse FormSigmayaSigmiyor firlatir
    -- yani gercekten TEK SAYFAYA sigmayacak kadar buyuk bir istektir,
    cagiran kod kullaniciya bunu bildirmeli.
    """
    sik_sayisi = len(sik_harfleri)
    if sik_sayisi < 2:
        raise ValueError("En az 2 sik gerekli.")
    if soru_sayisi < 1:
        raise ValueError("En az 1 soru gerekli.")
    if len(set(sik_harfleri)) != sik_sayisi:
        raise ValueError("Sik harfleri icinde tekrar eden harf var.")

    answer_top = id_plani["bubbles_bottom_mm"] + 2.0
    answer_bottom = PAGE_H_MM - MARGIN_MM - ANCHOR_SIZE_MM - ANSWER_BOTTOM_MARGIN_MM
    dikey_alan = answer_bottom - answer_top
    yatay_alan = CONTENT_RIGHT_MM - CONTENT_LEFT_MM

    if dikey_alan <= 0:
        raise FormSigmayaSigmiyor(
            f"{id_plani['hane_sayisi']} haneli ogrenci no bloğu, soru alani icin yer birakmiyor."
        )

    row_height = ROW_HEIGHT_DEFAULT_MM
    sik_araligi = SIK_SPACING_DEFAULT_MM

    def _kapasite(row_h, sik_a):
        maks_satir = max(1, int(dikey_alan // row_h))
        kolon_genislik = _kolon_genisligi(sik_sayisi, sik_a)
        maks_kolon = max(1, int(yatay_alan // kolon_genislik))
        return maks_satir, maks_kolon, maks_satir * maks_kolon, kolon_genislik

    maks_satir, maks_kolon, kapasite, kolon_genislik = _kapasite(row_height, sik_araligi)

    adim = 0
    while kapasite < soru_sayisi and adim < 60:
        if row_height > ROW_HEIGHT_MIN_MM:
            row_height = max(ROW_HEIGHT_MIN_MM, round(row_height - 0.1, 2))
        elif sik_araligi > SIK_SPACING_MIN_MM:
            sik_araligi = max(SIK_SPACING_MIN_MM, round(sik_araligi - 0.1, 2))
        else:
            break
        maks_satir, maks_kolon, kapasite, kolon_genislik = _kapasite(row_height, sik_araligi)
        adim += 1

    if kapasite < soru_sayisi:
        raise FormSigmayaSigmiyor(
            f"{soru_sayisi} soru x {sik_sayisi} sik, tek A4 sayfaya sigmiyor "
            f"(minimum boyutlarda maksimum kapasite: {kapasite}). Soru veya sik "
            f"sayisini azaltin ya da coklu sayfa destegi ekleyin."
        )

    gereken_kolon = math.ceil(soru_sayisi / maks_satir)
    kullanilan_kolon = min(maks_kolon, gereken_kolon)
    bubble_radius = max(1.0, min(1.8, sik_araligi * 0.4))

    # Kullanilan gercek satir sayisini dengele: son sutuna az soru dusmesin
    # diye sorulari sutunlara mumkun oldugunca esit dagit.
    fiili_maks_satir = math.ceil(soru_sayisi / kullanilan_kolon)
    fiili_maks_satir = min(fiili_maks_satir, maks_satir)

    sorular = []
    for i in range(soru_sayisi):
        soru_no = i + 1
        kolon_idx = i // fiili_maks_satir
        satir_idx = i % fiili_maks_satir
        qx = CONTENT_LEFT_MM + kolon_idx * kolon_genislik
        qy = answer_top + satir_idx * row_height

        soru = {"soru_no": soru_no}
        for harf in sik_harfleri:
            j = sik_harfleri.index(harf)
            sx = qx + FIRST_SIK_OFFSET_MM + j * sik_araligi
            sy = qy
            soru[harf] = (sx, sy)
        sorular.append(soru)

    return {
        "soru_sayisi": soru_sayisi,
        "sik_harfleri": sik_harfleri,
        "row_height_mm": row_height,
        "sik_araligi_mm": sik_araligi,
        "bubble_radius_mm": bubble_radius,
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