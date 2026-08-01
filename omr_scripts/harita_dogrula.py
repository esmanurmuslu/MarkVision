"""
harita_dogrula.py
------------------
Bugun uretilen YENI koordinat haritasinin gercekten dogru olup olmadigini
GORSEL olarak kontrol etmek icin. Elle tiklama YOK -- sadece koordinatlari
duzlestirilmis fotografin uzerine noktalar halinde cizip kaydeder.

NEDEN GEREKLI: mmToOmrPx() formulu (JS tarafinda) teorik olarak dogru, ama
gercek bir yazicidan cikan kagit + gercek bir telefon fotografiyla test
etmeden emin olamayiz. Bu script o testi saniyeler icinde, gozle gorulur
sekilde yapmani sagliyor.

Kullanim:
    python harita_dogrula.py fotograf.jpg koordinat_7.json

(koordinat_7.json = storage/app/omr_scripts/koordinat_<sinav_id>.json --
 sinavi "Yayinla" dedikten sonra o klasorden kopyala)

Cikti:
    dogrulama_<fotograf_adi>.jpg  -> ayni klasore kaydedilir
    Kirmizi daire = ogrenci no hucreleri
    Mavi daire    = soru siklari

Her daire, kagittaki gercek kabarciğin TAM ORTASINA denk gelmeli. Denk
gelmiyorsa (hepsi ayni yone kaymissa ya da oransiz buyuyup kuculuyorsa),
optik_form_koordinat_YENI.js icindeki OMR_OUT_W/H/PAD veya ANCHOR_*_MM
sabitleri, anchor_detect.py / PDF cizim koduyla senkron degildir --
KISI_B_GUNLUK_REHBER.md dosyasindaki "Sorun cikarsa kontrol listesi"
bolumune bak.
"""
import sys
import json
import os

import cv2

# Bu scripti anchor_detect.py ile AYNI klasore koy (omr_scripts).
from anchor_detect import belgeyi_duzlestir


def main():
    if len(sys.argv) < 3:
        print("Kullanim: python harita_dogrula.py fotograf.jpg koordinat.json")
        return

    foto_yolu = sys.argv[1]
    koordinat_yolu = sys.argv[2]

    if not os.path.exists(foto_yolu):
        print(f"HATA: {foto_yolu} bulunamadi.")
        return
    if not os.path.exists(koordinat_yolu):
        print(f"HATA: {koordinat_yolu} bulunamadi.")
        return

    with open(koordinat_yolu, encoding="utf-8") as f:
        harita = json.load(f)

    gri, renkli = belgeyi_duzlestir(foto_yolu)
    if renkli is None:
        print(
            "HATA: Fotograf duzlestirilemedi (anchor bulunamadi ya da bulanik). "
            "Koordinat sorunundan ONCE bu sorunu coz -- yoksa hangi sorunun "
            "sebep oldugunu ayirt edemezsin."
        )
        return

    kirmizi_sayac = 0
    for basamak, secenekler in harita.get("ogrenci_no", {}).items():
        for rakam, koord in secenekler.items():
            x, y = int(koord[0]), int(koord[1])
            cv2.circle(renkli, (x, y), 8, (0, 0, 255), 2)  # kirmizi (BGR)
            cv2.putText(renkli, rakam, (x - 4, y + 4),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.3, (0, 0, 255), 1)
            kirmizi_sayac += 1

    mavi_sayac = 0
    for soru in harita.get("sorular", []):
        soru_no = soru.get("soru_no", "?")
        for k, v in soru.items():
            if k == "soru_no":
                continue
            x, y = int(v[0]), int(v[1])
            cv2.circle(renkli, (x, y), 8, (255, 0, 0), 2)  # mavi (BGR)
            mavi_sayac += 1
        # soru numarasini ilk sikkin biraz solune yaz (okunabilirlik icin)
        ilk_sik_koord = next((v for k, v in soru.items() if k != "soru_no"), None)
        if ilk_sik_koord:
            cv2.putText(renkli, str(soru_no),
                        (int(ilk_sik_koord[0]) - 25, int(ilk_sik_koord[1]) + 4),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, (0, 128, 0), 1)

    taban_ad = os.path.basename(foto_yolu)
    cikti_adi = "dogrulama_" + taban_ad
    cv2.imwrite(cikti_adi, renkli)

    print(f"Bitti: {cikti_adi} olusturuldu.")
    print(f"  -> {kirmizi_sayac} ogrenci-no noktasi (kirmizi), {mavi_sayac} sik noktasi (mavi) cizildi.")
    print("  -> Dosyayi ac, her noktanin kagittaki kabarciğin TAM ORTASINDA olup olmadigina bak.")


if __name__ == "__main__":
    main()