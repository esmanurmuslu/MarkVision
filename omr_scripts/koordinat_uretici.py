#!/usr/bin/env python
"""
koordinat_uretici.py
----------------------
Belirtilen (soru_sayisi, sik_harfleri, hane_sayisi) kombinasyonu icin,
omr_form_geometry.py'deki TEK GERCEK KAYNAKTAN turetilen koordinat
haritasini (JSON) uretir. Bu JSON, pipeline_main.py / bubble_detect.py
tarafindan DOGRUDAN kullanilir -- elle kalibrasyona GEREK YOKTUR.

Bu script Laravel tarafinda MarkVisionController::koordinatHaritasiUret()
tarafindan otomatik cagirilir:

    python koordinat_uretici.py --soru 40 --sik "ABCDE" --hane 9 --cikti out.json

AYNI parametrelerle sablon_uret.py'nin urettigi PDF ile bu JSON HER ZAMAN
piksel-hassasiyetinde ortusur -- ikisi de omr_form_geometry.py'den
besleniyor, bu yuzden yeni bir soru/sik/hane kombinasyonu geldiginde
elle hicbir sey yapmaya gerek yoktur.
"""
import argparse
import json
import sys

from omr_form_geometry import form_planla, mm_to_px, FormSigmayaSigmiyor


def haritaya_cevir(plan: dict) -> dict:
    harita = {"ogrenci_no": {}, "sorular": []}

    for basamak_adi, rakamlar in plan["ogrenci_no"]["basamaklar"].items():
        harita["ogrenci_no"][basamak_adi] = {
            rakam: list(mm_to_px(x, y)) for rakam, (x, y) in rakamlar.items()
        }

    for soru in plan["sorular"]["sorular"]:
        soru_px = {"soru_no": soru["soru_no"]}
        for harf, coord in soru.items():
            if harf == "soru_no":
                continue
            x, y = coord
            soru_px[harf] = list(mm_to_px(x, y))
        harita["sorular"].append(soru_px)

    return harita


def main():
    parser = argparse.ArgumentParser(description="MarkVision OMR koordinat haritasi uretici")
    parser.add_argument("--soru", type=int, required=True, help="Toplam soru sayisi")
    parser.add_argument("--sik", type=str, default="ABCDE", help="Sik harfleri, orn. ABCDE, ABCDEFGH")
    parser.add_argument("--hane", type=int, default=9, help="Ogrenci no hane sayisi")
    parser.add_argument("--cikti", type=str, required=True, help="Yazilacak JSON dosya yolu")
    args = parser.parse_args()

    sik_harfleri = args.sik.strip().upper()

    try:
        plan = form_planla(args.soru, sik_harfleri, args.hane)
    except (ValueError, FormSigmayaSigmiyor) as e:
        print(f"HATA: {e}", file=sys.stderr)
        sys.exit(1)

    harita = haritaya_cevir(plan)

    with open(args.cikti, "w", encoding="utf-8") as f:
        json.dump(harita, f, ensure_ascii=False, indent=2)

    sp = plan["sorular"]
    print(
        f"Bitti: {args.cikti} -- {args.soru} soru x {len(sik_harfleri)} sik, "
        f"{args.hane} haneli ogrenci no, {sp['kullanilan_kolon']} sutun, "
        f"satir yuksekligi {sp['row_height_mm']:.2f}mm, sik araligi {sp['sik_araligi_mm']:.2f}mm."
    )


if __name__ == "__main__":
    main()