"""
harita_olusturucu.py
----------------------
kalibrasyon_araci.py tarafindan uretilen 290 noktalik ham koordinatlar.json
dosyasini okur ve pipeline_main.py'nin istedigi duzenli yapiya donusturur.
"""

import json
import os

def donustur():
    if not os.path.exists("koordinatlar.json"):
        print("Hata: 'koordinatlar.json' dosyasi bulunamadi. Lutfen once kalibrasyonu yapin.")
        return

    with open("koordinatlar.json", "r", encoding="utf-8") as f:
        ham_veriler = json.load(f)

    if len(ham_veriler) != 290:
        print(f"Hata: Tam olarak 290 nokta olmasi gerekiyor ama senin dosyasinda {len(ham_veriler)} nokta var.")
        print("Eksik veya fazla tiklama yapmis olabilirsin, lutfen kalibrasyon aracini tekrar calistirip dikkatlice tikla.")
        return

    # Sablonu olustur
    harita = {
        "_aciklama": "Otomatik olusturulmus koordinat haritasi. 9 haneli ogrenci no ve 40 soruluk cevap anahtari.",
        "ogrenci_no": {},
        "sorular": []
    }

    # 1. Bolum: Ogrenci Numarasi (Ilk 90 tiklama)
    # DIKKAT: kalibrasyon_araci.py ile tiklama optik kagit UZERINDE SATIR SATIR
    # yapilir (once 0 satirindaki 9 basamak/kutucuk soldan saga, sonra 1 satirindaki
    # 9 basamak, ...), YANI veri SIRASI once "rakam" (satir), icinde "basamak" (sutun)
    # seklindedir. Bu yuzden dongu de ayni sirada (once rakam, icinde basamak)
    # kurulmali; aksi halde basamaklar birbirine karisir.
    index = 0
    for basamak in range(1, 10):
        harita["ogrenci_no"][f"basamak_{basamak}"] = {}
    for rakam in range(10):
        for basamak in range(1, 10):
            basamak_adi = f"basamak_{basamak}"
            harita["ogrenci_no"][basamak_adi][str(rakam)] = ham_veriler[index]
            index += 1

    # 2. Bolum: Cevaplar (Son 200 tiklama)
    secenekler = ["A", "B", "C", "D", "E"]
    for soru_no in range(1, 41):
        soru_sozlugu = {"soru_no": soru_no}
        for harf in secenekler:
            soru_sozlugu[harf] = ham_veriler[index]
            index += 1
        harita["sorular"].append(soru_sozlugu)

    # Duzenli veriyi asil dosyaya yaz
    with open("koordinat_haritasi_ornek.json", "w", encoding="utf-8") as f:
        json.dump(harita, f, ensure_ascii=False, indent=2)

    print("Harika! 290 tiklama basariyla gruplandirildi.")
    print("Yeni 'koordinat_haritasi_ornek.json' dosyan kullanima hazir!")

if __name__ == "__main__":
    donustur()