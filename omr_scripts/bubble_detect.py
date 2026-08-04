"""
bubble_detect.py
------------------
Warp edilmis (duzlestirilmis) gri optik goruntusu ve onceden kalibre
edilmis koordinatlar verildiginde, hangi kabarciklarin dolu (isaretli)
oldugunu tespit eder.

ONEMLI: Buradaki koordinatlar (merkez noktalari) MUTLAKA gercek, warp
edilmis bir taramadan olculmelidir -- tasarim dosyasindan degil. Cunku
kagit farkli olcekte basilmis olabilir, warp sonrasi hafif kaymalar
olusabilir. Kalibrasyon islemi bir kere yapilip koordinat_haritasi.json
gibi bir dosyaya kaydedilir, sonra her kagit icin tekrar kullanilir.

Kullanim:
    from bubble_detect import soruyu_oku

    secenekler = {"A": (120, 340), "B": (150, 340), "C": (180, 340), "D": (210, 340)}
    cevap = soruyu_oku(duz_gri, secenekler)   # -> "A", "BOS" ya da "GECERSIZ"
"""

import cv2
import numpy as np


def _bolge_kirp(img_gri, merkez, yaricap):
    x, y = int(merkez[0]), int(merkez[1])
    h, w = img_gri.shape[:2]
    x1, y1 = max(0, x - yaricap), max(0, y - yaricap)
    x2, y2 = min(w, x + yaricap), min(h, y + yaricap)
    return img_gri[y1:y2, x1:x2]


def kabarcik_doluluk_orani(img_gri, merkez, yaricap=12, ic_orani=0.6):
    """
    Kabarcigin doluluk oranini dondurur. 0.0 = tamamen bos/beyaz, 1.0 =
    tamamen dolu/siyah.

    YONTEM (ONEMLI -- eski OTSU tabanli yontemin YERINE gecti):
    Kabarcigin TAM ORTASINDAKI kucuk bir DISK ile, onun HEMEN DISINDAKI
    bir HALKA (annulus) bolgesinin ORTALAMA piksel parlakligini kiyaslar:

        skor = (halka_ortalama - ic_disk_ortalama) / halka_ortalama

    Halka bolgesi HER ZAMAN kagidin bos zeminidir (kabarcigin biraz
    disinda kalir), bu yuzden HER FOTOGRAFTA o fotografin KENDI isik/
    pozlama kosuluna gore otomatik kalibre olur -- ayri bir global esik
    hesabina bile gerek kalmaz.

    NEDEN DEGISTIRILDI: Eski yontem her kabarcik icin AYRI AYRI bir OTSU
    esigi ariyordu. Bos/isaretsiz bir kabarcikta bolge neredeyse duz
    (unimodal) oldugundan gercek bir siyah/beyaz ayrimi YOKTUR; OTSU yine
    de zorla bir esik bulmaya calisir ve bu esik, JPEG gurultusune ve
    fotograftan fotografa degisen kucuk parlaklik farklarina gore
    neredeyse RASTGELE kayar. Sonuc: AYNI kagidin farkli fotograflarinda
    (kullanicinin bildirdigi gibi) bir kabarcik bir seferinde "dolu", bir
    dahaki seferinde "bos" cikiyordu. Sayfa GENELINDE tek bir sabit esik
    denenmisti ama bu da soluk/hafif kalem izlerini "beyaz" tarafta
    birakabiliyordu (cok koyu anchor kareleriyle ayni histogramda
    oldugundan). Halka-kiyasli bu yontem ise HEM kararlidir (OTSU/
    histogram aramasi yok) HEM DE her kabarcigi SADECE kendi cevresindeki
    kagida gore degerlendirdigi icin soluk kalem izlerini de yakalar.
    """
    x, y = int(merkez[0]), int(merkez[1])
    ic_r = max(2, int(yaricap * ic_orani))
    dis_r = int(yaricap * 1.8)

    h, w = img_gri.shape[:2]
    x1, y1 = max(0, x - dis_r), max(0, y - dis_r)
    x2, y2 = min(w, x + dis_r), min(h, y + dis_r)
    bolge = img_gri[y1:y2, x1:x2]
    if bolge.size == 0:
        return 0.0
    cy, cx = y - y1, x - x1

    ic_mask = np.zeros(bolge.shape, dtype=np.uint8)
    cv2.circle(ic_mask, (cx, cy), ic_r, 255, -1)

    dis_mask = np.zeros(bolge.shape, dtype=np.uint8)
    cv2.circle(dis_mask, (cx, cy), dis_r, 255, -1)
    cv2.circle(dis_mask, (cx, cy), int(yaricap * 1.3), 0, -1)  # halka: ic kismi cikar

    if cv2.countNonZero(ic_mask) == 0 or cv2.countNonZero(dis_mask) == 0:
        return 0.0

    ic_ortalama = cv2.mean(bolge, mask=ic_mask)[0]
    halka_ortalama = cv2.mean(bolge, mask=dis_mask)[0]
    if halka_ortalama <= 1:
        return 0.0

    skor = (halka_ortalama - ic_ortalama) / halka_ortalama
    return float(np.clip(skor, 0.0, 1.0))


def soruyu_oku(img_gri, secenek_merkezleri, esik=0.45, yaricap=12,
               bagil_yedek=False, bagil_taban=0.28, bagil_oran=1.4):
    """
    secenek_merkezleri: {"A": (x, y), "B": (x, y), "C": (x, y), "D": (x, y)}

    Donus:
        "A" / "B" / "C" / "D"  -> tek bir secenek isaretli
        "BOS"                  -> hicbir secenek isaretli degil
        "GECERSIZ"              -> birden fazla secenek isaretli

    bagil_yedek (varsayilan KAPALI, mevcut davranisi degistirmez):
        True yapilirsa, MUTLAK esik (esik) hicbir seceneği "dolu" bulamadiginda
        (ornegin cok ince uctu/soluk bir kalemle isaretlenmis, doluluk orani
        %45'e ulasmayan bir kabarcik icin) ek bir GORELI kontrol devreye
        girer: o sorudaki en yuksek doluluk orani, digerlerinden belirgin
        sekilde (varsayilan en az 1.4 kat) yuksekse VE cok dusuk bir tabanin
        (varsayilan 0.28) uzerindeyse, o secenek isaretli sayilir.
        Gercekten BOS birakilmis bir soruda tum secenekler birbirine yakin
        (dusuk) ciktigindan bu kontrol yanlislikla bir cevap uretmez --
        sadece "biri digerlerinden acikca daha koyu" oldugunda devreye girer.
    """
    oranlar = {
        harf: kabarcik_doluluk_orani(img_gri, merkez, yaricap)
        for harf, merkez in secenek_merkezleri.items()
    }

    doldurulmus = [harf for harf, oran in oranlar.items() if oran >= esik]

    if len(doldurulmus) == 0 and bagil_yedek and len(oranlar) >= 2:
        siralanan = sorted(oranlar.items(), key=lambda kv: kv[1], reverse=True)
        en_yuksek_harf, en_yuksek_oran = siralanan[0]
        ikinci_oran = siralanan[1][1]
        if en_yuksek_oran >= bagil_taban and (
            ikinci_oran <= 0 or en_yuksek_oran >= ikinci_oran * bagil_oran
        ):
            doldurulmus = [en_yuksek_harf]

    if len(doldurulmus) == 0:
        return "BOS"
    if len(doldurulmus) > 1:
        return "GECERSIZ"
    return doldurulmus[0]


def rakami_oku(img_gri, rakam_merkezleri, esik=0.45, yaricap=15, bagil_yedek=False):
    """
    Ogrenci No / T.C. Kimlik No gibi alanlarda TEK BIR SUTUN icin kullanilir.
    rakam_merkezleri: {"0": (x,y), "1": (x,y), ..., "9": (x,y)}
    Donus: "0"-"9" arasi bir karakter, "BOS" ya da "GECERSIZ"
    """
    return soruyu_oku(img_gri, rakam_merkezleri, esik=esik, yaricap=yaricap, bagil_yedek=bagil_yedek)


def coklu_soru_oku(img_gri, koordinat_listesi, esik=0.45, yaricap=12, bagil_yedek=False):
    """
    Bir bolumun (ornegin TURKCE) tum sorularini tek seferde okur.

    koordinat_listesi ornegi:
        [
            {"soru_no": 1, "A": (x,y), "B": (x,y), "C": (x,y), "D": (x,y)},
            {"soru_no": 2, "A": (x,y), "B": (x,y), "C": (x,y), "D": (x,y)},
            ...
        ]

    Donus: {1: "A", 2: "BOS", 3: "GECERSIZ", ...}
    """
    sonuc = {}
    for soru in koordinat_listesi:
        soru_no = soru["soru_no"]
        secenekler = {k: v for k, v in soru.items() if k != "soru_no"}
        sonuc[soru_no] = soruyu_oku(img_gri, secenekler, esik=esik, yaricap=yaricap, bagil_yedek=bagil_yedek)
    return sonuc