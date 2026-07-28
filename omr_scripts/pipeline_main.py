"""
pipeline_main.py
------------------
anchor_detect + bubble_detect modullerini birlestirip, bir kagit
fotografini alip `exam_results` tablosuna DOGRUDAN yazilabilecek
bir JSON ureten uctan uca akis.

Veritabani semasi (markvision.sql) ile hizali calisir:
    - exams.total_questions  -> her sinavda soru sayisi farkli olabilir,
                                 bu yuzden SABIT 40 degil, disaridan verilir.
    - exams.answer_key       -> zaten {"1":"A","2":"B",...} formatinda,
                                 asagidaki sinav_bilgisi JSON'undaki
                                 "answer_key" alaniyla birebir eslesir.
    - exam_results.student_no, correct_count, wrong_count, blank_count,
      score, status -> bu scriptin ciktisindaki alanlarla birebir eslesir.

Kullanim:
    python pipeline_main.py kagit.jpg koordinat_haritasi.json sinav_bilgisi.json

sinav_bilgisi.json, Laravel'in `exams` tablosundan tek bir satiri
disa aktarmasiyla olusur (bkz. sinav_bilgisi_ornek.json):
    {
      "exam_id": 1,
      "total_questions": 10,
      "answer_key": {"1": "A", "2": "B", ...}
    }
"""
import os
from dotenv import load_dotenv
import cv2
import sys

# Scriptin bulunduğu klasöre geçiş yap
os.chdir(os.path.dirname(os.path.abspath(__file__)))
load_dotenv() # .env dosyasındaki bilgileri yükle
import sys
import json
import io
import contextlib

from anchor_detect import belgeyi_duzlestir, kenar_koyuluk_orani
from bubble_detect import soruyu_oku, coklu_soru_oku, kabarcik_doluluk_orani

# Sinav puanlamasinda yanlislarin dogrulari goturup goturmeyecegini
# belirleyen katsayi. Universitenizin puanlama politikasina gore
# degistirin:
#   0      -> yanlis, dogruyu goturmez (net puan = dogru sayisi)
#   1/4    -> 4 secenekli klasik "4 yanlis 1 dogruyu goturur" kurali
#   1/(N-1)-> N secenekli sinavlarda istatistiksel olarak "adil" ceza
CEZA_KATSAYISI = 0


def _akilli_rakam_sec(img_gri, secenekler, yaricap=9, min_fark=0.05, min_taban=0.30):
    """
    Ogrenci no kutucuklari icin MUTLAK esik yerine GORELI guven kullanir.

    Neden: kucuk yaricapli (8-9px) kirpimda, ISARETLENMEMIS bos bir
    kutucugun basili halka cizgisi bile (per-bolge Otsu esiklemesi
    yuzunden) yanlislikla "dolu" sayilabiliyor -- ozellikle gercek
    kamera fotograflarinda (tarama/ekran goruntusune gore daha
    gurultulu). Bu yuzden mutlak "esik=0.45" yerine:
      1) En yuksek dolulugu bul.
      2) Bu, ikinci en yuksekten YETERINCE ayrisiyor mu kontrol et.
      3) Ayrisim yoksa ya da hicbiri yeterince dolu degilse '?' don
         (boylece cagiran kod bunu 'pending_review' olarak isaretler --
         yanlis bir rakami sessizce kaydetmek yerine).
    """
    oranlar = {h: kabarcik_doluluk_orani(img_gri, m, yaricap) for h, m in secenekler.items()}
    siralanan = sorted(oranlar.items(), key=lambda kv: -kv[1])
    en_iyi_harf, en_iyi_oran = siralanan[0]
    ikinci_oran = siralanan[1][1] if len(siralanan) > 1 else 0.0

    if en_iyi_oran < min_taban:
        return "?"
    if (en_iyi_oran - ikinci_oran) < min_fark:
        return "?"
    return en_iyi_harf


def ogrenci_no_oku(img_gri, harita):
    """
    Ogrenci no'yu basamak basamak okuyup birlestirir.
    Okunamayan/belirsiz bir basamak varsa '?' ile isaretlenir --
    boylece cagiran kod (kagidi_isle) bunu tespit edip 'pending_review'
    durumuna cekebilir.
    """
    no = ""
    for basamak_adi in sorted(
        harita["ogrenci_no"].keys(),
        key=lambda s: int(s.split("_")[1])  # basamak_1, basamak_2, ... dogru sirada gitsin
    ):
        secenekler = harita["ogrenci_no"][basamak_adi]
        secenekler = {k: tuple(v) for k, v in secenekler.items()}
        no += _akilli_rakam_sec(img_gri, secenekler, yaricap=9)
    return no


def sorulari_oku(img_gri, harita, toplam_soru):
    """
    Optik kagitta fiziksel olarak 40 soruluk yer olsa da, sinava gore
    sadece ilk `toplam_soru` kadari kullanilir (exams.total_questions).
    """
    if toplam_soru > len(harita["sorular"]):
        raise ValueError(
            f"Sinavda {toplam_soru} soru var ama koordinat haritasinda "
            f"sadece {len(harita['sorular'])} sorunun yeri tanimli."
        )

    sorular_tuple = []
    for soru in harita["sorular"][:toplam_soru]:
        soru_tuple = {"soru_no": soru["soru_no"]}
        for k, v in soru.items():
            if k != "soru_no":
                soru_tuple[k] = tuple(v)  # [x,y] -> (x,y)
        sorular_tuple.append(soru_tuple)

    return coklu_soru_oku(img_gri, sorular_tuple)


def puanla(cevaplar, answer_key):
    """
    cevaplar: {soru_no: verilen_harf, ...}         (soru_no int)
    answer_key: {"1": "A", "2": "B", ...}           (exams.answer_key ile ayni format)

    Donus, exam_results sutunlariyla birebir eslesen alanlar +
    veritabaninda sutunu olmayan ama teachers'in "hangi sorular
    cift isaretlenmis" gorebilmesi icin faydali bir liste icerir.
    """
    dogru = yanlis = bos = 0
    gecersiz_sorular = []

    for soru_no, verilen in cevaplar.items():
        gercek = answer_key.get(str(soru_no))
        if verilen == "BOS":
            bos += 1
        elif verilen == "GECERSIZ":
            # DB'de ayri bir "gecersiz_count" sutunu yok; puanlama
            # acisindan yanlis sayilir (dogruyu goturmez ama net'e
            # +1 vermez), ama hangi sorularin sorunlu oldugu ayrica
            # raporlanir ki ogretmen isterse elle kontrol etsin.
            yanlis += 1
            gecersiz_sorular.append(soru_no)
        elif verilen == gercek:
            dogru += 1
        else:
            yanlis += 1

    net = dogru - (yanlis * CEZA_KATSAYISI)
    net = max(net, 0)
    toplam_soru = len(cevaplar)
    puan = round((net / toplam_soru) * 100, 2) if toplam_soru > 0 else 0.0

    return {
        "correct_count": dogru,
        "wrong_count": yanlis,
        "blank_count": bos,
        "score": puan,
        "gecersiz_sorular": gecersiz_sorular,  # DB sutunu degil, bilgi amacli
    }


def kagidi_isle(resim_yolu, koordinat_dosyasi, sinav_bilgisi_dosyasi):
    with open(koordinat_dosyasi, encoding="utf-8") as f:
        harita = json.load(f)
    with open(sinav_bilgisi_dosyasi, encoding="utf-8") as f:
        sinav = json.load(f)

    exam_id = sinav["exam_id"]
    toplam_soru = sinav["total_questions"]
    answer_key = sinav["answer_key"]

    img_gri, img_renkli = belgeyi_duzlestir(resim_yolu)
    if img_gri is None:
        return {
            "basarili": False,
            "status": "failed",
            "exam_id": exam_id,
            "hata": "Kagit duzlestirilemedi (anchor bulunamadi).",
        }

    # --- DEBUG: ALGORİTMANIN GÖZÜNDEN ÇİZİM ---
    try:
        # Öğrenci numarasının okunduğu piksellere Kırmızı yuvarlak çiz
        for basamak, secenekler in harita["ogrenci_no"].items():
            for rakam, kord in secenekler.items():
                x, y = kord[0], kord[1]
                cv2.circle(img_renkli, (int(x), int(y)), 10, (0, 0, 255), 2)

        # Soruların okunduğu piksellere Mavi yuvarlak çiz
        for soru in harita["sorular"][:toplam_soru]:
            for k, v in soru.items():
                if k != "soru_no":
                    x, y = v[0], v[1]
                    cv2.circle(img_renkli, (int(x), int(y)), 10, (255, 0, 0), 2)

        # Çizilmiş görüntüyü resmi okuduğu klasöre kaydet
        kayit_dizini = os.path.dirname(resim_yolu)
        debug_yolu = os.path.join(kayit_dizini, "debug_algoritma_gozu.jpg")
        cv2.imwrite(debug_yolu, img_renkli)
    except Exception as e:
        pass # Çizim sırasında hata olursa kod çökmesin, asıl işleme devam etsin
    # --- DEBUG BİTİŞ ---

    ogrenci_no_str = ogrenci_no_oku(img_gri, harita)
    cevaplar = sorulari_oku(img_gri, harita, toplam_soru)
    puanlar = puanla(cevaplar, answer_key)

    ogrenci_no_okunabilir = "?" not in ogrenci_no_str

    if not ogrenci_no_okunabilir or puanlar["gecersiz_sorular"]:
        status = "pending_review"
    else:
        status = "success"

    return {
        "basarili": True,
        "exam_id": exam_id,
        "student_no": int(ogrenci_no_str) if ogrenci_no_okunabilir else None,
        "student_answers": {str(k): v for k, v in cevaplar.items()},
        "correct_count": puanlar["correct_count"],
        "wrong_count": puanlar["wrong_count"],
        "blank_count": puanlar["blank_count"],
        "score": puanlar["score"],
        "status": status,
        "gecersiz_sorular": puanlar["gecersiz_sorular"],
    }


if __name__ == "__main__":
    # DIKKAT: Bu script Laravel tarafindan Process::run() ile cagirilacak.
    # Bu yuzden stdout'a SADECE tek bir JSON satiri basilmali -- baska hicbir
    # print() burada olmamali. Hata/debug mesajlari alt modullerde stderr'e
    # yonlendirilmis durumda (bkz. anchor_detect.py).
    if len(sys.argv) < 4:
        print(json.dumps({
            "basarili": False,
            "status": "failed",
            "hata": "Kullanim: python pipeline_main.py kagit.jpg koordinat_haritasi.json sinav_bilgisi.json"
        }, ensure_ascii=False))
        sys.exit(1)

    try:
        sonuc = kagidi_isle(sys.argv[1], sys.argv[2], sys.argv[3])
    except Exception as e:
        # Beklenmeyen bir hata da JSON olarak donsun ki Laravel tarafi
        # her zaman JSON parse edebilsin, ham Python traceback'i degil.
        sonuc = {"basarili": False, "status": "failed", "hata": str(e)}

    # Tek satir, saf JSON -- Laravel Process::run()->output() bunu okuyacak
    print(json.dumps(sonuc, ensure_ascii=False))

    # Laravel'in basarili()/failed() kontrolu icin exit kodu
    sys.exit(0 if sonuc.get("basarili") else 1)