#!/usr/bin/env python
"""
sablon_uret.py
---------------
omr_form_geometry.py'deki plana gore, HERHANGI BIR (soru_sayisi,
sik_harfleri, hane_sayisi) kombinasyonu icin basilabilir bir OMR
formu (PDF) uretir. Ciktisi, koordinat_uretici.py'nin AYNI parametrelerle
urettigi JSON ile piksel-hassasiyetinde ortusur -- ikisi de
omr_form_geometry.py'den besleniyor, bu yuzden elle kalibrasyona hicbir
zaman gerek yok.

Kurulum:
    pip install reportlab --break-system-packages

Kullanim:
    python sablon_uret.py --soru 40 --sik ABCDE --hane 9 --cikti form_40s.pdf
    python sablon_uret.py --soru 10 --sik ABCDEFGH --hane 9 --cikti form_8sik.pdf
"""
import argparse
import sys

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.pdfgen import canvas

from omr_form_geometry import (
    form_planla, FormSigmayaSigmiyor,
    PAGE_H_MM, ANCHOR_SIZE_MM,
    ANCHOR_TL, ANCHOR_TR, ANCHOR_BL, ANCHOR_BR,
    CONTENT_LEFT_MM, CONTENT_RIGHT_MM,
    HEADER_TOP_MM, HEADER_BOTTOM_MM,
    ID_LABEL_Y_MM, ID_BOXES_TOP_MM, ID_BOXES_HEIGHT_MM,
)

# reportlab'da Y ekseni ASAGIDAN YUKARIYA artar; omr_form_geometry ise
# sayfanin USTUNDEN asagiya (DTP standardi) mm sayar. Her cizimde
# donusum yapiyoruz ki iki modul ayni "sayfa dili"ni konussun.
def Y(y_mm: float) -> float:
    return (PAGE_H_MM - y_mm) * mm


def X(x_mm: float) -> float:
    return x_mm * mm


def anchor_karelerini_ciz(c: canvas.Canvas):
    for (ax, ay) in (ANCHOR_TL, ANCHOR_TR, ANCHOR_BL, ANCHOR_BR):
        sol = ax - ANCHOR_SIZE_MM / 2
        ust = ay - ANCHOR_SIZE_MM / 2
        c.setFillColorRGB(0, 0, 0)
        c.rect(X(sol), Y(ust + ANCHOR_SIZE_MM), ANCHOR_SIZE_MM * mm, ANCHOR_SIZE_MM * mm, fill=1, stroke=0)


def basligi_ciz(c: canvas.Canvas, etiket_ad_soyad: str, etiket_sinif: str, etiket_sinav_adi: str):
    c.setLineWidth(0.6)
    c.setStrokeColorRGB(0, 0, 0)
    orta_x = (CONTENT_LEFT_MM + CONTENT_RIGHT_MM) / 2
    orta_y = (HEADER_TOP_MM + HEADER_BOTTOM_MM) / 2

    c.rect(X(CONTENT_LEFT_MM), Y(HEADER_BOTTOM_MM),
           X(CONTENT_RIGHT_MM) - X(CONTENT_LEFT_MM), Y(HEADER_TOP_MM) - Y(HEADER_BOTTOM_MM),
           fill=0, stroke=1)
    c.line(X(CONTENT_LEFT_MM), Y(orta_y), X(CONTENT_RIGHT_MM), Y(orta_y))
    c.line(X(orta_x), Y(HEADER_TOP_MM), X(orta_x), Y(HEADER_BOTTOM_MM))

    c.setFont("Helvetica", 8)
    # Kutucuklar HER ZAMAN cizilir (el yazisi alani olarak kalsin), ama etiket
    # metni bos string gelirse ("kaldir" secilmisse) YAZILMAZ -- boylece
    # kullanici sihirbazda bir alani kapattiginda gercekten optikten kalkar.
    c.setFillColorRGB(0, 0, 0)
    if etiket_ad_soyad:
        c.drawString(X(CONTENT_LEFT_MM) + 4, Y(HEADER_TOP_MM) - 12, f"{etiket_ad_soyad}:")
    c.drawString(X(orta_x) + 4, Y(HEADER_TOP_MM) - 12, "Tarih:")

    if etiket_sinif:
        c.setFillColorRGB(0, 0, 0)
        c.drawString(X(CONTENT_LEFT_MM) + 4, Y(orta_y) - 12, f"{etiket_sinif}:")
    if etiket_sinav_adi:
        c.setFillColorRGB(0, 0, 0)
        c.drawString(X(orta_x) + 4, Y(orta_y) - 12, f"{etiket_sinav_adi}:")
    c.setFillColorRGB(0, 0, 0)

def ogrenci_no_ciz(c: canvas.Canvas, id_plani: dict):
    c.setFont("Helvetica", 7)
    c.drawString(X(CONTENT_LEFT_MM), Y(ID_LABEL_Y_MM), "Ogrenci No (Student ID)")

    col_spacing = id_plani["col_spacing_mm"]
    r = id_plani["bubble_radius_mm"]

    for basamak_idx in range(id_plani["hane_sayisi"]):
        col_x = CONTENT_LEFT_MM + col_spacing / 2 + basamak_idx * col_spacing
        kutu_genislik = col_spacing * 0.7
        c.rect(X(col_x - kutu_genislik / 2), Y(ID_BOXES_TOP_MM + ID_BOXES_HEIGHT_MM),
               kutu_genislik * mm, ID_BOXES_HEIGHT_MM * mm, fill=0, stroke=1)

    basamak_adi_listesi = sorted(id_plani["basamaklar"].keys(), key=lambda s: int(s.split("_")[1]))
    for basamak_adi in basamak_adi_listesi:
        rakamlar = id_plani["basamaklar"][basamak_adi]
        for rakam_str, (x, y) in rakamlar.items():
            c.setStrokeColorRGB(0, 0, 0)
            c.circle(X(x), Y(y), r * mm, fill=0, stroke=1)
            
            # --- CANVA KAYMA DÜZELTMESİ ---
            # Font boyutu 5'ten 6'ya çıkarıldı, Canva'da dairenin dışına
            # taşmaması ve tam ortaya oturması için Y ekseni ofseti (1.8) ayarlandı.
            c.setFont("Helvetica", 6)
            c.drawCentredString(X(x), Y(y) - 1.8, rakam_str)


def sorulari_ciz(c: canvas.Canvas, soru_plani: dict):
    r = soru_plani["bubble_radius_mm"]
    
    # --- CANVA KAYMA DÜZELTMESİ ---
    # Şıkların fontu dinamik olarak büyütüldü ve merkez ofsetleri
    # font boyutuna göre matematiksel olarak ortalandı.
    font_boyutu = max(5, min(8, r * 2.6))

    for soru in soru_plani["sorular"]:
        soru_no = soru["soru_no"]
        ilk_koord = None
        for harf, coord in soru.items():
            if harf == "soru_no":
                continue
            x, y = coord
            if ilk_koord is None:
                ilk_koord = (x, y)
            c.setStrokeColorRGB(0, 0, 0)
            c.circle(X(x), Y(y), r * mm, fill=0, stroke=1)
            c.setFont("Helvetica", font_boyutu)
            c.setFillColorRGB(0, 0, 0)
            
            # Y(y) tam merkezdir. Yazının font boyutunun yaklaşık %32'si kadar 
            # aşağı çekilmesi harfi dairenin tam göbeğine oturtur.
            c.drawCentredString(X(x), Y(y) - (font_boyutu * 0.32), harf)
            
        if ilk_koord:
            c.setFont("Helvetica-Bold", font_boyutu)
            c.setFillColorRGB(0, 0, 0)
            c.drawRightString(X(ilk_koord[0]) - r * mm - 5, Y(ilk_koord[1]) - (font_boyutu * 0.32), f"{soru_no}.")


def form_uret(soru_sayisi: int, sik_harfleri: str, hane_sayisi: int, cikti_yolu: str,
              baslik: str = "", etiket_ad_soyad: str = "Ad Soyad",
              etiket_sinif: str = "Sinif", etiket_sinav_adi: str = "Sinav Adi"):
    plan = form_planla(soru_sayisi, sik_harfleri, hane_sayisi)

    c = canvas.Canvas(cikti_yolu, pagesize=A4)

    # PDF'e baslik metadata'si yaz -- yoksa tarayici sekmesi "untitled" gosterir.
    import os
    pdf_baslik = baslik.strip() if baslik and baslik.strip() else os.path.splitext(os.path.basename(cikti_yolu))[0]
    c.setTitle(pdf_baslik)
    c.setAuthor("MarkVision")
    c.setSubject(f"{soru_sayisi} soru x {len(sik_harfleri)} sik optik form")

    anchor_karelerini_ciz(c)
    basligi_ciz(c, etiket_ad_soyad, etiket_sinif, etiket_sinav_adi)
    ogrenci_no_ciz(c, plan["ogrenci_no"])
    sorulari_ciz(c, plan["sorular"])

    c.setFont("Helvetica", 9)
    c.setFillColorRGB(0, 0, 0)
    c.saveState()
    c.translate(X(16), Y(PAGE_H_MM / 2))
    c.rotate(90)
    c.drawCentredString(0, 0, "MARKVISION")
    c.restoreState()

    c.showPage()
    c.save()
    return plan


def main():
    parser = argparse.ArgumentParser(description="MarkVision OMR form (PDF) uretici")
    parser.add_argument("--soru", type=int, required=True)
    parser.add_argument("--sik", type=str, default="ABCDE")
    parser.add_argument("--hane", type=int, default=9)
    parser.add_argument("--cikti", type=str, required=True)
    parser.add_argument("--baslik", type=str, default="", help="PDF metadata basligi (sinav adi) -- indirilen dosya adiyla ayni olmali")
    parser.add_argument("--etiket-ad-soyad", type=str, default="Ad Soyad", help="Bos string verilirse bu alan formda gosterilmez")
    parser.add_argument("--etiket-sinif", type=str, default="Sinif", help="Bos string verilirse bu alan formda gosterilmez")
    parser.add_argument("--etiket-sinav-adi", type=str, default="Sinav Adi", help="Bos string verilirse bu alan formda gosterilmez")
    args = parser.parse_args()

    try:
        plan = form_uret(
            args.soru, args.sik.strip().upper(), args.hane, args.cikti,
            baslik=args.baslik,
            etiket_ad_soyad=args.etiket_ad_soyad,
            etiket_sinif=args.etiket_sinif,
            etiket_sinav_adi=args.etiket_sinav_adi,
        )
    except FormSigmayaSigmiyor as e:
        print(f"HATA: {e}", file=sys.stderr)
        sys.exit(1)

    sp = plan["sorular"]
    print(
        f"Bitti: {args.cikti} -- {args.soru} soru x {len(args.sik)} sik, "
        f"{sp['kullanilan_kolon']} sutun."
    )


if __name__ == "__main__":
    main()